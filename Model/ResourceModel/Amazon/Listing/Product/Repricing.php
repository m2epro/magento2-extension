<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product;

use Ess\M2ePro\Model\Account;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Repricing
 */
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
    ) {
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

    public function getSkus(Account $account, $filterSkus = null, $repricingDisabled = null)
    {
        $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->getSelect()->joinLeft(
            ['l' => $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_listing')],
            'l.id = main_table.listing_id'
        );
        $listingProductCollection->addFieldToFilter('is_variation_parent', 0);
        $listingProductCollection->addFieldToFilter('is_repricing', 1);
        $listingProductCollection->addFieldToFilter('l.account_id', $account->getId());
        $listingProductCollection->addFieldToFilter('second_table.sku', ['notnull' => true]);
        if (!empty($filterSkus)) {
            $listingProductCollection->addFieldToFilter('second_table.sku', ['in' => $filterSkus]);
        }
        $listingProductCollection->addFieldToFilter('second_table.online_regular_price', ['notnull' => true]);

        if ($repricingDisabled !== null) {
            $listingProductCollection->getSelect()->joinLeft(
                ['alpr' => $this->getMainTable()],
                'alpr.listing_product_id = main_table.id'
            );

            $listingProductCollection->addFieldToFilter('alpr.is_online_disabled', (int)$repricingDisabled);
        }

        $listingProductCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingProductCollection->getSelect()->columns(
            ['sku'  => 'second_table.sku']
        );

        return $listingProductCollection->getColumnValues('sku');
    }

    //########################################

    public function markAsProcessRequired(array $listingsProductsIds)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            [
                'update_date'         => $this->getHelper('Data')->getCurrentGmtDate(),
                'is_process_required' => 1
            ],
            [
                'listing_product_id IN (?)' => array_unique($listingsProductsIds),
                'is_process_required = ?'   => 0,
            ]
        );
    }

    public function resetProcessRequired(array $listingsProductsIds)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            [
                'update_date'         => $this->getHelper('Data')->getCurrentGmtDate(),
                'is_process_required' => 0
            ],
            [
                'listing_product_id IN (?)' => array_unique($listingsProductsIds),
                'is_process_required = ?'   => 1,
            ]
        );
    }

    //########################################
}
