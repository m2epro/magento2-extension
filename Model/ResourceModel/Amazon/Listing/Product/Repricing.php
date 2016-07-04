<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product;

use Ess\M2ePro\Model\Account;

class Repricing extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractDb
{
    protected $_isPkAutoIncrement = false;

    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
    )
    {
        parent::__construct($helperFactory, $activeRecordFactory, $parentFactory, $context, $connectionName);

        $this->amazonFactory = $amazonFactory;
    }

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_amazon_listing_product_repricing', 'account_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function getAllSkus(Account $account)
    {
        $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->getSelect()->joinLeft(
            array('l' => $this->getTable('m2epro_listing')),
            'l.id = main_table.listing_id'
        );
        $listingProductCollection->getSelect()->joinInner(
            array('alpr' => $this->getMainTable()),
            'alpr.listing_product_id=main_table.id',
            array()
        );
        $listingProductCollection->addFieldToFilter('l.account_id', $account->getId());
        $listingProductCollection->addFieldToFilter('second_table.sku', array('notnull' => true));

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

    public function remove(Account $account, array $skus = array())
    {
        $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();

        $listingProductCollection->getSelect()->join(
            array('l' => $this->getTable('m2epro_listing')),
            'l.id = main_table.listing_id',
            array()
        );

        $listingProductCollection->getSelect()->where('l.account_id = ?', $account->getId());

        if (!empty($skus)) {
            $listingProductCollection->addFieldToFilter('sku', array('in' => array_unique($skus)));
        }

        $listingProductCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingProductCollection->getSelect()->columns(array(
            'id' => 'main_table.id'
        ));

        $listingProductIds = $listingProductCollection->getColumnValues('id');
        if (empty($listingProductIds)) {
            return;
        }

        $this->getConnection()->delete(
            $this->getMainTable(),
            array('listing_product_id IN (?)' => $listingProductIds)
        );
    }

    //########################################
}