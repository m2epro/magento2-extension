<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y23_m01;

use Magento\Framework\DB\Ddl\Table;

class EbayListingProductScheduledStopAction extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    private const TABLE_NAME = 'ebay_listing_product_scheduled_stop_action';

    /**
     * @return void
     * @throws \Zend_Db_Exception
     */
    public function execute()
    {
        if (!$this->installer->tableExists($this->getFullTableName(self::TABLE_NAME))) {
            $this->createTable();
        }

        $this->migrateOldFlagToScheduledStopActions();
    }

    /**
     * @return void
     * @throws \Zend_Db_Exception
     */
    private function createTable(): void
    {
        $table = $this->getConnection()
            ->newTable($this->getFullTableName(self::TABLE_NAME))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'primary' => true,
                    'nullable' => false,
                    'auto_increment' => true,
                ]
            )
            ->addColumn(
                'listing_product_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false
                ]
            )
            ->addColumn(
                'create_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                'process_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($table);
    }

    private function migrateOldFlagToScheduledStopActions(): void
    {
        $listingProductTable = $this->getFullTableName('listing_product');
        $scheduledStopActionTable = $this->getFullTableName(self::TABLE_NAME);

        $query = $this->getConnection()->select()
            ->from($listingProductTable, ['id', 'additional_data'])
            ->where('`component_mode` = ?', 'ebay')
            ->where('`additional_data` LIKE ?', '%"skip_first_completed_status_on_sync":%')
            ->query();

        $createDate = new \DateTime('now');
        $createDate = $createDate->format('Y-m-d H:i:s');

        $connection = $this->getConnection();
        while ($row = $query->fetch()) {
            $additionalData = json_decode($row['additional_data'], true);
            $flagState = !empty($additionalData['skip_first_completed_status_on_sync']);
            unset($additionalData['skip_first_completed_status_on_sync']);
            $newAdditionalData = json_encode($additionalData);

            $connection->update(
                $listingProductTable,
                ['additional_data' => $newAdditionalData],
                '`id` = ' . $row['id']
            );

            if ($flagState) {
                $connection->insert(
                    $scheduledStopActionTable,
                    [
                        'listing_product_id' => $row['id'],
                        'create_date' => $createDate,
                    ]
                );
            }
        }
    }
}
