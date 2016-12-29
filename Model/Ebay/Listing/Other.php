<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Listing\Other getParentObject()
 */
namespace Ess\M2ePro\Model\Ebay\Listing;

class Other extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Ebay\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Other');
    }

    //########################################

    public function __construct(
        \Magento\Email\Model\Template\Filter $emailFilter,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->emailFilter = $emailFilter;
        parent::__construct(
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
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
            ->from(array('ei' => $this->activeRecordFactory->getObject('Ebay\Item')->getResource()->getMainTable()))
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

        $dataForAdd = array(
            'account_id'     => $this->getAccount()->getId(),
            'marketplace_id' => $this->getMarketplace()->getId(),
            'item_id'        => $this->getItemId(),
            'product_id'     => $this->getParentObject()->getProductId(),
            'store_id'       => $this->getRelatedStoreId()
        );

        $this->activeRecordFactory->getObject('Ebay\Item')->setData($dataForAdd)->save();
    }

    public function beforeUnmapProduct()
    {
        $existedRelation = $this->getResource()->getConnection()
            ->select()
            ->from(array('ei' => $this->activeRecordFactory->getObject('Ebay\Item')->getResource()->getMainTable()),
                array())
            ->join(array(
                'elp' => $this->activeRecordFactory->getObject('Ebay\Listing\Product')->getResource()->getMainTable()
            ),
            '(`elp`.`ebay_item_id` = `ei`.`id`)', array('elp.listing_product_id'))
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
                array(
                    '`item_id` = ?' => $this->getItemId(),
                    '`product_id` = ?' => $this->getParentObject()->getProductId(),
                    '`account_id` = ?' => $this->getAccount()->getId()
                )
            );
    }

    //########################################
}