<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Listing;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product
 */
class Product extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    protected $_isPkAutoIncrement = false;

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_ebay_listing_product', 'listing_product_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function getTemplateCategoryIds(array $listingProductIds)
    {
        $select = $this->getConnection()
                       ->select()
                       ->from(['elp' => $this->getMainTable()])
                       ->reset(\Zend_Db_Select::COLUMNS)
                       ->columns(['template_category_id'])
                       ->where('listing_product_id IN (?)', $listingProductIds)
                       ->where('template_category_id IS NOT NULL');

        $ids = $select->query()->fetchAll(\PDO::FETCH_COLUMN);

        return array_unique($ids);
    }

    public function getTemplateOtherCategoryIds(array $listingProductIds)
    {
        $select = $this->getConnection()
                       ->select()
                       ->from(['elp' => $this->getMainTable()])
                       ->reset(\Zend_Db_Select::COLUMNS)
                       ->columns(['template_other_category_id'])
                       ->where('listing_product_id IN (?)', $listingProductIds)
                       ->where('template_other_category_id IS NOT NULL');

        $ids = $select->query()->fetchAll(\PDO::FETCH_COLUMN);

        return array_unique($ids);
    }

    //########################################

    public function getChangedItems(array $attributes, $withStoreFilter = false)
    {
        return $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getChangedItems(
            $attributes,
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            $withStoreFilter
        );
    }

    public function getChangedItemsByListingProduct(array $attributes, $withStoreFilter = false)
    {
        return $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getChangedItemsByListingProduct(
            $attributes,
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            $withStoreFilter
        );
    }

    public function getChangedItemsByVariationOption(array $attributes, $withStoreFilter = false)
    {
        return $this->activeRecordFactory->getObject('Listing\Product')->getResource()
            ->getChangedItemsByVariationOption(
                $attributes,
                \Ess\M2ePro\Helper\Component\Ebay::NICK,
                $withStoreFilter
            );
    }

    //########################################

    public function setSynchStatusNeedByCategoryTemplate($newData, $oldData, $listingProduct)
    {
        $newTemplateSnapshot = [];

        try {
            $newTemplateSnapshot = $this->activeRecordFactory
                ->getCachedObjectLoaded(
                    'Ebay_Template_Category',
                    $newData['template_category_id']
                )->getDataSnapshot();
        } catch (\Exception $exception) {
        }

        $oldTemplateSnapshot = [];

        try {
            $oldTemplateSnapshot = $this->activeRecordFactory
                ->getCachedObjectLoaded(
                    'Ebay_Template_Category',
                    $oldData['template_category_id']
                )->getDataSnapshot();
        } catch (\Exception $exception) {
        }

        if (!$newTemplateSnapshot && !$oldTemplateSnapshot) {
            return;
        }

        $this->activeRecordFactory->getObject('Ebay_Template_Category')->getResource()->setSynchStatusNeed(
            $newTemplateSnapshot,
            $oldTemplateSnapshot,
            [$listingProduct]
        );
    }

    public function setSynchStatusNeedByOtherCategoryTemplate($newData, $oldData, $listingProduct)
    {
        $newTemplateSnapshot = [];

        try {
            $newTemplateSnapshot = $this->activeRecordFactory
                ->getCachedObjectLoaded(
                    'Ebay_Template_OtherCategory',
                    $newData['template_other_category_id']
                )->getDataSnapshot();
        } catch (\Exception $exception) {
        }

        $oldTemplateSnapshot = [];

        try {
            $oldTemplateSnapshot = $this->activeRecordFactory
                ->getCachedObjectLoaded(
                    'Ebay_Template_OtherCategory',
                    $oldData['template_other_category_id']
                )->getDataSnapshot();
        } catch (\Exception $exception) {
        }

        if (!$newTemplateSnapshot && !$oldTemplateSnapshot) {
            return;
        }

        $this->activeRecordFactory->getObject('Ebay_Template_OtherCategory')->getResource()->setSynchStatusNeed(
            $newTemplateSnapshot,
            $oldTemplateSnapshot,
            [$listingProduct]
        );
    }

    //########################################
}
