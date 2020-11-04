<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y20_m09;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m09\InventorySynchronization
 */
class InventorySynchronization extends AbstractFeature
{
    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Setup
     * @throws \Zend_Db_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute()
    {
        $this->processAmazon();
        $this->processWalmart();
        $this->processEbay();
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Setup
     * @throws \Zend_Db_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    protected function processAmazon()
    {
        /**
         * Create table 'm2epro_amazon_inventory_sku'
         */
        $amazonInventoryTable = $this->getConnection()->newTable($this->getFullTableName('amazon_inventory_sku'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'account_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'sku',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addIndex(
                'account_id__sku',
                ['account_id', 'sku'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');

        $this->getConnection()->createTable($amazonInventoryTable);

        $this->getTableModifier('amazon_account')
            ->addColumn('inventory_last_synchronization', 'DATETIME', 'NULL', 'other_listings_mapping_settings');

        $accountTable = $this->getFullTableName('account');

        $accountStmt = $this->getConnection()->select()
            ->from(
                $accountTable,
                ['id', 'additional_data']
            )
            ->where('component_mode = ?', 'amazon')
            ->query();

        while ($row = $accountStmt->fetch()) {
            $additionalData = (array)json_decode($row['additional_data'], true);
            unset(
                $additionalData['last_other_listing_products_synchronization'],
                $additionalData['last_listing_products_synchronization']
            );

            $this->getConnection()->update(
                $accountTable,
                ['additional_data' => json_encode($additionalData)],
                ['id = ?' => (int)$row['id']]
            );
        }

        $this->getTableModifier('amazon_listing_product')
            ->addColumn('list_date', 'DATETIME', 'NULL', 'defected_messages', true);

        $productsStmt = $this->getConnection()->select()
            ->from(
                $this->getFullTableName('listing_product'),
                ['id', 'additional_data']
            )
            ->where('component_mode = ?', 'amazon')
            ->where('additional_data LIKE ?', '%"list_date":%')
            ->query();

        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->getConnection()->update(
            $this->getFullTableName('amazon_listing_product'),
            ['list_date' => $now->format('Y-m-d H:i:s')]
        );

        while ($row = $productsStmt->fetch()) {
            $additionalData = (array)json_decode($row['additional_data'], true);
            if (empty($additionalData['list_date'])) {
                continue;
            }

            unset($additionalData['list_date']);
            $additionalData = json_encode($additionalData);

            $this->getConnection()->update(
                $this->getFullTableName('listing_product'),
                ['additional_data' => $additionalData],
                ['id = ?' => (int)$row['id']]
            );
        }

        $tableModifier = $this->getTableModifier('m2epro_amazon_listing_other');
        $tableModifier->dropIndex('title');
        $tableModifier->changeColumn('title', 'TEXT', 'NULL', null);

        $this->installer->run(<<<SQL
ALTER TABLE `{$this->getFullTableName('amazon_listing_other')}` ADD INDEX `title` (`title`(255))
SQL
        );
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Setup
     * @throws \Zend_Db_Exception
     */
    protected function processWalmart()
    {
        /**
         * Create table 'm2epro_walmart_inventory_wpid'
         */
        $walmartInventoryTable = $this->getConnection()->newTable($this->getFullTableName('walmart_inventory_wpid'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'account_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'wpid',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addIndex(
                'account_id__wpid',
                ['account_id', 'wpid'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');

        $this->getConnection()->createTable($walmartInventoryTable);

        $this->getTableModifier('walmart_account')
            ->addColumn('inventory_last_synchronization', 'DATETIME', 'NULL', 'orders_last_synchronization');
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    protected function processEbay()
    {
        $this->getTableModifier('ebay_account')
            ->renameColumn(
                'defaults_last_synchronization',
                'inventory_last_synchronization',
                true,
                false
            )
            ->commit();
    }

    //########################################
}