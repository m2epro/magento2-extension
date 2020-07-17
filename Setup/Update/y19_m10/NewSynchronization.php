<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */
// @codingStandardsIgnoreFile

namespace Ess\M2ePro\Setup\Update\y19_m10;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class \Ess\M2ePro\Setup\Update\y19_m10\NewSynchronization
 */
class NewSynchronization extends AbstractFeature
{
    const LONG_COLUMN_SIZE = 16777217;

    //########################################

    public function execute()
    {
        $this->installSchema();
        $this->upgradeData();
    }

    //########################################

    private function installSchema()
    {
        /**
         * Create table 'm2epro_amazon_order_action_processing'
         */
        $amazonOrderActionProcessingTable = $this->getFullTableName('amazon_order_action_processing');

        if (!$this->installer->tableExists($amazonOrderActionProcessingTable)) {
            $amazonOrderActionProcessing = $this->getConnection()->newTable($amazonOrderActionProcessingTable)
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
                )
                ->addColumn(
                    'order_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'default' => null]
                )
                ->addColumn(
                    'processing_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false]
                )
                ->addColumn(
                    'request_pending_single_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'default' => null]
                )
                ->addColumn(
                    'type',
                    Table::TYPE_TEXT,
                    12,
                    ['nullable' => false]
                )
                ->addColumn(
                    'request_data',
                    Table::TYPE_TEXT,
                    self::LONG_COLUMN_SIZE,
                    ['nullable' => false]
                )
                ->addColumn(
                    'update_date',
                    Table::TYPE_DATETIME,
                    null,
                    ['default' => null]
                )
                ->addColumn(
                    'create_date',
                    Table::TYPE_DATETIME,
                    null,
                    ['default' => null]
                )
                ->addIndex('order_id', 'order_id')
                ->addIndex('processing_id', 'processing_id')
                ->addIndex('request_pending_single_id', 'request_pending_single_id')
                ->addIndex('type', 'type');
            $this->getConnection()->createTable($amazonOrderActionProcessing);
        }

        /**
         * Create table 'm2epro_listing_product_instruction'
         */
        $listingProductInstructionTable = $this->getFullTableName('listing_product_instruction');

        if (!$this->installer->tableExists($listingProductInstructionTable)) {
            $listingProductInstruction = $this->getConnection()->newTable($listingProductInstructionTable)
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
                )
                ->addColumn(
                    'listing_product_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false]
                )
                ->addColumn(
                    'component',
                    Table::TYPE_TEXT,
                    10,
                    ['default' => null]
                )
                ->addColumn(
                    'type',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => false]
                )
                ->addColumn(
                    'initiator',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => false]
                )
                ->addColumn(
                    'priority',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false]
                )
                ->addColumn(
                    'additional_data',
                    Table::TYPE_TEXT,
                    self::LONG_COLUMN_SIZE,
                    ['default' => null]
                )
                ->addColumn(
                    'skip_until',
                    Table::TYPE_DATETIME,
                    null,
                    ['default' => null]
                )
                ->addColumn(
                    'create_date',
                    Table::TYPE_DATETIME,
                    null,
                    ['default' => null]
                )
                ->addIndex('listing_product_id', 'listing_product_id')
                ->addIndex('component', 'component')
                ->addIndex('type', 'type')
                ->addIndex('priority', 'priority')
                ->addIndex('skip_until', 'skip_until')
                ->addIndex('create_date', 'create_date');
            $this->getConnection()->createTable($listingProductInstruction);
        }

        /**
         * Create table 'm2epro_listing_product_scheduled_action'
         */
        $listingProductScheduledActionTable = $this->getFullTableName('listing_product_scheduled_action');

        if (!$this->installer->tableExists($listingProductScheduledActionTable)) {
            $listingProductScheduledAction = $this->getConnection()->newTable($listingProductScheduledActionTable)
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
                )
                ->addColumn(
                    'listing_product_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false]
                )
                ->addColumn(
                    'component',
                    Table::TYPE_TEXT,
                    10,
                    ['default' => null]
                )
                ->addColumn(
                    'action_type',
                    Table::TYPE_TEXT,
                    12,
                    ['nullable' => false]
                )
                ->addColumn(
                    'is_force',
                    Table::TYPE_SMALLINT,
                    null,
                    ['nullable' => false, 'default' => 0]
                )
                ->addColumn(
                    'tag',
                    Table::TYPE_TEXT,
                    255,
                    ['default' => null]
                )
                ->addColumn(
                    'additional_data',
                    Table::TYPE_TEXT,
                    self::LONG_COLUMN_SIZE,
                    ['default' => null]
                )
                ->addColumn(
                    'update_date',
                    Table::TYPE_DATETIME,
                    null,
                    ['default' => null]
                )
                ->addColumn(
                    'create_date',
                    Table::TYPE_DATETIME,
                    null,
                    ['default' => null]
                )
                ->addIndex(
                    'listing_product_id',
                    ['listing_product_id'],
                    ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
                )
                ->addIndex('component', 'component')
                ->addIndex('action_type', 'action_type')
                ->addIndex('tag', 'tag')
                ->addIndex('create_date', 'create_date');
            $this->getConnection()->createTable($listingProductScheduledAction);
        }
    }

    //########################################

    private function upgradeData()
    {
        $this->upgradeProcessingData();
        $this->upgradeConnectorData();
        $this->upgradeListingProductData();
        $this->upgradeTemplate();
        $this->upgradeAccount();
        $this->upgradeEbayTemplateShipping();
        $this->upgradeListing();
        $this->upgradeOrder();

        // ---------------------------------------

        $this->getTableModifier('stop_queue')
            ->dropColumn('item_data')
            ->dropColumn('account_hash')
            ->dropColumn('marketplace_id')
            ->addColumn('additional_data', 'TEXT', 'NULL', 'is_processed');

        // ---------------------------------------

        $synchronizationConfigTable = $this->getFullTableName('synchronization_config');
        $inspectorMode = 0;

        if ($this->installer->tableExists($synchronizationConfigTable)) {
            $inspectorMode = $this->getConnection()
                ->select()
                ->from($synchronizationConfigTable, ['value'])
                ->where('`group` = ?', '/global/magento_products/inspector/')
                ->where('`key` = ?', 'mode')
                ->query()
                ->fetchColumn();

            $this->getConnection()->dropTable($synchronizationConfigTable);
        }

        // ---------------------------------------

        $moduleConfig = $this->getConfigModifier('module');

        $moduleConfig->insert('/cron/task/system/servicing/synchronize/', 'interval', rand(43200, 86400));
        $moduleConfig->insert('/cron/task/ebay/listing/product/process_instructions/', 'mode', '1');
        $moduleConfig->insert('/cron/task/amazon/listing/product/process_instructions/', 'mode', '1');
        $moduleConfig->insert('/cron/task/walmart/listing/product/process_instructions/', 'mode', '1');

        //eBay
        // ---------------------------------------

        $moduleConfig->insert('/listing/product/inspector/', 'mode', $inspectorMode, '0 - disable, \r\n1 - enable');
        $moduleConfig->insert('/listing/product/inspector/ebay/', 'max_allowed_instructions_count', '2000');
        $moduleConfig->insert('/ebay/listing/product/instructions/cron/', 'listings_products_per_one_time', '1000');
        $moduleConfig->insert('/ebay/listing/product/action/list/', 'priority_coefficient', '25');
        $moduleConfig->insert('/ebay/listing/product/action/list/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/ebay/listing/product/action/relist/', 'priority_coefficient', '125');
        $moduleConfig->insert('/ebay/listing/product/action/relist/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/ebay/listing/product/action/revise_qty/', 'priority_coefficient', '500');
        $moduleConfig->insert('/ebay/listing/product/action/revise_qty/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/ebay/listing/product/action/revise_price/', 'priority_coefficient', '250');
        $moduleConfig->insert('/ebay/listing/product/action/revise_price/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/ebay/listing/product/action/revise_title/', 'priority_coefficient', '50');
        $moduleConfig->insert('/ebay/listing/product/action/revise_title/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/ebay/listing/product/action/revise_subtitle/', 'priority_coefficient', '50');
        $moduleConfig->insert('/ebay/listing/product/action/revise_subtitle/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/ebay/listing/product/action/revise_description/', 'priority_coefficient', '50');
        $moduleConfig->insert('/ebay/listing/product/action/revise_description/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/ebay/listing/product/action/revise_images/', 'priority_coefficient', '50');
        $moduleConfig->insert('/ebay/listing/product/action/revise_images/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/ebay/listing/product/action/revise_categories/', 'priority_coefficient', '50');
        $moduleConfig->insert('/ebay/listing/product/action/revise_categories/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/ebay/listing/product/action/revise_payment/', 'priority_coefficient', '50');
        $moduleConfig->insert('/ebay/listing/product/action/revise_payment/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/ebay/listing/product/action/revise_shipping/', 'priority_coefficient', '50');
        $moduleConfig->insert('/ebay/listing/product/action/revise_shipping/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/ebay/listing/product/action/revise_return/', 'priority_coefficient', '50');
        $moduleConfig->insert('/ebay/listing/product/action/revise_return/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/ebay/listing/product/action/revise_other/', 'priority_coefficient', '50');
        $moduleConfig->insert('/ebay/listing/product/action/revise_other/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/ebay/listing/product/action/stop/', 'priority_coefficient', '1000');
        $moduleConfig->insert('/ebay/listing/product/action/stop/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/ebay/listing/product/scheduled_actions/', 'max_prepared_actions_count', '3000');

        //Amazon
        // ---------------------------------------

        $moduleConfig->insert('/amazon/listing/product/action/scheduled_data/', 'limit', '20000');
        $moduleConfig->insert('/listing/product/inspector/amazon/', 'max_allowed_instructions_count', '2000');
        $moduleConfig->insert('/amazon/listing/product/instructions/cron/', 'listings_products_per_one_time', '1000');
        $moduleConfig->insert('/amazon/listing/product/action/list/', 'priority_coefficient', '25');
        $moduleConfig->insert('/amazon/listing/product/action/list/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/amazon/listing/product/action/list/', 'min_allowed_wait_interval', '3600');
        $moduleConfig->insert('/amazon/listing/product/action/relist/', 'priority_coefficient', '125');
        $moduleConfig->insert('/amazon/listing/product/action/relist/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/amazon/listing/product/action/relist/', 'min_allowed_wait_interval', '1800');
        $moduleConfig->insert('/amazon/listing/product/action/revise_qty/', 'priority_coefficient', '500');
        $moduleConfig->insert('/amazon/listing/product/action/revise_qty/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/amazon/listing/product/action/revise_qty/', 'min_allowed_wait_interval', '900');
        $moduleConfig->insert('/amazon/listing/product/action/revise_price/', 'priority_coefficient', '250');
        $moduleConfig->insert('/amazon/listing/product/action/revise_price/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/amazon/listing/product/action/revise_price/', 'min_allowed_wait_interval', '1800');
        $moduleConfig->insert('/amazon/listing/product/action/revise_details/', 'priority_coefficient', '50');
        $moduleConfig->insert('/amazon/listing/product/action/revise_details/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/amazon/listing/product/action/revise_details/', 'min_allowed_wait_interval', '7200');
        $moduleConfig->insert('/amazon/listing/product/action/revise_images/', 'priority_coefficient', '50');
        $moduleConfig->insert('/amazon/listing/product/action/revise_images/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/amazon/listing/product/action/revise_images/', 'min_allowed_wait_interval', '7200');
        $moduleConfig->insert('/amazon/listing/product/action/stop/', 'priority_coefficient', '1000');
        $moduleConfig->insert('/amazon/listing/product/action/stop/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/amazon/listing/product/action/stop/', 'min_allowed_wait_interval', '600');
        $moduleConfig->insert('/amazon/listing/product/action/delete/', 'priority_coefficient', '1000');
        $moduleConfig->insert('/amazon/listing/product/action/delete/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/amazon/listing/product/action/delete/', 'min_allowed_wait_interval', '600');
        $moduleConfig->insert(
            '/amazon/listing/product/action/processing/prepare/',
            'max_listings_products_count',
            '2000'
        );

        //Walmart
        // ---------------------------------------

        $moduleConfig->insert('/listing/product/inspector/walmart/', 'max_allowed_instructions_count', '2000');
        $moduleConfig->insert('/walmart/listing/product/action/scheduled_data/', 'limit', '20000');
        $moduleConfig->insert('/walmart/listing/product/instructions/cron/', 'listings_products_per_one_time', '1000');
        $moduleConfig->insert('/walmart/listing/product/action/list/', 'priority_coefficient', '25');
        $moduleConfig->insert('/walmart/listing/product/action/list/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/walmart/listing/product/action/list/', 'min_allowed_wait_interval', '3600');
        $moduleConfig->insert('/walmart/listing/product/action/relist/', 'priority_coefficient', '125');
        $moduleConfig->insert('/walmart/listing/product/action/relist/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/walmart/listing/product/action/relist/', 'min_allowed_wait_interval', '1800');
        $moduleConfig->insert('/walmart/listing/product/action/revise_qty/', 'priority_coefficient', '500');
        $moduleConfig->insert('/walmart/listing/product/action/revise_qty/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/walmart/listing/product/action/revise_qty/', 'min_allowed_wait_interval', '900');
        $moduleConfig->insert('/walmart/listing/product/action/revise_price/', 'priority_coefficient', '250');
        $moduleConfig->insert('/walmart/listing/product/action/revise_price/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/walmart/listing/product/action/revise_price/', 'min_allowed_wait_interval', '1800');
        $moduleConfig->insert('/walmart/listing/product/action/revise_details/', 'priority_coefficient', '50');
        $moduleConfig->insert('/walmart/listing/product/action/revise_details/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/walmart/listing/product/action/revise_details/', 'min_allowed_wait_interval', '7200');
        $moduleConfig->insert('/walmart/listing/product/action/revise_promotions/', 'priority_coefficient', '50');
        $moduleConfig->insert('/walmart/listing/product/action/revise_promotions/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/walmart/listing/product/action/revise_lag_time/', 'priority_coefficient', '250');
        $moduleConfig->insert('/walmart/listing/product/action/revise_lag_time/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/walmart/listing/product/action/revise_lag_time/', 'min_allowed_wait_interval', '7200');
        $moduleConfig->insert('/walmart/listing/product/action/stop/', 'priority_coefficient', '1000');
        $moduleConfig->insert('/walmart/listing/product/action/stop/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/walmart/listing/product/action/stop/', 'min_allowed_wait_interval', '600');
        $moduleConfig->insert('/walmart/listing/product/action/delete/', 'priority_coefficient', '1000');
        $moduleConfig->insert('/walmart/listing/product/action/delete/', 'wait_increase_coefficient', '100');
        $moduleConfig->insert('/walmart/listing/product/action/delete/', 'min_allowed_wait_interval', '600');
        $moduleConfig->insert(
            '/walmart/listing/product/action/processing/prepare/',
            'max_listings_products_count',
            '2000'
        );
        $moduleConfig->insert(
            '/walmart/listing/product/action/revise_promotions/',
            'min_allowed_wait_interval',
            '7200'
        );

        // ---------------------------------------

        $lockItemTableName = $this->getFullTableName("lock_item");

        $cronTaskLockItems = $this->getConnection()->query("
  SELECT * FROM `{$lockItemTableName}` WHERE `nick` LIKE 'cron_task_%';
")->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($cronTaskLockItems as $cronTaskLockItem) {
            $nick = $cronTaskLockItem['nick'];

            if (strpos($nick, 'ebay_actions') !== false) {
                $this->getConnection()->update(
                    $lockItemTableName,
                    ['nick' => 'cron_task_ebay_listing_product_process_actions'],
                    ['id = ?' => $cronTaskLockItem['id']]
                );
                continue;
            }

            $this->getConnection()->delete($lockItemTableName, ['id = ?' => $cronTaskLockItem['id']]);
        }
    }

    //########################################

    private function upgradeProcessingData()
    {
        $this->getTableModifier('processing')
            ->addColumn('type', 'SMALLINT(5) UNSIGNED NOT NULL', 0, 'params', true, false)
            ->commit();

        // ---------------------------------------

        if ($this->installer->tableExists($this->getFullTableName('amazon_processing_action_list_sku')) &&
            !$this->installer->tableExists($this->getFullTableName('amazon_listing_product_action_processing_list_sku'))
        ) {
            $this->renameTable(
                'amazon_processing_action_list_sku',
                'amazon_listing_product_action_processing_list_sku'
            );
        }

        // ---------------------------------------

        if ($this->installer->tableExists($this->getFullTableName('amazon_processing_action')) &&
            !$this->installer->tableExists($this->getFullTableName('amazon_listing_product_action_processing'))
        ) {
            $this->renameTable(
                'amazon_processing_action',
                'amazon_listing_product_action_processing'
            );
        }

        $this->getTableModifier('amazon_listing_product_action_processing')
            ->renameColumn('related_id', 'listing_product_id', true, false)
            ->dropColumn('account_id', true, false)
            ->dropColumn('start_date', true, false)
            ->addColumn('is_prepared', 'SMALLINT(6) NOT NULL', 0, 'type', true, false)
            ->addColumn('group_hash', 'VARCHAR(255)', 'NULL', 'is_prepared', true, false)
            ->changeColumn('request_data', 'LONGTEXT', 'NULL', null, false)
            ->dropIndex('related_id', false)
            ->commit();

        $amazonListingActionProcessingTable = $this->getFullTableName('amazon_listing_product_action_processing');
        $amazonOrderActionProcessingTable = $this->getFullTableName('amazon_order_action_processing');

        $this->installer->run(<<<SQL
UPDATE `{$amazonListingActionProcessingTable}` SET `type` = 'add' WHERE `type` = 0;
SQL
        );

        $this->installer->run(<<<SQL
UPDATE `{$amazonListingActionProcessingTable}` SET `type` = 'update' WHERE `type` = 1;
SQL
        );

        $this->installer->run(<<<SQL
UPDATE `{$amazonListingActionProcessingTable}` SET `type` = 'delete' WHERE `type` = 2;
SQL
        );

        // ---------------------------------------

        $this->installer->run(<<<SQL
INSERT INTO `{$amazonOrderActionProcessingTable}` 
(`order_id`, `processing_id`, `request_pending_single_id`, `type`, `request_data`, `update_date`, `create_date`)
SELECT 
`listing_product_id`,
`processing_id`, 
`request_pending_single_id`, 
`type`, `request_data`,
 `update_date`, 
 `create_date` 
FROM `{$amazonListingActionProcessingTable}` WHERE `type` IN (3, 4, 5);
SQL
        );

        $this->installer->run(<<<SQL
DELETE FROM `{$amazonListingActionProcessingTable}` WHERE `type` IN (3, 4, 5);
SQL
        );

        $this->installer->run(<<<SQL
UPDATE `{$amazonOrderActionProcessingTable}` SET `type` = 'update' WHERE `type` = 3;
SQL
        );

        $this->installer->run(<<<SQL
UPDATE `{$amazonOrderActionProcessingTable}` SET `type` = 'cancel' WHERE `type` = 4;
SQL
        );

        $this->installer->run(<<<SQL
UPDATE `{$amazonOrderActionProcessingTable}` SET `type` = 'refund' WHERE `type` = 5;
SQL
        );

        // ---------------------------------------

        if ($this->installer->tableExists($this->getFullTableName('ebay_processing_action')) &&
            !$this->installer->tableExists($this->getFullTableName('ebay_listing_product_action_processing'))
        ) {
            $this->renameTable(
                'ebay_processing_action',
                'ebay_listing_product_action_processing'
            );
        }

        $this->getTableModifier('ebay_listing_product_action_processing')
            ->renameColumn('related_id', 'listing_product_id', false, false)
            ->dropColumn('account_id', true, false)
            ->dropColumn('marketplace_id', true, false)
            ->dropColumn('priority', true, false)
            ->dropColumn('start_date')
            ->commit();

        $this->getTableModifier('ebay_listing_product_action_processing')
            ->addIndex('listing_product_id', false)
            ->commit();

        $ebayListingActionProcessingTable = $this->getFullTableName('ebay_listing_product_action_processing');

        $this->installer->run(<<<SQL
UPDATE `{$ebayListingActionProcessingTable}` SET `type` = 'list' WHERE `type` = 0;
SQL
        );

        $this->installer->run(<<<SQL
UPDATE `{$ebayListingActionProcessingTable}` SET `type` = 'revise' WHERE `type` = 1;
SQL
        );

        $this->installer->run(<<<SQL
UPDATE `{$ebayListingActionProcessingTable}` SET `type` = 'relist' WHERE `type` = 2;
SQL
        );

        $this->installer->run(<<<SQL
UPDATE `{$ebayListingActionProcessingTable}` SET `type` = 'stop' WHERE `type` = 3;
SQL
        );

        // ---------------------------------------

        if ($this->installer->tableExists($this->getFullTableName('walmart_processing_action_list_sku')) &&
            !$this->installer->tableExists($this->getFullTableName('walmart_listing_product_action_processing_list'))
        ) {
            $this->renameTable(
                'walmart_processing_action_list_sku',
                'walmart_listing_product_action_processing_list'
            );
        }

        $this->getTableModifier('walmart_listing_product_action_processing_list')
            ->addColumn('listing_product_id', 'INT(10) UNSIGNED NOT NULL', null, 'account_id', true, false)
            ->addColumn('stage', 'SMALLINT(5) UNSIGNED NOT NULL', 1, 'sku', true, false)
            ->addColumn('relist_request_pending_single_id', 'INT(10) UNSIGNED', 'NULL', 'stage', false, false)
            ->addColumn('relist_request_data', 'LONGTEXT', 'NULL', 'relist_request_pending_single_id', false, false)
            ->addColumn('relist_configurator_data', 'LONGTEXT', 'NULL', 'relist_request_data', false, false)
            ->commit();

        // ---------------------------------------

        if ($this->installer->tableExists($this->getFullTableName('walmart_processing_action')) &&
            !$this->installer->tableExists($this->getFullTableName('walmart_listing_product_action_processing'))
        ) {
            $this->renameTable(
                'walmart_processing_action',
                'walmart_listing_product_action_processing'
            );
        }

        $this->getTableModifier('walmart_listing_product_action_processing')
            ->renameColumn('related_id', 'listing_product_id', true, false)
            ->dropColumn('account_id', true, false)
            ->dropColumn('start_date', true, false)
            ->addColumn('is_prepared', 'SMALLINT(6) NOT NULL', 0, 'type', true, false)
            ->addColumn('group_hash', 'VARCHAR(255)', 'NULL', 'is_prepared', true, false)
            ->changeColumn('request_data', 'LONGTEXT', 'NULL', null, false)
            ->commit();

        $walmartListingActionProcessingTable = $this->getFullTableName('walmart_listing_product_action_processing');

        $this->installer->run(<<<SQL
UPDATE `{$walmartListingActionProcessingTable}` SET `type` = 'add' WHERE `type` = 0;
SQL
        );

        $this->installer->run(<<<SQL
UPDATE `{$walmartListingActionProcessingTable}` SET `type` = 'update' WHERE `type` = 1;
SQL
        );

        // ---------------------------------------

        $processingRunnerModelNameMap = [
            'Amazon\Synchronization\OtherListings\Update\ProcessingRunner' =>
                'Cron\Task\Amazon\Listing\Other\Channel\SynchronizeData\ProcessingRunner',
            'Amazon\Synchronization\OtherListings\Update\Blocked\ProcessingRunner' =>
                'Cron\Task\Amazon\Listing\Other\Channel\SynchronizeData\Blocked\ProcessingRunner',
            'Amazon\Synchronization\ListingsProducts\Update\ProcessingRunner' =>
                'Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\ProcessingRunner',
            'Amazon\Synchronization\ListingsProducts\Update\Blocked\ProcessingRunner' =>
                'Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\Blocked\ProcessingRunner',
            'Amazon\Synchronization\ListingsProducts\Update\Defected\ProcessingRunner' =>
                'Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\Defected\ProcessingRunner',
            'Ebay\Synchronization\OtherListings\Update\ProcessingRunner' =>
                'Cron\Task\Ebay\Listing\Other\Channel\SynchronizeData\ProcessingRunner',
            'Walmart\Synchronization\ListingsProducts\Update\ProcessingRunner' =>
                'Cron\Task\Walmart\Listing\Product\Channel\SynchronizeData\ProcessingRunner',
            'Walmart\Synchronization\OtherListings\Update\ProcessingRunner' =>
                'Cron\Task\Walmart\Listing\Other\Channel\SynchronizeData\ProcessingRunner'
        ];

        $responserModelNameMap = [
            'Amazon\Synchronization\OtherListings\Update\Responser' =>
                'Cron\Task\Amazon\Listing\Other\Channel\SynchronizeData\Responser',
            'Amazon\Synchronization\OtherListings\Update\Blocked\Responser' =>
                'Cron\Task\Amazon\Listing\Other\Channel\SynchronizeData\Blocked\Responser',
            'Amazon\Synchronization\ListingsProducts\Update\Responser' =>
                'Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\Responser',
            'Amazon\Synchronization\ListingsProducts\Update\Blocked\Responser' =>
                'Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\Blocked\Responser',
            'Amazon\Synchronization\ListingsProducts\Update\Defected\Responser' =>
                'Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\Defected\Responser',
            'Amazon\Synchronization\Orders\Cancel\Responser' => 'Cron\Task\Amazon\Order\Cancel\Responser',
            'Amazon\Synchronization\Orders\Receive\Details\Responser' =>
                'Cron\Task\Amazon\Order\Receive\Details\Responser',
            'Amazon\Synchronization\Orders\Refund\Responser' => 'Cron\Task\Amazon\Order\Refund\Responser',
            'Amazon\Synchronization\Orders\Update\Responser' => 'Cron\Task\Amazon\Order\Update\Responser',
            'Ebay\Synchronization\OtherListings\Update\Responser' =>
                'Cron\Task\Ebay\Listing\Other\Channel\SynchronizeData\Responser',
            'Walmart\Synchronization\OtherListings\Update\Responser' =>
                'Cron\Task\Walmart\Listing\Other\Channel\SynchronizeData\Responser',
            'Walmart\Synchronization\ListingsProducts\Update\Responser' =>
                'Cron\Task\Walmart\Listing\Product\Channel\SynchronizeData\Responser'
        ];

        $processingTable = $this->getFullTableName('processing');

        $processings = $this->getConnection()
            ->query("SELECT * FROM {$processingTable};")
            ->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($processings as $processing) {
            $isUpdated = false;

            if (isset($processingRunnerModelNameMap[$processing['model']])) {
                $processing['model'] = $processingRunnerModelNameMap[$processing['model']];
                $isUpdated = true;
            }

            $params = json_decode($processing['params'], true);

            if (isset($params['responser_model_name']) &&
                isset($responserModelNameMap[$params['responser_model_name']])) {
                $params['responser_model_name'] = $responserModelNameMap[$params['responser_model_name']];
                $processing['params'] = json_encode($params);
                $isUpdated = true;
            }

            if ($isUpdated) {
                $this->getConnection()->update(
                    $processingTable,
                    ['model' => $processing['model'], 'params' => $processing['params']],
                    ['id = ?' => $processing['id']]
                );
            }
        }

        // ---------------------------------------
    }

    //########################################

    private function upgradeConnectorData()
    {
        if ($this->installer->tableExists($this->getFullTableName('connector_pending_requester_single')) &&
            !$this->installer->tableExists($this->getFullTableName('connector_command_pending_processing_single'))
        ) {
            $this->renameTable(
                'connector_pending_requester_single',
                'connector_command_pending_processing_single'
            );
        }

        if ($this->installer->tableExists($this->getFullTableName('connector_pending_requester_partial')) &&
            !$this->installer->tableExists($this->getFullTableName('connector_command_pending_processing_partial'))
        ) {
            $this->renameTable(
                'connector_pending_requester_partial',
                'connector_command_pending_processing_partial'
            );
        }
    }

    //########################################

    private function upgradeListingProductData()
    {
        $listingProductTable = $this->getFullTableName('listing_product');
        $productChangeTable = $this->getFullTableName('product_change');

        if ($this->installer->tableExists($productChangeTable)) {

            $changedProductsListingsProductsData = $this->getConnection()->query("
 SELECT `lp`.`id`, `lp`.`component_mode` FROM `{$listingProductTable}` AS `lp`
 LEFT JOIN `{$productChangeTable}` AS `pc` ON `pc`.`product_id` = `lp`.`product_id`
 WHERE `pc`.`product_id` IS NOT NULL
")->fetchAll(\PDO::FETCH_ASSOC);

            $listingProductInstructionData = [];

            foreach ($changedProductsListingsProductsData as $listingProductData) {
                $listingProductInstructionData[] = [
                    'listing_product_id' => $listingProductData['id'],
                    'component' => $listingProductData['component_mode']
                ];
            }

            $this->getConnection()->insertMultiple(
                $this->getFullTableName('listing_product_instruction'),
                $listingProductInstructionData
            );

            $this->getConnection()->dropTable($productChangeTable);
        }

        // ---------------------------------------

        if ($this->getTableModifier('listing_product')->isColumnExists('synch_status')) {
            $synchStatusNeedListingsProductsData = $this->getConnection()->query("
SELECT `id`, `component_mode` FROM `{$listingProductTable}` WHERE `synch_status` = 1;
")->fetchAll(\PDO::FETCH_ASSOC);

            $listingProductInstructionData = [];

            foreach ($synchStatusNeedListingsProductsData as $listingProductData) {
                $listingProductInstructionData[] = [
                    'listing_product_id' => $listingProductData['id'],
                    'component' => $listingProductData['component_mode']
                ];
            }

            $this->getConnection()->insertMultiple(
                $this->getFullTableName('listing_product_instruction'),
                $listingProductInstructionData
            );

            $this->getTableModifier('listing_product')
                ->dropColumn('synch_status', true, false)
                ->dropColumn('synch_reasons', false, false)
                ->dropColumn('need_synch_rules_check', true, false)
                ->dropColumn('tried_to_list', true, false)
                ->commit();
        }

        // ---------------------------------------

        $this->getTableModifier('amazon_listing_product')
            ->addColumn('online_handling_time', 'INT(10) UNSIGNED', 'NULL', 'online_qty', false, false)
            ->addColumn('online_restock_date', 'DATETIME', 'NULL', 'online_handling_time', false, false)
            ->addColumn('online_details_data', 'LONGTEXT', 'NULL', 'online_restock_date', false, false)
            ->addColumn('online_images_data', 'LONGTEXT', 'NULL', 'online_details_data', false, false)
            ->commit();

        // ---------------------------------------

        $this->getTableModifier('amazon_listing_product_repricing')
            ->changeColumn('is_online_disabled', 'SMALLINT(5) UNSIGNED NOT NULL', '0', null, false)
            ->addColumn(
                'is_online_inactive',
                'SMALLINT(5) UNSIGNED NOT NULL',
                '0',
                'is_online_disabled',
                true,
                false
            )
            ->addColumn(
                'last_updated_regular_price',
                'DECIMAL(12, 4) UNSIGNED',
                'NULL',
                'online_max_price',
                false,
                false
            )
            ->addColumn(
                'last_updated_min_price',
                'DECIMAL(12, 4) UNSIGNED',
                'NULL',
                'last_updated_regular_price',
                false,
                false
            )
            ->addColumn(
                'last_updated_max_price',
                'DECIMAL(12, 4) UNSIGNED',
                'NULL',
                'last_updated_min_price',
                false,
                false
            )
            ->addColumn(
                'last_updated_is_disabled',
                'SMALLINT(5) UNSIGNED',
                'NULL',
                'last_updated_max_price',
                false,
                false
            )
            ->commit();

        // ---------------------------------------

        $this->getTableModifier('ebay_listing_product')
            ->renameColumn('online_category', 'online_main_category', true, false)
            ->commit();

        $this->getTableModifier('ebay_listing_product')
            ->addColumn('online_sub_title', 'VARCHAR(255)', 'NULL', 'online_title', false, false)
            ->addColumn('online_description', 'LONGTEXT', 'NULL', 'online_sub_title', false, false)
            ->addColumn('online_images', 'LONGTEXT', 'NULL', 'online_description', false, false)
            ->addColumn('online_categories_data', 'LONGTEXT', 'NULL', 'online_main_category', false, false)
            ->addColumn('online_shipping_data', 'LONGTEXT', 'NULL', 'online_categories_data', false, false)
            ->addColumn('online_payment_data', 'LONGTEXT', 'NULL', 'online_shipping_data', false, false)
            ->addColumn('online_return_data', 'LONGTEXT', 'NULL', 'online_payment_data', false, false)
            ->addColumn('online_other_data', 'LONGTEXT', 'NULL', 'online_return_data', false, false)
            ->commit();
    }

    //########################################

    private function upgradeTemplate()
    {
        $this->getTableModifier('template_synchronization')
            ->dropColumn('revise_change_listing', true, false)
            ->dropColumn('revise_change_selling_format_template', true, false)
            ->commit();

        $this->getTableModifier('ebay_template_synchronization')
            ->renameColumn('revise_change_category_template', 'revise_update_categories', false, false)
            ->renameColumn('revise_change_shipping_template', 'revise_update_shipping', false, false)
            ->renameColumn('revise_change_payment_template', 'revise_update_payment', false, false)
            ->renameColumn('revise_change_return_policy_template', 'revise_update_return', false, false)
            ->dropColumn('revise_update_specifics', false, false)
            ->dropColumn('relist_send_data', false, false)
            ->dropColumn('revise_change_description_template', false, false)
            ->dropColumn('revise_update_shipping_services', false, false)
            ->commit();

            $this->getTableModifier('ebay_template_synchronization')
            ->changeColumn(
                'revise_update_shipping',
                'SMALLINT(5) UNSIGNED NOT NULL',
                null,
                'revise_update_categories',
                false
            )
            ->changeColumn(
                'revise_update_categories',
                'SMALLINT(5) UNSIGNED NOT NULL',
                null,
                'revise_update_images',
                false
            )
            ->addColumn(
                'revise_update_other',
                'SMALLINT(5) UNSIGNED NOT NULL',
                null,
                'revise_update_return',
                false,
                false
            )
            ->addColumn(
                'stop_mode',
                'SMALLINT(5) UNSIGNED NOT NULL',
                null,
                'relist_advanced_rules_filters',
                false,
                false
            )
            ->commit();

        $this->getTableModifier('amazon_template_synchronization')
            ->dropColumn('relist_send_data', false, false)
            ->dropColumn('revise_change_description_template', false, false)
            ->dropColumn('revise_change_shipping_template', false, false)
            ->dropColumn('revise_change_product_tax_code_template', false, false)
            ->addColumn(
                'stop_mode',
                'SMALLINT(5) UNSIGNED NOT NULL',
                null,
                'relist_advanced_rules_filters',
                false,
                false
            )
            ->commit();

        $amazonTemplateSynchronizationTable = $this->getFullTableName('amazon_template_synchronization');
        $ebayTemplateSynchronizationTable = $this->getFullTableName('ebay_template_synchronization');
        $this->installer->run(<<<SQL
UPDATE `{$amazonTemplateSynchronizationTable}`
SET `stop_mode` = 1
WHERE (`stop_status_disabled`+`stop_out_off_stock`+`stop_qty_magento`+`stop_qty_calculated`) > 0;
SQL
        );

        $this->installer->run(<<<SQL
UPDATE `{$ebayTemplateSynchronizationTable}`
SET `stop_mode` = 1
WHERE (`stop_status_disabled`+`stop_out_off_stock`+`stop_qty_magento`+`stop_qty_calculated`) > 0;
SQL
        );

        $this->getTableModifier('walmart_template_description')
            ->changeColumn('description_template', 'LONGTEXT NOT NULL', null, 'description_mode', false)
            ->commit();

        $this->getTableModifier('amazon_template_description_definition')
            ->changeColumn('msrp_rrp_mode', 'SMALLINT(5) UNSIGNED', '0', 'number_of_items_custom_attribute', false)
            ->changeColumn('msrp_rrp_custom_attribute', 'VARCHAR(255)', 'NULL', 'msrp_rrp_mode', false)
            ->commit();
    }

    //########################################

    private function upgradeAccount()
    {
        $this->getTableModifier('ebay_account')
            ->addColumn('sell_api_token_session', 'VARCHAR(255)', 'NULL', 'token_expired_date', false, false)
            ->addColumn('sell_api_token_expired_date', 'DATETIME', 'NULL', 'sell_api_token_session', false, false)
            ->addColumn('rate_tables', 'TEXT', 'NULL', 'user_preferences', false, false)
            ->commit();
    }

    //########################################

    private function upgradeEbayTemplateShipping()
    {
        $this->getTableModifier('ebay_template_shipping')
            ->renameColumn('dispatch_time', 'dispatch_time_value', false, false)
            ->renameColumn('local_shipping_discount_mode', 'local_shipping_discount_promotional_mode', false, false)
            ->renameColumn(
                'local_shipping_discount_profile_id',
                'local_shipping_discount_combined_profile_id',
                false,
                false
            )
            ->renameColumn(
                'international_shipping_discount_mode',
                'international_shipping_discount_promotional_mode',
                false,
                false
            )
            ->renameColumn(
                'international_shipping_discount_profile_id',
                'international_shipping_discount_combined_profile_id',
                false,
                false
            )
            ->commit();

        $this->getTableModifier('ebay_template_shipping')
            ->addColumn(
                'dispatch_time_mode',
                'SMALLINT(5) UNSIGNED NOT NULL',
                '1',
                'address_custom_attribute',
                false,
                false
            )
            ->addColumn('dispatch_time_attribute', 'VARCHAR(255)', 'NULL', 'dispatch_time_value', false, false)
            ->addColumn('local_shipping_rate_table', 'TEXT', 'NULL', 'dispatch_time_attribute', false, false)
            ->addColumn('international_shipping_rate_table', 'TEXT', 'NULL', 'local_shipping_rate_table', false, false)
            ->changeColumn(
                'local_shipping_discount_promotional_mode',
                'SMALLINT(5) UNSIGNED NOT NULL',
                '0',
                null,
                false
            )
            ->changeColumn(
                'international_shipping_discount_promotional_mode',
                'SMALLINT(5) UNSIGNED NOT NULL',
                '0',
                null,
                false
            )
            ->commit();

        if ($this->getTableModifier('ebay_template_shipping')->isColumnExists('local_shipping_rate_table_mode')) {

            $tableName = $this->getFullTableName('ebay_account');
            $query = $this->getConnection()->query("SELECT account_id FROM {$tableName}");
            $accounts = $query->fetchAll(\PDO::FETCH_ASSOC);

            $enabledShippingRateTable = [];
            $disabledShippingRateTable = [];
            foreach ($accounts as $account) {

                $enabledShippingRateTable[$account['account_id']] = [
                    "mode" => 1,
                    "value" => 1
                ];

                $disabledShippingRateTable[$account['account_id']] = [
                    "mode" => 1,
                    "value" => 0
                ];
            }

            $tableName = $this->getFullTableName('ebay_template_shipping');

            $this->getConnection()->update(
                $tableName,
                array('local_shipping_rate_table' => json_encode($enabledShippingRateTable)),
                array('local_shipping_rate_table_mode = ?' => 1)
            );

            $this->getConnection()->update(
                $tableName,
                array('international_shipping_rate_table' => json_encode($enabledShippingRateTable)),
                array('international_shipping_rate_table_mode = ?' => 1)
            );

            $this->getConnection()->update(
                $tableName,
                array('local_shipping_rate_table' => json_encode($disabledShippingRateTable)),
                array('local_shipping_rate_table_mode = ?' => 0)
            );

            $this->getConnection()->update(
                $tableName,
                array('international_shipping_rate_table' => json_encode($disabledShippingRateTable)),
                array('international_shipping_rate_table_mode = ?' => 0)
            );
        }

        $this->getTableModifier('ebay_template_shipping')
            ->dropColumn('local_shipping_rate_table_mode', false, false)
            ->dropColumn('international_shipping_rate_table_mode', false, false)
            ->commit();
    }

    //########################################

    private function upgradeListing()
    {
        $this->getTableModifier('amazon_listing_other')
            ->addColumn(
                'is_repricing_inactive',
                'SMALLINT(5) UNSIGNED NOT NULL',
                '0',
                'is_repricing_disabled',
                true,
                false
            )
            ->commit();

        $this->getTableModifier('walmart_listing_product')
            ->dropColumn('is_images_data_changed', false, false)
            ->changeColumn('online_details_data', 'LONGTEXT', 'NULL', null, false)
            ->commit();
    }

    //########################################

    private function upgradeOrder()
    {
        $this->getTableModifier('amazon_order')
            ->addColumn('seller_order_id', 'VARCHAR(255)', 'NULL', 'amazon_order_id', true, false)
            ->commit();
    }

    //########################################
}
