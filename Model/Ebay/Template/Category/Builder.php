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
class Builder extends \Ess\M2ePro\Model\AbstractModel
{
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

    /**
     * @param array $data
     * @return \Ess\M2ePro\Model\Ebay\Template\Category|null
     * @throws \Exception
     */
    public function build(array $data)
    {
        $categoryTemplateData = [];

        $categoryTemplateData['category_main_mode']      = (int)$data['category_main_mode'];
        $categoryTemplateData['category_main_id']        = $data['category_main_id'];
        $categoryTemplateData['category_main_attribute'] = $data['category_main_attribute'];
        $categoryTemplateData['marketplace_id']          = (int)$data['marketplace_id'];

        if (!empty($data['category_main_path'])) {
            $categoryTemplateData['category_main_path'] = $data['category_main_path'];
        }

        $categoryTemplate = $this->getTemplateIfTheSameAlreadyExists($categoryTemplateData, $data['specifics']);
        if ($categoryTemplate) {
            return $categoryTemplate;
        }

        $categoryTemplate = $this->activeRecordFactory
            ->getObject('Ebay_Template_Category')->setData($categoryTemplateData);
        $categoryTemplate->save();

        $transaction = $this->transactionFactory->create();

        foreach ($data['specifics'] as $specific) {
            $specificData = [
                'mode'                   => (int)$specific['mode'],
                'attribute_title'        => $specific['attribute_title'],
                'value_mode'             => (int)$specific['value_mode'],
                'value_ebay_recommended' => $specific['value_ebay_recommended'],
                'value_custom_value'     => $specific['value_custom_value'],
                'value_custom_attribute' => $specific['value_custom_attribute']
            ];

            $specificData['template_category_id'] = $categoryTemplate->getId();

            $specific = $this->activeRecordFactory->getObject('Ebay_Template_Category_Specific');
            $specific->setData($specificData);

            $transaction->addObject($specific);
        }

        $transaction->save();

        return $categoryTemplate;
    }

    //########################################

    /**
     * Is needed to reduce amount of the Items Specifics blocks an Categories in use
     * @param array $templateData
     * @param array $postSpecifics
     * @return \Ess\M2ePro\Model\Ebay\Template\Category|null
     */
    private function getTemplateIfTheSameAlreadyExists(array $templateData, array $postSpecifics)
    {
        $existingTemplates = $this->activeRecordFactory->getObject('Ebay_Template_Category')->getCollection()
             ->getItemsByPrimaryCategories([$templateData]);

        /** @var $existingCategoryTemplate \Ess\M2ePro\Model\Ebay\Template\Category */
        foreach ($existingTemplates as $existingCategoryTemplate) {
            $currentSpecifics = $existingCategoryTemplate->getSpecifics();

            foreach ($currentSpecifics as &$specific) {
                unset($specific['id'], $specific['template_category_id']);
            }
            unset($specific);

            foreach ($postSpecifics as &$specific) {
                unset($specific['id'], $specific['template_category_id']);
            }
            unset($specific);

            if ($currentSpecifics == $postSpecifics) {
                return $existingCategoryTemplate;
            }
        }

        return null;
    }

    //########################################
}
