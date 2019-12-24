<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\OtherCategory;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\OtherCategory\Builder
 */
class Builder extends \Ess\M2ePro\Model\AbstractModel
{
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function build(array $data)
    {
        $otherCategoryTemplateData = [];

        $categoryPrefixes = [
            'category_secondary_',
            'store_category_main_',
            'store_category_secondary_'
        ];

        foreach ($categoryPrefixes as $prefix) {
            $otherCategoryTemplateData[$prefix.'mode']       = (int)$data[$prefix.'mode'];
            $otherCategoryTemplateData[$prefix.'id']         = (float)$data[$prefix.'id'];
            $otherCategoryTemplateData[$prefix.'attribute']  = (string)$data[$prefix.'attribute'];

            if (!empty($data[$prefix.'path'])) {
                $otherCategoryTemplateData[$prefix.'path'] = $data[$prefix.'path'];
            }
        }

        $otherCategoryTemplateData['marketplace_id'] = (int)$data['marketplace_id'];
        $otherCategoryTemplateData['account_id'] = (int)$data['account_id'];

        $otherCategoryTemplate = $this->getTemplateIfTheSameAlreadyExists($otherCategoryTemplateData);
        if ($otherCategoryTemplate) {
            return $otherCategoryTemplate;
        }

        $categoryTemplate = $this->activeRecordFactory->getObject('Ebay_Template_OtherCategory')
            ->setData($otherCategoryTemplateData);
        $categoryTemplate->save();

        return $categoryTemplate;
    }

    //########################################

    /**
     * Is needed to reduce amount of the Items Specifics blocks an Categories in use
     * @param array $templateData
     * @return \Ess\M2ePro\Model\Ebay\Template\Category|null
     */
    private function getTemplateIfTheSameAlreadyExists(array $templateData)
    {
        $collection = $this->activeRecordFactory->getObject('Ebay_Template_OtherCategory')->getCollection();

        foreach ($templateData as $field => $fieldValue) {
            $fieldValue === null && $filter = ['null' => true];
            $collection->addFieldToFilter($field, $fieldValue);
        }

        if ($collection->getFirstItem()->getId()) {
            return $collection->getFirstItem();
        }

        return null;
    }

    //########################################
}
