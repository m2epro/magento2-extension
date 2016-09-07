<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Listing;

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
                       ->from(array('elp' => $this->getMainTable()))
                       ->reset(\Zend_Db_Select::COLUMNS)
                       ->columns(array('template_category_id'))
                       ->where('listing_product_id IN (?)', $listingProductIds)
                       ->where('template_category_id IS NOT NULL');

        $ids = $select->query()->fetchAll(\PDO::FETCH_COLUMN);

        return array_unique($ids);
    }

    public function getTemplateOtherCategoryIds(array $listingProductIds)
    {
        $select = $this->getConnection()
                       ->select()
                       ->from(array('elp' => $this->getMainTable()))
                       ->reset(\Zend_Db_Select::COLUMNS)
                       ->columns(array('template_other_category_id'))
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
        $newTemplateSnapshot = array();

        try {
            $newTemplateSnapshot = $this->activeRecordFactory
                ->getCachedObjectLoaded(
                    'Ebay\Template\Category',
                    $newData['template_category_id']
                )->getDataSnapshot();
        } catch (\Exception $exception) {}

        $oldTemplateSnapshot = array();

        try {
            $oldTemplateSnapshot = $this->activeRecordFactory
                ->getCachedObjectLoaded(
                    'Ebay\Template\Category',
                    $oldData['template_category_id']
                )->getDataSnapshot();
        } catch (\Exception $exception) {}

        if (!$newTemplateSnapshot && !$oldTemplateSnapshot) {
            return;
        }

        $this->activeRecordFactory->getObject('Ebay\Template\Category')->getResource()->setSynchStatusNeed(
            $newTemplateSnapshot,
            $oldTemplateSnapshot,
            array($listingProduct)
        );
    }

    public function setSynchStatusNeedByOtherCategoryTemplate($newData, $oldData, $listingProduct)
    {
        $newTemplateSnapshot = array();

        try {
            $newTemplateSnapshot = $this->activeRecordFactory
                ->getCachedObjectLoaded(
                    'Ebay\Template\OtherCategory',
                    $newData['template_other_category_id']
                )->getDataSnapshot();
        } catch (\Exception $exception) {}

        $oldTemplateSnapshot = array();

        try {
            $oldTemplateSnapshot = $this->activeRecordFactory
                ->getCachedObjectLoaded(
                    'Ebay\Template\OtherCategory',
                    $oldData['template_other_category_id']
                )->getDataSnapshot();
        } catch (\Exception $exception) {}

        if (!$newTemplateSnapshot && !$oldTemplateSnapshot) {
            return;
        }

        $this->activeRecordFactory->getObject('Ebay\Template\OtherCategory')->getResource()->setSynchStatusNeed(
            $newTemplateSnapshot,
            $oldTemplateSnapshot,
            array($listingProduct)
        );
    }

    //########################################
}
