<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 *
 * Migration from 6.5.5 to 1.4.1
 */

namespace Ess\M2ePro\Setup\MigrationFromMagento1\v1_0_0;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Setup\Module\Setup as MagentoSetup;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface as DbAdapter;

/**
 * Class \Ess\M2ePro\Setup\MigrationFromMagento1\v1\Modifier_0_0
 */
class Modifier implements \Ess\M2ePro\Setup\MigrationFromMagento1\IModifierInterface
{
    const LONG_COLUMN_SIZE = 16777217;

    protected $helperFactory;

    protected $modelFactory;

    protected $configModifierFactory;

    protected $tableModifierFactory;

    protected $deploymentConfig;

    protected $installer;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        ResourceConnection $resourceConnection,
        DeploymentConfig $deploymentConfig
    ) {
        $this->helperFactory = $helperFactory;
        $this->modelFactory  = $modelFactory;

        $this->installer = new MagentoSetup($resourceConnection);

        $this->deploymentConfig = $deploymentConfig;
    }

    //########################################

    public function process()
    {
        $this->prepareFloatUnsignedColumns();

        $this->migrateModuleConfig();
        $this->migrateSynchronizationConfig();
        $this->migratePrimaryConfig();

        $this->migrateInStorePickupGlobalKey();
        $this->migrateProductCustomTypes();
        $this->migrateHealthStatus();
        $this->migrateArchivedEntity();
        $this->migrateProductVocabulary();

        $this->migrateAccounts();
        $this->migrateMarketplaces();
        $this->migrateListingProduct();
        $this->migrateProcessing();
        $this->migrateStopQueue();
        $this->migrateOrders();
        $this->migrateLogs();
        $this->migrateWizards();
        $this->migrateGridsPerformanceStructure();
        $this->migrateSynchronizationTemplate();
        $this->migrateTemplate();
        $this->migrateDictionary();

        $this->migrateAmazonShippingOverride();
        $this->migrateAmazonListingProductRepricing();
        $this->migrationAmazonListingOther();

        $this->migrateEbayReturnTemplate();
        $this->migrateEbayTemplateShipping();
        $this->migrateEbayCharity();

        $this->migrateOther();
    }

    //########################################

    private function prepareFloatUnsignedColumns()
    {
        /**
         * convert FLOAT UNSIGNED columns to FLOAT because of zend framework bug in ->createTableByDdl method,
         * that does not support 'FLOAT UNSIGNED' column type
         */
        $this->getTableModifier('ebay_template_selling_format')
             ->changeColumn('vat_percent', 'FLOAT NOT NULL', 0);

        $this->getTableModifier('amazon_template_selling_format')
             ->changeColumn('regular_price_vat_percent', 'FLOAT NOT NULL', 0, null, false)
             ->changeColumn('business_price_vat_percent', 'FLOAT NOT NULL', 0, null, false)
             ->commit();

        $this->getTableModifier('walmart_template_selling_format')
             ->changeColumn('price_vat_percent', 'FLOAT NOT NULL', 0);
    }

    // ---------------------------------------

    private function migrateModuleConfig()
    {
        $this->getConnection()->renameTable(
            $this->getFullTableName('config'),
            $this->getFullTableName('module_config')
        );

        $this->getConnection()->delete(
            $this->getFullTableName('module_config'),
            '`group` LIKE \'%/listing/product/inspector/%\''
        );

        $this->getConnection()->delete(
            $this->getFullTableName('module_config'),
            '`group` LIKE \'%/listing/product/revise/total/%\''
        );

        $this->getConnection()->delete(
            $this->getFullTableName('module_config'),
            '`group` LIKE \'%/listing/product/instructions/%\''
        );

        $this->getConnection()->delete(
            $this->getFullTableName('module_config'),
            '`group` LIKE \'%/listing/product/action/%\''
        );

        $this->getConnection()->delete(
            $this->getFullTableName('module_config'),
            '`group` LIKE \'%/listing/product/scheduled_actions/%\''
        );

        $this->getConnection()->delete(
            $this->getFullTableName('module_config'),
            '`group` LIKE \'%/cron/task/%\''
        );

        $moduleConfigModifier = $this->getConfigModifier('module');

        $moduleConfigModifier->insert('/support/', 'forum_url', 'https://community.m2epro.com/', null);

        $moduleConfigModifier->getEntity('/support/', 'main_website_url')->updateKey('website_url');
        $moduleConfigModifier->getEntity('/support/', 'main_support_url')->updateKey('support_url');

        $moduleConfigModifier->getEntity('/support/', 'knowledge_base_url')->delete();
        $moduleConfigModifier->getEntity('/support/', 'ideas')->delete();

        $moduleConfigModifier->getEntity('/support/', 'magento_connect_url')->updateKey('magento_marketplace_url');
        $magentoMarketplaceUrl = 'https://marketplace.magento.com/'
                                 . 'm2epro-ebay-amazon-rakuten-sears-magento-integration-'
                                 . 'order-import-and-stock-level-synchronization.html';
        $moduleConfigModifier->getEntity('/support/', 'magento_marketplace_url')->updateValue($magentoMarketplaceUrl);

        $servicingInterval = rand(43200, 86400);

        $moduleConfigModifier->getEntity(null, 'environment')->delete();

        $value = $moduleConfigModifier->getEntity('/cron/service/', 'disabled')->getValue();
        $moduleConfigModifier->insert('/cron/service_pub/', 'disabled', $value);
        $moduleConfigModifier->getEntity('/cron/service/', 'disabled')->updateGroup('/cron/service_controller/');

        $moduleConfigModifier->getEntity('/view/ebay/', 'mode')->delete();
        $moduleConfigModifier->getEntity('/component/amazon/', 'allowed')->delete();
        $moduleConfigModifier->getEntity('/component/ebay/', 'allowed')->delete();
        $moduleConfigModifier->getEntity('/component/walmart/', 'allowed')->delete();

        $moduleConfigModifier->insert('/view/ebay/advanced/autoaction_popup/', 'shown', '0', null);
        $moduleConfigModifier->insert('/view/ebay/motors_epids_attribute/', 'listing_notification_shown', '0', null);
        $moduleConfigModifier->insert('/view/ebay/multi_currency_marketplace_2/', 'notification_shown', '0', null);
        $moduleConfigModifier->insert('/view/ebay/multi_currency_marketplace_19/', 'notification_shown', '0', null);

        $moduleConfigModifier->insert('/cron/task/logs_clearing/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert('/cron/task/logs_clearing/', 'interval', '86400', 'in seconds');
        $moduleConfigModifier->insert('/cron/task/logs_clearing/', 'last_access', null, 'date of last access');
        $moduleConfigModifier->insert('/cron/task/logs_clearing/', 'last_run', null, 'date of last run');
        $moduleConfigModifier->insert('/cron/task/request_pending_single/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert('/cron/task/request_pending_single/', 'interval', '60', 'in seconds');
        $moduleConfigModifier->insert('/cron/task/request_pending_single/', 'last_access', null, 'date of last access');
        $moduleConfigModifier->insert('/cron/task/request_pending_single/', 'last_run', null, 'date of last run');
        $moduleConfigModifier->insert(
            '/cron/task/request_pending_partial/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $moduleConfigModifier->insert('/cron/task/request_pending_partial/', 'interval', '60', 'in seconds');
        $moduleConfigModifier->insert(
            '/cron/task/request_pending_partial/',
            'last_access',
            null,
            'date of last access'
        );
        $moduleConfigModifier->insert('/cron/task/request_pending_partial/', 'last_run', null, 'date of last run');
        $moduleConfigModifier->insert(
            '/cron/task/connector_requester_pending_single/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $moduleConfigModifier->insert('/cron/task/connector_requester_pending_single/', 'interval', '60', 'in seconds');
        $moduleConfigModifier->insert(
            '/cron/task/connector_requester_pending_single/',
            'last_access',
            null,
            'date of last access'
        );
        $moduleConfigModifier->insert(
            '/cron/task/connector_requester_pending_single/',
            'last_run',
            null,
            'date of last run'
        );
        $moduleConfigModifier->insert(
            '/cron/task/connector_requester_pending_partial/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $moduleConfigModifier->insert(
            '/cron/task/connector_requester_pending_partial/',
            'interval',
            '60',
            'in seconds'
        );
        $moduleConfigModifier->insert(
            '/cron/task/connector_requester_pending_partial/',
            'last_access',
            null,
            'date of last access'
        );
        $moduleConfigModifier->insert(
            '/cron/task/connector_requester_pending_partial/',
            'last_run',
            null,
            'date of last run'
        );
        $moduleConfigModifier->insert('/cron/task/amazon/actions/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert('/cron/task/amazon/actions/', 'interval', '60', 'in seconds');
        $moduleConfigModifier->insert('/cron/task/amazon/actions/', 'last_access', null, 'date of last access');
        $moduleConfigModifier->insert('/cron/task/amazon/actions/', 'last_run', null, 'date of last run');
        $moduleConfigModifier->insert(
            '/cron/task/amazon/repricing_update_settings/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $moduleConfigModifier->insert('/cron/task/amazon/repricing_update_settings/', 'interval', '180', 'in seconds');
        $moduleConfigModifier->insert(
            '/cron/task/amazon/repricing_update_settings/',
            'last_access',
            null,
            'date of last access'
        );
        $moduleConfigModifier->insert(
            '/cron/task/amazon/repricing_update_settings/',
            'last_run',
            null,
            'date of last run'
        );
        $moduleConfigModifier->insert(
            '/cron/task/amazon/repricing_synchronization_actual_price/',
            'mode',
            1,
            '0 - disable,\r\n1 - enable'
        );
        $moduleConfigModifier->insert(
            '/cron/task/amazon/repricing_synchronization_actual_price/',
            'interval',
            3600,
            'in seconds'
        );
        $moduleConfigModifier->insert(
            '/cron/task/amazon/repricing_synchronization_actual_price/',
            'last_run',
            null,
            'date of last access'
        );
        $moduleConfigModifier->insert(
            '/cron/task/amazon/repricing_synchronization_general/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $moduleConfigModifier->insert(
            '/cron/task/amazon/repricing_synchronization_general/',
            'interval',
            '86400',
            'in seconds'
        );
        $moduleConfigModifier->insert(
            '/cron/task/amazon/repricing_synchronization_general/',
            'last_access',
            null,
            'date of last access'
        );
        $moduleConfigModifier->insert(
            '/cron/task/amazon/repricing_synchronization_general/',
            'last_run',
            null,
            'date of last run'
        );
        $moduleConfigModifier->insert(
            '/cron/task/amazon/repricing_inspect_products/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $moduleConfigModifier->insert(
            '/cron/task/amazon/repricing_inspect_products/',
            'interval',
            '3600',
            'in seconds'
        );
        $moduleConfigModifier->insert(
            '/cron/task/amazon/repricing_inspect_products/',
            'last_access',
            null,
            'date of last access'
        );
        $moduleConfigModifier->insert(
            '/cron/task/amazon/repricing_inspect_products/',
            'last_run',
            null,
            'date of last run'
        );

        $moduleConfigModifier->insert('/walmart/configuration/', 'option_images_url_mode', '0', null);
        ;

        $moduleConfigModifier->insert('/cron/task/walmart/actions/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert('/cron/task/walmart/actions/', 'interval', '3600', 'in seconds');
        $moduleConfigModifier->insert('/cron/task/walmart/actions/', 'last_access', null, 'date of last access');
        $moduleConfigModifier->insert('/cron/task/walmart/actions/', 'last_run', null, 'date of last run');

        $moduleConfigModifier->insert('/cron/task/synchronization/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert('/cron/task/synchronization/', 'interval', '300', 'in seconds');
        $moduleConfigModifier->insert('/cron/task/synchronization/', 'last_access', null, 'date of last access');
        $moduleConfigModifier->insert('/cron/task/synchronization/', 'last_run', null, 'date of last run');
        $moduleConfigModifier->insert('/cron/task/servicing/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert('/cron/task/servicing/', 'interval', $servicingInterval, 'in seconds');
        $moduleConfigModifier->insert('/cron/task/servicing/', 'last_access', null, 'date of last access');
        $moduleConfigModifier->insert('/cron/task/servicing/', 'last_run', null, 'date of last run');
        $moduleConfigModifier->insert('/cron/task/health_status/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert('/cron/task/health_status/', 'interval', '3600', 'in seconds');
        $moduleConfigModifier->insert('/cron/task/health_status/', 'last_access', null, 'date of last access');
        $moduleConfigModifier->insert('/cron/task/health_status/', 'last_run', null, 'date of last run');
        $moduleConfigModifier->insert(
            '/cron/task/archive_orders_entities/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $moduleConfigModifier->insert('/cron/task/archive_orders_entities/', 'interval', '3600', 'in seconds');
        $moduleConfigModifier->insert(
            '/cron/task/archive_orders_entities/',
            'last_access',
            null,
            'date of last access'
        );
        $moduleConfigModifier->insert('/cron/task/archive_orders_entities/', 'last_run', null, 'date of last run');
        $moduleConfigModifier->insert('/cron/task/issues_resolver/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert('/cron/task/issues_resolver/', 'interval', '3600', 'in seconds');
        $moduleConfigModifier->insert('/cron/task/issues_resolver/', 'last_access', null, 'date of last access');
        $moduleConfigModifier->insert('/cron/task/issues_resolver/', 'last_run', null, 'date of last run');

        $moduleConfigModifier->insert('/cron/task/ebay/actions/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert('/cron/task/ebay/actions/', 'interval', '60', 'in seconds');
        $moduleConfigModifier->insert('/cron/task/ebay/actions/', 'last_access', null, 'date of last access');
        $moduleConfigModifier->insert('/cron/task/ebay/actions/', 'last_run', null, 'date of last run');
        $moduleConfigModifier->insert(
            '/cron/task/ebay/update_accounts_preferences/',
            'mode',
            1,
            '0 - disable,\r\n1 - enable'
        );
        $moduleConfigModifier->insert('/cron/task/ebay/update_accounts_preferences/', 'interval', 86400, 'in seconds');
        $moduleConfigModifier->insert(
            '/cron/task/ebay/update_accounts_preferences/',
            'last_run',
            null,
            'date of last run'
        );
    }

    private function migrateSynchronizationConfig()
    {
        $synchronizationConfigTableName = $this->getFullTableName('synchronization_config');
        $synchronizationConfigTable     = $this->getConnection()->newTable($synchronizationConfigTableName)
                                               ->addColumn(
                                                   'id',
                                                   Table::TYPE_INTEGER,
                                                   null,
                                                   ['unsigned'       => true,
                                                    'primary'        => true,
                                                    'nullable'       => false,
                                                    'auto_increment' => true
                                                   ]
                                               )
                                               ->addColumn(
                                                   'group',
                                                   Table::TYPE_TEXT,
                                                   255,
                                                   ['default' => null]
                                               )
                                               ->addColumn(
                                                   'key',
                                                   Table::TYPE_TEXT,
                                                   255,
                                                   ['nullable' => false]
                                               )
                                               ->addColumn(
                                                   'value',
                                                   Table::TYPE_TEXT,
                                                   255,
                                                   ['default' => null]
                                               )
                                               ->addColumn(
                                                   'notice',
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
                                               ->addIndex('group', 'group')
                                               ->addIndex('key', 'key')
                                               ->addIndex('value', 'value')
                                               ->setOption('type', 'INNODB')
                                               ->setOption('charset', 'utf8')
                                               ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($synchronizationConfigTable);

        $synchronizationConfigModifier = $this->getConfigModifier('synchronization');

        $synchronizationConfigModifier->insert(null, 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(null, 'last_access', null, null);
        $synchronizationConfigModifier->insert(null, 'last_run', null, null);
        $synchronizationConfigModifier->insert('/settings/product_change/', 'max_count_per_one_time', '500', null);
        $synchronizationConfigModifier->insert('/settings/product_change/', 'max_lifetime', '172800', 'in seconds');
        $synchronizationConfigModifier->insert('/global/', 'mode', '1', null);
        $synchronizationConfigModifier->insert('/global/magento_products/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/global/magento_products/deleted_products/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/global/magento_products/deleted_products/',
            'interval',
            '3600',
            'in seconds'
        );
        $synchronizationConfigModifier->insert(
            '/global/magento_products/deleted_products/',
            'last_time',
            null,
            'Last check time'
        );
        $synchronizationConfigModifier->insert(
            '/global/magento_products/added_products/',
            'last_magento_product_id',
            null,
            null
        );
        $synchronizationConfigModifier->insert(
            '/global/magento_products/inspector/',
            'mode',
            '0',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/global/magento_products/inspector/',
            'last_listing_product_id',
            null,
            null
        );
        $synchronizationConfigModifier->insert(
            '/global/magento_products/inspector/',
            'min_interval_between_circles',
            '3600',
            'in seconds'
        );
        $synchronizationConfigModifier->insert(
            '/global/magento_products/inspector/',
            'max_count_times_for_full_circle',
            '50',
            null
        );
        $synchronizationConfigModifier->insert(
            '/global/magento_products/inspector/',
            'min_count_items_per_one_time',
            '100',
            null
        );
        $synchronizationConfigModifier->insert(
            '/global/magento_products/inspector/',
            'max_count_items_per_one_time',
            '500',
            null
        );
        $synchronizationConfigModifier->insert(
            '/global/magento_products/inspector/',
            'last_time_start_circle',
            null,
            null
        );
        $synchronizationConfigModifier->insert('/global/processing/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert('/global/stop_queue/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert('/global/stop_queue/', 'interval', '3600', 'in seconds');
        $synchronizationConfigModifier->insert('/global/stop_queue/', 'last_time', null, 'Last check time');

        //- eBay
        $synchronizationConfigModifier->insert(
            '/ebay/templates/synchronization/list/immediately_not_checked/',
            'items_limit',
            '200',
            null
        );
        $synchronizationConfigModifier->insert(
            '/ebay/templates/synchronization/revise/total/',
            'items_limit',
            '200',
            null
        );
        $synchronizationConfigModifier->insert(
            '/ebay/templates/synchronization/revise/need_synch/',
            'items_limit',
            '200',
            null
        );
        $synchronizationConfigModifier->insert('/ebay/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert('/ebay/general/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/ebay/general/account_pickup_store/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/general/account_pickup_store/process/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/general/account_pickup_store/update/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/ebay/general/feedbacks/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/ebay/general/feedbacks/receive/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/ebay/general/feedbacks/receive/', 'interval', '10800', 'in seconds');
        $synchronizationConfigModifier->insert(
            '/ebay/general/feedbacks/receive/',
            'last_time',
            null,
            'date of last access'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/general/feedbacks/response/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/ebay/general/feedbacks/response/', 'interval', '10800', 'in seconds');
        $synchronizationConfigModifier->insert(
            '/ebay/general/feedbacks/response/',
            'last_time',
            null,
            'date of last access'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/general/feedbacks/response/',
            'attempt_interval',
            '86400',
            'in seconds'
        );
        $synchronizationConfigModifier->insert('/ebay/listings_products/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/ebay/listings_products/remove_duplicates/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/listings_products/update/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/ebay/marketplaces/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/ebay/marketplaces/categories/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/marketplaces/details/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/marketplaces/motors_epids/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/marketplaces/motors_ktypes/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/ebay/orders/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert('/ebay/orders/receive/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert('/ebay/orders/update/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/ebay/orders/cancellation/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/ebay/orders/cancellation/', 'interval', '86400', 'in seconds');
        $synchronizationConfigModifier->insert('/ebay/orders/cancellation/', 'last_time', null, 'date of last access');
        $synchronizationConfigModifier->insert('/ebay/orders/cancellation/', 'start_date', null, 'date of first run');
        $synchronizationConfigModifier->insert('/ebay/orders/reserve_cancellation/', 'mode', '1', 'in seconds');
        $synchronizationConfigModifier->insert('/ebay/orders/reserve_cancellation/', 'interval', '3600', 'in seconds');
        $synchronizationConfigModifier->insert(
            '/ebay/orders/reserve_cancellation/',
            'last_time',
            null,
            'Last check time'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/orders/create_failed/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/ebay/orders/create_failed/', 'interval', '300', 'in seconds');
        $synchronizationConfigModifier->insert('/ebay/orders/create_failed/', 'last_time', null, 'Last check time');
        $synchronizationConfigModifier->insert('/ebay/other_listings/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/ebay/other_listings/update/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/ebay/other_listings/update/', 'interval', '3600', 'in seconds');
        $synchronizationConfigModifier->insert('/ebay/other_listings/sku/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert('/ebay/templates/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/ebay/templates/synchronization/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/templates/synchronization/list/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/templates/synchronization/relist/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/templates/synchronization/revise/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/templates/synchronization/revise/total/',
            'last_listing_product_id',
            null,
            null
        );
        $synchronizationConfigModifier->insert(
            '/ebay/templates/synchronization/revise/total/',
            'start_date',
            null,
            null
        );
        $synchronizationConfigModifier->insert('/ebay/templates/synchronization/revise/total/', 'end_date', null, null);
        $synchronizationConfigModifier->insert(
            '/ebay/templates/synchronization/stop/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/templates/remove_unused/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/ebay/templates/remove_unused/', 'interval', '86400', 'in seconds');
        $synchronizationConfigModifier->insert('/ebay/templates/remove_unused/', 'last_time', null, 'Last check time');

        //- Amazon
        $synchronizationConfigModifier->insert(
            '/amazon/templates/synchronization/list/immediately_not_checked/',
            'items_limit',
            '200',
            null
        );
        $synchronizationConfigModifier->insert(
            '/amazon/templates/synchronization/revise/total/',
            'items_limit',
            '200',
            null
        );
        $synchronizationConfigModifier->insert(
            '/amazon/templates/synchronization/revise/need_synch/',
            'items_limit',
            '200',
            null
        );
        $synchronizationConfigModifier->insert('/amazon/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert('/amazon/general/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/amazon/general/run_parent_processors/',
            'interval',
            '300',
            'in seconds'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/general/run_parent_processors/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/general/run_parent_processors/',
            'last_time',
            null,
            'Last check time'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/listings_products/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/amazon/listings_products/update/', 'interval', '86400', 'in seconds');
        $synchronizationConfigModifier->insert(
            '/amazon/listings_products/update/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/listings_products/update/',
            'last_time',
            null,
            'Last check time'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/listings_products/update/defected/',
            'interval',
            '259200',
            'in seconds'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/listings_products/update/defected/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/listings_products/update/defected/',
            'last_time',
            null,
            'Last check time'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/listings_products/update/blocked/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/listings_products/update/blocked/',
            'interval',
            '3600',
            'in seconds'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/listings_products/update/blocked/',
            'last_time',
            null,
            'Last check time'
        );
        $synchronizationConfigModifier->insert('/amazon/marketplaces/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/amazon/marketplaces/categories/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/marketplaces/details/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/marketplaces/specifics/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/amazon/orders/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert('/amazon/orders/reserve_cancellation/', 'mode', '1', 'in seconds');
        $synchronizationConfigModifier->insert(
            '/amazon/orders/reserve_cancellation/',
            'interval',
            '3600',
            'in seconds'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/orders/reserve_cancellation/',
            'last_time',
            null,
            'Last check time'
        );
        $synchronizationConfigModifier->insert('/amazon/orders/update/', 'mode', '1', 'in seconds');
        $synchronizationConfigModifier->insert('/amazon/orders/update/', 'interval', 1800, 'in seconds');
        $synchronizationConfigModifier->insert(
            '/amazon/orders/receive_details/',
            'mode',
            0,
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/amazon/orders/receive_details/', 'interval', 7200, 'in seconds');
        $synchronizationConfigModifier->insert('/amazon/orders/receive_details/', 'last_time', null, 'Last check time');
        $synchronizationConfigModifier->insert(
            '/amazon/orders/create_failed/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/amazon/orders/create_failed/', 'interval', 300, 'in seconds');
        $synchronizationConfigModifier->insert('/amazon/orders/create_failed/', 'last_time', null, 'Last check time');
        $synchronizationConfigModifier->insert('/amazon/other_listings/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/amazon/other_listings/update/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/amazon/other_listings/update/', 'interval', '86400', 'in seconds');
        $synchronizationConfigModifier->insert('/amazon/other_listings/update/', 'last_time', null, 'Last check time');
        $synchronizationConfigModifier->insert(
            '/amazon/other_listings/title/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/other_listings/update/blocked/',
            'last_time',
            null,
            'Last check time'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/other_listings/update/blocked/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/other_listings/update/blocked/',
            'interval',
            '3600',
            'in seconds'
        );
        $synchronizationConfigModifier->insert('/amazon/templates/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/amazon/templates/repricing/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/templates/synchronization/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/templates/synchronization/list/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/templates/synchronization/relist/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/templates/synchronization/revise/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/templates/synchronization/revise/total/',
            'last_listing_product_id',
            null,
            null
        );
        $synchronizationConfigModifier->insert(
            '/amazon/templates/synchronization/revise/total/',
            'start_date',
            null,
            null
        );
        $synchronizationConfigModifier->insert(
            '/amazon/templates/synchronization/revise/total/',
            'end_date',
            null,
            null
        );
        $synchronizationConfigModifier->insert(
            '/amazon/templates/synchronization/stop/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );

        //- Walmart
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
    }

    private function migratePrimaryConfig()
    {
        $primaryConfigModifier = $this->getConfigModifier('primary');

        $primaryConfigModifier->getEntity('/server/', 'default_baseurl_index')->updateGroup('/server/location/');
        $primaryConfigModifier->getEntity('/server/location/', 'default_baseurl_index')->updateKey('default_index');

        $query = $this->getConnection()->select()
                      ->from($this->getFullTableName('primary_config'))
                      ->where("`group` = '/server/' AND (`key` LIKE 'baseurl_%' OR `key` LIKE 'hostname_%')");

        $result = $this->getConnection()->fetchAll($query);

        foreach ($result as $row) {
            $key   = (strpos($row['key'], 'baseurl') !== false) ? 'baseurl' : 'hostname';
            $index = str_replace($key . '_', '', $row['key']);
            $group = "/server/location/{$index}/";

            $primaryConfigModifier->getEntity('/server/', $row['key'])->updateGroup($group);
            $primaryConfigModifier->getEntity($group, $row['key'])->updateKey($key);
        }

        $primaryConfigModifier->delete('/modules/');

        $select = $this->getConnection()->select()->from($this->getFullTableName('primary_config'));
        $select->reset(\Zend_Db_Select::COLUMNS);
        $select->columns('group');
        $select->where('`group` like ?', '/M2ePro/%');

        $groupsForRenaming = $this->getConnection()->fetchCol($select);

        foreach (array_unique($groupsForRenaming) as $group) {
            $newGroup = preg_replace('/^\/M2ePro/', '', $group);
            $primaryConfigModifier->updateGroup($newGroup, ['`group` = ?' => $group]);
        }

        $primaryConfigModifier->getEntity('/server/', 'application_key')
                              ->updateValue('02edcc129b6128f5fa52d4ad1202b427996122b6');
    }

    private function migrateInStorePickupGlobalKey()
    {
        $select = $this->getConnection()->select()->from($this->getFullTableName('account'));
        $select->where('`component_mode` = ?', 'ebay');
        $select->where('`additional_data` like ?', '%"bopis":%');

        $pickupStoreAccounts = $this->getConnection()->fetchAll($select, [], \PDO::FETCH_ASSOC);

        $isPickupStoreEnabled = 0;
        foreach ($pickupStoreAccounts as $account) {
            $additionalData = json_decode($account['additional_data'], true);

            if (!$additionalData) {
                continue;
            }

            if (isset($additionalData['bopis']) && $additionalData['bopis']) {
                $isPickupStoreEnabled = 1;
                break;
            }
        }

        $this->getConfigModifier('module')->insert(
            '/ebay/in_store_pickup/',
            'mode',
            $isPickupStoreEnabled,
            '0 - disable,\r\n1 - enable'
        );
    }

    private function migrateProductCustomTypes()
    {
        $this->getConfigModifier('module')->insert(
            '/magento/product/simple_type/',
            'custom_types',
            '',
            'Magento product custom types'
        );
        $this->getConfigModifier('module')->insert(
            '/magento/product/downloadable_type/',
            'custom_types',
            '',
            'Magento product custom types'
        );
        $this->getConfigModifier('module')->insert(
            '/magento/product/configurable_type/',
            'custom_types',
            '',
            'Magento product custom types'
        );
        $this->getConfigModifier('module')->insert(
            '/magento/product/bundle_type/',
            'custom_types',
            '',
            'Magento product custom types'
        );
        $this->getConfigModifier('module')->insert(
            '/magento/product/grouped_type/',
            'custom_types',
            '',
            'Magento product custom types'
        );
    }

    private function migrateHealthStatus()
    {
        $this->getConfigModifier('module')->insert(
            '/cron/task/health_status/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $this->getConfigModifier('module')->insert(
            '/cron/task/health_status/',
            'interval',
            '3600',
            'in seconds'
        );
        $this->getConfigModifier('module')->insert(
            '/cron/task/health_status/',
            'last_access',
            null,
            'date of last access'
        );
        $this->getConfigModifier('module')->insert(
            '/cron/task/health_status/',
            'last_run',
            null,
            'date of last run'
        );

        $this->getConfigModifier('module')->insert('/health_status/notification/', 'mode', 1);
        $this->getConfigModifier('module')->insert('/health_status/notification/', 'email', '');
        $this->getConfigModifier('module')->insert('/health_status/notification/', 'level', 40);
    }

    private function migrateArchivedEntity()
    {
        $this->getConfigModifier('module')->insert(
            '/cron/task/archive_orders_entities/',
            'mode',
            '1',
            '0 - disable, \r\n1 - enable'
        );
        $this->getConfigModifier('module')->insert(
            '/cron/task/archive_orders_entities/',
            'interval',
            '3600',
            'in seconds'
        );
        $this->getConfigModifier('module')->insert(
            '/cron/task/archive_orders_entities/',
            'last_access',
            null,
            'date of last access'
        );
        $this->getConfigModifier('module')->insert(
            '/cron/task/archive_orders_entities/',
            'last_run',
            null,
            'date of last run'
        );
    }

    private function migrateProductVocabulary()
    {
        $vocabularyAttributeAutoActionValue = $this->getConfigModifier('module')->getEntity(
            '/product/variation/vocabulary/attribute/auto_action/',
            'enabled'
        )->getValue();

        if ($vocabularyAttributeAutoActionValue !== null) {
            $this->getConfigModifier('module')
                 ->insert('/amazon/vocabulary/attribute/auto_action/', 'enabled', $vocabularyAttributeAutoActionValue);
            $this->getConfigModifier('module')
                 ->insert('/walmart/vocabulary/attribute/auto_action/', 'enabled', $vocabularyAttributeAutoActionValue);
        }

        $vocabularyOptionAutoActionValue = $this->getConfigModifier('module')->getEntity(
            '/product/variation/vocabulary/option/auto_action/',
            'enabled'
        )->getValue();

        if ($vocabularyOptionAutoActionValue !== null) {
            $this->getConfigModifier('module')
                 ->insert('/amazon/vocabulary/option/auto_action/', 'enabled', $vocabularyOptionAutoActionValue);
            $this->getConfigModifier('module')
                 ->insert('/walmart/vocabulary/option/auto_action/', 'enabled', $vocabularyOptionAutoActionValue);
        }

        $select = $this->getConnection()
                       ->select()
                       ->from(
                           $this->getFullTableName('registry'),
                           ['key', 'value']
                       )
                       ->where('`key` = ?', '/product/variation/vocabulary/server/')
                       ->orWhere('`key` = ?', '/product/variation/vocabulary/local/');

        $rows = $this->getConnection()->fetchAssoc($select);

        foreach ($rows as $row) {
            $suffix = strpos($row['key'], 'server') !== false ? 'server' : 'local';
            $this->getConnection()->insert(
                $this->getFullTableName('registry'),
                [
                    'key'   => 'amazon_vocabulary_' . $suffix,
                    'value' => $row['value']
                ]
            );

            $this->getConnection()->insert(
                $this->getFullTableName('registry'),
                [
                    'key'   => 'walmart_vocabulary_' . $suffix,
                    'value' => $row['value']
                ]
            );
        }

        $this->getConnection()->delete(
            $this->getFullTableName('registry'),
            "`key` LIKE '%/product/variation/vocabulary/%'"
        );
    }

    private function migrateAccounts()
    {
        $this->getTableModifier('amazon_account')
             ->addColumn(
                 'other_listings_move_mode',
                 'SMALLINT(5) UNSIGNED NOT NULL',
                 '0',
                 'other_listings_mapping_settings'
             )
             ->addColumn('other_listings_move_settings', 'VARCHAR(255)', 'NULL', 'other_listings_move_mode');

        $this->getTableModifier('ebay_account')
             ->dropColumn('sell_api_token_session', true, false)
             ->dropColumn('sell_api_token_expired_date', true, false)
             ->dropColumn('rate_tables', true, false)
             ->commit();

        $this->getTableModifier('walmart_account')
             ->addColumn('old_private_key', 'TEXT', 'NULL', 'consumer_id', false, false)
             ->changeColumn('client_id', 'varchar(255)', 'NULL', null, false)
             ->commit();
    }

    private function migrateProcessing()
    {
        $this->getTableModifier('processing')->dropColumn('type');

        $this->getConnection()->dropTable($this->getFullTableName('amazon_listing_product_action_processing'));
        $this->getConnection()->dropTable($this->getFullTableName('amazon_listing_product_action_processing_list_sku'));
        $this->getConnection()->dropTable($this->getFullTableName('amazon_order_action_processing'));
        $this->getConnection()->dropTable($this->getFullTableName('ebay_listing_product_action_processing'));
        $this->getConnection()->dropTable($this->getFullTableName('walmart_listing_product_action_processing'));
        $this->getConnection()->dropTable($this->getFullTableName('walmart_listing_product_action_processing_list'));

        $this->getConnection()->renameTable(
            $this->getFullTableName('connector_command_pending_processing_partial'),
            $this->getFullTableName('connector_pending_requester_partial')
        );
        $this->getConnection()->renameTable(
            $this->getFullTableName('connector_command_pending_processing_single'),
            $this->getFullTableName('connector_pending_requester_single')
        );

        $amazonProcessingActionTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_processing_action')
        )
                                            ->addColumn(
                                                'id',
                                                Table::TYPE_INTEGER,
                                                null,
                                                ['unsigned'       => true,
                                                 'primary'        => true,
                                                 'nullable'       => false,
                                                 'auto_increment' => true
                                                ]
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
                                                self::LONG_COLUMN_SIZE,
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
        $this->getConnection()->createTable($amazonProcessingActionTable);

        $amazonProcessingActionListSku = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_processing_action_list_sku')
        )
                                              ->addColumn(
                                                  'id',
                                                  Table::TYPE_INTEGER,
                                                  null,
                                                  ['unsigned'       => true,
                                                   'primary'        => true,
                                                   'nullable'       => false,
                                                   'auto_increment' => true
                                                  ]
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
                                                  ['type' => DbAdapter::INDEX_TYPE_UNIQUE]
                                              )
                                              ->setOption('type', 'INNODB')
                                              ->setOption('charset', 'utf8')
                                              ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonProcessingActionListSku);

        $ebayProcessingActionTable = $this->getConnection()->newTable($this->getFullTableName('ebay_processing_action'))
                                          ->addColumn(
                                              'id',
                                              Table::TYPE_INTEGER,
                                              null,
                                              ['unsigned'       => true,
                                               'primary'        => true,
                                               'nullable'       => false,
                                               'auto_increment' => true
                                              ]
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
                                              'processing_id',
                                              Table::TYPE_INTEGER,
                                              null,
                                              ['unsigned' => true, 'nullable' => false]
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
                                              'priority',
                                              Table::TYPE_INTEGER,
                                              null,
                                              ['unsigned' => true, 'nullable' => false, 'default' => 0]
                                          )
                                          ->addColumn(
                                              'request_timeout',
                                              Table::TYPE_INTEGER,
                                              null,
                                              ['unsigned' => true, 'default' => null]
                                          )
                                          ->addColumn(
                                              'request_data',
                                              Table::TYPE_TEXT,
                                              self::LONG_COLUMN_SIZE,
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
                                          ->addIndex('marketplace_id', 'marketplace_id')
                                          ->addIndex('processing_id', 'processing_id')
                                          ->addIndex('type', 'type')
                                          ->addIndex('priority', 'priority')
                                          ->addIndex('start_date', 'start_date')
                                          ->setOption('type', 'INNODB')
                                          ->setOption('charset', 'utf8')
                                          ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayProcessingActionTable);

        $walmartProcessingActionTable = $this->getConnection()->newTable(
            $this->getFullTableName('walmart_processing_action')
        )
                                             ->addColumn(
                                                 'id',
                                                 Table::TYPE_INTEGER,
                                                 null,
                                                 ['unsigned'       => true,
                                                  'primary'        => true,
                                                  'nullable'       => false,
                                                  'auto_increment' => true
                                                 ]
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
                                                 self::LONG_COLUMN_SIZE,
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
                [
                    'unsigned'       => true,
                    'primary'        => true,
                    'nullable'       => false,
                    'auto_increment' => true
                ]
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
    }

    private function migrateStopQueue()
    {
        $this->getTableModifier('stop_queue')
             ->addColumn('item_data', 'text NOT NULL', null, 'id', false, false)
             ->addColumn('account_hash', 'VARCHAR(255) NOT NULL', null, 'item_data', true, false)
             ->addColumn('marketplace_id', 'int(10) UNSIGNED', 'NULL', 'account_hash', true, false)
             ->dropColumn('additional_data', true, false)
             ->commit();
    }

    private function migrateOrders()
    {
        $this->getTableModifier('amazon_order')->dropColumn('seller_order_id');
        $this->getConnection()->dropTable($this->getFullTableName('order_note'));

        $this->getTableModifier('amazon_order_item')
             ->changeColumn('gift_message', 'TEXT');

        $this->getTableModifier('ebay_order')
             ->changeColumn('buyer_message', 'TEXT', null, null, false)
             ->changeColumn('saved_amount', 'decimal(12,4) NOT NULL', '0.0000', null, false)
             ->commit();

        $this->getTableModifier('walmart_order_item')
             ->changeColumn('merged_walmart_order_item_ids', 'TEXT');
    }

    private function migrateLogs()
    {
        $this->getTableModifier('listing_log')
             ->addColumn('account_id', 'INT(10) UNSIGNED NOT NULL', null, 'id', true, false)
             ->addColumn('marketplace_id', 'INT(10) UNSIGNED NOT NULL', null, 'account_id', true, false)
             ->changeColumn('listing_id', 'INT(10) UNSIGNED NOT NULL', null, null, false)
             ->changeColumn('action_id', 'INT(10) UNSIGNED NOT NULL', null, null, false)
             ->commit();

        $this->getTableModifier('listing_other_log')
             ->addColumn('account_id', 'INT(10) UNSIGNED NOT NULL', null, 'id', true, false)
             ->addColumn('marketplace_id', 'INT(10) UNSIGNED NOT NULL', null, 'account_id', true, false)
             ->changeColumn('listing_other_id', 'INT(10) UNSIGNED NOT NULL', null, null, false)
             ->changeColumn('action_id', 'INT(10) UNSIGNED NOT NULL', null, null, false)
             ->commit();

        $this->getTableModifier('order_log')
             ->addColumn('account_id', 'INT(10) UNSIGNED NOT NULL', null, 'id', true, false)
             ->addColumn('marketplace_id', 'INT(10) UNSIGNED NOT NULL', null, 'account_id', true, false)
             ->changeColumn('order_id', 'INT(10) UNSIGNED NOT NULL', null, null, false)
             ->commit();

        $this->getTableModifier('ebay_account_pickup_store_log')
             ->changeColumn('action_id', 'INT(10) UNSIGNED NOT NULL');

        $this->getTableModifier('listing_log')->addIndex('create_date');
        $this->getTableModifier('listing_other_log')->addIndex('create_date');
        $this->getTableModifier('order_log')->addIndex('create_date');
        $this->getTableModifier('synchronization_log')->addIndex('create_date');
        $this->getTableModifier('ebay_account_pickup_store_log')->addIndex('create_date');

        $this->getConfigModifier('module')->insert(
            '/logs/view/grouped/',
            'max_last_handled_records_count',
            100000
        );

        $this->getConnection()->update(
            $this->getFullTableName('module_config'),
            ['value' => 90],
            new \Zend_Db_Expr('`group` LIKE "/logs/clearing/%" AND `key` = "days" AND `value` > 90')
        );
    }

    private function migrateWizards()
    {
        $this->getConnection()->truncateTable($this->getFullTableName('wizard'));

        $wizardsData = [
            [
                'nick'     => 'migrationFromMagento1',
                'view'     => '*',
                'status'   => 0,
                'step'     => null,
                'type'     => 1,
                'priority' => 1,
            ],
            [
                'nick'     => 'installationEbay',
                'view'     => 'ebay',
                'status'   => empty($this->getTableRows($this->getFullTableName('ebay_account')))
                    ? 0 : 2,
                'step'     => null,
                'type'     => 1,
                'priority' => 2,
            ],
            [
                'nick'     => 'installationAmazon',
                'view'     => 'amazon',
                'status'   => empty($this->getTableRows($this->getFullTableName('amazon_account')))
                    ? 0 : 2,
                'step'     => null,
                'type'     => 1,
                'priority' => 3,
            ],
            [
                'nick'     => 'installationWalmart',
                'view'     => 'walmart',
                'status'   => empty($this->getTableRows($this->getFullTableName('walmart_account')))
                    ? 0 : 2,
                'step'     => null,
                'type'     => 1,
                'priority' => 4,
            ]
        ];

        $this->getConnection()->insertMultiple($this->getFullTableName('wizard'), $wizardsData);
    }

    private function migrateGridsPerformanceStructure()
    {
        $this->getConnection()->renameTable(
            $this->getFullTableName('ebay_indexer_listing_product_parent'),
            $this->getFullTableName('ebay_indexer_listing_product_variation_parent')
        );
        $this->getTableModifier('ebay_indexer_listing_product_variation_parent')
             ->addColumn('component_mode', 'VARCHAR(10)', 'NULL', 'listing_id', true);

        $this->getConnection()->renameTable(
            $this->getFullTableName('amazon_indexer_listing_product_parent'),
            $this->getFullTableName('amazon_indexer_listing_product_variation_parent')
        );
        $this->getTableModifier('amazon_indexer_listing_product_variation_parent')
             ->addColumn('component_mode', 'VARCHAR(10)', 'NULL', 'listing_id', true);

        $this->getConnection()->renameTable(
            $this->getFullTableName('walmart_indexer_listing_product_parent'),
            $this->getFullTableName('walmart_indexer_listing_product_variation_parent')
        );
        $this->getTableModifier('walmart_indexer_listing_product_variation_parent')
             ->addColumn('component_mode', 'VARCHAR(10)', 'NULL', 'listing_id', true, false)
             ->changeColumn('min_price', 'DECIMAL(12,4) UNSIGNED', 'NULL', null, false)
             ->changeColumn('max_price', 'DECIMAL(12,4) UNSIGNED', 'NULL', null, false)
             ->changeColumn('create_date', 'DATETIME', 'NULL', null, false)
             ->commit();
    }

    private function migrateSynchronizationTemplate()
    {
        $this->getTableModifier('template_synchronization')
             ->addColumn('revise_change_listing', 'SMALLINT(4) UNSIGNED NOT NULL', null, 'title', true, false)
             ->addColumn(
                 'revise_change_selling_format_template',
                 'SMALLINT(4) UNSIGNED NOT NULL',
                 null,
                 'revise_change_listing',
                 true,
                 false
             )
             ->commit();

        $this->getTableModifier('amazon_template_synchronization')
             ->addColumn(
                 'list_advanced_rules_mode',
                 'SMALLINT(4) UNSIGNED NOT NULL',
                 null,
                 'list_qty_calculated_value_max',
                 false,
                 false
             )
             ->addColumn('list_advanced_rules_filters', 'TEXT', null, 'list_advanced_rules_mode', false, false)
             ->addColumn(
                 'revise_update_details',
                 'SMALLINT(4) UNSIGNED NOT NULL',
                 null,
                 'revise_update_price_max_allowed_deviation',
                 false,
                 false
             )
             ->addColumn(
                 'revise_update_images',
                 'SMALLINT(4) UNSIGNED NOT NULL',
                 null,
                 'revise_update_details',
                 false,
                 false
             )
             ->addColumn(
                 'revise_change_description_template',
                 'SMALLINT(4) UNSIGNED NOT NULL',
                 null,
                 'revise_update_images',
                 false,
                 false
             )
             ->addColumn(
                 'revise_change_shipping_template',
                 'SMALLINT(4) UNSIGNED NOT NULL',
                 null,
                 'revise_change_description_template',
                 false,
                 false
             )
             ->addColumn(
                 'revise_change_product_tax_code_template',
                 'SMALLINT(4) UNSIGNED NOT NULL',
                 null,
                 'revise_change_shipping_template',
                 false,
                 false
             )
             ->addColumn(
                 'relist_send_data',
                 'SMALLINT(4) UNSIGNED NOT NULL',
                 null,
                 'relist_filter_user_lock',
                 false,
                 false
             )
             ->addColumn(
                 'relist_advanced_rules_mode',
                 'SMALLINT(4) UNSIGNED NOT NULL',
                 null,
                 'relist_qty_calculated_value_max',
                 false,
                 false
             )
             ->addColumn('relist_advanced_rules_filters', 'TEXT', null, 'relist_advanced_rules_mode', false, false)
             ->addColumn(
                 'stop_advanced_rules_mode',
                 'SMALLINT(4) UNSIGNED NOT NULL',
                 null,
                 'stop_qty_calculated_value_max',
                 false,
                 false
             )
             ->addColumn('stop_advanced_rules_filters', 'TEXT', null, 'stop_advanced_rules_mode', false, false)
             ->dropColumn('stop_mode', true, false)
             ->commit();

        $this->getTableModifier('ebay_template_synchronization')
             ->renameColumn(
                 'revise_update_categories',
                 'revise_change_category_template',
                 true,
                 false
             )
             ->renameColumn(
                 'revise_update_payment',
                 'revise_change_payment_template',
                 true,
                 false
             )
             ->renameColumn(
                 'revise_update_return',
                 'revise_change_return_policy_template',
                 true,
                 false
             )
             ->renameColumn(
                 'revise_update_shipping',
                 'revise_change_shipping_template',
                 true,
                 false
             )->commit();

        $this->getTableModifier('ebay_template_synchronization')
             ->addColumn(
                 'list_advanced_rules_mode',
                 'SMALLINT(4) UNSIGNED NOT NULL',
                 null,
                 'list_qty_calculated_value_max',
                 false,
                 false
             )
             ->addColumn('list_advanced_rules_filters', 'TEXT', null, 'list_advanced_rules_mode', false, false)
             ->addColumn(
                 'revise_update_specifics',
                 'SMALLINT(4) UNSIGNED NOT NULL',
                 null,
                 'revise_update_images',
                 false,
                 false
             )
             ->addColumn(
                 'revise_update_shipping_services',
                 'SMALLINT(4) UNSIGNED NOT NULL',
                 null,
                 'revise_update_specifics',
                 false,
                 false
             )
             ->addColumn(
                 'revise_change_description_template',
                 'SMALLINT(4) UNSIGNED NOT NULL',
                 null,
                 'revise_change_shipping_template',
                 false,
                 false
             )
             ->dropColumn('revise_update_other', true, false)
             ->addColumn(
                 'relist_advanced_rules_mode',
                 'SMALLINT(4) UNSIGNED NOT NULL',
                 null,
                 'relist_qty_calculated_value_max',
                 false,
                 false
             )
             ->addColumn(
                 'relist_send_data',
                 'SMALLINT(4) UNSIGNED NOT NULL',
                 null,
                 'relist_filter_user_lock',
                 false,
                 false
             )
             ->addColumn('relist_advanced_rules_filters', 'TEXT', null, 'relist_advanced_rules_mode', false, false)
             ->addColumn(
                 'stop_advanced_rules_mode',
                 'SMALLINT(4) UNSIGNED NOT NULL',
                 null,
                 'stop_qty_calculated_value_max',
                 false,
                 false
             )
             ->addColumn('stop_advanced_rules_filters', 'TEXT', null, 'stop_advanced_rules_mode', false, false)
             ->dropColumn('stop_mode', true, false)
             ->commit();
    }

    private function migrateMarketplaces()
    {
        $this->getConnection()->update(
            $this->getFullTableName('marketplace'),
            ['group_title' => 'Asia / Pacific'],
            ['id IN(?)' => [4, 35]]
        );

        $this->getTableModifier('ebay_marketplace')
             ->addColumn('is_holiday_return', 'SMALLINT(4) UNSIGNED NOT NULL', '0', 'is_in_store_pickup', true)
             ->dropColumn('is_return_description');

        $this->getTableModifier('amazon_marketplace')
             ->addIndex('is_automatic_token_retrieving_available');

        $this->getConnection()->update(
            $this->getFullTableName('amazon_marketplace'),
            [
                'is_business_available'                => 1,
                'is_product_tax_code_policy_available' => 1
            ],
            ['marketplace_id IN(?)' => [26, 30, 31]]
        );

        $this->getConnection()->update(
            $this->getFullTableName('amazon_marketplace'),
            ['is_new_asin_available' => 1],
            ['marketplace_id = ?' => [34]]
        );

        $this->getConnection()->update(
            $this->getFullTableName('amazon_marketplace'),
            ['is_automatic_token_retrieving_available' => 0],
            ['marketplace_id = ?' => [35]]
        );

        $this->getConnection()->delete(
            $this->getFullTableName('marketplace'),
            ['id IN(?)' => [27, 32, 36]] // Japan, China, India
        );

        $this->getConnection()->delete(
            $this->getFullTableName('amazon_marketplace'),
            ['marketplace_id IN(?)' => [27, 32, 36]] // Japan, China, India
        );
    }

    private function migrateListingProduct()
    {
        $this->getTableModifier('listing_product')
             ->addColumn('need_synch_rules_check', 'SMALLINT(5) UNSIGNED NOT NULL', '0', 'additional_data', true, false)
             ->addColumn('tried_to_list', 'SMALLINT(5) UNSIGNED NOT NULL', '0', 'need_synch_rules_check', true, false)
             ->addColumn('synch_status', 'SMALLINT(5) unsigned NOT NULL', '0', 'tried_to_list', true, false)
             ->addColumn('synch_reasons', 'TEXT', 'NULL', 'synch_status', false, false)
             ->commit();

        $this->getTableModifier('amazon_listing_product')
             ->dropColumn('online_handling_time', true, false)
             ->dropColumn('online_restock_date', true, false)
             ->dropColumn('online_details_data', true, false)
             ->dropColumn('online_images_data', true, false)
             ->dropColumn('is_details_data_changed', true, false)
             ->dropColumn('is_images_data_changed', true, false)
             ->commit();

        $this->getTableModifier('ebay_listing_product')
             ->renameColumn('online_main_category', 'online_category');

        $this->getTableModifier('ebay_listing_product')
             ->dropColumn('online_sub_title', true, false)
             ->dropColumn('online_description', true, false)
             ->dropColumn('online_images', true, false)
             ->dropColumn('online_categories_data', true, false)
             ->dropColumn('online_shipping_data', true, false)
             ->dropColumn('online_payment_data', true, false)
             ->dropColumn('online_return_data', true, false)
             ->dropColumn('online_other_data', true, false)
             ->commit();
    }

    private function migrateAmazonShippingOverride()
    {
        $this->getTableModifier('amazon_account')
             ->addColumn('shipping_mode', 'INT(10) UNSIGNED', 1, 'related_store_id');

        $this->getConnection()->renameTable(
            $this->getFullTableName('amazon_template_shipping'),
            $this->getFullTableName('amazon_template_shipping_template')
        );

        $this->getTableModifier('amazon_listing_product')
             ->renameColumn('template_shipping_id', 'template_shipping_template_id')
             ->addColumn(
                 'template_shipping_override_id',
                 'INT(10) UNSIGNED',
                 'NULL',
                 'template_shipping_template_id',
                 true
             );

        $amazonDictionaryShippingOverride = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_dictionary_shipping_override')
        )
                                                 ->addColumn(
                                                     'id',
                                                     Table::TYPE_INTEGER,
                                                     null,
                                                     ['unsigned'       => true,
                                                      'primary'        => true,
                                                      'nullable'       => false,
                                                      'auto_increment' => true
                                                     ]
                                                 )
                                                 ->addColumn(
                                                     'marketplace_id',
                                                     Table::TYPE_INTEGER,
                                                     null,
                                                     ['unsigned' => true, 'nullable' => false]
                                                 )
                                                 ->addColumn(
                                                     'service',
                                                     Table::TYPE_TEXT,
                                                     255,
                                                     ['nullable' => false]
                                                 )
                                                 ->addColumn(
                                                     'location',
                                                     Table::TYPE_TEXT,
                                                     255,
                                                     ['nullable' => false]
                                                 )
                                                 ->addColumn(
                                                     'option',
                                                     Table::TYPE_TEXT,
                                                     255,
                                                     ['nullable' => false]
                                                 )
                                                 ->addIndex('marketplace_id', 'marketplace_id')
                                                 ->setOption('type', 'MYISAM')
                                                 ->setOption('charset', 'utf8')
                                                 ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonDictionaryShippingOverride);

        $amazonTemplateShippingOverrideTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_template_shipping_override')
        )
                                                    ->addColumn(
                                                        'id',
                                                        Table::TYPE_INTEGER,
                                                        null,
                                                        ['unsigned'       => true,
                                                         'primary'        => true,
                                                         'nullable'       => false,
                                                         'auto_increment' => true
                                                        ]
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
                                                    ->addIndex('title', 'title')
                                                    ->addIndex('marketplace_id', 'marketplace_id')
                                                    ->setOption('type', 'INNODB')
                                                    ->setOption('charset', 'utf8')
                                                    ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonTemplateShippingOverrideTable);

        $amazonTemplateShippingOverrideServiceTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_template_shipping_override_service')
        )
                                                           ->addColumn(
                                                               'id',
                                                               Table::TYPE_INTEGER,
                                                               null,
                                                               ['unsigned'       => true,
                                                                'primary'        => true,
                                                                'nullable'       => false,
                                                                'auto_increment' => true
                                                               ]
                                                           )
                                                           ->addColumn(
                                                               'template_shipping_override_id',
                                                               Table::TYPE_INTEGER,
                                                               null,
                                                               ['unsigned' => true, 'nullable' => false]
                                                           )
                                                           ->addColumn(
                                                               'service',
                                                               Table::TYPE_TEXT,
                                                               255,
                                                               ['nullable' => false]
                                                           )
                                                           ->addColumn(
                                                               'location',
                                                               Table::TYPE_TEXT,
                                                               255,
                                                               ['nullable' => false]
                                                           )
                                                           ->addColumn(
                                                               'option',
                                                               Table::TYPE_TEXT,
                                                               255,
                                                               ['nullable' => false]
                                                           )
                                                           ->addColumn(
                                                               'type',
                                                               Table::TYPE_SMALLINT,
                                                               null,
                                                               ['unsigned' => true, 'nullable' => false, 'default' => 0]
                                                           )
                                                           ->addColumn(
                                                               'cost_mode',
                                                               Table::TYPE_SMALLINT,
                                                               null,
                                                               ['unsigned' => true, 'nullable' => false, 'default' => 0]
                                                           )
                                                           ->addColumn(
                                                               'cost_value',
                                                               Table::TYPE_TEXT,
                                                               255,
                                                               ['nullable' => false]
                                                           )
                                                           ->addIndex(
                                                               'template_shipping_override_id',
                                                               'template_shipping_override_id'
                                                           )
                                                           ->setOption('type', 'INNODB')
                                                           ->setOption('charset', 'utf8')
                                                           ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonTemplateShippingOverrideServiceTable);
    }

    private function migrateAmazonListingProductRepricing()
    {
        $this->getTableModifier('amazon_listing_product_repricing')
             ->dropColumn('is_online_inactive', true, false)
             ->dropColumn('last_updated_regular_price', true, false)
             ->dropColumn('last_updated_min_price', true, false)
             ->dropColumn('last_updated_max_price', true, false)
             ->dropColumn('last_updated_is_disabled', true, false)
             ->commit();

        $listingProduct       = $this->getFullTableName('listing_product');
        $amazonListingProduct = $this->getFullTableName('amazon_listing_product');

        $this->getConnection()->exec(<<<SQL
UPDATE {$listingProduct} mlp
  JOIN {$amazonListingProduct} malp
    ON mlp.id = malp.listing_product_id
  SET mlp.additional_data = REPLACE(`additional_data`, 'repricing_not_managed_count', 'repricing_disabled_count')
  WHERE malp.is_repricing = 1;
SQL
        );

        $this->getConnection()->exec(<<<SQL
UPDATE {$listingProduct} mlp
  JOIN {$amazonListingProduct} malp
    ON mlp.id = malp.listing_product_id
  SET mlp.additional_data = REPLACE(`additional_data`, 'repricing_managed_count', 'repricing_enabled_count')
  WHERE malp.is_repricing = 1;
SQL
        );
    }

    private function migrationAmazonListingOther()
    {
        $this->getTableModifier('amazon_listing_other')->dropColumn('is_repricing_inactive');
    }

    private function migrateEbayReturnTemplate()
    {
        $this->getConnection()->renameTable(
            $this->getFullTableName('ebay_template_return'),
            $this->getFullTableName('ebay_template_return_policy')
        );

        $this->getTableModifier('ebay_template_return_policy')
             ->addColumn('holiday_mode', 'SMALLINT(4) UNSIGNED NOT NULL', '0', 'within', false, false)
             ->addColumn('restocking_fee', 'VARCHAR(255) NOT NULL', null, 'shipping_cost', false, false)
             ->dropColumn('international_accepted', true, false)
             ->dropColumn('international_option', true, false)
             ->dropColumn('international_within', true, false)
             ->dropColumn('international_shipping_cost', true, false)
             ->commit();

        // ---------------------------------------

        $ebayListingTableModifier = $this->getTableModifier('ebay_listing');

        $ebayListingTableModifier->renameColumn(
            'template_return_mode',
            'template_return_policy_mode',
            true,
            false
        );
        $ebayListingTableModifier->renameColumn(
            'template_return_id',
            'template_return_policy_id',
            true,
            false
        );
        $ebayListingTableModifier->renameColumn(
            'template_return_custom_id',
            'template_return_policy_custom_id',
            true,
            false
        );
        $ebayListingTableModifier->commit();

        // ---------------------------------------

        $ebayListingProductTableModifier = $this->getTableModifier('ebay_listing_product');

        $ebayListingProductTableModifier->renameColumn(
            'template_return_mode',
            'template_return_policy_mode',
            true,
            false
        );
        $ebayListingProductTableModifier->renameColumn(
            'template_return_id',
            'template_return_policy_id',
            true,
            false
        );
        $ebayListingProductTableModifier->renameColumn(
            'template_return_custom_id',
            'template_return_policy_custom_id',
            true,
            false
        );
        $ebayListingProductTableModifier->commit();
    }

    private function migrateEbayTemplateShipping()
    {
        $this->getTableModifier('ebay_template_shipping')
             ->renameColumn('dispatch_time_value', 'dispatch_time', true, false)
             ->renameColumn('local_shipping_discount_promotional_mode', 'local_shipping_discount_mode', true, false)
             ->renameColumn(
                 'local_shipping_discount_combined_profile_id',
                 'local_shipping_discount_profile_id',
                 true,
                 false
             )
             ->renameColumn(
                 'international_shipping_discount_promotional_mode',
                 'international_shipping_discount_mode',
                 true,
                 false
             )
             ->renameColumn(
                 'international_shipping_discount_combined_profile_id',
                 'international_shipping_discount_profile_id',
                 true,
                 false
             )
             ->commit();

        $this->getTableModifier('ebay_template_shipping')
             ->addColumn(
                 'local_shipping_rate_table_mode',
                 'SMALLINT(5) unsigned NOT NULL',
                 '0',
                 'dispatch_time',
                 false,
                 false
             )
             ->addColumn(
                 'international_shipping_rate_table_mode',
                 'SMALLINT(5) unsigned NOT NULL',
                 '0',
                 'local_shipping_rate_table_mode',
                 false,
                 false
             )
             ->commit();

        $templateTable = $this->getFullTableName('ebay_template_shipping');
        $select        = $this->getConnection()->select()->from(
            $templateTable,
            ['id', 'local_shipping_rate_table', 'international_shipping_rate_table']
        );

        $rows = $this->getConnection()->fetchAssoc($select);

        $rateTableMode = [
            'local'         => [
                'enabled'  => [],
                'disabled' => []
            ],
            'international' => [
                'enabled'  => [],
                'disabled' => []
            ]
        ];
        foreach ($rows as $row) {
            foreach (['local', 'international'] as $loc) {
                $shippingRateTable = json_decode($row[$loc . '_shipping_rate_table'], true);

                if (!empty($shippingRateTable)) {
                    $acceptMode = array_filter($shippingRateTable, function ($item) {
                        return isset($item['mode']) && $item['mode'] == 1 &&
                               isset($item['value']) && $item['value'] == 1;
                    });

                    if (count($acceptMode) >= round(count($shippingRateTable) / 2)) {
                        $rateTableMode[$loc]['enabled'][] = $row['id'];
                        continue;
                    }
                }

                $rateTableMode[$loc]['disabled'][] = $row['id'];
            }
        }

        foreach ($rateTableMode as $loc => $data) {
            if (!empty($data['enabled'])) {
                $this->getConnection()->update(
                    $templateTable,
                    [$loc . '_shipping_rate_table_mode' => 1],
                    ['id IN (?)' => implode(',', $data['enabled'])]
                );
            }

            if (!empty($data['disabled'])) {
                $this->getConnection()->update(
                    $templateTable,
                    [$loc . '_shipping_rate_table_mode' => 0],
                    ['id IN (?)' => implode(',', $data['enabled'])]
                );
            }
        }

        $this->getTableModifier('ebay_template_shipping')
             ->dropColumn('dispatch_time_mode', true, false)
             ->dropColumn('dispatch_time_attribute', true, false)
             ->dropColumn('local_shipping_rate_table', true, false)
             ->dropColumn('international_shipping_rate_table', true, false)
             ->commit();
    }

    private function migrateEbayCharity()
    {
        $this->getTableModifier('ebay_template_selling_format')
             ->changeColumn(
                 'charity',
                 'TEXT',
                 null,
                 'best_offer_reject_attribute',
                 true
             );

        $this->getConnection()->update(
            $this->getFullTableName('ebay_template_selling_format'),
            ['charity' => null],
            '`charity` = "" OR `charity` = "[]" OR `charity` = "{}"'
        );

        $select = $this->getConnection()->select()->from(
            ['etsf' => $this->getFullTableName('ebay_template_selling_format')]
        );
        $select->where('`etsf`.`is_custom_template` = ?', 1);
        $select->where('`etsf`.`charity` IS NOT NULL');
        $select->group('template_selling_format_id');

        // Joining Listings and Products with template mode Custom
        $select->joinLeft(
            ['el' => $this->getFullTableName('ebay_listing')],
            '`etsf`.`template_selling_format_id`=`el`.`template_selling_format_custom_id` AND
                `el`.`template_selling_format_mode` = 1',
            ['listing_id']
        );

        $select->joinLeft(
            ['elp' => $this->getFullTableName('ebay_listing_product')],
            '`etsf`.`template_selling_format_id`=`elp`.`template_selling_format_custom_id` AND
                `elp`.`template_selling_format_mode` = 1',
            ['listing_product_id']
        );

        $select->where('`el`.`listing_id` IS NOT NULL OR `elp`.`listing_product_id` IS NOT NULL ');

        $select->joinLeft(
            ['l' => $this->getFullTableName('listing')],
            '`el`.`listing_id`=`l`.`id`',
            []
        );

        $select->joinLeft(
            ['lp' => $this->getFullTableName('listing_product')],
            '`elp`.`listing_product_id`=`lp`.`id`',
            []
        );

        $select->joinLeft(
            ['lpl' => $this->getFullTableName('listing')],
            '`lp`.`listing_id`=`lpl`.`id`',
            []
        );

        $select->columns([
            'marketplace_id' => 'IF(
                `el`.`listing_id` IS NOT NULL,
                `l`.`marketplace_id`,
                IF(
                    `elp`.`listing_product_id` IS NOT NULL,
                    `lpl`.`marketplace_id`,
                    NULL
                )
            )'
        ]);

        $sellingFormatTemplates = $this->getConnection()->fetchAll($select, [], \PDO::FETCH_ASSOC);

        $resetCharityConditions = [];
        if (!empty($sellingFormatTemplates)) {
            $resetCharityConditions[] = $this->getConnection()->quoteInto(
                '`template_selling_format_id` NOT IN (?)',
                array_column($sellingFormatTemplates, 'template_selling_format_id')
            );
        }

        $resetCharityConditions[] = '`charity` IS NOT NULL';

        $this->getConnection()->update(
            $this->getFullTableName('ebay_template_selling_format'),
            ['charity' => null],
            $resetCharityConditions
        );

        if (empty($sellingFormatTemplates)) {
            return;
        }

        foreach ($sellingFormatTemplates as $sellingFormatTemplate) {
            $oldCharity = json_decode($sellingFormatTemplate['charity'], true);

            if (!empty($oldCharity[$sellingFormatTemplate['marketplace_id']])) {
                continue;
            }

            $newCharity                                           = [];
            $newCharity[$sellingFormatTemplate['marketplace_id']] = [
                'marketplace_id'      => $sellingFormatTemplate['marketplace_id'],
                'organization_id'     => $oldCharity['id'],
                'organization_name'   => $oldCharity['name'],
                'organization_custom' => 1,
                'percentage'          => $oldCharity['percentage'],
            ];

            $this->getConnection()->update(
                $this->getFullTableName('ebay_template_selling_format'),
                ['charity' => json_encode($newCharity)],
                $this->getConnection()->quoteInto(
                    '`template_selling_format_id` = ?',
                    $sellingFormatTemplate['template_selling_format_id']
                )
            );
        }
    }

    private function migrateTemplate()
    {
        $this->getTableModifier('amazon_template_description_definition')
             ->changeColumn('msrp_rrp_mode', 'SMALLINT(5) UNSIGNED NOT NULL', '0', null, false)
             ->changeColumn('msrp_rrp_custom_attribute', 'VARCHAR(255) NOT NULL', null, null, false)
             ->commit();

        $this->getTableModifier('ebay_template_selling_format')
             ->dropColumn('lot_size_mode', true, false)
             ->dropColumn('lot_size_custom_value', true, false)
             ->dropColumn('lot_size_attribute', true, false)
             ->commit();

        $this->getTableModifier('ebay_template_shipping_calculated')
             ->changeColumn('package_size_value', 'TEXT NOT NULL', null, null, false)
             ->changeColumn('dimension_width_value', 'TEXT NOT NULL', null, null, false)
             ->changeColumn('dimension_length_value', 'TEXT NOT NULL', null, null, false)
             ->changeColumn('dimension_depth_value', 'TEXT NOT NULL', null, null, false)
             ->changeColumn('weight_minor', 'TEXT NOT NULL', null, null, false)
             ->changeColumn('weight_major', 'TEXT NOT NULL', null, null, false)
             ->commit();

        $this->getTableModifier('walmart_template_selling_format_shipping_override')
             ->renameIndex('template_shipping_override_id', 'template_selling_format_id');
    }

    private function migrateDictionary()
    {
        $this->getTableModifier('ebay_dictionary_category')
             ->changeColumn('path', 'TEXT');

        $this->getTableModifier('amazon_dictionary_category')
             ->changeColumn('product_data_nicks', 'TEXT', null, null, false)
             ->changeColumn('path', 'TEXT', null, null, false)
             ->commit();

        $this->getTableModifier('walmart_dictionary_category')
             ->changeColumn('product_data_nicks', 'TEXT', null, null, false)
             ->changeColumn('path', 'TEXT', null, null, false)
             ->commit();
    }

    private function migrateOther()
    {
        $this->getTableModifier('walmart_listing')
             ->renameIndex('auto_global_adding_description_template_id', 'auto_global_adding_category_template_id')
             ->renameIndex('auto_website_adding_description_template_id', 'auto_website_adding_category_template_id');

        $this->getConnection()->delete(
            $this->getFullTableName('module_config'),
            ['`group` REGEXP \'^\/component\/(ebay|amazon|walmart){1}\/$\' AND `key` = \'allowed\'']
        );

        $this->getConnection()->delete(
            $this->getFullTableName('module_config'),
            ['`group` = \'/view/amazon/autocomplete/\'']
        );

        $this->getConfigModifier('module')->getEntity(null, 'is_disabled')->updateValue('0');
        $this->getConfigModifier('primary')->getEntity('/server/', 'messages')->delete();

        $productChangeTable = $this->getConnection()->newTable($this->getFullTableName('product_change'))
                                   ->addColumn(
                                       'id',
                                       Table::TYPE_INTEGER,
                                       null,
                                       ['unsigned'       => true,
                                        'primary'        => true,
                                        'nullable'       => false,
                                        'auto_increment' => true
                                       ]
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
                                       ['unsigned' => true, 'default' => null]
                                   )
                                   ->addColumn(
                                       'action',
                                       Table::TYPE_TEXT,
                                       255,
                                       ['nullable' => false]
                                   )
                                   ->addColumn(
                                       'attribute',
                                       Table::TYPE_TEXT,
                                       255,
                                       ['default' => null]
                                   )
                                   ->addColumn(
                                       'value_old',
                                       Table::TYPE_TEXT,
                                       self::LONG_COLUMN_SIZE,
                                       ['default' => null]
                                   )
                                   ->addColumn(
                                       'value_new',
                                       Table::TYPE_TEXT,
                                       self::LONG_COLUMN_SIZE,
                                       ['default' => null]
                                   )
                                   ->addColumn(
                                       'initiators',
                                       Table::TYPE_TEXT,
                                       16,
                                       ['nullable' => false]
                                   )
                                   ->addColumn(
                                       'count_changes',
                                       Table::TYPE_INTEGER,
                                       null,
                                       ['unsigned' => true, 'default' => null]
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
                                   ->addIndex('action', 'action')
                                   ->addIndex('attribute', 'attribute')
                                   ->addIndex('initiators', 'initiators')
                                   ->addIndex('product_id', 'product_id')
                                   ->addIndex('store_id', 'store_id')
                                   ->setOption('type', 'MYISAM')
                                   ->setOption('charset', 'utf8')
                                   ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($productChangeTable);

        $this->getConnection()->dropTable($this->getFullTableName('listing_product_instruction'));
        $this->getConnection()->dropTable($this->getFullTableName('listing_product_scheduled_action'));
        $this->getConnection()->dropTable($this->getFullTableName('magento_product_websites_update'));
        $this->getConnection()->dropTable($this->getFullTableName('order_note'));
    }

    //########################################

    private function getConnection()
    {
        return $this->installer->getConnection();
    }

    private function getFullTableName($tableName)
    {
        return $this->helperFactory->getObject('Module_Database_Tables')->getFullName($tableName);
    }

    //########################################

    private function getTableRows($tableName)
    {
        $select = $this->getConnection()->select()->from($tableName);
        return $this->getConnection()->fetchAll($select);
    }

    /**
     * @param $tableName
     * @return \Ess\M2ePro\Model\Setup\Database\Modifier\Table
     */
    protected function getTableModifier($tableName)
    {
        return $this->modelFactory->getObject(
            'Setup_Database_Modifier_Table',
            [
                'installer' => $this->installer,
                'tableName' => $tableName,
            ]
        );
    }

    /**
     * @param $configName
     * @return \Ess\M2ePro\Model\Setup\Database\Modifier\Config
     */
    protected function getConfigModifier($configName)
    {
        $tableName = $configName . '_config';

        return $this->modelFactory->getObject(
            'Setup_Database_Modifier_Config',
            [
                'installer' => $this->installer,
                'tableName' => $tableName,
            ]
        );
    }

    //########################################
}
