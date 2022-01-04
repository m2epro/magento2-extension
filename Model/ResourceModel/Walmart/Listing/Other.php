<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Listing;

use Ess\M2ePro\Helper\Component\Walmart as WalmartHelper;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Other
 */
class Other extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    protected $_isPkAutoIncrement = false;

    protected $resourceConnection;

    protected $walmartFactory;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->walmartFactory = $walmartFactory;

        parent::__construct($helperFactory, $activeRecordFactory, $parentFactory, $context, $connectionName);
    }

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_walmart_listing_other', 'listing_other_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function getProductsDataBySkus(
        array $skus = [],
        array $filters = [],
        array $columns = []
    ) {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Other\Collection $listingOtherCollection */
        $listingOtherCollection = $this->walmartFactory->getObject('Listing\Other')->getCollection();

        if (!empty($skus)) {
            $skus = array_map(function ($el) {
                return (string)$el;
            }, $skus);
            $listingOtherCollection->addFieldToFilter('sku', ['in' => array_unique($skus)]);
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

        return $listingOtherCollection->getData();
    }

    //########################################

    public function resetEntities()
    {
        $listingOther = $this->parentFactory->getObject(WalmartHelper::NICK, 'Listing\Other');
        $walmartListingOther = $this->activeRecordFactory->getObject('Walmart_Listing_Other');

        $stmt = $listingOther->getResourceCollection()->getSelect()->query();

        $SKUs = [];
        foreach ($stmt as $row) {
            $listingOther->setData($row);
            $walmartListingOther->setData($row);

            $listingOther->setChildObject($walmartListingOther);
            $walmartListingOther->setParentObject($listingOther);
            $SKUs[] = $walmartListingOther->getSku();

            $listingOther->delete();
        }

        $tableName = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_walmart_item');
        foreach (array_chunk($SKUs, 1000) as $chunkSKUs) {
            $this->resourceConnection->getConnection()->delete($tableName, ['sku IN (?)' => $chunkSKUs]);
        }

        $accountsCollection = $this->parentFactory
            ->getObject(WalmartHelper::NICK, 'Account')
            ->getCollection()
            ->addFieldToFilter('other_listings_synchronization', 1);

        foreach ($accountsCollection->getItems() as $account) {
            $account->getChildObject()->setData('inventory_last_synchronization', null)->save();
        }
    }

    //########################################
}
