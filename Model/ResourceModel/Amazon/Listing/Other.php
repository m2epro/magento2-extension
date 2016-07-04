<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Listing;

use Ess\M2ePro\Model\Account;

class Other extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractDb
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
        parent::__construct($helperFactory, $activeRecordFactory, $parentFactory, $context, $connectionName);

        $this->amazonFactory = $amazonFactory;
    }

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_amazon_listing_other', 'listing_other_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function getAllRepricingSkus(Account $account)
    {
        $listingOtherCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $listingOtherCollection->addFieldToFilter('is_repricing', 1);
        $listingOtherCollection->addFieldToFilter('account_id', $account->getId());

        $listingOtherCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingOtherCollection->getSelect()->columns(
            array('sku'  => 'second_table.sku')
        );

        return $listingOtherCollection->getColumnValues('sku');
    }

    //########################################

    public function removeRepricing(Account $account, array $skus = array())
    {
        if (empty($skus)) {
            $this->getConnection()->update(
                $this->getMainTable(),
                array(
                    'is_repricing'          => 0,
                    'is_repricing_disabled' => 0
                )
            );

            return;
        }
        $listingOtherCollection = $this->amazonFactory->getObject('Listing\Other')->getCollection();

        $listingOtherCollection->addFieldToFilter('account_id', $account->getId());
        $listingOtherCollection->addFieldToFilter('is_repricing', 1);

        if (!empty($skus)) {
            $listingOtherCollection->addFieldToFilter('sku', array('in' => array_unique($skus)));
        }

        $listingOtherCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingOtherCollection->getSelect()->columns(array(
            'id' => 'main_table.id'
        ));

        $listingOtherIds = $listingOtherCollection->getColumnValues('id');
        if (empty($listingOtherIds)) {
            return;
        }

        $this->getConnection()->update(
            $this->getMainTable(),
            array(
                'is_repricing'          => 0,
                'is_repricing_disabled' => 0
            ),
            array('listing_other_id IN (?)' => $listingOtherIds)
        );
    }

    //########################################
}