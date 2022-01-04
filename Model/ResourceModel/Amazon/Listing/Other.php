<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Listing;

use Ess\M2ePro\Model\Account;
use Ess\M2ePro\Helper\Component\Amazon as AmazonHelper;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Other
 */
class Other extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    protected $_isPkAutoIncrement = false;

    protected $resourceConnection;

    protected $amazonFactory;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->amazonFactory = $amazonFactory;

        parent::__construct($helperFactory, $activeRecordFactory, $parentFactory, $context, $connectionName);
    }

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_amazon_listing_other', 'listing_other_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function getRepricingSkus(Account $account, $filterSkus = null, $repricingDisabled = null)
    {
        $listingOtherCollection = $this->amazonFactory->getObject('Listing\Other')->getCollection();
        $listingOtherCollection->addFieldToFilter('is_repricing', 1);
        $listingOtherCollection->addFieldToFilter('account_id', $account->getId());

        if (!empty($filterSkus)) {
            $listingOtherCollection->addFieldToFilter('sku', ['in' => $filterSkus]);
        }

        if ($repricingDisabled !== null) {
            $listingOtherCollection->addFieldToFilter('is_repricing_disabled', (int)$repricingDisabled);
        }

        $listingOtherCollection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $listingOtherCollection->getSelect()->columns(
            ['sku'  => 'second_table.sku']
        );

        return $listingOtherCollection->getColumnValues('sku');
    }

    //########################################

    public function getProductsDataBySkus(
        array $skus = [],
        array $filters = [],
        array $columns = []
    ) {
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
                $skusChunk = array_map(function ($el) {
                    return (string)$el;
                }, $skusChunk);
                $listingOtherCollection->addFieldToFilter('sku', ['in' => array_unique($skusChunk)]);
            }

            if (!empty($filters)) {
                foreach ($filters as $columnName => $columnValue) {
                    $listingOtherCollection->addFieldToFilter($columnName, $columnValue);
                }
            }

            if (!empty($columns)) {
                $listingOtherCollection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
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

    public function resetEntities()
    {
        $listingOther = $this->parentFactory->getObject(AmazonHelper::NICK, 'Listing\Other');
        $amazonListingOther = $this->activeRecordFactory->getObject('Amazon_Listing_Other');

        $stmt = $listingOther->getResourceCollection()->getSelect()->query();

        $SKUs = [];
        foreach ($stmt as $row) {
            $listingOther->setData($row);
            $amazonListingOther->setData($row);

            $listingOther->setChildObject($amazonListingOther);
            $amazonListingOther->setParentObject($listingOther);
            $SKUs[] = $amazonListingOther->getSku();

            $listingOther->delete();
        }

        $tableName = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_amazon_item');
        foreach (array_chunk($SKUs, 1000) as $chunkSKUs) {
            $this->resourceConnection->getConnection()->delete($tableName, ['sku IN (?)' => $chunkSKUs]);
        }

        $accountsCollection = $this->parentFactory->getObject(AmazonHelper::NICK, 'Account')->getCollection();
        $accountsCollection->addFieldToFilter('other_listings_synchronization', 1);

        foreach ($accountsCollection->getItems() as $account) {
            $additionalData = (array)$this->getHelper('Data')
                ->jsonDecode($account->getAdditionalData());

            unset($additionalData['is_amazon_other_listings_full_items_data_already_received']);

            $account->setSettings('additional_data', $additionalData)->save();
            $account->getChildObject()->setData('inventory_last_synchronization', null)->save();
        }
    }

    //########################################
}
