<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y19_m04;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Class \Ess\M2ePro\Setup\Update\y19\Walmart_m04
 */
class Walmart extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return [];
    }

    public function execute()
    {
        $this->installSchema();
        $this->installData();
    }

    private function installSchema()
    {
        /**
         * Create table 'm2epro_walmart_account'
         */
        $walmartAccountTable = $this->getConnection()->newTable($this->getFullTableName('walmart_account'))
            ->addColumn(
                'account_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'server_hash',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'marketplace_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'consumer_id',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'client_id',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true]
            )
            ->addColumn(
                'client_secret',
                Table::TYPE_TEXT,
                null,
                ['nullable' => true]
            )
            ->addColumn(
                'related_store_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'other_listings_synchronization',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'other_listings_mapping_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'null'
            )
            ->addColumn(
                'other_listings_mapping_settings',
                Table::TYPE_TEXT,
                null,
                ['nullable' => true]
            )
            ->addColumn(
                'magento_orders_settings',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'orders_last_synchronization',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => true]
            )
            ->addColumn(
                'info',
                Table::TYPE_TEXT,
                null,
                ['nullable' => true]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($walmartAccountTable);

        /**
         * Create table 'm2epro_walmart_dictionary_category'
         */
        $walmartDictionaryCategoryTable = $this->getConnection()->newTable(
            $this->getFullTableName('walmart_dictionary_category')
        )
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'marketplace_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'unsigned' => true]
            )
            ->addColumn(
                'category_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'parent_category_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true]
            )
            ->addColumn(
                'browsenode_id',
                Table::TYPE_DECIMAL,
                [20, 0],
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'product_data_nicks',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'title',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'path',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'keywords',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'is_leaf',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addIndex('marketplace_id', 'marketplace_id')
            ->addIndex('category_id', 'category_id')
            ->addIndex('browsenode_id', 'browsenode_id')
            ->addIndex('parent_category_id', 'parent_category_id')
            ->addIndex('product_data_nicks', [['name' => 'product_data_nicks', 'size' => 500]])
            ->addIndex('title', 'title')
            ->addIndex('path', [['name' => 'path', 'size' => 500]])
            ->addIndex('is_leaf', 'is_leaf')
            ->setOption('type', 'MYISAM')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($walmartDictionaryCategoryTable);

        /**
         * Create table 'm2epro_walmart_dictionary_marketplace'
         */
        $walmartDictionaryMarketplaceTable = $this->getConnection()
            ->newTable($this->getFullTableName('walmart_dictionary_marketplace'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'marketplace_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'client_details_last_update_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                'server_details_last_update_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                'product_data',
                Table::TYPE_TEXT,
                \Ess\M2ePro\Setup\InstallSchema::LONG_COLUMN_SIZE,
                ['default' => null]
            )
            ->addColumn(
                'tax_codes',
                Table::TYPE_TEXT,
                \Ess\M2ePro\Setup\InstallSchema::LONG_COLUMN_SIZE,
                ['nullable' => true]
            )
            ->addIndex('marketplace_id', 'marketplace_id')
            ->setOption('type', 'MYISAM')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($walmartDictionaryMarketplaceTable);

        /**
         * Create table 'm2epro_walmart_dictionary_specific'
         */
        $walmartDictionarySpecificTable = $this->getConnection()
            ->newTable($this->getFullTableName('walmart_dictionary_specific'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'marketplace_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'specific_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'parent_specific_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'product_data_nick',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'title',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'xml_tag',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'xpath',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'type',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'values',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'recommended_values',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'params',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'data_definition',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'min_occurs',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'max_occurs',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addIndex('marketplace_id', 'marketplace_id')
            ->addIndex('specific_id', 'specific_id')
            ->addIndex('parent_specific_id', 'parent_specific_id')
            ->addIndex('product_data_nick', 'product_data_nick')
            ->addIndex('title', 'title')
            ->addIndex('xml_tag', 'xml_tag')
            ->addIndex('xpath', 'xpath')
            ->addIndex('type', 'type')
            ->addIndex('min_occurs', 'min_occurs')
            ->addIndex('max_occurs', 'max_occurs')
            ->setOption('type', 'MYISAM')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($walmartDictionarySpecificTable);

        /**
         * Create table 'm2epro_walmart_indexer_listing_product_variation_parent'
         */
        $walmartIndexerListingProductParent = $this->getConnection()
            ->newTable($this->getFullTableName('walmart_indexer_listing_product_variation_parent'))
            ->addColumn(
                'listing_product_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'listing_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'min_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'max_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'create_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addIndex('listing_id', 'listing_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($walmartIndexerListingProductParent);

        /**
         * Create table 'm2epro_walmart_item'
         */
        $walmartItem = $this->getConnection()->newTable($this->getFullTableName('walmart_item'))
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
                'marketplace_id',
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
            ->addColumn(
                'product_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'store_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'variation_product_options',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'variation_channel_options',
                Table::TYPE_TEXT,
                null,
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
            ->addIndex('account_id', 'account_id')
            ->addIndex('marketplace_id', 'marketplace_id')
            ->addIndex('sku', 'sku')
            ->addIndex('product_id', 'product_id')
            ->addIndex('store_id', 'store_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($walmartItem);

        /**
         * Create table 'm2epro_walmart_listing'
         */
        $walmartListingTable = $this->getConnection()->newTable($this->getFullTableName('walmart_listing'))
            ->addColumn(
                'listing_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'auto_global_adding_category_template_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'auto_website_adding_category_template_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_description_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'template_selling_format_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'template_synchronization_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addIndex('auto_global_adding_category_template_id', 'auto_global_adding_category_template_id')
            ->addIndex('auto_website_adding_category_template_id', 'auto_website_adding_category_template_id')
            ->addIndex('template_description_id', 'template_description_id')
            ->addIndex('template_selling_format_id', 'template_selling_format_id')
            ->addIndex('template_synchronization_id', 'template_synchronization_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($walmartListingTable);

        /**
         * Create table 'm2epro_walmart_listing_auto_category_group'
         */
        $walmartListingAutoCategoryGroupTable = $this->getConnection()
            ->newTable($this->getFullTableName('walmart_listing_auto_category_group'))
            ->addColumn(
                'listing_auto_category_group_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'adding_category_template_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addIndex('adding_category_template_id', 'adding_category_template_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($walmartListingAutoCategoryGroupTable);

        /**
         * Create table 'm2epro_walmart_listing_other'
         */
        $walmartListingOtherTable = $this->getConnection()
            ->newTable($this->getFullTableName('walmart_listing_other'))
            ->addColumn(
                'listing_other_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'sku',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'gtin',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'upc',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'ean',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'wpid',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'item_id',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'channel_url',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'publish_status',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'lifecycle_status',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'status_change_reasons',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'is_online_price_invalid',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'title',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'online_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'nullable' => false, 'default' => '0.0000']
            )
            ->addColumn(
                'online_qty',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addIndex('sku', 'sku')
            ->addIndex('gtin', 'gtin')
            ->addIndex('upc', 'upc')
            ->addIndex('ean', 'ean')
            ->addIndex('wpid', 'wpid')
            ->addIndex('item_id', 'item_id')
            ->addIndex('title', 'title')
            ->addIndex('online_price', 'online_price')
            ->addIndex('online_qty', 'online_qty')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($walmartListingOtherTable);

        /**
         * Create table 'm2epro_walmart_listing_product'
         */
        $walmartListingProductTable = $this->getConnection()
            ->newTable($this->getFullTableName('walmart_listing_product'))
            ->addColumn(
                'listing_product_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'template_category_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'is_variation_product',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_variation_product_matched',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_variation_channel_matched',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_variation_parent',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'variation_parent_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'variation_parent_need_processor',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'variation_child_statuses',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'sku',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'gtin',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'upc',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'ean',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'isbn',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'wpid',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'item_id',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'channel_url',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'publish_status',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'lifecycle_status',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'status_change_reasons',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'online_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'is_online_price_invalid',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'online_promotions',
                Table::TYPE_TEXT,
                null,
                ['nullable' => true]
            )
            ->addColumn(
                'online_qty',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'online_lag_time',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true]
            )
            ->addColumn(
                'online_details_data',
                Table::TYPE_TEXT,
                null,
                ['nullable' => true]
            )
            ->addColumn(
                'is_details_data_changed',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'online_start_date',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => true]
            )
            ->addColumn(
                'online_end_date',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => true]
            )
            ->addColumn(
                'is_missed_on_channel',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'list_date',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => true]
            )
            ->addIndex('template_category_id', 'template_category_id')
            ->addIndex('is_variation_product', 'is_variation_product')
            ->addIndex('is_variation_product_matched', 'is_variation_product_matched')
            ->addIndex('is_variation_channel_matched', 'is_variation_channel_matched')
            ->addIndex('is_variation_parent', 'is_variation_parent')
            ->addIndex('variation_parent_id', 'variation_parent_id')
            ->addIndex('variation_parent_need_processor', 'variation_parent_need_processor')
            ->addIndex('sku', 'sku')
            ->addIndex('gtin', 'gtin')
            ->addIndex('upc', 'upc')
            ->addIndex('ean', 'ean')
            ->addIndex('isbn', 'isbn')
            ->addIndex('wpid', 'wpid')
            ->addIndex('item_id', 'item_id')
            ->addIndex('online_price', 'online_price')
            ->addIndex('online_qty', 'online_qty')
            ->addIndex('online_start_date', 'online_start_date')
            ->addIndex('online_end_date', 'online_end_date')
            ->addIndex('list_date', 'list_date')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($walmartListingProductTable);

        $walmartProcessingActionTable = $this->getConnection()->newTable(
            $this->getFullTableName('walmart_processing_action')
        )
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
                'processing_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'request_pending_single_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true]
            )
            ->addColumn(
                'related_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true]
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
                \Ess\M2ePro\Setup\InstallSchema::LONG_COLUMN_SIZE,
                ['nullable' => false]
            )
            ->addColumn(
                'start_date',
                Table::TYPE_DATETIME,
                null,
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
            ->addIndex('account_id', 'account_id')
            ->addIndex('processing_id', 'processing_id')
            ->addIndex('request_pending_single_id', 'request_pending_single_id')
            ->addIndex('related_id', 'related_id')
            ->addIndex('type', 'type')
            ->addIndex('start_date', 'start_date')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($walmartProcessingActionTable);

        $walmartProcessingActionListSku = $this->getConnection()->newTable(
            $this->getFullTableName('walmart_processing_action_list_sku')
        )
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
            ->addColumn(
                'create_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addIndex(
                'account_id__sku',
                ['account_id', 'sku'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($walmartProcessingActionListSku);

        /**
         * Create table 'm2epro_walmart_listing_product_variation'
         */
        $walmartListingProductVariationTable = $this->getConnection()
            ->newTable($this->getFullTableName('walmart_listing_product_variation'))
            ->addColumn(
                'listing_product_variation_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($walmartListingProductVariationTable);

        /**
         * Create table 'm2epro_walmart_listing_product_variation_option'
         */
        $walmartListingProductVariationOptionTable = $this->getConnection()
            ->newTable($this->getFullTableName('walmart_listing_product_variation_option'))
            ->addColumn(
                'listing_product_variation_option_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($walmartListingProductVariationOptionTable);

        /**
         * Create table 'm2epro_walmart_marketplace'
         */
        $walmartMarketplaceTable = $this->getConnection()
            ->newTable($this->getFullTableName('walmart_marketplace'))
            ->addColumn(
                'marketplace_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'developer_key',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'default_currency',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($walmartMarketplaceTable);

        /**
         * Create table 'm2epro_walmart_order'
         */
        $walmartOrderTable = $this->getConnection()
            ->newTable($this->getFullTableName('walmart_order'))
            ->addColumn(
                'order_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'walmart_order_id',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'status',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'buyer_name',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'buyer_email',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'shipping_service',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'shipping_address',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'shipping_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'paid_amount',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'tax_details',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'currency',
                Table::TYPE_TEXT,
                10,
                ['nullable' => false]
            )
            ->addColumn(
                'is_tried_to_acknowledge',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'purchase_update_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                'purchase_create_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addIndex('walmart_order_id', 'walmart_order_id')
            ->addIndex('buyer_name', 'buyer_name')
            ->addIndex('buyer_email', 'buyer_email')
            ->addIndex('paid_amount', 'paid_amount')
            ->addIndex('is_tried_to_acknowledge', 'is_tried_to_acknowledge')
            ->addIndex('purchase_create_date', 'purchase_create_date')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($walmartOrderTable);

        /**
         * Create table 'm2epro_walmart_order_item'
         */
        $walmartOrderItemTable = $this->getConnection()
            ->newTable($this->getFullTableName('walmart_order_item'))
            ->addColumn(
                'order_item_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'walmart_order_item_id',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'merged_walmart_order_item_ids',
                Table::TYPE_TEXT,
                500,
                ['nullable' => true]
            )
            ->addColumn(
                'status',
                Table::TYPE_TEXT,
                30,
                ['nullable' => false]
            )
            ->addColumn(
                'title',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'sku',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'qty',
                Table::TYPE_INTEGER,
                null,
                ['unsigned'  => true, 'nullable'  => false, 'default'   => 0]
            )
            ->addIndex('title', 'title')
            ->addIndex('sku', 'sku')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($walmartOrderItemTable);

        /**
         * Create table 'm2epro_walmart_template_category'
         */
        $walmartTemplateCategoryTable = $this->getConnection()
            ->newTable($this->getFullTableName('walmart_template_category'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'title',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'marketplace_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'product_data_nick',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true]
            )
            ->addColumn(
                'category_path',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true]
            )
            ->addColumn(
                'browsenode_id',
                Table::TYPE_DECIMAL,
                [20, 0],
                ['unsigned'  => true, 'nullable'  => true]
            )
            ->addColumn(
                'update_date',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => true]
            )
            ->addColumn(
                'create_date',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => true]
            )
            ->addIndex('title', 'title')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($walmartTemplateCategoryTable);

        /**
         * Create table 'm2epro_walmart_template_category_specific'
         */
        $walmartTemplateCategorySpecificTable = $this->getConnection()
            ->newTable($this->getFullTableName('walmart_template_category_specific'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'template_category_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned'  => true, 'nullable'  => false]
            )
            ->addColumn(
                'xpath',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'mode',
                Table::TYPE_TEXT,
                25,
                ['nullable' => false]
            )
            ->addColumn(
                'is_required',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => true, 'default'   => 0]
            )
            ->addColumn(
                'custom_value',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true]
            )
            ->addColumn(
                'custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true]
            )
            ->addColumn(
                'type',
                Table::TYPE_TEXT,
                25,
                ['nullable' => true]
            )
            ->addColumn(
                'attributes',
                Table::TYPE_TEXT,
                null,
                ['nullable' => true]
            )
            ->addColumn(
                'update_date',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => true]
            )
            ->addColumn(
                'create_date',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => true]
            )
            ->addIndex('template_category_id', 'template_category_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($walmartTemplateCategorySpecificTable);

        /**
         * Create table 'm2epro_walmart_template_description'
         */
        $walmartTemplateDescriptionTable = $this->getConnection()
            ->newTable($this->getFullTableName('walmart_template_description'))
            ->addColumn(
                'template_description_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'title_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false, 'default'   => 0]
            )
            ->addColumn(
                'title_template',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'brand_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false, 'default'   => 0]
            )
            ->addColumn(
                'brand_custom_value',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true]
            )
            ->addColumn(
                'brand_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true]
            )
            ->addColumn(
                'manufacturer_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false, 'default'   => 0]
            )
            ->addColumn(
                'manufacturer_custom_value',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true]
            )
            ->addColumn(
                'manufacturer_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true]
            )
            ->addColumn(
                'manufacturer_part_number_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false, 'default'   => 0]
            )
            ->addColumn(
                'manufacturer_part_number_custom_value',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'manufacturer_part_number_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false,]
            )
            ->addColumn(
                'model_number_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false, 'default'   => 0]
            )
            ->addColumn(
                'model_number_custom_value',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'model_number_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'msrp_rrp_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => true, 'default'   => 0]
            )
            ->addColumn(
                'msrp_rrp_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true]
            )
            ->addColumn(
                'image_main_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false, 'default'   => 0]
            )
            ->addColumn(
                'image_main_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'image_variation_difference_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false, 'default'   => 0]
            )
            ->addColumn(
                'image_variation_difference_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'gallery_images_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false]
            )
            ->addColumn(
                'gallery_images_limit',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false, 'default'   => 1]
            )
            ->addColumn(
                'gallery_images_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'description_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false, 'default'   => 0]
            )
            ->addColumn(
                'description_template',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'multipack_quantity_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => true, 'default'   => 0]
            )
            ->addColumn(
                'multipack_quantity_custom_value',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true]
            )
            ->addColumn(
                'multipack_quantity_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true]
            )
            ->addColumn(
                'count_per_pack_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => true, 'default'   => 0]
            )
            ->addColumn(
                'count_per_pack_custom_value',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true]
            )
            ->addColumn(
                'count_per_pack_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true]
            )
            ->addColumn(
                'total_count_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => true, 'default'   => 0]
            )
            ->addColumn(
                'total_count_custom_value',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true]
            )
            ->addColumn(
                'total_count_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true]
            )
            ->addColumn(
                'key_features_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false, 'default'   => 0]
            )
            ->addColumn(
                'key_features',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'other_features_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false, 'default'   => 0]
            )
            ->addColumn(
                'other_features',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'keywords_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false, 'default'   => 0]
            )
            ->addColumn(
                'keywords_custom_value',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true]
            )
            ->addColumn(
                'keywords_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true]
            )
            ->addColumn(
                'attributes_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false, 'default'   => 0]
            )
            ->addColumn(
                'attributes',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($walmartTemplateDescriptionTable);

        /**
         * Create table 'm2epro_walmart_template_selling_format'
         */
        $walmartTemplateSellingFormatTable = $this->getConnection()
            ->newTable($this->getFullTableName('walmart_template_selling_format'))
            ->addColumn(
                'template_selling_format_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'marketplace_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'qty_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'qty_custom_value',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'qty_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'qty_percentage',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 100]
            )
            ->addColumn(
                'qty_modification_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'qty_min_posted_value',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'qty_max_posted_value',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'price_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'price_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'map_price_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false]
            )
            ->addColumn(
                'map_price_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'price_coefficient',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'price_variation_mode',
                Table::TYPE_SMALLINT,
                2,
                ['unsigned'  => true, 'nullable'  => false,]
            )
            ->addColumn(
                'price_vat_percent',
                Table::TYPE_FLOAT,
                null,
                ['unsigned'  => true, 'nullable'  => true]
            )
            ->addColumn(
                'promotions_mode',
                Table::TYPE_SMALLINT,
                null,
                ['nullable'  => false, 'default'   => 0]
            )
            ->addColumn(
                'lag_time_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false]
            )
            ->addColumn(
                'lag_time_value',
                Table::TYPE_INTEGER,
                null,
                ['unsigned'  => true, 'nullable'  => false]
            )
            ->addColumn(
                'lag_time_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'product_tax_code_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false]
            )
            ->addColumn(
                'product_tax_code_custom_value',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'product_tax_code_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'item_weight_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => true, 'default'   => 0]
            )
            ->addColumn(
                'item_weight_custom_value',
                Table::TYPE_DECIMAL,
                [10, 2],
                ['unsigned'  => true, 'nullable'  => true, 'scale'     => '2']
            )
            ->addColumn(
                'item_weight_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true]
            )
            ->addColumn(
                'must_ship_alone_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false]
            )
            ->addColumn(
                'must_ship_alone_value',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false]
            )
            ->addColumn(
                'must_ship_alone_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'ships_in_original_packaging_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false]
            )
            ->addColumn(
                'ships_in_original_packaging_value',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false]
            )
            ->addColumn(
                'ships_in_original_packaging_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'shipping_override_rule_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false, 'default'   => 0]
            )
            ->addColumn(
                'sale_time_start_date_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false]
            )
            ->addColumn(
                'sale_time_start_date_value',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'sale_time_start_date_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'sale_time_end_date_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false]
            )
            ->addColumn(
                'sale_time_end_date_value',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'sale_time_end_date_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'attributes_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false, 'default'   => 0]
            )
            ->addColumn(
                'attributes',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addIndex('marketplace_id', 'marketplace_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($walmartTemplateSellingFormatTable);

        /**
         * Create table 'm2epro_walmart_template_selling_format_promotion'
         */
        $walmartTemplateSellingFormatPromotionTable = $this->getConnection()
            ->newTable($this->getFullTableName('walmart_template_selling_format_promotion'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'template_selling_format_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'start_date_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false, 'default'   => 0]
            )
            ->addColumn(
                'start_date_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true]
            )
            ->addColumn(
                'start_date_value',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => true]
            )
            ->addColumn(
                'end_date_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false, 'default'   => 0]
            )
            ->addColumn(
                'end_date_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true]
            )
            ->addColumn(
                'end_date_value',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => true]
            )
            ->addColumn(
                'price_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false]
            )
            ->addColumn(
                'price_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'price_coefficient',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'comparison_price_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false]
            )
            ->addColumn(
                'comparison_price_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'comparison_price_coefficient',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'type',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addIndex('template_selling_format_id', 'template_selling_format_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($walmartTemplateSellingFormatPromotionTable);

        /**
         * Create table 'm2epro_walmart_template_selling_format_shipping_override'
         */
        $walmartTemplateSellingFormatShippingOverrideTable = $this->getConnection()
            ->newTable($this->getFullTableName('walmart_template_selling_format_shipping_override'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'template_selling_format_id',
                Table::TYPE_INTEGER,
                11,
                ['unsigned'  => true, 'nullable'  => false]
            )
            ->addColumn(
                'method',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'is_shipping_allowed',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'region',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'cost_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false, 'default'   => 0]
            )
            ->addColumn(
                'cost_value',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'cost_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addIndex('template_selling_format_id', 'template_selling_format_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($walmartTemplateSellingFormatShippingOverrideTable);

        /**
         * Create table 'm2epro_walmart_template_synchronization'
         */
        $walmartTemplateSynchronizationTable = $this->getConnection()
            ->newTable($this->getFullTableName('walmart_template_synchronization'))
            ->addColumn(
                'template_synchronization_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'list_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'list_status_enabled',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'list_is_in_stock',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'list_qty_magento',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'list_qty_magento_value',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'list_qty_magento_value_max',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'list_qty_calculated',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'list_qty_calculated_value',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'list_qty_calculated_value_max',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'revise_update_qty',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'revise_update_qty_max_applied_value_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'revise_update_qty_max_applied_value',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'revise_update_price',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'revise_update_price_max_allowed_deviation_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'revise_update_price_max_allowed_deviation',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'revise_update_promotions',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false]
            )
            ->addColumn(
                'relist_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'relist_filter_user_lock',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'relist_status_enabled',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'relist_is_in_stock',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'relist_qty_magento',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'relist_qty_magento_value',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'relist_qty_magento_value_max',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'relist_qty_calculated',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'relist_qty_calculated_value',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'relist_qty_calculated_value_max',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'stop_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false]
            )
            ->addColumn(
                'stop_status_disabled',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false]
            )
            ->addColumn(
                'stop_out_off_stock',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned'  => true, 'nullable'  => false]
            )
            ->addColumn(
                'stop_qty_magento',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'stop_qty_magento_value',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'stop_qty_magento_value_max',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'stop_qty_calculated',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'stop_qty_calculated_value',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'stop_qty_calculated_value_max',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($walmartTemplateSynchronizationTable);
    }

    private function installData()
    {
        $moduleConfigModifier = $this->getConfigModifier('module');

        $moduleConfigModifier->insert('/cron/task/walmart/actions/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert('/cron/task/walmart/actions/', 'interval', '3600', 'in seconds');
        $moduleConfigModifier->insert('/cron/task/walmart/actions/', 'last_access', null, 'date of last access');
        $moduleConfigModifier->insert('/cron/task/walmart/actions/', 'last_run', null, 'date of last run');

        $wizardTable = $this->getFullTableName('wizard');

        $query = $this->getConnection()->query('SELECT id FROM '
                                               . $wizardTable . ' WHERE `nick` = \'installationWalmart\'');
        $result = $query->fetchColumn();

        if (empty($result)) {
            $this->getConnection()->insert($this->getFullTableName('wizard'), [
                'nick'     => 'installationWalmart',
                'view'     => 'walmart',
                'status'   => 0,
                'step'     => null,
                'type'     => 1,
                'priority' => 4,
            ]);
        }

        $moduleConfigModifier = $this->getConfigModifier('module');

        $moduleConfigModifier->insert('/walmart/', 'application_name', 'M2ePro - Walmart Magento Integration', null);

        $moduleConfigModifier->insert('/component/walmart/', 'mode', '1', '0 - disable, \r\n1 - enable');

        $moduleConfigModifier->insert('/walmart/configuration/', 'sku_mode', '1', null);
        $moduleConfigModifier->insert('/walmart/configuration/', 'sku_custom_attribute', null, null);
        $moduleConfigModifier->insert('/walmart/configuration/', 'sku_modification_mode', '0', null);
        $moduleConfigModifier->insert('/walmart/configuration/', 'sku_modification_custom_value', null, null);
        $moduleConfigModifier->insert('/walmart/configuration/', 'generate_sku_mode', '0', null);
        $moduleConfigModifier->insert('/walmart/configuration/', 'upc_mode', '0', null);
        $moduleConfigModifier->insert('/walmart/configuration/', 'upc_custom_attribute', null, null);
        $moduleConfigModifier->insert('/walmart/configuration/', 'ean_mode', '0', null);
        $moduleConfigModifier->insert('/walmart/configuration/', 'ean_custom_attribute', null, null);
        $moduleConfigModifier->insert('/walmart/configuration/', 'gtin_mode', '0', null);
        $moduleConfigModifier->insert('/walmart/configuration/', 'gtin_custom_attribute', null, null);
        $moduleConfigModifier->insert('/walmart/configuration/', 'isbn_mode', '0', null);
        $moduleConfigModifier->insert('/walmart/configuration/', 'isbn_custom_attribute', null, null);

        $moduleConfigModifier->insert(
            '/walmart/order/settings/marketplace_25/',
            'use_first_street_line_as_company',
            '1',
            '0 - disable, \r\n1 - enable'
        );

        $synchronizationConfigModifier = $this->getConfigModifier('synchronization');

        $synchronizationConfigModifier->insert(
            '/walmart/templates/synchronization/list/immediately_not_checked/',
            'items_limit',
            '200',
            null
        );
        $synchronizationConfigModifier->insert(
            '/walmart/templates/synchronization/revise/total/',
            'items_limit',
            '200',
            null
        );
        $synchronizationConfigModifier->insert(
            '/walmart/templates/synchronization/revise/need_synch/',
            'items_limit',
            '200',
            null
        );

        $synchronizationConfigModifier->insert('/walmart/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert('/walmart/general/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/walmart/general/run_parent_processors/',
            'interval',
            '60',
            'in seconds'
        );
        $synchronizationConfigModifier->insert(
            '/walmart/general/run_parent_processors/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/walmart/general/run_parent_processors/',
            'last_time',
            null,
            'Last check time'
        );

        $synchronizationConfigModifier->insert(
            '/walmart/listings_products/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/walmart/listings_products/update/', 'interval', '86400', 'in seconds');
        $synchronizationConfigModifier->insert(
            '/walmart/listings_products/update/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/walmart/listings_products/update/',
            'last_time',
            null,
            'Last check time'
        );

        $synchronizationConfigModifier->insert(
            '/walmart/listings_products/update/blocked/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/walmart/listings_products/update/blocked/',
            'interval',
            '86400',
            'in seconds'
        );
        $synchronizationConfigModifier->insert(
            '/walmart/listings_products/update/blocked/',
            'last_time',
            null,
            'Last check time'
        );

        $synchronizationConfigModifier->insert('/walmart/marketplaces/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/walmart/marketplaces/categories/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/walmart/marketplaces/details/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/walmart/marketplaces/specifics/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );

        $synchronizationConfigModifier->insert('/walmart/orders/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert('/walmart/orders/acknowledge/', 'mode', '1', 'in seconds');
        $synchronizationConfigModifier->insert('/walmart/orders/acknowledge/', 'interval', '60', 'in seconds');
        $synchronizationConfigModifier->insert('/walmart/orders/acknowledge/', 'last_time', null, 'Last check time');

        $synchronizationConfigModifier->insert('/walmart/orders/cancel/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert('/walmart/orders/cancel/', 'interval', '60', 'in seconds');
        $synchronizationConfigModifier->insert('/walmart/orders/cancel/', 'last_time', null, 'Last check time');

        $synchronizationConfigModifier->insert('/walmart/orders/receive/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert('/walmart/orders/receive/', 'interval', '60', 'in seconds');
        $synchronizationConfigModifier->insert('/walmart/orders/receive/', 'last_time', null, 'Last check time');

        $synchronizationConfigModifier->insert('/walmart/orders/refund/', 'mode', '1', 'in seconds');
        $synchronizationConfigModifier->insert('/walmart/orders/refund/', 'interval', '60', 'in seconds');
        $synchronizationConfigModifier->insert('/walmart/orders/refund/', 'last_time', null, 'Last check time');

        $synchronizationConfigModifier->insert('/walmart/orders/shipping/', 'mode', '1', 'in seconds');
        $synchronizationConfigModifier->insert('/walmart/orders/shipping/', 'interval', '60', 'in seconds');
        $synchronizationConfigModifier->insert('/walmart/orders/shipping/', 'last_time', null, 'Last check time');

        $synchronizationConfigModifier->insert('/walmart/other_listings/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/walmart/other_listings/update/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/walmart/other_listings/update/', 'interval', '60', 'in seconds');
        $synchronizationConfigModifier->insert('/walmart/other_listings/update/', 'last_time', null, 'Last check time');

        $synchronizationConfigModifier->insert('/walmart/templates/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/walmart/templates/synchronization/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/walmart/templates/synchronization/list/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/walmart/templates/synchronization/relist/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/walmart/templates/synchronization/revise/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/walmart/templates/synchronization/stop/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );

        $marketplaceTable = $this->getFullTableName('marketplace');

        $query = $this->getConnection()->query('SELECT id FROM ' . $marketplaceTable . ' WHERE id = 37');
        $result = $query->fetchColumn();

        if (empty($result)) {
            $this->getConnection()->insertMultiple($marketplaceTable, [
                [
                    'id'             => 37,
                    'native_id'      => 1,
                    'title'          => 'United States',
                    'code'           => 'US',
                    'url'            => 'walmart.com',
                    'status'         => 0,
                    'sorder'         => 3,
                    'group_title'    => 'America',
                    'component_mode' => 'walmart',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 38,
                    'native_id'      => 2,
                    'title'          => 'Canada',
                    'code'           => 'CA',
                    'url'            => 'walmart.ca',
                    'status'         => 0,
                    'sorder'         => 4,
                    'group_title'    => 'America',
                    'component_mode' => 'walmart',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
            ]);

            $this->getConnection()->insertMultiple($this->getFullTableName('walmart_marketplace'), [
                [
                    'marketplace_id'   => 37,
                    'developer_key'    => '8636-1433-4377',
                    'default_currency' => 'USD'
                ],
                [
                    'marketplace_id'   => 38,
                    'developer_key'    => '7078-7205-1944',
                    'default_currency' => 'CAD'
                ]
            ]);
        }
    }

    //########################################
}
