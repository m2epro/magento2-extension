<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup;

use Ess\M2ePro\Helper\Factory;
use Ess\M2ePro\Helper\Module;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Config\ConfigOptionsListConstants;

/**
 * Class \Ess\M2ePro\Setup\InstallSchema
 */
class InstallSchema implements InstallSchemaInterface
{
    const LONG_COLUMN_SIZE = 16777217;

    /** @var Factory $helperFactory */
    private $helperFactory;

    /** @var ModuleListInterface $moduleList */
    private $moduleList;

    /** @var SchemaSetupInterface $installer */
    private $installer;

    /** @var \Magento\Framework\App\DeploymentConfig */
    private $deploymentConfig;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    //########################################

    public function __construct(
        Factory $helperFactory,
        ModuleListInterface $moduleList,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Ess\M2ePro\Setup\LoggerFactory $loggerFactory
    ) {
        $this->helperFactory = $helperFactory;
        $this->moduleList = $moduleList;
        $this->deploymentConfig = $deploymentConfig;

        $this->logger = $loggerFactory->create();
    }

    //########################################

    /**
     * Module versions from setup_module magento table uses only by magento for run install or upgrade files.
     * We do not use these versions in setup & upgrade logic (only set correct values to it, using m2epro_setup table).
     * So version, that presented in $context parameter, is not used.
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->installer = $setup;

        if ($this->helperFactory->getObject('Data\GlobalData')->getValue('is_setup_failed')) {
            return;
        }

        $this->helperFactory->getObject('Data\GlobalData')->setValue('is_install_process', true);

        if ($this->helperFactory->getObject('Module\Maintenance')->isEnabled() &&
            !$this->isMaintenanceCanBeIgnored()) {
            return;
        }

        if ($this->isInstalled()) {
            return;
        }

        $this->helperFactory->getObject('Module\Maintenance')->enable();
        $this->installer->startSetup();

        try {
            $this->dropTables();

            $this->initVersionHistory();
            $this->initSetupRow();

            $this->installGeneral();
            $this->installEbay();
            $this->installAmazon();
            $this->installWalmart();
        } catch (\Exception $exception) {
            $this->logger->error($exception, ['source' => 'InstallSchema']);
            $this->helperFactory->getObject('Data\GlobalData')->setValue('is_setup_failed', true);

            $this->installer->endSetup();
            return;
        }

        $this->helperFactory->getObject('Data\GlobalData')->setValue('is_install_schema_completed', true);
        $this->installer->endSetup();
    }

    //########################################

    private function dropTables()
    {
        $tables = $this->installer->getConnection()->getTables(
            (string)$this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX) . 'm2epro_%'
        );

        foreach ($tables as $table) {
            $this->installer->getConnection()->dropTable($table);
        }
    }

    private function initVersionHistory()
    {
        $versionHistoryTable = $this->getConnection()->newTable($this->getFullTableName('versions_history'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'version_from',
                Table::TYPE_TEXT,
                32,
                ['default' => null]
            )
            ->addColumn(
                'version_to',
                Table::TYPE_TEXT,
                32,
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
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($versionHistoryTable);

        $this->getConnection()->insert($this->getFullTableName('versions_history'), [
            'version_from' => null,
            'version_to'   => $this->helperFactory->getObject('Module')->getPublicVersion(),
            'update_date'  => $this->helperFactory->getObject('Data')->getCurrentGmtDate(),
            'create_date'  => $this->helperFactory->getObject('Data')->getCurrentGmtDate(),
        ]);
    }

    private function initSetupRow()
    {
        $setupTable = $this->getConnection()->newTable($this->getFullTableName('setup'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'version_from',
                Table::TYPE_TEXT,
                32,
                ['default' => null]
            )
            ->addColumn(
                'version_to',
                Table::TYPE_TEXT,
                32,
                ['nullable' => false]
            )
            ->addColumn(
                'is_backuped',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_completed',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'profiler_data',
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
            ->addIndex('version_from', 'version_from')
            ->addIndex('version_to', 'version_to')
            ->addIndex('is_backuped', 'is_backuped')
            ->addIndex('is_completed', 'is_completed')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($setupTable);

        $this->getConnection()->insert($this->getFullTableName('setup'), [
            'version_from' => null,
            'version_to'   => $this->getConfigVersion(),
            'is_completed' => 0,
            'update_date'  => $this->helperFactory->getObject('Data')->getCurrentGmtDate(),
            'create_date'  => $this->helperFactory->getObject('Data')->getCurrentGmtDate(),
        ]);
    }

    //########################################

    private function installGeneral()
    {
        $accountTable = $this->getConnection()->newTable($this->getFullTableName('account'))
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
                'component_mode',
                Table::TYPE_TEXT,
                10,
                ['default' => null]
            )
            ->addColumn(
                'additional_data',
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
            ->addIndex('title', 'title')
            ->addIndex('component_mode', 'component_mode')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($accountTable);

        $cacheConfigTable = $this->getConnection()->newTable($this->getFullTableName('cache_config'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
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
        $this->getConnection()->createTable($cacheConfigTable);

        $moduleConfigTable = $this->getConnection()->newTable($this->getFullTableName('module_config'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
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
        $this->getConnection()->createTable($moduleConfigTable);

        $listingTable = $this->getConnection()->newTable($this->getFullTableName('listing'))
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
                'title',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'store_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'products_total_count',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'products_active_count',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'products_inactive_count',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'items_active_count',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'source_products',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'additional_data',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['default' => null]
            )
            ->addColumn(
                'component_mode',
                Table::TYPE_TEXT,
                10,
                ['default' => null]
            )
            ->addColumn(
                'auto_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'auto_global_adding_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'auto_global_adding_add_not_visible',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'auto_website_adding_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'auto_website_adding_add_not_visible',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'auto_website_deleting_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
            ->addIndex('title', 'title')
            ->addIndex('store_id', 'store_id')
            ->addIndex('component_mode', 'component_mode')
            ->addIndex('auto_mode', 'auto_mode')
            ->addIndex('auto_global_adding_mode', 'auto_global_adding_mode')
            ->addIndex('auto_website_adding_mode', 'auto_website_adding_mode')
            ->addIndex('auto_website_deleting_mode', 'auto_website_deleting_mode')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($listingTable);

        $listingAutoCategoryTable = $this->getConnection()->newTable(
            $this->getFullTableName('listing_auto_category')
        )
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'group_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'category_id',
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
            ->addIndex('category_id', 'category_id')
            ->addIndex('group_id', 'group_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($listingAutoCategoryTable);

        $listingAutoCategoryGroupTable = $this->getConnection()->newTable(
            $this->getFullTableName('listing_auto_category_group')
        )
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'listing_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'title',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'adding_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'adding_add_not_visible',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'deleting_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'component_mode',
                Table::TYPE_TEXT,
                10,
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
            ->addIndex('listing_id', 'listing_id')
            ->addIndex('title', 'title')
            ->addIndex('component_mode', 'component_mode')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($listingAutoCategoryGroupTable);

        $listingLogTable = $this->getConnection()->newTable($this->getFullTableName('listing_log'))
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
                'listing_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'product_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'listing_product_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'parent_listing_product_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'listing_title',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'product_title',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'action_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'action',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'initiator',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'type',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'priority',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 3]
            )
            ->addColumn(
                'description',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'component_mode',
                Table::TYPE_TEXT,
                10,
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
            ->addIndex('account_id', 'account_id')
            ->addIndex('marketplace_id', 'marketplace_id')
            ->addIndex('action', 'action')
            ->addIndex('action_id', 'action_id')
            ->addIndex('component_mode', 'component_mode')
            ->addIndex('initiator', 'initiator')
            ->addIndex('listing_id', 'listing_id')
            ->addIndex('listing_product_id', 'listing_product_id')
            ->addIndex('parent_listing_product_id', 'parent_listing_product_id')
            ->addIndex('listing_title', 'listing_title')
            ->addIndex('priority', 'priority')
            ->addIndex('product_id', 'product_id')
            ->addIndex('product_title', 'product_title')
            ->addIndex('type', 'type')
            ->addIndex('create_date', 'create_date')
            ->setOption('type', 'MYISAM')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($listingLogTable);

        $listingOtherTable = $this->getConnection()->newTable($this->getFullTableName('listing_other'))
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
                'product_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'status',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'status_changer',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'component_mode',
                Table::TYPE_TEXT,
                10,
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
            ->addIndex('account_id', 'account_id')
            ->addIndex('component_mode', 'component_mode')
            ->addIndex('marketplace_id', 'marketplace_id')
            ->addIndex('product_id', 'product_id')
            ->addIndex('status', 'status')
            ->addIndex('status_changer', 'status_changer')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($listingOtherTable);

        $listingOtherLogTable = $this->getConnection()->newTable($this->getFullTableName('listing_other_log'))
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
                'listing_other_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'identifier',
                Table::TYPE_TEXT,
                32,
                ['default' => null]
            )
            ->addColumn(
                'title',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'action_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'action',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'initiator',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'type',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'priority',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 3]
            )
            ->addColumn(
                'description',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'component_mode',
                Table::TYPE_TEXT,
                10,
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
            ->addIndex('account_id', 'account_id')
            ->addIndex('marketplace_id', 'marketplace_id')
            ->addIndex('action', 'action')
            ->addIndex('action_id', 'action_id')
            ->addIndex('component_mode', 'component_mode')
            ->addIndex('initiator', 'initiator')
            ->addIndex('identifier', 'identifier')
            ->addIndex('listing_other_id', 'listing_other_id')
            ->addIndex('priority', 'priority')
            ->addIndex('title', 'title')
            ->addIndex('type', 'type')
            ->addIndex('create_date', 'create_date')
            ->setOption('type', 'MYISAM')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($listingOtherLogTable);

        $listingProductTable = $this->getConnection()->newTable($this->getFullTableName('listing_product'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'listing_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'product_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'status',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'status_changer',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'component_mode',
                Table::TYPE_TEXT,
                10,
                ['default' => null]
            )
            ->addColumn(
                'additional_data',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['default' => null]
            )
            ->addColumn(
                'need_synch_rules_check',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'tried_to_list',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'synch_status',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'synch_reasons',
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
            ->addIndex('component_mode', 'component_mode')
            ->addIndex('listing_id', 'listing_id')
            ->addIndex('product_id', 'product_id')
            ->addIndex('status', 'status')
            ->addIndex('status_changer', 'status_changer')
            ->addIndex('tried_to_list', 'tried_to_list')
            ->addIndex('need_synch_rules_check', 'need_synch_rules_check')
            ->addIndex('synch_status', 'synch_status')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($listingProductTable);

        $listingProductVariationTable = $this->getConnection()->newTable(
            $this->getFullTableName('listing_product_variation')
        )
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'listing_product_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'component_mode',
                Table::TYPE_TEXT,
                10,
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
            ->addIndex('component_mode', 'component_mode')
            ->addIndex('listing_product_id', 'listing_product_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($listingProductVariationTable);

        $listingProductVariationOptionTable = $this->getConnection()->newTable(
            $this->getFullTableName('listing_product_variation_option')
        )
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'listing_product_variation_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'product_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'product_type',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'attribute',
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
                'component_mode',
                Table::TYPE_TEXT,
                10,
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
            ->addIndex('attribute', 'attribute')
            ->addIndex('component_mode', 'component_mode')
            ->addIndex('listing_product_variation_id', 'listing_product_variation_id')
            ->addIndex('option', 'option')
            ->addIndex('product_id', 'product_id')
            ->addIndex('product_type', 'product_type')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($listingProductVariationOptionTable);

        $lockItemTable = $this->getConnection()->newTable($this->getFullTableName('lock_item'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'nick',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'parent_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'data',
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
            ->addIndex('nick', 'nick')
            ->addIndex('parent_id', 'parent_id')
            ->setOption('type', 'MYISAM')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($lockItemTable);

        $lockTransactional = $this->getConnection()->newTable(
            $this->getFullTableName('lock_transactional')
        )
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'nick',
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
            ->addIndex('nick', 'nick')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($lockTransactional);

        $marketplaceTable = $this->getConnection()->newTable($this->getFullTableName('marketplace'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'native_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'title',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'code',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'url',
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
                'sorder',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'group_title',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'component_mode',
                Table::TYPE_TEXT,
                10,
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
            ->addIndex('component_mode', 'component_mode')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($marketplaceTable);

        $orderTable = $this->getConnection()->newTable($this->getFullTableName('order'))
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
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'magento_order_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'magento_order_creation_failure',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'magento_order_creation_fails_count',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'magento_order_creation_latest_attempt_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                'store_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'reservation_state',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'default' => 0]
            )
            ->addColumn(
                'reservation_start_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                'component_mode',
                Table::TYPE_TEXT,
                10,
                ['default' => null]
            )
            ->addColumn(
                'additional_data',
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
            ->addIndex('component_mode', 'component_mode')
            ->addIndex('magento_order_id', 'magento_order_id')
            ->addIndex('magento_order_creation_failure', 'magento_order_creation_failure')
            ->addIndex('magento_order_creation_fails_count', 'magento_order_creation_fails_count')
            ->addIndex('magento_order_creation_latest_attempt_date', 'magento_order_creation_latest_attempt_date')
            ->addIndex('marketplace_id', 'marketplace_id')
            ->addIndex('reservation_state', 'reservation_state')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($orderTable);

        $orderChangeTable = $this->getConnection()->newTable($this->getFullTableName('order_change'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'component',
                Table::TYPE_TEXT,
                10,
                ['nullable' => false]
            )
            ->addColumn(
                'order_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'action',
                Table::TYPE_TEXT,
                50,
                ['nullable' => false]
            )
            ->addColumn(
                'params',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['nullable' => false]
            )
            ->addColumn(
                'creator_type',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'processing_attempt_count',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'processing_attempt_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                'hash',
                Table::TYPE_TEXT,
                50,
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
            ->addIndex('action', 'action')
            ->addIndex('creator_type', 'creator_type')
            ->addIndex('hash', 'hash')
            ->addIndex('order_id', 'order_id')
            ->addIndex('processing_attempt_count', 'processing_attempt_count')
            ->setOption('type', 'MYISAM')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($orderChangeTable);

        $orderItemTable = $this->getConnection()->newTable($this->getFullTableName('order_item'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'order_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'product_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'product_details',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'component_mode',
                Table::TYPE_TEXT,
                10,
                ['default' => null]
            )
            ->addColumn(
                'qty_reserved',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => 0]
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
            ->addIndex('component_mode', 'component_mode')
            ->addIndex('order_id', 'order_id')
            ->addIndex('product_id', 'product_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($orderItemTable);

        $orderLogTable = $this->getConnection()->newTable($this->getFullTableName('order_log'))
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
                'order_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'type',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 2]
            )
            ->addColumn(
                'initiator',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'description',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'component_mode',
                Table::TYPE_TEXT,
                10,
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
            ->addIndex('account_id', 'account_id')
            ->addIndex('marketplace_id', 'marketplace_id')
            ->addIndex('component_mode', 'component_mode')
            ->addIndex('initiator', 'initiator')
            ->addIndex('order_id', 'order_id')
            ->addIndex('type', 'type')
            ->addIndex('create_date', 'create_date')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($orderLogTable);

        $orderMatchingTable = $this->getConnection()->newTable($this->getFullTableName('order_matching'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'product_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'input_variation_options',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'output_variation_options',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'hash',
                Table::TYPE_TEXT,
                50,
                ['default' => null]
            )
            ->addColumn(
                'component',
                Table::TYPE_TEXT,
                10,
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
            ->addIndex('component', 'component')
            ->addIndex('hash', 'hash')
            ->addIndex('product_id', 'product_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($orderMatchingTable);

        $primaryConfigTable = $this->getConnection()->newTable($this->getFullTableName('primary_config'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
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
        $this->getConnection()->createTable($primaryConfigTable);

        $processingTable = $this->getConnection()->newTable($this->getFullTableName('processing'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'model',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'params',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['default' => null]
            )
            ->addColumn(
                'result_data',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['default' => null]
            )
            ->addColumn(
                'result_messages',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['default' => null]
            )
            ->addColumn(
                'is_completed',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'expiration_date',
                Table::TYPE_DATETIME,
                null,
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
            ->addIndex('model', 'model')
            ->addIndex('is_completed', 'is_completed')
            ->addIndex('expiration_date', 'expiration_date')
            ->setOption('type', 'MYISAM')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($processingTable);

        $processingLockTable = $this->getConnection()->newTable($this->getFullTableName('processing_lock'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'processing_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'model_name',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'object_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'tag',
                Table::TYPE_TEXT,
                255,
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
            ->addIndex('processing_id', 'processing_id')
            ->addIndex('model_name', 'model_name')
            ->addIndex('object_id', 'object_id')
            ->addIndex('tag', 'tag')
            ->setOption('type', 'MYISAM')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($processingLockTable);

        $requestPendingSingleTable = $this->getConnection()->newTable($this->getFullTableName('request_pending_single'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'component',
                Table::TYPE_TEXT,
                12,
                ['nullable' => false]
            )
            ->addColumn(
                'server_hash',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'result_data',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['default' => null]
            )
            ->addColumn(
                'result_messages',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['default' => null]
            )
            ->addColumn(
                'expiration_date',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'is_completed',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
            ->addIndex('component', 'component')
            ->addIndex('server_hash', 'server_hash')
            ->addIndex('is_completed', 'is_completed')
            ->setOption('type', 'MYISAM')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($requestPendingSingleTable);

        $requestPendingPartialTable = $this->getConnection()->newTable(
            $this->getFullTableName('request_pending_partial')
        )
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'component',
                Table::TYPE_TEXT,
                12,
                ['nullable' => false]
            )
            ->addColumn(
                'server_hash',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'next_part',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'result_messages',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['default' => null]
            )
            ->addColumn(
                'expiration_date',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'is_completed',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
            ->addIndex('component', 'component')
            ->addIndex('server_hash', 'server_hash')
            ->addIndex('next_part', 'next_part')
            ->addIndex('is_completed', 'is_completed')
            ->setOption('type', 'MYISAM')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($requestPendingPartialTable);

        $requestPendingPartialDataTable = $this->getConnection()->newTable(
            $this->getFullTableName('request_pending_partial_data')
        )
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'request_pending_partial_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'part_number',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'data',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['default' => null]
            )
            ->addIndex('part_number', 'part_number')
            ->addIndex('request_pending_partial_id', 'request_pending_partial_id')
            ->setOption('type', 'MYISAM')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($requestPendingPartialDataTable);

        $connectorPendingRequesterSingleTable = $this->getConnection()->newTable(
            $this->getFullTableName('connector_pending_requester_single')
        )
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
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
            ->addIndex('processing_id', 'processing_id')
            ->addIndex('request_pending_single_id', 'request_pending_single_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($connectorPendingRequesterSingleTable);

        $connectorPendingRequesterPartialTable = $this->getConnection()->newTable(
            $this->getFullTableName('connector_pending_requester_partial')
        )
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'processing_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'request_pending_partial_id',
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
            ->addIndex('request_pending_partial_id', 'request_pending_partial_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($connectorPendingRequesterPartialTable);

        $productChangeTable = $this->getConnection()->newTable($this->getFullTableName('product_change'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
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

        $stopQueueTable = $this->getConnection()->newTable($this->getFullTableName('stop_queue'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'item_data',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'account_hash',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'marketplace_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'component_mode',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'is_processed',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
            ->addIndex('account_hash', 'account_hash')
            ->addIndex('component_mode', 'component_mode')
            ->addIndex('is_processed', 'is_processed')
            ->addIndex('marketplace_id', 'marketplace_id')
            ->setOption('type', 'MYISAM')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($stopQueueTable);

        $synchronizationConfigTableName = $this->getFullTableName('synchronization_config');
        $synchronizationConfigTable = $this->getConnection()->newTable($synchronizationConfigTableName)
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
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

        $synchronizationLogTable = $this->getConnection()->newTable($this->getFullTableName('synchronization_log'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'operation_history_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'task',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'initiator',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'type',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'priority',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 3]
            )
            ->addColumn(
                'description',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'component_mode',
                Table::TYPE_TEXT,
                10,
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
            ->addIndex('component_mode', 'component_mode')
            ->addIndex('initiator', 'initiator')
            ->addIndex('priority', 'priority')
            ->addIndex('task', 'task')
            ->addIndex('operation_history_id', 'operation_history_id')
            ->addIndex('type', 'type')
            ->addIndex('create_date', 'create_date')
            ->setOption('type', 'MYISAM')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($synchronizationLogTable);

        $systemLogTable = $this->getConnection()->newTable($this->getFullTableName('system_log'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'type',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'description',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
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
            ->addIndex('type', 'type')
            ->setOption('type', 'MYISAM')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($systemLogTable);

        $operationHistoryTable = $this->getConnection()->newTable($this->getFullTableName('operation_history'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'nick',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'parent_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'initiator',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'start_date',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'end_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                'data',
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
            ->addIndex('nick', 'nick')
            ->addIndex('parent_id', 'parent_id')
            ->addIndex('initiator', 'initiator')
            ->addIndex('start_date', 'start_date')
            ->addIndex('end_date', 'end_date')
            ->setOption('type', 'MYISAM')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($operationHistoryTable);

        $templateSellingFormatTableName = $this->getFullTableName('template_selling_format');
        $templateSellingFormatTable = $this->getConnection()->newTable($templateSellingFormatTableName)
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
                'component_mode',
                Table::TYPE_TEXT,
                10,
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
            ->addIndex('component_mode', 'component_mode')
            ->addIndex('title', 'title')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($templateSellingFormatTable);

        $templateSynchronizationTable = $this->getConnection()->newTable(
            $this->getFullTableName('template_synchronization')
        )
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
                'revise_change_listing',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'revise_change_selling_format_template',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'component_mode',
                Table::TYPE_TEXT,
                10,
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
            ->addIndex('component_mode', 'component_mode')
            ->addIndex('revise_change_listing', 'revise_change_listing')
            ->addIndex('revise_change_selling_format_template', 'revise_change_selling_format_template')
            ->addIndex('title', 'title')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($templateSynchronizationTable);

        $templateDescriptionTable = $this->getConnection()->newTable($this->getFullTableName('template_description'))
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
                'component_mode',
                Table::TYPE_TEXT,
                10,
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
            ->addIndex('component_mode', 'component_mode')
            ->addIndex('title', 'title')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($templateDescriptionTable);

        $wizardTable = $this->getConnection()->newTable($this->getFullTableName('wizard'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'nick',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'view',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'status',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'step',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'type',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'priority',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addIndex('nick', 'nick')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($wizardTable);

        $registryTable = $this->getConnection()->newTable($this->getFullTableName('registry'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
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
            ->addIndex('key', 'key')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($registryTable);

        $archivedEntity = $this->getConnection()->newTable(
            $this->getFullTableName('archived_entity')
        )
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'origin_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'name',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'data',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['nullable' => false]
            )
            ->addColumn(
                'create_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addIndex('origin_id__name', ['origin_id', 'name'])
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($archivedEntity);
    }

    private function installEbay()
    {
        $ebayAccountTable = $this->getConnection()->newTable($this->getFullTableName('ebay_account'))
            ->addColumn(
                'account_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'server_hash',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'user_id',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'translation_hash',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'translation_info',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'token_session',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'token_expired_date',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'marketplaces_data',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'defaults_last_synchronization',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
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
                ['nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'other_listings_mapping_settings',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'other_listings_last_synchronization',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                'feedbacks_receive',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'feedbacks_auto_response',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'feedbacks_auto_response_only_positive',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'feedbacks_last_used_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'ebay_store_title',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'ebay_store_url',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'ebay_store_subscription_level',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'ebay_store_description',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'info',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'user_preferences',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'ebay_shipping_discount_profiles',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'job_token',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'orders_last_synchronization',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                'magento_orders_settings',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'messages_receive',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 0]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayAccountTable);

        $ebayAccountStoreCategoryTable = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_account_store_category')
        )
            ->addColumn(
                'account_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'category_id',
                Table::TYPE_DECIMAL,
                [20, 0],
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'parent_id',
                Table::TYPE_DECIMAL,
                [20, 0],
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'title',
                Table::TYPE_TEXT,
                200,
                ['nullable' => false]
            )
            ->addColumn(
                'is_leaf',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'sorder',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addIndex('primary', ['account_id', 'category_id'], ['type' => AdapterInterface::INDEX_TYPE_PRIMARY])
            ->addIndex('parent_id', 'parent_id')
            ->addIndex('sorder', 'sorder')
            ->addIndex('title', 'title')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayAccountStoreCategoryTable);

        $ebayAccountPickupStoreTable = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_account_pickup_store')
        )
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'name',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'location_id',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
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
                'phone',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'postal_code',
                Table::TYPE_TEXT,
                50,
                ['nullable' => false]
            )
            ->addColumn(
                'url',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'utc_offset',
                Table::TYPE_TEXT,
                50,
                ['nullable' => false]
            )
            ->addColumn(
                'country',
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
                'city',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'address_1',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'address_2',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'latitude',
                Table::TYPE_FLOAT,
                null,
                []
            )
            ->addColumn(
                'longitude',
                Table::TYPE_FLOAT,
                null,
                []
            )
            ->addColumn(
                'business_hours',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'special_hours',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'pickup_instruction',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
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
            ->addIndex('name', 'name')
            ->addIndex('location_id', 'location_id')
            ->addIndex('account_id', 'account_id')
            ->addIndex('marketplace_id', 'marketplace_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayAccountPickupStoreTable);

        $ebayAccountPickupStoreStateTable = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_account_pickup_store_state')
        )
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'account_pickup_store_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'is_in_processing',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'default' => 0]
            )
            ->addColumn(
                'sku',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'online_qty',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'target_qty',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'is_added',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_deleted',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 0]
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
            ->addIndex('account_pickup_store_id', 'account_pickup_store_id')
            ->addIndex('is_in_processing', 'is_in_processing')
            ->addIndex('sku', 'sku')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayAccountPickupStoreStateTable);

        $ebayAccountPickupStoreLogTable = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_account_pickup_store_log')
        )
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'account_pickup_store_state_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'location_id',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'location_title',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'action_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'action',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'type',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'priority',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 3]
            )
            ->addColumn(
                'description',
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
            ->addIndex('account_pickup_store_state_id', 'account_pickup_store_state_id')
            ->addIndex('location_id', 'location_id')
            ->addIndex('location_title', 'location_title')
            ->addIndex('action', 'action')
            ->addIndex('action_id', 'action_id')
            ->addIndex('priority', 'priority')
            ->addIndex('type', 'type')
            ->addIndex('create_date', 'create_date')
            ->setOption('type', 'MYISAM')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayAccountPickupStoreLogTable);

        $ebayProcessingActionTable = $this->getConnection()->newTable($this->getFullTableName('ebay_processing_action'))
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

        $ebayDictionaryCategory = $this->getConnection()->newTable($this->getFullTableName('ebay_dictionary_category'))
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
                'category_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'parent_category_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
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
                'features',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['default' => null]
            )
            ->addColumn(
                'item_specifics',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
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
            ->addIndex('is_leaf', 'is_leaf')
            ->addIndex('parent_category_id', 'parent_category_id')
            ->addIndex('title', 'title')
            ->addIndex('path', [['name' => 'path', 'size' => 500]])
            ->setOption('type', 'MYISAM')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayDictionaryCategory);

        $ebayDictionaryMarketplace = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_dictionary_marketplace')
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
                'dispatch',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['nullable' => false]
            )
            ->addColumn(
                'packages',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['nullable' => false]
            )
            ->addColumn(
                'return_policy',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['nullable' => false]
            )
            ->addColumn(
                'listing_features',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['nullable' => false]
            )
            ->addColumn(
                'payments',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['nullable' => false]
            )
            ->addColumn(
                'shipping_locations',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['nullable' => false]
            )
            ->addColumn(
                'shipping_locations_exclude',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['nullable' => false]
            )
            ->addColumn(
                'additional_data',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['default' => null]
            )
            ->addColumn(
                'tax_categories',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['nullable' => false]
            )
            ->addColumn(
                'charities',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['nullable' => false]
            )
            ->addIndex('marketplace_id', 'marketplace_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayDictionaryMarketplace);

        $ebayDictionaryShippingTable = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_dictionary_shipping')
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
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'ebay_id',
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
                'category',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'is_flat',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_calculated',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_international',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'data',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['nullable' => false]
            )
            ->addIndex('category', 'category')
            ->addIndex('ebay_id', 'ebay_id')
            ->addIndex('is_calculated', 'is_calculated')
            ->addIndex('is_flat', 'is_flat')
            ->addIndex('is_international', 'is_international')
            ->addIndex('marketplace_id', 'marketplace_id')
            ->addIndex('title', 'title')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayDictionaryShippingTable);

        $ebayFeedbackTable = $this->getConnection()->newTable($this->getFullTableName('ebay_feedback'))
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
                'ebay_item_id',
                Table::TYPE_DECIMAL,
                [20, 0],
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'ebay_item_title',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'ebay_transaction_id',
                Table::TYPE_TEXT,
                20,
                ['nullable' => false]
            )
            ->addColumn(
                'buyer_name',
                Table::TYPE_TEXT,
                200,
                ['nullable' => false]
            )
            ->addColumn(
                'buyer_feedback_id',
                Table::TYPE_DECIMAL,
                [20, 0],
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'buyer_feedback_text',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'buyer_feedback_date',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'buyer_feedback_type',
                Table::TYPE_TEXT,
                20,
                ['nullable' => false]
            )
            ->addColumn(
                'seller_feedback_id',
                Table::TYPE_DECIMAL,
                [20, 0],
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'seller_feedback_text',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'seller_feedback_date',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'seller_feedback_type',
                Table::TYPE_TEXT,
                20,
                ['nullable' => false]
            )
            ->addColumn(
                'last_response_attempt_date',
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
            ->addIndex('buyer_feedback_id', 'buyer_feedback_id')
            ->addIndex('ebay_item_id', 'ebay_item_id')
            ->addIndex('ebay_transaction_id', 'ebay_transaction_id')
            ->addIndex('seller_feedback_id', 'seller_feedback_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayFeedbackTable);

        $ebayFeedbackTemplateTable = $this->getConnection()->newTable($this->getFullTableName('ebay_feedback_template'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'account_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'body',
                Table::TYPE_TEXT,
                null,
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
            ->addIndex('account_id', 'account_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayFeedbackTemplateTable);

        $ebayItemTable = $this->getConnection()->newTable($this->getFullTableName('ebay_item'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'item_id',
                Table::TYPE_DECIMAL,
                [20, 0],
                ['unsigned' => true, 'nullable' => false]
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
                'variations',
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
            ->addIndex('item_id', 'item_id')
            ->addIndex('account_id', 'account_id')
            ->addIndex('marketplace_id', 'marketplace_id')
            ->addIndex('product_id', 'product_id')
            ->addIndex('store_id', 'store_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayItemTable);

        $ebayListingTable = $this->getConnection()->newTable($this->getFullTableName('ebay_listing'))
            ->addColumn(
                'listing_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'products_sold_count',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'items_sold_count',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'auto_global_adding_template_category_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'auto_global_adding_template_other_category_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'auto_website_adding_template_category_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'auto_website_adding_template_other_category_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_payment_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'template_payment_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_payment_custom_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_shipping_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'template_shipping_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_shipping_custom_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_return_policy_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'template_return_policy_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_return_policy_custom_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_description_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'template_description_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_description_custom_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_selling_format_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'template_selling_format_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_selling_format_custom_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_synchronization_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'template_synchronization_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_synchronization_custom_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'product_add_ids',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'parts_compatibility_mode',
                Table::TYPE_TEXT,
                10,
                ['default' => null]
            )
            ->addIndex('auto_global_adding_template_category_id', 'auto_global_adding_template_category_id')
            ->addIndex('auto_global_adding_template_other_category_id', 'auto_global_adding_template_other_category_id')
            ->addIndex('auto_website_adding_template_category_id', 'auto_website_adding_template_category_id')
            ->addIndex(
                'auto_website_adding_template_other_category_id',
                'auto_website_adding_template_other_category_id'
            )
            ->addIndex('items_sold_count', 'items_sold_count')
            ->addIndex('products_sold_count', 'products_sold_count')
            ->addIndex('template_description_custom_id', 'template_description_custom_id')
            ->addIndex('template_description_id', 'template_description_id')
            ->addIndex('template_description_mode', 'template_description_mode')
            ->addIndex('template_payment_custom_id', 'template_payment_custom_id')
            ->addIndex('template_payment_id', 'template_payment_id')
            ->addIndex('template_payment_mode', 'template_payment_mode')
            ->addIndex('template_return_policy_custom_id', 'template_return_policy_custom_id')
            ->addIndex('template_return_policy_id', 'template_return_policy_id')
            ->addIndex('template_return_policy_mode', 'template_return_policy_mode')
            ->addIndex('template_selling_format_custom_id', 'template_selling_format_custom_id')
            ->addIndex('template_selling_format_id', 'template_selling_format_id')
            ->addIndex('template_selling_format_mode', 'template_selling_format_mode')
            ->addIndex('template_shipping_custom_id', 'template_shipping_custom_id')
            ->addIndex('template_shipping_id', 'template_shipping_id')
            ->addIndex('template_shipping_mode', 'template_shipping_mode')
            ->addIndex('template_synchronization_custom_id', 'template_synchronization_custom_id')
            ->addIndex('template_synchronization_id', 'template_synchronization_id')
            ->addIndex('template_synchronization_mode', 'template_synchronization_mode')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayListingTable);

        $ebayListingAutoCategoryGroup = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_listing_auto_category_group')
        )
            ->addColumn(
                'listing_auto_category_group_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'adding_template_category_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'adding_template_other_category_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addIndex('adding_template_category_id', 'adding_template_category_id')
            ->addIndex('adding_template_other_category_id', 'adding_template_other_category_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayListingAutoCategoryGroup);

        $ebayListingOtherTable = $this->getConnection()->newTable($this->getFullTableName('ebay_listing_other'))
            ->addColumn(
                'listing_other_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'item_id',
                Table::TYPE_DECIMAL,
                [20, 0],
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'sku',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'title',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'currency',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'online_duration',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
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
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'online_qty_sold',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'online_bids',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'start_date',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'end_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addIndex('currency', 'currency')
            ->addIndex('end_date', 'end_date')
            ->addIndex('item_id', 'item_id')
            ->addIndex('online_bids', 'online_bids')
            ->addIndex('online_price', 'online_price')
            ->addIndex('online_qty', 'online_qty')
            ->addIndex('online_qty_sold', 'online_qty_sold')
            ->addIndex('sku', 'sku')
            ->addIndex('start_date', 'start_date')
            ->addIndex('title', 'title')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayListingOtherTable);

        $ebayListingProductTable = $this->getConnection()->newTable($this->getFullTableName('ebay_listing_product'))
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
                'template_other_category_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'ebay_item_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'item_uuid',
                Table::TYPE_TEXT,
                32,
                ['default' => null]
            )
            ->addColumn(
                'is_duplicate',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'online_is_variation',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'online_is_auction_type',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'online_sku',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'online_title',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'online_duration',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'online_current_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'online_start_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'online_reserve_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'online_buyitnow_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'online_qty',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'online_qty_sold',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'online_bids',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'online_category',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'translation_status',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'translation_service',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'translated_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                'start_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                'end_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                'template_payment_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'template_payment_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_payment_custom_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_shipping_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'template_shipping_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_shipping_custom_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_return_policy_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'template_return_policy_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_return_policy_custom_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_description_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'template_description_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_description_custom_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_selling_format_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'template_selling_format_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_selling_format_custom_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_synchronization_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'template_synchronization_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_synchronization_custom_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addIndex('ebay_item_id', 'ebay_item_id')
            ->addIndex('item_uuid', 'item_uuid')
            ->addIndex('is_duplicate', 'is_duplicate')
            ->addIndex('online_is_variation', 'online_is_variation')
            ->addIndex('online_is_auction_type', 'online_is_auction_type')
            ->addIndex('end_date', 'end_date')
            ->addIndex('online_bids', 'online_bids')
            ->addIndex('online_buyitnow_price', 'online_buyitnow_price')
            ->addIndex('online_category', 'online_category')
            ->addIndex('online_qty', 'online_qty')
            ->addIndex('online_qty_sold', 'online_qty_sold')
            ->addIndex('online_reserve_price', 'online_reserve_price')
            ->addIndex('online_sku', 'online_sku')
            ->addIndex('online_current_price', 'online_current_price')
            ->addIndex('online_start_price', 'online_start_price')
            ->addIndex('online_title', 'online_title')
            ->addIndex('start_date', 'start_date')
            ->addIndex('translation_status', 'translation_status')
            ->addIndex('translation_service', 'translation_service')
            ->addIndex('translated_date', 'translated_date')
            ->addIndex('template_category_id', 'template_category_id')
            ->addIndex('template_description_custom_id', 'template_description_custom_id')
            ->addIndex('template_description_id', 'template_description_id')
            ->addIndex('template_description_mode', 'template_description_mode')
            ->addIndex('template_other_category_id', 'template_other_category_id')
            ->addIndex('template_payment_custom_id', 'template_payment_custom_id')
            ->addIndex('template_payment_id', 'template_payment_id')
            ->addIndex('template_payment_mode', 'template_payment_mode')
            ->addIndex('template_return_policy_custom_id', 'template_return_policy_custom_id')
            ->addIndex('template_return_policy_id', 'template_return_policy_id')
            ->addIndex('template_return_policy_mode', 'template_return_policy_mode')
            ->addIndex('template_selling_format_custom_id', 'template_selling_format_custom_id')
            ->addIndex('template_selling_format_id', 'template_selling_format_id')
            ->addIndex('template_selling_format_mode', 'template_selling_format_mode')
            ->addIndex('template_shipping_custom_id', 'template_shipping_custom_id')
            ->addIndex('template_shipping_id', 'template_shipping_id')
            ->addIndex('template_shipping_mode', 'template_shipping_mode')
            ->addIndex('template_synchronization_custom_id', 'template_synchronization_custom_id')
            ->addIndex('template_synchronization_id', 'template_synchronization_id')
            ->addIndex('template_synchronization_mode', 'template_synchronization_mode')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayListingProductTable);

        $ebayListingProductPickupStoreTable = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_listing_product_pickup_store')
        )
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'listing_product_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'account_pickup_store_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'is_process_required',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addIndex('listing_product_id', 'listing_product_id')
            ->addIndex('account_pickup_store_id', 'account_pickup_store_id')
            ->addIndex('is_process_required', 'is_process_required')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayListingProductPickupStoreTable);

        $ebayListingProductVariationTable = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_listing_product_variation')
        )
            ->addColumn(
                'listing_product_variation_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'add',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'delete',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'online_sku',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'online_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['default' => null]
            )
            ->addColumn(
                'online_qty',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'online_qty_sold',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'status',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addIndex('add', 'add')
            ->addIndex('delete', 'delete')
            ->addIndex('online_sku', 'online_sku')
            ->addIndex('online_price', 'online_price')
            ->addIndex('online_qty', 'online_qty')
            ->addIndex('online_qty_sold', 'online_qty_sold')
            ->addIndex('status', 'status')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayListingProductVariationTable);

        $ebayListingProductVariationOptionTable = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_listing_product_variation_option')
        )
            ->addColumn(
                'listing_product_variation_option_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayListingProductVariationOptionTable);

        $ebayIndexerListingProductVariationParentTable = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_indexer_listing_product_variation_parent')
        )
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
                'component_mode',
                Table::TYPE_TEXT,
                10,
                ['default' => null]
            )
            ->addColumn(
                'min_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'nullable' => false, 'default' => '0.0000']
            )
            ->addColumn(
                'max_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'nullable' => false, 'default' => '0.0000']
            )
            ->addColumn(
                'create_date',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => false]
            )
            ->addIndex('listing_id', 'listing_id')
            ->addIndex('component_mode', 'component_mode')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayIndexerListingProductVariationParentTable);

        $ebayMarketplaceTable = $this->getConnection()->newTable($this->getFullTableName('ebay_marketplace'))
            ->addColumn(
                'marketplace_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'currency',
                Table::TYPE_TEXT,
                70,
                ['nullable' => false, 'default' => 'USD']
            )
            ->addColumn(
                'origin_country',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'language_code',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'translation_service_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_multivariation',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_freight_shipping',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_calculated_shipping',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_tax_table',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_vat',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_stp',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_stp_advanced',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_map',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_local_shipping_rate_table',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_international_shipping_rate_table',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_english_measurement_system',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_metric_measurement_system',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_cash_on_delivery',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_global_shipping_program',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_charity',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_click_and_collect',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_in_store_pickup',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_holiday_return',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_epid',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_ktype',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addIndex('is_calculated_shipping', 'is_calculated_shipping')
            ->addIndex('is_cash_on_delivery', 'is_cash_on_delivery')
            ->addIndex('is_charity', 'is_charity')
            ->addIndex('is_english_measurement_system', 'is_english_measurement_system')
            ->addIndex('is_freight_shipping', 'is_freight_shipping')
            ->addIndex('is_international_shipping_rate_table', 'is_international_shipping_rate_table')
            ->addIndex('is_local_shipping_rate_table', 'is_local_shipping_rate_table')
            ->addIndex('is_metric_measurement_system', 'is_metric_measurement_system')
            ->addIndex('is_tax_table', 'is_tax_table')
            ->addIndex('is_vat', 'is_vat')
            ->addIndex('is_stp', 'is_stp')
            ->addIndex('is_stp_advanced', 'is_stp_advanced')
            ->addIndex('is_map', 'is_map')
            ->addIndex('is_click_and_collect', 'is_click_and_collect')
            ->addIndex('is_in_store_pickup', 'is_in_store_pickup')
            ->addIndex('is_holiday_return', 'is_holiday_return')
            ->addIndex('is_epid', 'is_epid')
            ->addIndex('is_ktype', 'is_ktype')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayMarketplaceTable);

        $ebaDictionaryMotorEpidTable = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_dictionary_motor_epid')
        )
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'epid',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'product_type',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'make',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'model',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'year',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'trim',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'engine',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'submodel',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'is_custom',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'scope',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0']
            )
            ->addIndex('epid', 'epid')
            ->addIndex('engine', 'engine')
            ->addIndex('make', 'make')
            ->addIndex('model', 'model')
            ->addIndex('product_type', 'product_type')
            ->addIndex('submodel', 'submodel')
            ->addIndex('trim', 'trim')
            ->addIndex('year', 'year')
            ->addIndex('is_custom', 'is_custom')
            ->addIndex('scope', 'scope')
            ->setOption('type', 'MYISAM')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebaDictionaryMotorEpidTable);

        $ebayDictionaryMotorKtypeTable = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_dictionary_motor_ktype')
        )
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'ktype',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'make',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'model',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'variant',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'body_style',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'type',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'from_year',
                Table::TYPE_INTEGER,
                null,
                ['default' => null]
            )
            ->addColumn(
                'to_year',
                Table::TYPE_INTEGER,
                null,
                ['default' => null]
            )
            ->addColumn(
                'engine',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'is_custom',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addIndex('body_style', 'body_style')
            ->addIndex('engine', 'engine')
            ->addIndex('from_year', 'from_year')
            ->addIndex('ktype', 'ktype')
            ->addIndex('make', 'make')
            ->addIndex('model', 'model')
            ->addIndex('to_year', 'to_year')
            ->addIndex('type', 'type')
            ->addIndex('variant', 'variant')
            ->addIndex('is_custom', 'is_custom')
            ->setOption('type', 'MYISAM')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayDictionaryMotorKtypeTable);

        $ebayMotorFilterTable = $this->getConnection()->newTable($this->getFullTableName('ebay_motor_filter'))
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
                'type',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'conditions',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'note',
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
            ->addIndex('type', 'type')
            ->setOption('type', 'MYISAM')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayMotorFilterTable);

        $ebayMotorGroup = $this->getConnection()->newTable($this->getFullTableName('ebay_motor_group'))
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
                'mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'type',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'items_data',
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
            ->addIndex('mode', 'mode')
            ->addIndex('type', 'type')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayMotorGroup);

        $ebayMotorFilterToGroupTable = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_motor_filter_to_group')
        )
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'filter_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'group_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addIndex('filter_id', 'filter_id')
            ->addIndex('group_id', 'group_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayMotorFilterToGroupTable);

        $ebayOrderTable = $this->getConnection()->newTable($this->getFullTableName('ebay_order'))
            ->addColumn(
                'order_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'ebay_order_id',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'selling_manager_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
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
                ['nullable' => false]
            )
            ->addColumn(
                'buyer_user_id',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'buyer_message',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'buyer_tax_id',
                Table::TYPE_TEXT,
                64,
                ['default' => null]
            )
            ->addColumn(
                'paid_amount',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['nullable' => false, 'default' => '0.0000']
            )
            ->addColumn(
                'saved_amount',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['nullable' => false, 'default' => '0.0000']
            )
            ->addColumn(
                'currency',
                Table::TYPE_TEXT,
                10,
                ['nullable' => false]
            )
            ->addColumn(
                'checkout_status',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'shipping_status',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'payment_status',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'shipping_details',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'payment_details',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'tax_details',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
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
            ->addIndex('ebay_order_id', 'ebay_order_id')
            ->addIndex('selling_manager_id', 'selling_manager_id')
            ->addIndex('buyer_email', 'buyer_email')
            ->addIndex('buyer_name', 'buyer_name')
            ->addIndex('buyer_user_id', 'buyer_user_id')
            ->addIndex('paid_amount', 'paid_amount')
            ->addIndex('checkout_status', 'checkout_status')
            ->addIndex('payment_status', 'payment_status')
            ->addIndex('shipping_status', 'shipping_status')
            ->addIndex('purchase_create_date', 'purchase_create_date')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayOrderTable);

        $ebayOrderExternalTransactionTable = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_order_external_transaction')
        )
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'order_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'transaction_id',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'fee',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['nullable' => false, 'default' => '0.0000']
            )
            ->addColumn(
                'sum',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['nullable' => false, 'default' => '0.0000']
            )
            ->addColumn(
                'is_refund',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'transaction_date',
                Table::TYPE_DATETIME,
                null,
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
            ->addIndex('transaction_id', 'transaction_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayOrderExternalTransactionTable);

        $ebayOrderItemTable = $this->getConnection()->newTable($this->getFullTableName('ebay_order_item'))
            ->addColumn(
                'order_item_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'transaction_id',
                Table::TYPE_TEXT,
                20,
                ['nullable' => false]
            )
            ->addColumn(
                'selling_manager_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'item_id',
                Table::TYPE_DECIMAL,
                [20, 0],
                ['unsigned' => true, 'nullable' => false]
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
                64,
                ['default' => null]
            )
            ->addColumn(
                'price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['nullable' => false, 'default' => '0.0000']
            )
            ->addColumn(
                'qty_purchased',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'tax_details',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'final_fee',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['nullable' => false, 'default' => '0.0000']
            )
            ->addColumn(
                'waste_recycling_fee',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['nullable' => false, 'default' => '0.0000']
            )
            ->addColumn(
                'variation_details',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'tracking_details',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'unpaid_item_process_state',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addIndex('transaction_id', 'transaction_id')
            ->addIndex('selling_manager_id', 'selling_manager_id')
            ->addIndex('item_id', 'item_id')
            ->addIndex('sku', 'sku')
            ->addIndex('title', 'title')
            ->addIndex('unpaid_item_process_state', 'unpaid_item_process_state')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayOrderItemTable);

        $ebayTemplateCategoryTable = $this->getConnection()->newTable($this->getFullTableName('ebay_template_category'))
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
                'category_main_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'category_main_path',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'category_main_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 2]
            )
            ->addColumn(
                'category_main_attribute',
                Table::TYPE_TEXT,
                255,
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
            ->addIndex('marketplace_id', 'marketplace_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayTemplateCategoryTable);

        $ebayTemplateCategorySpecificTable = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_template_category_specific')
        )
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
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'attribute_title',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'value_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'value_ebay_recommended',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['default' => null]
            )
            ->addColumn(
                'value_custom_value',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'value_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addIndex('template_category_id', 'template_category_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayTemplateCategorySpecificTable);

        $ebayTemplateDescriptionTable = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_template_description')
        )
            ->addColumn(
                'template_description_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'is_custom_template',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'title_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'title_template',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'subtitle_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'subtitle_template',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'description_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'description_template',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['nullable' => false]
            )
            ->addColumn(
                'condition_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'condition_value',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'condition_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'condition_note_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'condition_note_template',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'product_details',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'cut_long_titles',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'hit_counter',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'editor_type',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'enhancement',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'gallery_type',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 4]
            )
            ->addColumn(
                'image_main_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'image_main_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'gallery_images_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'gallery_images_limit',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'gallery_images_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'variation_images_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'variation_images_limit',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'variation_images_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'default_image_url',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'variation_configurable_images',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'use_supersize_images',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'watermark_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'watermark_image',
                Table::TYPE_BLOB,
                self::LONG_COLUMN_SIZE,
                ['default' => null]
            )
            ->addColumn(
                'watermark_settings',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addIndex('is_custom_template', 'is_custom_template')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayTemplateDescriptionTable);

        $ebayTemplateOtherCategoryTable = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_template_other_category')
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
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'account_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'category_secondary_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'category_secondary_path',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'category_secondary_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 2]
            )
            ->addColumn(
                'category_secondary_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'store_category_main_id',
                Table::TYPE_DECIMAL,
                [20, 0],
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'store_category_main_path',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'store_category_main_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'store_category_main_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'store_category_secondary_id',
                Table::TYPE_DECIMAL,
                [20, 0],
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'store_category_secondary_path',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'store_category_secondary_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'store_category_secondary_attribute',
                Table::TYPE_TEXT,
                255,
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
            ->addIndex('account_id', 'account_id')
            ->addIndex('marketplace_id', 'marketplace_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayTemplateOtherCategoryTable);

        $ebayTemplatePaymentTable = $this->getConnection()->newTable($this->getFullTableName('ebay_template_payment'))
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
                'title',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'is_custom_template',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'pay_pal_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'pay_pal_email_address',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'pay_pal_immediate_payment',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
            ->addIndex('is_custom_template', 'is_custom_template')
            ->addIndex('marketplace_id', 'marketplace_id')
            ->addIndex('title', 'title')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayTemplatePaymentTable);

        $ebayTemplatePaymentServiceTable = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_template_payment_service')
        )
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'template_payment_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'code_name',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addIndex('template_payment_id', 'template_payment_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayTemplatePaymentServiceTable);

        $ebayTemplateReturnPolicyTable = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_template_return_policy')
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
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'title',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'is_custom_template',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'accepted',
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
                'within',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'holiday_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'shipping_cost',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'restocking_fee',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'description',
                Table::TYPE_TEXT,
                null,
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
            ->addIndex('is_custom_template', 'is_custom_template')
            ->addIndex('marketplace_id', 'marketplace_id')
            ->addIndex('title', 'title')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayTemplateReturnPolicyTable);

        $ebayTemplateSellingFormatTable = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_template_selling_format')
        )
            ->addColumn(
                'template_selling_format_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'is_custom_template',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'listing_type',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'listing_type_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'listing_is_private',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'restricted_to_business',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'default' => 0]
            )
            ->addColumn(
                'duration_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'duration_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'out_of_stock_control',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
                'vat_percent',
                Table::TYPE_FLOAT,
                null,
                ['nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'tax_table_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'tax_category_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'tax_category_value',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'tax_category_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'price_increase_vat_percent',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'price_variation_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'fixed_price_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'fixed_price_coefficient',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'fixed_price_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'start_price_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'start_price_coefficient',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'start_price_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'reserve_price_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'reserve_price_coefficient',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'reserve_price_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'buyitnow_price_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'buyitnow_price_coefficient',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'buyitnow_price_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'price_discount_stp_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'price_discount_stp_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'price_discount_stp_type',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'price_discount_map_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'price_discount_map_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'price_discount_map_exposure_type',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'best_offer_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'best_offer_accept_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'best_offer_accept_value',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'best_offer_accept_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'best_offer_reject_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'best_offer_reject_value',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'best_offer_reject_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'charity',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'ignore_variations',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addIndex('is_custom_template', 'is_custom_template')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayTemplateSellingFormatTable);

        $ebayTemplateShippingTable = $this->getConnection()->newTable($this->getFullTableName('ebay_template_shipping'))
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
                'title',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'is_custom_template',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'country_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'country_custom_value',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'country_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'postal_code_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'postal_code_custom_value',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'postal_code_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'address_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'address_custom_value',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'address_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'dispatch_time',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'local_shipping_rate_table_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'international_shipping_rate_table_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'local_shipping_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'local_shipping_discount_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'local_shipping_discount_profile_id',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'click_and_collect_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'cash_on_delivery_cost',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'international_shipping_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'international_shipping_discount_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'international_shipping_discount_profile_id',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'excluded_locations',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'cross_border_trade',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'global_shipping_program',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
            ->addIndex('is_custom_template', 'is_custom_template')
            ->addIndex('marketplace_id', 'marketplace_id')
            ->addIndex('title', 'title')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayTemplateShippingTable);

        $ebayTemplateShippingCalculatedTable = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_template_shipping_calculated')
        )
            ->addColumn(
                'template_shipping_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'measurement_system',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'package_size_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'package_size_value',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'package_size_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'dimension_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'dimension_width_value',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'dimension_width_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'dimension_length_value',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'dimension_length_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'dimension_depth_value',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'dimension_depth_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'weight_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'weight_minor',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'weight_major',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'weight_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'local_handling_cost',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'international_handling_cost',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayTemplateShippingCalculatedTable);

        $ebayTemplateShippingServiceTable = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_template_shipping_service')
        )
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'template_shipping_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'shipping_type',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'shipping_value',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
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
            ->addColumn(
                'cost_additional_value',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'cost_surcharge_value',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'locations',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'priority',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addIndex('priority', 'priority')
            ->addIndex('template_shipping_id', 'template_shipping_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayTemplateShippingServiceTable);

        $ebayTemplateSynchronizationTable = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_template_synchronization')
        )
            ->addColumn(
                'template_synchronization_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'is_custom_template',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
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
                'list_advanced_rules_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'list_advanced_rules_filters',
                Table::TYPE_TEXT,
                null,
                ['nullable' => true]
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
                'revise_update_title',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'revise_update_sub_title',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'revise_update_description',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'revise_update_images',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'revise_update_specifics',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'revise_update_shipping_services',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'revise_change_category_template',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'revise_change_payment_template',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'revise_change_return_policy_template',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'revise_change_shipping_template',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'revise_change_description_template',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
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
                'relist_send_data',
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
                'relist_advanced_rules_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'relist_advanced_rules_filters',
                Table::TYPE_TEXT,
                null,
                ['nullable' => true]
            )
            ->addColumn(
                'stop_status_disabled',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'stop_out_off_stock',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
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
            ->addColumn(
                'stop_advanced_rules_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'stop_advanced_rules_filters',
                Table::TYPE_TEXT,
                null,
                ['nullable' => true]
            )
            ->addIndex('is_custom_template', 'is_custom_template')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayTemplateSynchronizationTable);
    }

    private function installAmazon()
    {
        $amazonAccountTable = $this->getConnection()->newTable($this->getFullTableName('amazon_account'))
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
                'merchant_id',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'token',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'related_store_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'shipping_mode',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => 1]
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
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'other_listings_mapping_settings',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'other_listings_move_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'other_listings_move_settings',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'magento_orders_settings',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'is_vat_calculation_service_enabled',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_magento_invoice_creation_disabled',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'info',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonAccountTable);

        $amazonAccountRepricingTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_account_repricing')
        )
            ->addColumn(
                'account_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'email',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'token',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'total_products',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'regular_price_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'regular_price_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'regular_price_coefficient',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'regular_price_variation_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'min_price_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'min_price_value',
                Table::TYPE_DECIMAL,
                [14, 2],
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'min_price_percent',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'min_price_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'min_price_coefficient',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'min_price_variation_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'max_price_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'max_price_value',
                Table::TYPE_DECIMAL,
                [14, 2],
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'max_price_percent',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'max_price_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'max_price_coefficient',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'max_price_variation_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'disable_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'disable_mode_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'last_checked_listing_product_update_date',
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
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonAccountRepricingTable);

        $amazonDictionaryCategoryTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_dictionary_category')
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
                ['unsigned' => true, 'nullable' => false]
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
                ['unsigned' => true, 'default' => null]
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
            ->addIndex('parent_category_id', 'parent_category_id')
            ->addIndex('browsenode_id', 'browsenode_id')
            ->addIndex('product_data_nicks', [['name' => 'product_data_nicks', 'size' => 500]])
            ->addIndex('title', 'title')
            ->addIndex('path', [['name' => 'path', 'size' => 500]])
            ->addIndex('is_leaf', 'is_leaf')
            ->setOption('type', 'MYISAM')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonDictionaryCategoryTable);

        $amazonDictionaryCategoryProductDataTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_dictionary_category_product_data')
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
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'browsenode_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'product_data_nick',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'is_applicable',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'required_attributes',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addIndex('marketplace_id', 'marketplace_id')
            ->addIndex('browsenode_id', 'browsenode_id')
            ->addIndex('product_data_nick', 'product_data_nick')
            ->addIndex('is_applicable', 'is_applicable')
            ->setOption('type', 'MYISAM')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonDictionaryCategoryProductDataTable);

        $amazonDictionaryMarketplaceTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_dictionary_marketplace')
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
                self::LONG_COLUMN_SIZE,
                ['default' => null]
            )
            ->addIndex('marketplace_id', 'marketplace_id')
            ->setOption('type', 'MYISAM')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonDictionaryMarketplaceTable);

        $amazonDictionarySpecificTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_dictionary_specific')
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
            ->addIndex('max_occurs', 'max_occurs')
            ->addIndex('min_occurs', 'min_occurs')
            ->setOption('type', 'MYISAM')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonDictionarySpecificTable);

        $amazonDictionaryShippingOverride = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_dictionary_shipping_override')
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

        $amazonItemTable = $this->getConnection()->newTable($this->getFullTableName('amazon_item'))
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
            ->addIndex('product_id', 'product_id')
            ->addIndex('sku', 'sku')
            ->addIndex('store_id', 'store_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonItemTable);

        $amazonListingTable = $this->getConnection()->newTable($this->getFullTableName('amazon_listing'))
            ->addColumn(
                'listing_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'auto_global_adding_description_template_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'auto_website_adding_description_template_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
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
            ->addColumn(
                'sku_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'sku_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'sku_modification_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'sku_modification_custom_value',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'generate_sku_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'general_id_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'general_id_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'worldwide_id_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'worldwide_id_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'search_by_magento_title_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'condition_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'condition_value',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'condition_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'condition_note_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'condition_note_value',
                Table::TYPE_TEXT,
                2000,
                ['nullable' => false]
            )
            ->addColumn(
                'image_main_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'image_main_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'gallery_images_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'gallery_images_limit',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'gallery_images_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'gift_wrap_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'gift_wrap_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'gift_message_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'gift_message_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'handling_time_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'handling_time_value',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'handling_time_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'restock_date_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'restock_date_value',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'restock_date_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addIndex('auto_global_adding_description_template_id', 'auto_global_adding_description_template_id')
            ->addIndex('auto_website_adding_description_template_id', 'auto_website_adding_description_template_id')
            ->addIndex('generate_sku_mode', 'generate_sku_mode')
            ->addIndex('template_selling_format_id', 'template_selling_format_id')
            ->addIndex('template_synchronization_id', 'template_synchronization_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonListingTable);

        $amazonListingAutoCategoryGroupTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_listing_auto_category_group')
        )
            ->addColumn(
                'listing_auto_category_group_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'adding_description_template_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addIndex('adding_description_template_id', 'adding_description_template_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonListingAutoCategoryGroupTable);

        $amazonListingOtherTable = $this->getConnection()->newTable($this->getFullTableName('amazon_listing_other'))
            ->addColumn(
                'listing_other_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'general_id',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'sku',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
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
            ->addColumn(
                'is_afn_channel',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_isbn_general_id',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_repricing',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_repricing_disabled',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addIndex('general_id', 'general_id')
            ->addIndex('sku', 'sku')
            ->addIndex('title', 'title')
            ->addIndex('online_price', 'online_price')
            ->addIndex('online_qty', 'online_qty')
            ->addIndex('is_afn_channel', 'is_afn_channel')
            ->addIndex('is_isbn_general_id', 'is_isbn_general_id')
            ->addIndex('is_repricing', 'is_repricing')
            ->addIndex('is_repricing_disabled', 'is_repricing_disabled')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonListingOtherTable);

        $amazonListingProductTable = $this->getConnection()->newTable($this->getFullTableName('amazon_listing_product'))
            ->addColumn(
                'listing_product_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'template_description_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_shipping_template_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_shipping_override_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_product_tax_code_id',
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
                'general_id',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'general_id_search_info',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'search_settings_status',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'search_settings_data',
                Table::TYPE_TEXT,
                Table::MAX_TEXT_SIZE,
                ['default' => null]
            )
            ->addColumn(
                'sku',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'online_regular_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'online_regular_sale_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'online_regular_sale_price_start_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                'online_regular_sale_price_end_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                'online_business_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'online_business_discounts',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'online_qty',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'is_repricing',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_afn_channel',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_isbn_general_id',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'is_general_id_owner',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'variation_parent_afn_state',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'variation_parent_repricing_state',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'defected_messages',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addIndex('general_id', 'general_id')
            ->addIndex('search_settings_status', 'search_settings_status')
            ->addIndex('is_repricing', 'is_repricing')
            ->addIndex('is_afn_channel', 'is_afn_channel')
            ->addIndex('is_isbn_general_id', 'is_isbn_general_id')
            ->addIndex('is_variation_product_matched', 'is_variation_product_matched')
            ->addIndex('is_variation_channel_matched', 'is_variation_channel_matched')
            ->addIndex('is_variation_product', 'is_variation_product')
            ->addIndex('online_regular_price', 'online_regular_price')
            ->addIndex('online_qty', 'online_qty')
            ->addIndex('online_regular_sale_price', 'online_regular_sale_price')
            ->addIndex('online_business_price', 'online_business_price')
            ->addIndex('sku', 'sku')
            ->addIndex('is_variation_parent', 'is_variation_parent')
            ->addIndex('variation_parent_need_processor', 'variation_parent_need_processor')
            ->addIndex('variation_parent_id', 'variation_parent_id')
            ->addIndex('is_general_id_owner', 'is_general_id_owner')
            ->addIndex('template_shipping_override_id', 'template_shipping_override_id')
            ->addIndex('template_shipping_template_id', 'template_shipping_template_id')
            ->addIndex('template_description_id', 'template_description_id')
            ->addIndex('template_product_tax_code_id', 'template_product_tax_code_id')
            ->addIndex('variation_parent_afn_state', 'variation_parent_afn_state')
            ->addIndex('variation_parent_repricing_state', 'variation_parent_repricing_state')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonListingProductTable);

        $amazonListingProductRepricingTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_listing_product_repricing')
        )
            ->addColumn(
                'listing_product_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'is_online_disabled',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'online_regular_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'online_min_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'online_max_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'is_process_required',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'last_synchronization_date',
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
            ->addIndex('is_online_disabled', 'is_online_disabled')
            ->addIndex('is_process_required', 'is_process_required')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonListingProductRepricingTable);

        $amazonListingProductVariationTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_listing_product_variation')
        )
            ->addColumn(
                'listing_product_variation_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonListingProductVariationTable);

        $amazonListingProductVariationOptionTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_listing_product_variation_option')
        )
            ->addColumn(
                'listing_product_variation_option_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonListingProductVariationOptionTable);

        $amazonIndexerListingProductVariationParentTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_indexer_listing_product_variation_parent')
        )
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
                'component_mode',
                Table::TYPE_TEXT,
                10,
                ['default' => null]
            )
            ->addColumn(
                'min_regular_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'max_regular_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'min_business_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'max_business_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'create_date',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => false]
            )
            ->addIndex('listing_id', 'listing_id')
            ->addIndex('component_mode', 'component_mode')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonIndexerListingProductVariationParentTable);

        $amazonMarketplaceTable = $this->getConnection()->newTable($this->getFullTableName('amazon_marketplace'))
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
            ->addColumn(
                'is_new_asin_available',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'is_merchant_fulfillment_available',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_business_available',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_vat_calculation_service_available',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_product_tax_code_policy_available',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_automatic_token_retrieving_available',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addIndex('is_new_asin_available', 'is_new_asin_available')
            ->addIndex('is_merchant_fulfillment_available', 'is_merchant_fulfillment_available')
            ->addIndex('is_business_available', 'is_business_available')
            ->addIndex('is_vat_calculation_service_available', 'is_vat_calculation_service_available')
            ->addIndex('is_product_tax_code_policy_available', 'is_product_tax_code_policy_available')
            ->addIndex('is_automatic_token_retrieving_available', 'is_automatic_token_retrieving_available')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonMarketplaceTable);

        $amazonOrderTable = $this->getConnection()->newTable($this->getFullTableName('amazon_order'))
            ->addColumn(
                'order_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'amazon_order_id',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'is_afn_channel',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_prime',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_business',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
                'shipping_dates',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
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
                'discount_details',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'qty_shipped',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'qty_unshipped',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'currency',
                Table::TYPE_TEXT,
                10,
                ['nullable' => false]
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
            ->addColumn(
                'merchant_fulfillment_data',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'merchant_fulfillment_label',
                Table::TYPE_BLOB,
                null,
                ['default' => null]
            )
            ->addIndex('amazon_order_id', 'amazon_order_id')
            ->addIndex('is_prime', 'is_prime')
            ->addIndex('is_business', 'is_business')
            ->addIndex('buyer_email', 'buyer_email')
            ->addIndex('buyer_name', 'buyer_name')
            ->addIndex('paid_amount', 'paid_amount')
            ->addIndex('purchase_create_date', 'purchase_create_date')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonOrderTable);

        $amazonOrderItemTable = $this->getConnection()->newTable($this->getFullTableName('amazon_order_item'))
            ->addColumn(
                'order_item_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'amazon_order_item_id',
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
                'sku',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'general_id',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'is_isbn_general_id',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'gift_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'nullable' => false, 'default' => '0.0000']
            )
            ->addColumn(
                'gift_message',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'gift_type',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'tax_details',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'discount_details',
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
                'qty_purchased',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addIndex('general_id', 'general_id')
            ->addIndex('sku', 'sku')
            ->addIndex('title', 'title')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonOrderItemTable);

        $amazonProcessingActionTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_processing_action')
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
        $this->getConnection()->createTable($amazonProcessingActionListSku);

        $amazonTemplateShippingTemplateTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_template_shipping_template')
        )
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
                'template_name_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'template_name_value',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'template_name_attribute',
                Table::TYPE_TEXT,
                255,
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
            ->addIndex('title', 'title')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonTemplateShippingTemplateTable);

        $amazonTemplateShippingOverrideTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_template_shipping_override')
        )
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
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
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
            ->addIndex('template_shipping_override_id', 'template_shipping_override_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonTemplateShippingOverrideServiceTable);

        $amazonTemplateProductTaxCodeTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_template_product_tax_code')
        )
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
                'product_tax_code_mode',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'product_tax_code_value',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'product_tax_code_attribute',
                Table::TYPE_TEXT,
                255,
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
            ->addIndex('title', 'title')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonTemplateProductTaxCodeTable);

        $amazonTemplateDescriptionTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_template_description')
        )
            ->addColumn(
                'template_description_id',
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
                'is_new_asin_accepted',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'default' => 0]
            )
            ->addColumn(
                'product_data_nick',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'category_path',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'browsenode_id',
                Table::TYPE_DECIMAL,
                [20, 0],
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'registered_parameter',
                Table::TYPE_TEXT,
                25,
                ['default' => null]
            )
            ->addColumn(
                'worldwide_id_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'default' => 0]
            )
            ->addColumn(
                'worldwide_id_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addIndex('marketplace_id', 'marketplace_id')
            ->addIndex('is_new_asin_accepted', 'is_new_asin_accepted')
            ->addIndex('product_data_nick', 'product_data_nick')
            ->addIndex('browsenode_id', 'browsenode_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonTemplateDescriptionTable);

        $amazonTemplateDescriptionDefinitionTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_template_description_definition')
        )
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
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'brand_custom_value',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'brand_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'manufacturer_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'manufacturer_custom_value',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'manufacturer_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'manufacturer_part_number_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
                ['nullable' => false]
            )
            ->addColumn(
                'msrp_rrp_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'msrp_rrp_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'item_package_quantity_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'default' => 0]
            )
            ->addColumn(
                'item_package_quantity_custom_value',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'item_package_quantity_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'number_of_items_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'default' => 0]
            )
            ->addColumn(
                'number_of_items_custom_value',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'number_of_items_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'item_dimensions_volume_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'default' => 0]
            )
            ->addColumn(
                'item_dimensions_volume_length_custom_value',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'item_dimensions_volume_width_custom_value',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'item_dimensions_volume_height_custom_value',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'item_dimensions_volume_length_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'item_dimensions_volume_width_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'item_dimensions_volume_height_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'item_dimensions_volume_unit_of_measure_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'default' => 0]
            )
            ->addColumn(
                'item_dimensions_volume_unit_of_measure_custom_value',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'item_dimensions_volume_unit_of_measure_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'item_dimensions_weight_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'default' => 0]
            )
            ->addColumn(
                'item_dimensions_weight_custom_value',
                Table::TYPE_DECIMAL,
                [10, 2],
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'item_dimensions_weight_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'item_dimensions_weight_unit_of_measure_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'default' => 0]
            )
            ->addColumn(
                'item_dimensions_weight_unit_of_measure_custom_value',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'item_dimensions_weight_unit_of_measure_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'package_dimensions_volume_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'default' => 0]
            )
            ->addColumn(
                'package_dimensions_volume_length_custom_value',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'package_dimensions_volume_width_custom_value',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'package_dimensions_volume_height_custom_value',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'package_dimensions_volume_length_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'package_dimensions_volume_width_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'package_dimensions_volume_height_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'package_dimensions_volume_unit_of_measure_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'default' => 0]
            )
            ->addColumn(
                'package_dimensions_volume_unit_of_measure_custom_value',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'package_dimensions_volume_unit_of_measure_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'shipping_weight_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'default' => 0]
            )
            ->addColumn(
                'shipping_weight_custom_value',
                Table::TYPE_DECIMAL,
                [10, 2],
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'shipping_weight_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'shipping_weight_unit_of_measure_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'default' => 1]
            )
            ->addColumn(
                'shipping_weight_unit_of_measure_custom_value',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'shipping_weight_unit_of_measure_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'package_weight_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'default' => 0]
            )
            ->addColumn(
                'package_weight_custom_value',
                Table::TYPE_DECIMAL,
                [10, 2],
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'package_weight_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'package_weight_unit_of_measure_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'default' => 1]
            )
            ->addColumn(
                'package_weight_unit_of_measure_custom_value',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'package_weight_unit_of_measure_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'target_audience_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'target_audience',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'search_terms_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'search_terms',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'bullet_points_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'bullet_points',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'description_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'description_template',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['nullable' => false]
            )
            ->addColumn(
                'image_main_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'gallery_images_limit',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'gallery_images_attribute',
                Table::TYPE_TEXT,
                255,
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
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonTemplateDescriptionDefinitionTable);

        $amazonTemplateDescriptionSpecificTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_template_description_specific')
        )
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'template_description_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
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
                ['unsigned' => true, 'default' => 0]
            )
            ->addColumn(
                'recommended_value',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'custom_value',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'type',
                Table::TYPE_TEXT,
                25,
                ['default' => null]
            )
            ->addColumn(
                'attributes',
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
            ->addIndex('template_description_id', 'template_description_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonTemplateDescriptionSpecificTable);

        $amazonTemplateSellingFormatTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_template_selling_format')
        )
            ->addColumn(
                'template_selling_format_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
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
                'is_regular_customer_allowed',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'is_business_customer_allowed',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'regular_price_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'regular_price_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'regular_price_coefficient',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'regular_map_price_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'regular_map_price_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'regular_sale_price_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'regular_sale_price_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'regular_sale_price_coefficient',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'regular_price_variation_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'regular_sale_price_start_date_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'regular_sale_price_start_date_value',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'regular_sale_price_start_date_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'regular_sale_price_end_date_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'regular_sale_price_end_date_value',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'regular_sale_price_end_date_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'regular_price_vat_percent',
                Table::TYPE_FLOAT,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'business_price_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'business_price_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'business_price_coefficient',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'business_price_variation_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'business_price_vat_percent',
                Table::TYPE_FLOAT,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'business_discounts_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'business_discounts_tier_coefficient',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'business_discounts_tier_customer_group_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonTemplateSellingFormatTable);

        $amazonTemplateSellingFormatBusinessDiscountTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_template_selling_format_business_discount')
        )
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
                'qty',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'attribute',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'coefficient',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addIndex('template_selling_format_id', 'template_selling_format_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonTemplateSellingFormatBusinessDiscountTable);

        $amazonTemplateSynchronizationTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_template_synchronization')
        )
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
                'list_advanced_rules_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'list_advanced_rules_filters',
                Table::TYPE_TEXT,
                null,
                ['nullable' => true]
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
                'revise_update_details',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'revise_update_images',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'revise_change_description_template',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'revise_change_shipping_template',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'revise_change_product_tax_code_template',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
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
                'relist_send_data',
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
                'relist_advanced_rules_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'relist_advanced_rules_filters',
                Table::TYPE_TEXT,
                null,
                ['nullable' => true]
            )
            ->addColumn(
                'stop_status_disabled',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'stop_out_off_stock',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
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
            ->addColumn(
                'stop_advanced_rules_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'stop_advanced_rules_filters',
                Table::TYPE_TEXT,
                null,
                ['nullable' => true]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonTemplateSynchronizationTable);
    }

    //########################################

    private function installWalmart()
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
                'old_private_key',
                Table::TYPE_TEXT,
                null,
                ['nullable' => true]
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
                self::LONG_COLUMN_SIZE,
                ['default' => null]
            )
            ->addColumn(
                'tax_codes',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
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
                'component_mode',
                Table::TYPE_TEXT,
                10,
                ['default' => null]
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
            ->addIndex('component_mode', 'component_mode')
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
                'list_advanced_rules_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'list_advanced_rules_filters',
                Table::TYPE_TEXT,
                null,
                ['nullable' => true]
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
                'relist_advanced_rules_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'relist_advanced_rules_filters',
                Table::TYPE_TEXT,
                null,
                ['nullable' => true]
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
            ->addColumn(
                'stop_advanced_rules_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'stop_advanced_rules_filters',
                Table::TYPE_TEXT,
                null,
                ['nullable' => true]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($walmartTemplateSynchronizationTable);
    }

    //########################################

    private function isInstalled()
    {
        if (!$this->getConnection()->isTableExists($this->getFullTableName('setup'))) {
            return false;
        }

        $setupRow = $this->getConnection()->select()
            ->from($this->getFullTableName('setup'))
            ->where('version_from IS NULL')
            ->where('is_completed = ?', 1)
            ->query()
            ->fetch();

        return $setupRow !== false;
    }

    private function isMaintenanceCanBeIgnored()
    {
        $select = $this->installer->getConnection()
            ->select()
            ->from($this->installer->getTable('core_config_data'), 'value')
            ->where('scope = ?', 'default')
            ->where('scope_id = ?', 0)
            ->where('path = ?', 'm2epro/setup/ignore_maintenace');

        return (bool)$this->installer->getConnection()->fetchOne($select);
    }

    //########################################

    /**
     * @return \Magento\Framework\DB\Adapter\Pdo\Mysql
     */
    private function getConnection()
    {
        return $this->installer->getConnection();
    }

    private function getFullTableName($tableName)
    {
        return $this->helperFactory->getObject('Module_Database_Tables')->getFullName($tableName);
    }

    private function getConfigVersion()
    {
        return $this->moduleList->getOne(Module::IDENTIFIER)['setup_version'];
    }

    //########################################
}
