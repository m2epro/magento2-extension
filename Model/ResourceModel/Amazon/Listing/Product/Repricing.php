<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product;

use Ess\M2ePro\Model\Account;

class Repricing extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    protected $_isPkAutoIncrement = false;

    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    )
    {
        $this->amazonFactory = $amazonFactory;

        parent::__construct($helperFactory, $activeRecordFactory, $parentFactory, $context, $connectionName);
    }

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_amazon_listing_product_repricing', 'listing_product_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function getAllSkus(Account $account, $repricingDisabled = null)
    {
        $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->getSelect()->joinLeft(
            array('l' => $this->getTable('m2epro_listing')),
            'l.id = main_table.listing_id'
        );
        $listingProductCollection->addFieldToFilter('is_variation_parent', 0);
        $listingProductCollection->addFieldToFilter('is_repricing', 1);
        $listingProductCollection->addFieldToFilter('l.account_id', $account->getId());
        $listingProductCollection->addFieldToFilter('second_table.sku', array('notnull' => true));
        $listingProductCollection->addFieldToFilter('second_table.online_regular_price', array('notnull' => true));

        if (!is_null($repricingDisabled)) {
            $listingProductCollection->getSelect()->joinLeft(
                array('alpr' => $this->getMainTable()),
                'alpr.listing_product_id = main_table.id'
            );

            $listingProductCollection->addFieldToFilter('alpr.is_online_disabled', (int)$repricingDisabled);
        }

        $listingProductCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingProductCollection->getSelect()->columns(
            array('sku'  => 'second_table.sku')
        );

        return $listingProductCollection->getColumnValues('sku');
    }

    //########################################

    public function markAsProcessRequired(array $listingsProductsIds)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            array('is_process_required' => 1),
            array(
                'listing_product_id IN (?)' => array_unique($listingsProductsIds),
                'is_process_required = ?'   => 0,
            )
        );
    }

    public function resetProcessRequired(array $listingsProductsIds)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            array('is_process_required' => 0),
            array(
                'listing_product_id IN (?)' => array_unique($listingsProductsIds),
                'is_process_required = ?'   => 1,
            )
        );
    }

    //########################################
}