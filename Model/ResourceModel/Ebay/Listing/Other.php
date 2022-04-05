<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Listing;

use Ess\M2ePro\Helper\Component\Ebay as EbayHelper;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Other
 */
class Other extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    /** @var bool */
    protected $_isPkAutoIncrement = false;

    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    protected $databaseStructure;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseStructure,
        $connectionName = null
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->databaseStructure = $databaseStructure;

        parent::__construct($helperFactory, $activeRecordFactory, $parentFactory, $context, $connectionName);
    }

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_ebay_listing_other', 'listing_other_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function resetEntities()
    {
        $listingOtherTable = $this->databaseStructure->getTableNameWithPrefix('m2epro_listing_other');
        $ebayItemTable = $this->databaseStructure->getTableNameWithPrefix('m2epro_ebay_item');
        $ebayListingOtherTable = $this->databaseStructure->getTableNameWithPrefix('m2epro_ebay_listing_other');
        $ebayListingProductTable = $this->databaseStructure->getTableNameWithPrefix('m2epro_ebay_listing_product');

        $componentName = EbayHelper::NICK;

        $ebayItemIdsQuery = <<<SQL
SELECT `ei`.`item_id`
FROM `{$ebayItemTable}` AS `ei`
INNER JOIN `{$ebayListingOtherTable}` AS `elo`
ON `ei`.`item_id` = `elo`.`item_id`
WHERE `ei`.`id` NOT IN (
    SELECT `elp`.`ebay_item_id`
    FROM `{$ebayListingProductTable}` AS `elp`
    WHERE `elp`.`ebay_item_id` IS NOT NULL
)
SQL;

        $listingOtherIdsQuery = <<<SQL
SELECT `id`
FROM `{$listingOtherTable}`
WHERE `component_mode` = '{$componentName}'
SQL;

        $ebayListingOtherIdsQuery = <<<SQL
SELECT `listing_other_id`
FROM `{$ebayListingOtherTable}`
SQL;

        $this->removeRecords($ebayItemIdsQuery, 'item_id', $ebayItemTable);
        $this->removeRecords($listingOtherIdsQuery, 'id', $listingOtherTable);
        $this->removeRecords($ebayListingOtherIdsQuery, 'listing_other_id', $ebayListingOtherTable);

        foreach ($this->parentFactory->getObject($componentName, 'Account')->getCollection() as $account) {
            $account->getChildObject()->setData('other_listings_last_synchronization', null)->save();
        }
    }

    /**
     * @param string $sql
     * @param string $key
     * @param string $table
     */
    private function removeRecords($sql, $key, $table)
    {
        $itemIds = [];

        $connection = $this->resourceConnection->getConnection();

        foreach ($connection->fetchAll($sql) as $row) {
            $itemIds[] = $row[$key];
        }

        foreach (array_chunk($itemIds, 1000) as $itemIdsSet) {
            $connection->delete($table, ['`' . $key . '` IN (?)' => $itemIdsSet]);
        }
    }

    //########################################
}
