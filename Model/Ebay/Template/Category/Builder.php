<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Category;

class Builder extends \Ess\M2ePro\Model\ActiveRecord\AbstractBuilder
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Model\Ebay\Template\CategoryFactory */
    private $templateCategoryFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    private $activeRecordFactory;

    /** @var \Magento\Framework\DB\TransactionFactory */
    private $transactionFactory;

    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay */
    private $componentEbayCategoryEbay;

    private $initDefaultSpecifics = false;

    private $filteredData = [];

    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\Ebay\Template\CategoryFactory $templateCategoryFactory,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay $componentEbayCategoryEbay,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        parent::__construct($helperFactory, $modelFactory);

        $this->dataHelper                = $dataHelper;
        $this->templateCategoryFactory   = $templateCategoryFactory;
        $this->activeRecordFactory       = $activeRecordFactory;
        $this->transactionFactory        = $transactionFactory;
        $this->componentEbayCategoryEbay = $componentEbayCategoryEbay;
    }

    public function build($model, array $rawData)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Template\Category $model */
        $model = parent::build($model, $rawData);
        $specifics = $this->getSpecifics($model);
        $this->saveSpecifics($model, $specifics);
        return $model;
    }

    protected function prepareData()
    {
        $template = $this->getTemplate();
        $this->initSpecificsFromTemplate($template);
        $this->model = $template;

        return $this->getFilteredData();
    }

    public function getDefaultData()
    {
        return [
            'category_id'        => 0,
            'category_path'      => '',
            'category_mode'      => \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY,
            'category_attribute' => ''
        ];
    }

    private function getTemplate()
    {
        if (isset($this->rawData['template_id'])) {
           return $this->loadTemplateById($this->rawData['template_id']);
        }

        $isCustomTemplate = $this->rawData['is_custom_template'] ?? false;

        return $isCustomTemplate
            ? $this->createCustomTemplate()
            : $this->getDefaultTemplate();
    }

    private function loadTemplateById($id)
    {
        $template = $this->templateCategoryFactory->create();
        $template->load($id);
        $this->checkIfTemplateDataMatch($template);
        return $template;
    }

    private function createCustomTemplate()
    {
        $template = $this->templateCategoryFactory->create();
        $template->setData('is_custom_template', 1);
        return $template;
    }

    private function getDefaultTemplate()
    {
        $template = $this->templateCategoryFactory->create();

        if (!isset($this->rawData['category_mode'], $this->rawData['marketplace_id'])) {
            return $template->setData('is_custom_template', 0);
        }

        $value =  $this->rawData['category_mode'] == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY
            ? $this->rawData['category_id']
            : $this->rawData['category_attribute'];

        $template->loadByCategoryValue(
            $value,
            $this->rawData['category_mode'],
            $this->rawData['marketplace_id'],
            0
        );

        if ($template->isObjectNew()) {
            $this->initDefaultSpecifics = true;
        }

        return $template;
    }

    private function getFilteredData()
    {
        if (!empty($this->filteredData)) {
            return $this->filteredData;
        }

        $allowedKeys = [
            'marketplace_id',
            'category_mode',
            'category_id',
            'category_attribute',
            'category_path'
        ];

        foreach ($allowedKeys as $key) {
            if (isset($this->rawData[$key])) {
                $this->filteredData[$key] = $this->rawData[$key];
            }
        }

        return $this->filteredData;
    }

    /** Editing of category data is not allowed */
    private function checkIfTemplateDataMatch(\Ess\M2ePro\Model\Ebay\Template\Category $template) {
        $significantKeys = [
            'marketplace_id',
            'category_mode',
            'category_id',
            'category_attribute',
        ];

        foreach ($this->getFilteredData() as $key => $value) {
            if (in_array($key, $significantKeys, true) && $template->getData($key) != $value) {
                $this->initSpecificsFromTemplate($template);
                $template->setData(['is_custom_template' => 1]);
            }
        }
    }

    private function getSpecifics(\Ess\M2ePro\Model\Ebay\Template\Category $template)
    {
        if (!empty($this->rawData['specific'])) {
            return $this->getNewSpecifics($template);
        }

        if ($this->initDefaultSpecifics) {
            return $this->initDefaultSpecifics($template);
        }

        return [];
    }

    private function initDefaultSpecifics(\Ess\M2ePro\Model\Ebay\Template\Category $template)
    {
        $dictionarySpecifics = (array)$this->componentEbayCategoryEbay->getSpecifics(
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

        return $specifics;
    }

    private function getNewSpecifics(\Ess\M2ePro\Model\Ebay\Template\Category $template)
    {
        $specifics = [];
        foreach ($template->getSpecifics(true) as $specific) {
            // @codingStandardsIgnoreLine
            $specific->delete();
        }
        foreach ($this->rawData['specific'] as $specific) {
            $specifics[] = $this->serializeSpecific($specific);
        }

        return $specifics;
    }

    private function saveSpecifics(\Ess\M2ePro\Model\Ebay\Template\Category $template, array $specifics)
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

    private function initSpecificsFromTemplate(\Ess\M2ePro\Model\Ebay\Template\Category $template)
    {
        if (!empty($this->rawData['specific']) || $template->isObjectNew()) {
            return;
        }

        foreach ($template->getSpecifics() as $specific) {
            $specific['value_ebay_recommended'] = $this->dataHelper->jsonDecode($specific['value_ebay_recommended']);
            $specific['value_custom_value']     = $this->dataHelper->jsonDecode($specific['value_custom_value']);

            $this->rawData['specific'][] = $specific;
        }
    }

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

            $specificData['value_ebay_recommended'] = $this->dataHelper->jsonEncode($recommendedValue);
        }

        if (isset($specific['value_custom_value'])) {
            $customValue = $specific['value_custom_value'];
            !is_array($customValue) && $customValue = [$customValue];

            $specificData['value_custom_value'] = $this->dataHelper->jsonEncode($customValue);
        }

        if (isset($specific['value_custom_attribute'])) {
            $specificData['value_custom_attribute'] = $specific['value_custom_attribute'];
        }

        return $specificData;
    }
}
