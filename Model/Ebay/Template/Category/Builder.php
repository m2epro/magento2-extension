<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Category;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\Category\Builder
 */
class Builder extends \Ess\M2ePro\Model\ActiveRecord\AbstractBuilder
{
    private $initDefaultSpecifics = false;

    protected $activeRecordFactory;
    protected $transactionFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->transactionFactory = $transactionFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function build($model, array $rawData)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Template\Category $model */
        $model = parent::build($model, $rawData);

        if (!empty($this->rawData['specific'])) {
            foreach ($model->getSpecifics(true) as $specific) {
                // @codingStandardsIgnoreLine
                $specific->delete();
            }

            $specifics = [];
            foreach ($this->rawData['specific'] as $specific) {
                $specifics[] = $this->serializeSpecific($specific);
            }

            $this->saveSpecifics($model, $specifics);
        } else {
            $this->initDefaultSpecifics && $this->initDefaultSpecifics($model);
        }

        return $model;
    }

    //########################################

    protected function prepareData()
    {
        $data = [];

        $keys = [
            'marketplace_id',
            'category_mode',
            'category_id',
            'category_attribute',
            'category_path'
        ];

        foreach ($keys as $key) {
            isset($this->rawData[$key]) && $data[$key] = $this->rawData[$key];
        }

        $template = $this->tryToLoadById($this->rawData, $data);
        $template->getId() === null && $template = $this->tryToLoadByData($this->rawData, $data);
        $this->initDefaultSpecifics = $template->getId() === null;
        $this->model = $template;

        return $data;
    }

    //########################################

    protected function tryToLoadById(array $data, array $newTemplateData)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Template\Category $template */
        $template = $this->activeRecordFactory->getObject('Ebay_Template_Category');

        if (!isset($data['template_id'])) {
            return $template;
        }

        $template->load($data['template_id']);
        $this->checkIfTemplateDataMatch($template, $newTemplateData);

        return $template;
    }

    protected function tryToLoadByData(array $data, array $newTemplateData)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Template\Category $template */
        $template = $this->activeRecordFactory->getObject('Ebay_Template_Category');

        if (!isset($data['category_mode'], $data['marketplace_id'])) {
            return $template;
        }

        $template->loadByCategoryValue(
            $data['category_mode'] == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY
                ? $data['category_id']
                : $data['category_attribute'],
            $data['category_mode'],
            $data['marketplace_id'],
            0
        );

        /* editing of category data is not allowed */
        if ($template->getId() !== null && $template->isLocked()) {
            $this->checkIfTemplateDataMatch($template, $newTemplateData);
            if ($template->getId() === null) {
                return $template;
            }

            if (empty($data['specific']) && !$template->getIsCustomTemplate()) {
                return $template;
            }

            $this->initSpecificsFromTemplate($template);
            $template->setData(['is_custom_template' => 1]);
        }

        return $template;
    }

    //########################################

    protected function saveSpecifics(\Ess\M2ePro\Model\Ebay\Template\Category $template, array $specifics)
    {
        $transaction = $this->transactionFactory->create();

        foreach ($specifics as $specific) {
            $specific['template_category_id'] = $template->getId();

            $specificModel = $this->activeRecordFactory->getObject('Ebay_Template_Category_Specific');
            $specificModel->setData($specific);

            $transaction->addObject($specificModel);
        }

        $transaction->save();
    }

    protected function initDefaultSpecifics(\Ess\M2ePro\Model\Ebay\Template\Category $template)
    {
        if (!$template->isCategoryModeEbay()) {
            return;
        }

        $dictionarySpecifics = (array)$this->getHelper('Component_Ebay_Category_Ebay')->getSpecifics(
            $template->getCategoryId(),
            $template->getMarketplaceId()
        );

        $specifics = [];
        foreach ($dictionarySpecifics as $dictionarySpecific) {
            $specifics[] = [
                'mode'            => \Ess\M2ePro\Model\Ebay\Template\Category\Specific::MODE_ITEM_SPECIFICS,
                'attribute_title' => $dictionarySpecific['title'],
                'value_mode'      => \Ess\M2ePro\Model\Ebay\Template\Category\Specific::VALUE_MODE_NONE
            ];
        }

        $this->saveSpecifics($template, $specifics);
    }

    protected function initSpecificsFromTemplate(\Ess\M2ePro\Model\Ebay\Template\Category $template)
    {
        if (!empty($this->rawData['specific'])) {
            return;
        }

        $helper = $this->getHelper('Data');
        foreach ($template->getSpecifics() as $specific) {
            $specific['value_ebay_recommended'] = (array)$helper->jsonDecode($specific['value_ebay_recommended']);
            $specific['value_custom_value']     = (array)$helper->jsonDecode($specific['value_custom_value']);

            $this->rawData['specific'][] = $specific;
        }
    }

    //----------------------------------------

    public function serializeSpecific(array $specific)
    {
        $specificData = [
            'mode'            => (int)$specific['mode'],
            'attribute_title' => $specific['attribute_title'],
            'value_mode'      => (int)$specific['value_mode']
        ];

        if (isset($specific['value_ebay_recommended'])) {
            $recommendedValue = $specific['value_ebay_recommended'];
            !is_array($recommendedValue) && $recommendedValue = [$recommendedValue];

            $specificData['value_ebay_recommended'] = $this->getHelper('Data')->jsonEncode($recommendedValue);
        }

        if (isset($specific['value_custom_value'])) {
            $customValue = $specific['value_custom_value'];
            !is_array($customValue) && $customValue = [$customValue];

            $specificData['value_custom_value'] = $this->getHelper('Data')->jsonEncode($customValue);
        }

        if (isset($specific['value_custom_attribute'])) {
            $specificData['value_custom_attribute'] = $specific['value_custom_attribute'];
        }

        return $specificData;
    }

    //########################################

    /**
     * editing of category data is not allowed
     */
    protected function checkIfTemplateDataMatch(
        \Ess\M2ePro\Model\Ebay\Template\Category $template,
        array $newTemplateData
    ) {
        $significantKeys = [
            'marketplace_id',
            'category_mode',
            'category_id',
            'category_attribute',
        ];

        foreach ($newTemplateData as $key => $value) {
            if (in_array($key, $significantKeys, true) && $template->getData($key) != $value) {
                $this->initSpecificsFromTemplate($template);
                $template->setData(['is_custom_template' => 1]);
            }
        }
    }

    //########################################

    public function getDefaultData()
    {
        return [
            'category_id'        => 0,
            'category_path'      => '',
            'category_mode'      => \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY,
            'category_attribute' => ''
        ];
    }

    //########################################
}
