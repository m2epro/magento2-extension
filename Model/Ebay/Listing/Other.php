<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Listing\Other getParentObject()
 */
namespace Ess\M2ePro\Model\Ebay\Listing;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Other
 */
class Other extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Ebay\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Other');
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    public function getAccount()
    {
        return $this->getParentObject()->getAccount();
    }

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    public function getMarketplace()
    {
        return $this->getParentObject()->getMarketplace();
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product\Cache
     */
    public function getMagentoProduct()
    {
        return $this->getParentObject()->getMagentoProduct();
    }

    //########################################

    public function getSku()
    {
        return $this->getData('sku');
    }

    /**
     * @return float
     */
    public function getItemId()
    {
        return (double)$this->getData('item_id');
    }

    // ---------------------------------------

    public function getTitle()
    {
        return $this->getData('title');
    }

    public function getCurrency()
    {
        return $this->getData('currency');
    }

    // ---------------------------------------

    public function getOnlineDuration()
    {
        return $this->getData('online_duration');
    }

    /**
     * @return float
     */
    public function getOnlinePrice()
    {
        return (float)$this->getData('online_price');
    }

    /**
     * @return int
     */
    public function getOnlineQty()
    {
        return (int)$this->getData('online_qty');
    }

    /**
     * @return int
     */
    public function getOnlineQtySold()
    {
        return (int)$this->getData('online_qty_sold');
    }

    /**
     * @return int
     */
    public function getOnlineBids()
    {
        return (int)$this->getData('online_bids');
    }

    // ---------------------------------------

    public function getOnlineMainCategory()
    {
        return $this->getData('online_main_category');
    }

    /**
     * @return array
     */
    public function getOnlineCategoriesData()
    {
        return $this->getSettings('online_categories_data');
    }

    // ---------------------------------------

    public function getStartDate()
    {
        return $this->getData('start_date');
    }

    public function getEndDate()
    {
        return $this->getData('end_date');
    }

    //########################################

    public function getRelatedStoreId()
    {
        return $this->getAccount()->getChildObject()->getRelatedStoreId($this->getParentObject()->getMarketplaceId());
    }

    //########################################

    public function afterMapProduct()
    {
        $existedRelation = $this->getResource()->getConnection()
            ->select()
            ->from(['ei' => $this->activeRecordFactory->getObject('Ebay\Item')->getResource()->getMainTable()])
            ->where('`account_id` = ?', $this->getAccount()->getId())
            ->where('`marketplace_id` = ?', $this->getMarketplace()->getId())
            ->where('`item_id` = ?', $this->getItemId())
            ->where('`product_id` = ?', $this->getParentObject()->getProductId())
            ->where('`store_id` = ?', $this->getRelatedStoreId())
            ->query()
            ->fetchColumn();

        if ($existedRelation) {
            return;
        }

        $dataForAdd = [
            'account_id'     => $this->getAccount()->getId(),
            'marketplace_id' => $this->getMarketplace()->getId(),
            'item_id'        => $this->getItemId(),
            'product_id'     => $this->getParentObject()->getProductId(),
            'store_id'       => $this->getRelatedStoreId()
        ];

        $this->activeRecordFactory->getObject('Ebay\Item')->setData($dataForAdd)->save();
    }

    public function beforeUnmapProduct()
    {
        $existedRelation = $this->getResource()->getConnection()
            ->select()
            ->from(
                ['ei' => $this->activeRecordFactory->getObject('Ebay\Item')->getResource()->getMainTable()],
                []
            )
            ->join(
                [
                'elp' => $this->activeRecordFactory->getObject('Ebay_Listing_Product')->getResource()->getMainTable()
                ],
                '(`elp`.`ebay_item_id` = `ei`.`id`)',
                ['elp.listing_product_id']
            )
            ->where('`ei`.`item_id` = ?', $this->getItemId())
            ->where('`ei`.`account_id` = ?', $this->getAccount()->getId())
            ->query()
            ->fetchColumn();

        if ($existedRelation) {
            return;
        }

        $this->getResource()->getConnection()
            ->delete(
                $this->activeRecordFactory->getObject('Ebay\Item')->getResource()->getMainTable(),
                [
                    '`item_id` = ?' => $this->getItemId(),
                    '`product_id` = ?' => $this->getParentObject()->getProductId(),
                    '`account_id` = ?' => $this->getAccount()->getId()
                ]
            );
    }

    //########################################
}
