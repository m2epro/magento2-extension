<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Listing;

use Ess\M2ePro\Model\Account;

class Other extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
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

    public function getAllRepricingSkus(Account $account, $repricingDisabled = null)
    {
        $listingOtherCollection = $this->amazonFactory->getObject('Listing\Other')->getCollection();
        $listingOtherCollection->addFieldToFilter('is_repricing', 1);
        $listingOtherCollection->addFieldToFilter('account_id', $account->getId());

        if (!is_null($repricingDisabled)) {
            $listingOtherCollection->addFieldToFilter('is_repricing_disabled', (int)$repricingDisabled);
        }

        $listingOtherCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingOtherCollection->getSelect()->columns(
            array('sku'  => 'second_table.sku')
        );

        return $listingOtherCollection->getColumnValues('sku');
    }

    //########################################

    public function getProductsDataBySkus(array $skus = array(),
                                          array $filters = array(),
                                          array $columns = array())
    {
        $result = [];
        $skuWithQuotes = false;

        foreach ($skus as $sku) {
            if (strpos($sku, '"') !== false) {
                $skuWithQuotes = true;
                break;
            }
        }

        $skus = (empty($skus) || !$skuWithQuotes) ? [$skus] : array_chunk($skus, 500);

        foreach ($skus as $skusChunk) {

            $listingOtherCollection = $this->amazonFactory->getObject('Listing\Other')->getCollection();

            if (!empty($skusChunk)) {
                $skusChunk = array_map(function($el){ return (string)$el; }, $skusChunk);
                $listingOtherCollection->addFieldToFilter('sku', array('in' => array_unique($skusChunk)));
            }

            if (!empty($filters)) {
                foreach ($filters as $columnName => $columnValue) {
                    $listingOtherCollection->addFieldToFilter($columnName, $columnValue);
                }
            }

            if (!empty($columns)) {
                $listingOtherCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
                $listingOtherCollection->getSelect()->columns($columns);
            }

            $result = array_merge(
                $result,
                $listingOtherCollection->getData()
            );
        }

        return $result;
    }

    //########################################
}