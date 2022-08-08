<?php
// @codingStandardsIgnoreFile
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Setup\SetupInterface;

/**
 * Installer M2E extension
 */
class Installer
{
    const LONG_COLUMN_SIZE = 16777217;

    /** @var SetupInterface $installer */
    private $installer;

    /** @var \Magento\Framework\App\DeploymentConfig */
    private $deploymentConfig;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var \Ess\M2ePro\Helper\Module\Database\Tables */
    private $tablesHelper;

    /** @var \Magento\Framework\Module\ModuleListInterface */
    private $moduleList;

    /** @var \Ess\M2ePro\Helper\Module\Maintenance */
    private $maintenance;

    /** @var \Ess\M2ePro\Model\ResourceModel\Setup */
    private $setupResource;

    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    /**
     * @param \Magento\Framework\App\DeploymentConfig       $deploymentConfig
     * @param \Magento\Framework\ObjectManagerInterface     $objectManager
     * @param \Ess\M2ePro\Helper\Module\Database\Tables     $tablesHelper
     * @param \Ess\M2ePro\Helper\Module\Maintenance         $maintenance
     * @param \Ess\M2ePro\Model\ResourceModel\Setup         $setupResource
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Ess\M2ePro\Setup\LoggerFactory               $loggerFactory
     */
    public function __construct(
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ess\M2ePro\Helper\Module\Database\Tables $tablesHelper,
        \Ess\M2ePro\Helper\Module\Maintenance $maintenance,
        \Ess\M2ePro\Model\ResourceModel\Setup $setupResource,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Ess\M2ePro\Setup\LoggerFactory $loggerFactory

    ) {
        $this->deploymentConfig = $deploymentConfig;
        $this->objectManager = $objectManager;
        $this->tablesHelper = $tablesHelper;
        $this->maintenance = $maintenance;
        $this->setupResource = $setupResource;
        $this->moduleList = $moduleList;
        $this->logger = $loggerFactory->create();
    }

    //########################################

    /**
     * Module versions from setup_module magento table uses only by magento for run install or upgrade files.
     * We do not use these versions in setup & upgrade logic (only set correct values to it, using m2epro_setup table).
     * So version, that presented in $context parameter, is not used.
     *
     * @param SetupInterface $setup
     */
    public function install(SetupInterface $setup)
    {
        $this->installer = $setup;

        $this->maintenance->enable();
        $this->installer->startSetup();

        try {
            $this->dropTables();

            $setupObject = $this->getCurrentSetupObject();

            $this->installGeneralSchema();
            $this->installGeneralData();

            $this->installEbaySchema();
            $this->installEbayData();

            $this->installAmazonSchema();
            $this->installAmazonData();

            $this->installWalmartSchema();
            $this->installWalmartData();

        } catch (\Throwable $exception) {
            $this->logger->error($exception, ['source' => 'Install']);

            if (isset($setupObject)) {
                $setupObject->setData('profiler_data', $exception->__toString());
                $setupObject->save();
            }

            $this->installer->endSetup();

            return;
        }

        $setupObject->setData('is_completed', 1);
        $setupObject->save();

        $this->maintenance->disable();
        $this->installer->endSetup();
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\RuntimeException
     */
    private function dropTables()
    {
        $tables = $this->getConnection()->getTables(
            $this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX) . 'm2epro_%'
        );

        foreach ($tables as $table) {
            $this->getConnection()->dropTable($table);
        }
    }

    /**
     * @return void
     * @throws \Zend_Db_Exception
     */
    private function installGeneralSchema()
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
            ->addIndex('component_mode', 'component_mode')
            ->addIndex('title', 'title')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($accountTable);

        $moduleConfigTable = $this->getConnection()->newTable($this->getFullTableName('config'))
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->addIndex('component_mode', 'component_mode')
            ->addIndex('marketplace_id', 'marketplace_id')
            ->addIndex('store_id', 'store_id')
            ->addIndex('title', 'title')
            ->addIndex('auto_mode', 'auto_mode')
            ->addIndex('auto_global_adding_mode', 'auto_global_adding_mode')
            ->addIndex('auto_website_adding_mode', 'auto_website_adding_mode')
            ->addIndex('auto_website_deleting_mode', 'auto_website_deleting_mode')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                'create_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addIndex('action', 'action')
            ->addIndex('action_id', 'action_id')
            ->addIndex('component_mode', 'component_mode')
            ->addIndex('initiator', 'initiator')
            ->addIndex('listing_id', 'listing_id')
            ->addIndex('listing_product_id', 'listing_product_id')
            ->addIndex('parent_listing_product_id', 'parent_listing_product_id')
            ->addIndex('listing_title', 'listing_title')
            ->addIndex('product_id', 'product_id')
            ->addIndex('product_title', 'product_title')
            ->addIndex('type', 'type')
            ->addIndex('account_id', 'account_id')
            ->addIndex('marketplace_id', 'marketplace_id')
            ->addIndex('create_date', 'create_date')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($listingOtherTable);

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
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($listingProductVariationOptionTable);

        $listingProductInstruction = $this->getConnection()->newTable(
            $this->getFullTableName('listing_product_instruction')
        )
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
            ->addIndex('create_date', 'create_date')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($listingProductInstruction);

        $listingProductScheduledAction = $this->getConnection()->newTable(
            $this->getFullTableName('listing_product_scheduled_action')
        )
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
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addIndex('component', 'component')
            ->addIndex('action_type', 'action_type')
            ->addIndex('tag', 'tag')
            ->addIndex('create_date', 'create_date')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($listingProductScheduledAction);

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
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->addIndex('component_mode', 'component_mode')
            ->addIndex('order_id', 'order_id')
            ->addIndex('product_id', 'product_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                'create_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addIndex('component_mode', 'component_mode')
            ->addIndex('initiator', 'initiator')
            ->addIndex('order_id', 'order_id')
            ->addIndex('type', 'type')
            ->addIndex('account_id', 'account_id')
            ->addIndex('marketplace_id', 'marketplace_id')
            ->addIndex('create_date', 'create_date')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($orderLogTable);

        $orderNoteTable = $this->getConnection()->newTable($this->getFullTableName('order_note'))
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
            ->addIndex('order_id', 'order_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($orderNoteTable);

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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($orderMatchingTable);

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
                'type',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
            ->addIndex('type', 'type')
            ->addIndex('is_completed', 'is_completed')
            ->addIndex('expiration_date', 'expiration_date')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($requestPendingPartialDataTable);

        $connectorPendingRequesterSingleTable = $this->getConnection()->newTable(
            $this->getFullTableName('connector_command_pending_processing_single')
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($connectorPendingRequesterSingleTable);

        $connectorPendingRequesterPartialTable = $this->getConnection()->newTable(
            $this->getFullTableName('connector_command_pending_processing_partial')
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($connectorPendingRequesterPartialTable);

        $magentoProductWebsitesUpdateTable = $this->getConnection()
            ->newTable($this->getFullTableName('magento_product_websites_update'))
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
                'action',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'website_id',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'create_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addIndex('product_id', 'product_id')
            ->addIndex('action', 'action')
            ->addIndex('create_date', 'create_date')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($magentoProductWebsitesUpdateTable);

        $stopQueueTable = $this->getConnection()->newTable($this->getFullTableName('stop_queue'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
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
            ->addIndex('component_mode', 'component_mode')
            ->addIndex('is_processed', 'is_processed')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($stopQueueTable);

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
                'description',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'detailed_description',
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
                'additional_data',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
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
            ->addIndex('task', 'task')
            ->addIndex('operation_history_id', 'operation_history_id')
            ->addIndex('type', 'type')
            ->addIndex('create_date', 'create_date')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                'class',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'description',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'detailed_description',
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
                'create_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addIndex('type', 'type')
            ->addIndex('class', 'class')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($archivedEntity);
    }

    /**
     * @return void
     * @throws \Exception
     */
    private function installGeneralData()
    {
        $magentoMarketplaceUrl = 'https://marketplace.magento.com/m2e-ebay-amazon-magento2.html';
        $servicingInterval = random_int(43200, 86400);

        $moduleConfig = $this->getConfigModifier();

        $moduleConfig->insert('/', 'is_disabled', '0');
        $moduleConfig->insert('/', 'environment', 'production');
        $moduleConfig->insert('/', 'installation_key', sha1((string)microtime(true)));
        $moduleConfig->insert('/license/', 'key');
        $moduleConfig->insert('/license/', 'status', 1);
        $moduleConfig->insert('/license/domain/', 'real');
        $moduleConfig->insert('/license/domain/', 'valid');
        $moduleConfig->insert('/license/domain/', 'is_valid');
        $moduleConfig->insert('/license/ip/', 'real');
        $moduleConfig->insert('/license/ip/', 'valid');
        $moduleConfig->insert('/license/ip/', 'is_valid');
        $moduleConfig->insert('/license/info/', 'email');
        $moduleConfig->insert('/server/', 'application_key', '02edcc129b6128f5fa52d4ad1202b427996122b6');
        $moduleConfig->insert('/server/location/1/', 'baseurl', 'https://s1.m2epro.com/');
        $moduleConfig->insert('/server/location/', 'default_index', 1);
        $moduleConfig->insert('/server/location/', 'current_index', 1);
        $moduleConfig->insert('/cron/', 'mode', '1');
        $moduleConfig->insert('/cron/', 'runner', 'magento');
        $moduleConfig->insert('/cron/', 'last_access');
        $moduleConfig->insert('/cron/', 'last_runner_change');
        $moduleConfig->insert('/cron/', 'last_executed_slow_task');
        $moduleConfig->insert('/cron/', 'last_executed_task_group');
        $moduleConfig->insert('/cron/service/', 'auth_key');
        $moduleConfig->insert('/cron/service_controller/', 'disabled', '0');
        $moduleConfig->insert('/cron/service_pub/', 'disabled', '0');
        $moduleConfig->insert('/cron/magento/', 'disabled', '0');
        $moduleConfig->insert('/cron/task/system/servicing/synchronize/', 'interval', $servicingInterval);
        $moduleConfig->insert('/logs/clearing/listings/', 'mode', '1');
        $moduleConfig->insert('/logs/clearing/listings/', 'days', '30');
        $moduleConfig->insert('/logs/clearing/synchronizations/', 'mode', '1');
        $moduleConfig->insert('/logs/clearing/synchronizations/', 'days', '30');
        $moduleConfig->insert('/logs/clearing/orders/', 'mode', '1');
        $moduleConfig->insert('/logs/clearing/orders/', 'days', '90');
        $moduleConfig->insert('/logs/clearing/ebay_pickup_store/', 'mode', '1');
        $moduleConfig->insert('/logs/clearing/ebay_pickup_store/', 'days', '30');
        $moduleConfig->insert('/logs/listings/', 'last_action_id', '0');
        $moduleConfig->insert('/logs/ebay_pickup_store/', 'last_action_id', '0');
        $moduleConfig->insert('/logs/grouped/', 'max_records_count', '100000');
        $moduleConfig->insert('/support/', 'documentation_url', 'https://m2e.atlassian.net/wiki/');
        $moduleConfig->insert('/support/', 'clients_portal_url', 'https://clients.m2epro.com/');
        $moduleConfig->insert('/support/', 'website_url', 'https://m2epro.com/');
        $moduleConfig->insert('/support/', 'support_url', 'https://support.m2epro.com/');
        $moduleConfig->insert('/support/', 'magento_marketplace_url', $magentoMarketplaceUrl);
        $moduleConfig->insert('/support/', 'contact_email', 'support@m2epro.com');
        $moduleConfig->insert('/general/configuration/', 'listing_product_inspector_mode', '0');
        $moduleConfig->insert('/general/configuration/', 'view_show_block_notices_mode', '1');
        $moduleConfig->insert('/general/configuration/', 'view_show_products_thumbnails_mode', '1');
        $moduleConfig->insert('/general/configuration/', 'view_products_grid_use_alternative_mysql_select_mode', '0');
        $moduleConfig->insert('/general/configuration/', 'renderer_description_convert_linebreaks_mode', '1');
        $moduleConfig->insert('/general/configuration/', 'other_pay_pal_url', 'paypal.com/cgi-bin/webscr/');
        $moduleConfig->insert('/general/configuration/', 'product_index_mode', '1');
        $moduleConfig->insert('/general/configuration/', 'product_force_qty_mode', '0');
        $moduleConfig->insert('/general/configuration/', 'product_force_qty_value', '10');
        $moduleConfig->insert('/general/configuration/', 'qty_percentage_rounding_greater', '0');
        $moduleConfig->insert('/general/configuration/', 'magento_attribute_price_type_converting_mode', '0');
        $moduleConfig->insert(
            '/general/configuration/',
            'create_with_first_product_options_when_variation_unavailable',
            '1'
        );
        $moduleConfig->insert('/general/configuration/', 'secure_image_url_in_item_description_mode', '0');
        $moduleConfig->insert('/general/configuration/', 'grouped_product_mode', '0');
        $moduleConfig->insert('/magento/product/simple_type/', 'custom_types', '');
        $moduleConfig->insert('/magento/product/downloadable_type/', 'custom_types', '');
        $moduleConfig->insert('/magento/product/configurable_type/', 'custom_types', '');
        $moduleConfig->insert('/magento/product/bundle_type/', 'custom_types', '');
        $moduleConfig->insert('/magento/product/grouped_type/', 'custom_types', '');
        $moduleConfig->insert('/health_status/notification/', 'mode', 1);
        $moduleConfig->insert('/health_status/notification/', 'email', '');
        $moduleConfig->insert('/health_status/notification/', 'level', 40);

        $this->getConnection()->insertMultiple(
            $this->getFullTableName('wizard'),
            [
                [
                    'nick'     => 'installationEbay',
                    'view'     => 'ebay',
                    'status'   => 0,
                    'step'     => null,
                    'type'     => 1,
                    'priority' => 2,
                ],
                [
                    'nick'     => 'installationAmazon',
                    'view'     => 'amazon',
                    'status'   => 0,
                    'step'     => null,
                    'type'     => 1,
                    'priority' => 3,
                ],
                [
                    'nick'     => 'installationWalmart',
                    'view'     => 'walmart',
                    'status'   => 0,
                    'step'     => null,
                    'type'     => 1,
                    'priority' => 4,
                ],
                [
                    'nick'     => 'migrationFromMagento1',
                    'view'     => '*',
                    'status'   => 2,
                    'step'     => null,
                    'type'     => 1,
                    'priority' => 1,
                ],
                [
                    'nick'     => 'migrationToInnodb',
                    'view'     => '*',
                    'status'   => 3,
                    'step'     => null,
                    'type'     => 1,
                    'priority' => 5,
                ]
            ]
        );
    }

    /**
     * @return void
     * @throws \Zend_Db_Exception
     */
    private function installEbaySchema()
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
                'sell_api_token_session',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'sell_api_token_expired_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                'marketplaces_data',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'inventory_last_synchronization',
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
                'rate_tables',
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
                'create_magento_invoice',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'create_magento_shipment',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'skip_evtin',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'messages_receive',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 0]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                'description',
                Table::TYPE_TEXT,
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
            ->addIndex('type', 'type')
            ->addIndex('create_date', 'create_date')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($ebayAccountPickupStoreLogTable);

        $ebayProcessingActionTable = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_listing_product_action_processing')
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
                'listing_product_id',
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
            ->addIndex('listing_product_id', 'listing_product_id')
            ->addIndex('processing_id', 'processing_id')
            ->addIndex('type', 'type')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->addIndex('path', [['name' => 'path', 'size' => 255]])
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->addIndex('item_id', 'item_id')
            ->addIndex('account_id', 'account_id')
            ->addIndex('marketplace_id', 'marketplace_id')
            ->addIndex('product_id', 'product_id')
            ->addIndex('store_id', 'store_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($ebayItemTable);

        $ebayListingTable = $this->getConnection()->newTable($this->getFullTableName('ebay_listing'))
            ->addColumn(
                'listing_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'auto_global_adding_template_category_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'auto_global_adding_template_category_secondary_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'auto_global_adding_template_store_category_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'auto_global_adding_template_store_category_secondary_id',
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
                'auto_website_adding_template_category_secondary_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'auto_website_adding_template_store_category_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'auto_website_adding_template_store_category_secondary_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_shipping_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_return_policy_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_description_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_selling_format_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_synchronization_id',
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
            ->addIndex(
                'auto_global_adding_template_category_secondary_id',
                'auto_global_adding_template_category_secondary_id'
            )
            ->addIndex(
                'auto_global_adding_template_store_category_id',
                'auto_global_adding_template_store_category_id'
            )
            ->addIndex(
                'auto_global_adding_template_store_category_secondary_id',
                'auto_global_adding_template_store_category_secondary_id'
            )
            ->addIndex('auto_website_adding_template_category_id', 'auto_website_adding_template_category_id')
            ->addIndex(
                'auto_website_adding_template_category_secondary_id',
                'auto_website_adding_template_category_secondary_id'
            )
            ->addIndex(
                'auto_website_adding_template_store_category_id',
                'auto_website_adding_template_store_category_id'
            )
            ->addIndex(
                'auto_website_adding_template_store_category_secondary_id',
                'auto_website_adding_template_store_category_secondary_id'
            )
            ->addIndex('template_description_id', 'template_description_id')
            ->addIndex('template_return_policy_id', 'template_return_policy_id')
            ->addIndex('template_selling_format_id', 'template_selling_format_id')
            ->addIndex('template_shipping_id', 'template_shipping_id')
            ->addIndex('template_synchronization_id', 'template_synchronization_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                'adding_template_category_secondary_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'adding_template_store_category_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'adding_template_store_category_secondary_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addIndex('adding_template_category_id', 'adding_template_category_id')
            ->addIndex('adding_template_category_secondary_id', 'adding_template_category_secondary_id')
            ->addIndex('adding_template_store_category_id', 'adding_template_store_category_id')
            ->addIndex('adding_template_store_category_secondary_id', 'adding_template_store_category_secondary_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                'online_main_category',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'online_categories_data',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['default' => null]
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                'template_category_secondary_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_store_category_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'template_store_category_secondary_id',
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
                'online_sub_title',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'online_description',
                Table::TYPE_TEXT,
                40,
                ['default' => null]
            )
            ->addColumn(
                'online_images',
                Table::TYPE_TEXT,
                40,
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
                'online_main_category',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'online_categories_data',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['default' => null]
            )
            ->addColumn(
                'online_parts_data',
                Table::TYPE_TEXT,
                32,
                ['default' => null]
            )
            ->addColumn(
                'online_shipping_data',
                Table::TYPE_TEXT,
                40,
                ['default' => null]
            )
            ->addColumn(
                'online_return_data',
                Table::TYPE_TEXT,
                40,
                ['default' => null]
            )
            ->addColumn(
                'online_other_data',
                Table::TYPE_TEXT,
                40,
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
            ->addIndex('ebay_item_id', 'ebay_item_id')
            ->addIndex('item_uuid', 'item_uuid')
            ->addIndex('is_duplicate', 'is_duplicate')
            ->addIndex('online_is_variation', 'online_is_variation')
            ->addIndex('online_is_auction_type', 'online_is_auction_type')
            ->addIndex('end_date', 'end_date')
            ->addIndex('online_bids', 'online_bids')
            ->addIndex('online_buyitnow_price', 'online_buyitnow_price')
            ->addIndex('online_main_category', 'online_main_category')
            ->addIndex('online_qty', 'online_qty')
            ->addIndex('online_qty_sold', 'online_qty_sold')
            ->addIndex('online_reserve_price', 'online_reserve_price')
            ->addIndex('online_sku', 'online_sku')
            ->addIndex('online_current_price', 'online_current_price')
            ->addIndex('online_start_price', 'online_start_price')
            ->addIndex('online_title', 'online_title')
            ->addIndex('start_date', 'start_date')
            ->addIndex('template_category_id', 'template_category_id')
            ->addIndex('template_category_secondary_id', 'template_category_secondary_id')
            ->addIndex('template_store_category_id', 'template_store_category_id')
            ->addIndex('template_store_category_secondary_id', 'template_store_category_secondary_id')
            ->addIndex('template_description_id', 'template_description_id')
            ->addIndex('template_description_mode', 'template_description_mode')
            ->addIndex('template_return_policy_id', 'template_return_policy_id')
            ->addIndex('template_return_policy_mode', 'template_return_policy_mode')
            ->addIndex('template_selling_format_id', 'template_selling_format_id')
            ->addIndex('template_selling_format_mode', 'template_selling_format_mode')
            ->addIndex('template_shipping_id', 'template_shipping_id')
            ->addIndex('template_shipping_mode', 'template_shipping_mode')
            ->addIndex('template_synchronization_id', 'template_synchronization_id')
            ->addIndex('template_synchronization_mode', 'template_synchronization_mode')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            $this->getFullTableName('ebay_listing_product_indexer_variation_parent')
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
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                'is_managed_payments',
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
                'is_in_store_pickup',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_return_description',
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
            ->addIndex('is_managed_payments', 'is_managed_payments')
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
            ->addIndex('is_in_store_pickup', 'is_in_store_pickup')
            ->addIndex('is_return_description', 'is_return_description')
            ->addIndex('is_epid', 'is_epid')
            ->addIndex('is_ktype', 'is_ktype')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                'street_name',
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
            ->addIndex('street_name', 'street_name')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                ['unsigned' => true, 'nullable' => false, 'default' => '0.0000']
            )
            ->addColumn(
                'final_fee',
                Table::TYPE_DECIMAL,
                [10, 2],
                ['unsigned' => true, 'nullable' => true, 'default' => null]
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
                'cancellation_status',
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
                'shipping_date_to',
                Table::TYPE_DATETIME,
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
                'tax_reference',
                Table::TYPE_TEXT,
                72,
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
            ->addIndex('shipping_date_to', 'shipping_date_to')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                'is_custom_template',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'category_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'category_path',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'category_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 2]
            )
            ->addColumn(
                'category_attribute',
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
            ->addIndex('is_custom_template', 'is_custom_template')
            ->addIndex('marketplace_id', 'marketplace_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($ebayTemplateDescriptionTable);

        $ebayTemplateOtherCategoryTable = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_template_store_category')
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
                'category_id',
                Table::TYPE_DECIMAL,
                [20, 0],
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'category_path',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'category_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 2]
            )
            ->addColumn(
                'category_attribute',
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
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($ebayTemplateOtherCategoryTable);

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
                'shipping_cost',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'international_accepted',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'international_option',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'international_within',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'international_shipping_cost',
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                'lot_size_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'lot_size_custom_value',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'lot_size_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'vat_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'vat_percent',
                Table::TYPE_DECIMAL,
                [10, 2],
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
                'fixed_price_modifier',
                Table::TYPE_TEXT,
                null,
                ['nullable' => true]
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                'dispatch_time_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'dispatch_time_value',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'dispatch_time_attribute',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'local_shipping_rate_table',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'international_shipping_rate_table',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                'local_shipping_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'local_shipping_discount_promotional_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'local_shipping_discount_combined_profile_id',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
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
                'international_shipping_discount_promotional_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'international_shipping_discount_combined_profile_id',
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                'revise_update_categories',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'revise_update_parts',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'revise_update_shipping',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'revise_update_return',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'revise_update_return',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'revise_update_other',
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
                ['unsigned' => true, 'nullable' => false]
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($ebayTemplateSynchronizationTable);
    }

    /**
     * @return void
     */
    private function installEbayData()
    {
        $moduleConfig = $this->getConfigModifier();

        $moduleConfig->insert('/component/ebay/', 'mode', '1');
        $moduleConfig->insert('/cron/task/ebay/listing/product/process_instructions/', 'mode', '1');
        $moduleConfig->insert('/listing/product/inspector/ebay/', 'max_allowed_instructions_count', '2000');
        $moduleConfig->insert('/ebay/listing/product/instructions/cron/', 'listings_products_per_one_time', '1000');
        $moduleConfig->insert('/ebay/listing/product/scheduled_actions/', 'max_prepared_actions_count', '3000');
        $moduleConfig->insert('/ebay/order/settings/marketplace_8/', 'use_first_street_line_as_company', '1');
        $moduleConfig->insert('/ebay/configuration/', 'prevent_item_duplicates_mode', '1');
        $moduleConfig->insert('/ebay/configuration/', 'variation_mpn_can_be_changed', '0');
        $moduleConfig->insert('/ebay/configuration/', 'motors_epids_attribute');
        $moduleConfig->insert('/ebay/configuration/', 'uk_epids_attribute');
        $moduleConfig->insert('/ebay/configuration/', 'de_epids_attribute');
        $moduleConfig->insert('/ebay/configuration/', 'au_epids_attribute');
        $moduleConfig->insert('/ebay/configuration/', 'it_epids_attribute');
        $moduleConfig->insert('/ebay/configuration/', 'ktypes_attribute');
        $moduleConfig->insert('/ebay/configuration/', 'upload_images_mode', 2);
        $moduleConfig->insert('/ebay/configuration/', 'view_template_selling_format_show_tax_category', '0');
        $moduleConfig->insert('/ebay/configuration/', 'feedback_notification_mode', '0');
        $moduleConfig->insert('/ebay/configuration/', 'feedback_notification_last_check');
        $moduleConfig->insert('/ebay/configuration/', 'upc_mode', '0');
        $moduleConfig->insert('/ebay/configuration/', 'upc_custom_attribute');
        $moduleConfig->insert('/ebay/configuration/', 'ean_mode', '0');
        $moduleConfig->insert('/ebay/configuration/', 'ean_custom_attribute');
        $moduleConfig->insert('/ebay/configuration/', 'isbn_mode', '0');
        $moduleConfig->insert('/ebay/configuration/', 'isbn_custom_attribute');
        $moduleConfig->insert('/ebay/configuration/', 'epid_mode', '0');
        $moduleConfig->insert('/ebay/configuration/', 'epid_custom_attribute');

        $this->getConnection()->insertMultiple(
            $this->getFullTableName('marketplace'),
            [
                [
                    'id'             => 1,
                    'native_id'      => 0,
                    'title'          => 'United States',
                    'code'           => 'US',
                    'url'            => 'ebay.com',
                    'status'         => 0,
                    'sorder'         => 1,
                    'group_title'    => 'America',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 2,
                    'native_id'      => 2,
                    'title'          => 'Canada',
                    'code'           => 'Canada',
                    'url'            => 'ebay.ca',
                    'status'         => 0,
                    'sorder'         => 8,
                    'group_title'    => 'America',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 3,
                    'native_id'      => 3,
                    'title'          => 'United Kingdom',
                    'code'           => 'UK',
                    'url'            => 'ebay.co.uk',
                    'status'         => 0,
                    'sorder'         => 2,
                    'group_title'    => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 4,
                    'native_id'      => 15,
                    'title'          => 'Australia',
                    'code'           => 'Australia',
                    'url'            => 'ebay.com.au',
                    'status'         => 0,
                    'sorder'         => 4,
                    'group_title'    => 'Asia / Pacific',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 5,
                    'native_id'      => 16,
                    'title'          => 'Austria',
                    'code'           => 'Austria',
                    'url'            => 'ebay.at',
                    'status'         => 0,
                    'sorder'         => 5,
                    'group_title'    => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 6,
                    'native_id'      => 23,
                    'title'          => 'Belgium (French)',
                    'code'           => 'Belgium_French',
                    'url'            => 'befr.ebay.be',
                    'status'         => 0,
                    'sorder'         => 7,
                    'group_title'    => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 7,
                    'native_id'      => 71,
                    'title'          => 'France',
                    'code'           => 'France',
                    'url'            => 'ebay.fr',
                    'status'         => 0,
                    'sorder'         => 10,
                    'group_title'    => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 8,
                    'native_id'      => 77,
                    'title'          => 'Germany',
                    'code'           => 'Germany',
                    'url'            => 'ebay.de',
                    'status'         => 0,
                    'sorder'         => 3,
                    'group_title'    => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 9,
                    'native_id'      => 100,
                    'title'          => 'eBay Motors',
                    'code'           => 'eBayMotors',
                    'url'            => 'ebay.com/motors',
                    'status'         => 0,
                    'sorder'         => 23,
                    'group_title'    => 'Other',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 10,
                    'native_id'      => 101,
                    'title'          => 'Italy',
                    'code'           => 'Italy',
                    'url'            => 'ebay.it',
                    'status'         => 0,
                    'sorder'         => 14,
                    'group_title'    => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 11,
                    'native_id'      => 123,
                    'title'          => 'Belgium (Dutch)',
                    'code'           => 'Belgium_Dutch',
                    'url'            => 'benl.ebay.be',
                    'status'         => 0,
                    'sorder'         => 6,
                    'group_title'    => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 12,
                    'native_id'      => 146,
                    'title'          => 'Netherlands',
                    'code'           => 'Netherlands',
                    'url'            => 'ebay.nl',
                    'status'         => 0,
                    'sorder'         => 16,
                    'group_title'    => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 13,
                    'native_id'      => 186,
                    'title'          => 'Spain',
                    'code'           => 'Spain',
                    'url'            => 'ebay.es',
                    'status'         => 0,
                    'sorder'         => 19,
                    'group_title'    => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 14,
                    'native_id'      => 193,
                    'title'          => 'Switzerland',
                    'code'           => 'Switzerland',
                    'url'            => 'ebay.ch',
                    'status'         => 0,
                    'sorder'         => 22,
                    'group_title'    => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 15,
                    'native_id'      => 201,
                    'title'          => 'Hong Kong',
                    'code'           => 'HongKong',
                    'url'            => 'ebay.com.hk',
                    'status'         => 0,
                    'sorder'         => 11,
                    'group_title'    => 'Asia / Pacific',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 16,
                    'native_id'      => 203,
                    'title'          => 'India',
                    'code'           => 'India',
                    'url'            => 'ebay.in',
                    'status'         => 0,
                    'sorder'         => 12,
                    'group_title'    => 'Asia / Pacific',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 17,
                    'native_id'      => 205,
                    'title'          => 'Ireland',
                    'code'           => 'Ireland',
                    'url'            => 'ebay.ie',
                    'status'         => 0,
                    'sorder'         => 13,
                    'group_title'    => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 18,
                    'native_id'      => 207,
                    'title'          => 'Malaysia',
                    'code'           => 'Malaysia',
                    'url'            => 'ebay.com.my',
                    'status'         => 0,
                    'sorder'         => 15,
                    'group_title'    => 'Asia / Pacific',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 19,
                    'native_id'      => 210,
                    'title'          => 'Canada (French)',
                    'code'           => 'CanadaFrench',
                    'url'            => 'cafr.ebay.ca',
                    'status'         => 0,
                    'sorder'         => 9,
                    'group_title'    => 'America',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 20,
                    'native_id'      => 211,
                    'title'          => 'Philippines',
                    'code'           => 'Philippines',
                    'url'            => 'ebay.ph',
                    'status'         => 0,
                    'sorder'         => 17,
                    'group_title'    => 'Asia / Pacific',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 21,
                    'native_id'      => 212,
                    'title'          => 'Poland',
                    'code'           => 'Poland',
                    'url'            => 'ebay.pl',
                    'status'         => 0,
                    'sorder'         => 18,
                    'group_title'    => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 22,
                    'native_id'      => 216,
                    'title'          => 'Singapore',
                    'code'           => 'Singapore',
                    'url'            => 'ebay.com.sg',
                    'status'         => 0,
                    'sorder'         => 20,
                    'group_title'    => 'Asia / Pacific',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ]
            ]
        );

        $this->getConnection()->insertMultiple(
            $this->getFullTableName('ebay_marketplace'),
            [
                [
                    'marketplace_id'                       => 1,
                    'currency'                             => 'USD',
                    'origin_country'                       => 'us',
                    'language_code'                        => 'en_US',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 1,
                    'is_calculated_shipping'               => 1,
                    'is_tax_table'                         => 1,
                    'is_vat'                               => 0,
                    'is_stp'                               => 1,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 1,
                    'is_local_shipping_rate_table'         => 1,
                    'is_international_shipping_rate_table' => 1,
                    'is_english_measurement_system'        => 1,
                    'is_metric_measurement_system'         => 0,
                    'is_managed_payments'                  => 1,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 1,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 1,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 2,
                    'currency'                             => 'CAD',
                    'origin_country'                       => 'ca',
                    'language_code'                        => 'en_CA',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 1,
                    'is_calculated_shipping'               => 1,
                    'is_tax_table'                         => 1,
                    'is_vat'                               => 0,
                    'is_stp'                               => 1,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 1,
                    'is_international_shipping_rate_table' => 1,
                    'is_english_measurement_system'        => 1,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 1,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 1,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 3,
                    'currency'                             => 'GBP',
                    'origin_country'                       => 'gb',
                    'language_code'                        => 'en_GB',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 1,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 1,
                    'is_stp'                               => 1,
                    'is_stp_advanced'                      => 1,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 1,
                    'is_international_shipping_rate_table' => 1,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 1,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 1,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 1,
                    'is_return_description'                => 0,
                    'is_epid'                              => 1,
                    'is_ktype'                             => 1
                ],
                [
                    'marketplace_id'                       => 4,
                    'currency'                             => 'AUD',
                    'origin_country'                       => 'au',
                    'language_code'                        => 'en_AU',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 1,
                    'is_calculated_shipping'               => 1,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 0,
                    'is_stp'                               => 1,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 1,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 1,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 1,
                    'is_return_description'                => 0,
                    'is_epid'                              => 1,
                    'is_ktype'                             => 1
                ],
                [
                    'marketplace_id'                       => 5,
                    'currency'                             => 'EUR',
                    'origin_country'                       => 'at',
                    'language_code'                        => 'de_AT',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 1,
                    'is_stp'                               => 0,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 0,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 1,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 6,
                    'currency'                             => 'EUR',
                    'origin_country'                       => 'be',
                    'language_code'                        => 'nl_BE',
                    'is_multivariation'                    => 0,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 1,
                    'is_stp'                               => 0,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 0,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 7,
                    'currency'                             => 'EUR',
                    'origin_country'                       => 'fr',
                    'language_code'                        => 'fr_FR',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 1,
                    'is_stp'                               => 1,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 1,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 1,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 1
                ],
                [
                    'marketplace_id'                       => 8,
                    'currency'                             => 'EUR',
                    'origin_country'                       => 'de',
                    'language_code'                        => 'de_DE',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 1,
                    'is_stp'                               => 1,
                    'is_stp_advanced'                      => 1,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 1,
                    'is_international_shipping_rate_table' => 1,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 1,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 1,
                    'is_return_description'                => 1,
                    'is_epid'                              => 1,
                    'is_ktype'                             => 1
                ],
                [
                    'marketplace_id'                       => 9,
                    'currency'                             => 'USD',
                    'origin_country'                       => 'us',
                    'language_code'                        => 'en_US',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 1,
                    'is_tax_table'                         => 1,
                    'is_vat'                               => 0,
                    'is_stp'                               => 1,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 1,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 1,
                    'is_metric_measurement_system'         => 0,
                    'is_managed_payments'                  => 1,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 1,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 0,
                    'is_epid'                              => 1,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 10,
                    'currency'                             => 'EUR',
                    'origin_country'                       => 'it',
                    'language_code'                        => 'it_IT',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 1,
                    'is_stp'                               => 1,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 1,
                    'is_international_shipping_rate_table' => 1,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 1,
                    'is_cash_on_delivery'                  => 1,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 1,
                    'is_epid'                              => 1,
                    'is_ktype'                             => 1
                ],
                [
                    'marketplace_id'                       => 11,
                    'currency'                             => 'EUR',
                    'origin_country'                       => 'be',
                    'language_code'                        => 'fr_BE',
                    'is_multivariation'                    => 0,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 1,
                    'is_stp'                               => 0,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 0,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 12,
                    'currency'                             => 'EUR',
                    'origin_country'                       => 'nl',
                    'language_code'                        => 'nl_NL',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 1,
                    'is_stp'                               => 0,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 0,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 13,
                    'currency'                             => 'EUR',
                    'origin_country'                       => 'es',
                    'language_code'                        => 'es_ES',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 1,
                    'is_stp'                               => 1,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 1,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 1,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 1
                ],
                [
                    'marketplace_id'                       => 14,
                    'currency'                             => 'CHF',
                    'origin_country'                       => 'ch',
                    'language_code'                        => 'fr_CH',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 1,
                    'is_stp'                               => 0,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 0,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 15,
                    'currency'                             => 'HKD',
                    'origin_country'                       => 'hk',
                    'language_code'                        => 'zh_HK',
                    'is_multivariation'                    => 0,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 0,
                    'is_stp'                               => 0,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 0,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 16,
                    'currency'                             => 'INR',
                    'origin_country'                       => 'in',
                    'language_code'                        => 'hi_IN',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 1,
                    'is_stp'                               => 0,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 0,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 17,
                    'currency'                             => 'EUR',
                    'origin_country'                       => 'ie',
                    'language_code'                        => 'en_IE',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 1,
                    'is_stp'                               => 0,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 0,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 18,
                    'currency'                             => 'MYR',
                    'origin_country'                       => 'my',
                    'language_code'                        => 'ms_MY',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 0,
                    'is_stp'                               => 0,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 0,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 19,
                    'currency'                             => 'CAD',
                    'origin_country'                       => 'ca',
                    'language_code'                        => 'fr_CA',
                    'is_multivariation'                    => 0,
                    'is_freight_shipping'                  => 1,
                    'is_calculated_shipping'               => 1,
                    'is_tax_table'                         => 1,
                    'is_vat'                               => 0,
                    'is_stp'                               => 1,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 1,
                    'is_international_shipping_rate_table' => 1,
                    'is_english_measurement_system'        => 1,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 0,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 20,
                    'currency'                             => 'PHP',
                    'origin_country'                       => 'ph',
                    'language_code'                        => 'fil_PH',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 0,
                    'is_stp'                               => 0,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 0,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 21,
                    'currency'                             => 'PLN',
                    'origin_country'                       => 'pl',
                    'language_code'                        => 'pl_PL',
                    'is_multivariation'                    => 0,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 0,
                    'is_stp'                               => 0,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 0,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 22,
                    'currency'                             => 'SGD',
                    'origin_country'                       => 'sg',
                    'language_code'                        => 'zh_SG',
                    'is_multivariation'                    => 0,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 0,
                    'is_stp'                               => 0,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 0,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ]
            ]
        );
    }

    /**
     * @return void
     * @throws \Zend_Db_Exception
     */
    private function installAmazonSchema()
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
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'other_listings_mapping_settings',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'inventory_last_synchronization',
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
                'auto_invoicing',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'invoice_generation',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'create_magento_invoice',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'create_magento_shipment',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'remote_fulfillment_program_mode',
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->addIndex('browsenode_id', 'browsenode_id')
            ->addIndex('category_id', 'category_id')
            ->addIndex('is_leaf', 'is_leaf')
            ->addIndex('marketplace_id', 'marketplace_id')
            ->addIndex('path', [['name' => 'path', 'size' => 255]])
            ->addIndex('parent_category_id', 'parent_category_id')
            ->addIndex('title', 'title')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                Table::TYPE_DECIMAL,
                [20, 0],
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
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->addIndex('max_occurs', 'max_occurs')
            ->addIndex('min_occurs', 'min_occurs')
            ->addIndex('parent_specific_id', 'parent_specific_id')
            ->addIndex('title', 'title')
            ->addIndex('type', 'type')
            ->addIndex('specific_id', 'specific_id')
            ->addIndex('xml_tag', 'xml_tag')
            ->addIndex('xpath', 'xpath')
            ->addIndex('product_data_nick', 'product_data_nick')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($amazonDictionarySpecificTable);

        $amazonInventorySkuTable = $this->getConnection()->newTable($this->getFullTableName('amazon_inventory_sku'))
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
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($amazonInventorySkuTable);

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
            ->addIndex('marketplace_id', 'marketplace_id')
            ->addIndex('product_id', 'product_id')
            ->addIndex('sku', 'sku')
            ->addIndex('store_id', 'store_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                'template_shipping_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true]
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
            ->addColumn(
                'product_add_ids',
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addIndex('auto_global_adding_description_template_id', 'auto_global_adding_description_template_id')
            ->addIndex('auto_website_adding_description_template_id', 'auto_website_adding_description_template_id')
            ->addIndex('generate_sku_mode', 'generate_sku_mode')
            ->addIndex('template_selling_format_id', 'template_selling_format_id')
            ->addIndex('template_synchronization_id', 'template_synchronization_id')
            ->addIndex('template_shipping_id', 'template_shipping_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                null,
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
            ->addColumn(
                'is_repricing_inactive',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addIndex('general_id', 'general_id')
            ->addIndex('is_afn_channel', 'is_afn_channel')
            ->addIndex('is_isbn_general_id', 'is_isbn_general_id')
            ->addIndex('is_repricing', 'is_repricing')
            ->addIndex('is_repricing_disabled', 'is_repricing_disabled')
            ->addIndex('is_repricing_inactive', 'is_repricing_inactive')
            ->addIndex('online_price', 'online_price')
            ->addIndex('online_qty', 'online_qty')
            ->addIndex('sku', 'sku')
            ->addIndex('title', [['name' => 'title', 'size' => 255]])
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                'template_shipping_id',
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
                'online_handling_time',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'online_restock_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                'online_details_data',
                Table::TYPE_TEXT,
                40,
                ['default' => null]
            )
            ->addColumn(
                'online_images_data',
                Table::TYPE_TEXT,
                40,
                ['default' => null]
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
            ->addColumn(
                'list_date',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => true]
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
            ->addIndex('variation_parent_afn_state', 'variation_parent_afn_state')
            ->addIndex('variation_parent_repricing_state', 'variation_parent_repricing_state')
            ->addIndex('template_shipping_id', 'template_shipping_id')
            ->addIndex('template_product_tax_code_id', 'template_product_tax_code_id')
            ->addIndex('template_description_id', 'template_description_id')
            ->addIndex('list_date', 'list_date')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_online_inactive',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
                'last_updated_regular_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'last_updated_min_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'last_updated_max_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'last_updated_is_disabled',
                Table::TYPE_SMALLINT,
                null,
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
            ->addIndex('is_online_inactive', 'is_online_inactive')
            ->addIndex('is_process_required', 'is_process_required')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($amazonListingProductVariationOptionTable);

        $amazonIndexerListingProductVariationParentTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_listing_product_indexer_variation_parent')
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
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->addIndex('is_new_asin_available', 'is_new_asin_available')
            ->addIndex('is_merchant_fulfillment_available', 'is_merchant_fulfillment_available')
            ->addIndex('is_business_available', 'is_business_available')
            ->addIndex('is_vat_calculation_service_available', 'is_vat_calculation_service_available')
            ->addIndex('is_product_tax_code_policy_available', 'is_product_tax_code_policy_available')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                'seller_order_id',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
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
                'is_invoice_sent',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_credit_memo_sent',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'invoice_data_report',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['default' => null]
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
                'shipping_date_to',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                'delivery_date_to',
                Table::TYPE_DATETIME,
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
                'ioss_number',
                Table::TYPE_TEXT,
                72,
                ['default' => null]
            )
            ->addColumn(
                'tax_registration_id',
                Table::TYPE_TEXT,
                72,
                ['default' => null]
            )
            ->addColumn(
                'is_buyer_requested_cancel',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'buyer_cancel_reason',
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
            ->addIndex('seller_order_id', 'seller_order_id')
            ->addIndex('is_prime', 'is_prime')
            ->addIndex('is_business', 'is_business')
            ->addIndex('is_invoice_sent', 'is_invoice_sent')
            ->addIndex('is_credit_memo_sent', 'is_credit_memo_sent')
            ->addIndex('buyer_email', 'buyer_email')
            ->addIndex('buyer_name', 'buyer_name')
            ->addIndex('paid_amount', 'paid_amount')
            ->addIndex('purchase_create_date', 'purchase_create_date')
            ->addIndex('shipping_date_to', 'shipping_date_to')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                'shipping_price',
                Table::TYPE_DECIMAL,
                [12, 4],
                ['unsigned' => true, 'nullable' => false, 'default' => '0.0000']
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
            ->addColumn(
                'fulfillment_center_id',
                Table::TYPE_TEXT,
                10,
                ['default' => null]
            )
            ->addIndex('general_id', 'general_id')
            ->addIndex('sku', 'sku')
            ->addIndex('title', 'title')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($amazonOrderItemTable);

        $amazonOrderInvoiceTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_order_invoice')
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
                'document_type',
                Table::TYPE_TEXT,
                64,
                ['default' => null]
            )
            ->addColumn(
                'document_number',
                Table::TYPE_TEXT,
                64,
                ['default' => null]
            )
            ->addColumn(
                'document_data',
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
            ->addIndex('order_id', 'order_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($amazonOrderInvoiceTable);

        $amazonProcessingActionTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_listing_product_action_processing')
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
                ['unsigned' => true, 'nullable' => true]
            )
            ->addColumn(
                'listing_product_id',
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
                'is_prepared',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'group_hash',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true, 'default' => null]
            )
            ->addColumn(
                'request_data',
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
            ->addIndex('listing_product_id', 'listing_product_id')
            ->addIndex('processing_id', 'processing_id')
            ->addIndex('request_pending_single_id', 'request_pending_single_id')
            ->addIndex('type', 'type')
            ->addIndex('is_prepared', 'is_prepared')
            ->addIndex('group_hash', 'group_hash')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($amazonProcessingActionTable);

        $amazonProcessingActionListSku = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_listing_product_action_processing_list_sku')
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
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($amazonProcessingActionListSku);

        $amazonOrderActionProcessing = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_order_action_processing')
        )
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
            ->addIndex('type', 'type')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($amazonOrderActionProcessing);

        $amazonTemplateShippingTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_template_shipping')
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($amazonTemplateShippingTable);

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
                ['nullable' => false]
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                'msrp_rrp_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'default' => 0]
            )
            ->addColumn(
                'msrp_rrp_custom_attribute',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true, 'default' => null]
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                Table::TYPE_DECIMAL,
                [10, 2],
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
                Table::TYPE_DECIMAL,
                [10, 2],
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                ['unsigned' => true, 'nullable' => false]
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($amazonTemplateSynchronizationTable);
    }

    /**
     * @return void
     */
    private function installAmazonData()
    {
        $moduleConfig = $this->getConfigModifier();

        $moduleConfig->insert('/amazon/', 'application_name', 'M2ePro - Amazon Magento Integration');
        $moduleConfig->insert('/component/amazon/', 'mode', '1');
        $moduleConfig->insert('/cron/task/amazon/listing/product/process_instructions/', 'mode', '1');
        $moduleConfig->insert('/cron/task/amazon/listing/synchronize_inventory/', 'interval_per_account', '86400');
        $moduleConfig->insert('/listing/product/inspector/amazon/', 'max_allowed_instructions_count', '2000');
        $moduleConfig->insert('/amazon/listing/product/instructions/cron/', 'listings_products_per_one_time', '1000');
        $moduleConfig->insert('/amazon/listing/product/action/scheduled_data/', 'limit', '20000');
        $moduleConfig->insert(
            '/amazon/listing/product/action/processing/prepare/',
            'max_listings_products_count',
            '2000'
        );
        $moduleConfig->insert('/amazon/listing/product/action/list/', 'min_allowed_wait_interval', '3600');
        $moduleConfig->insert('/amazon/listing/product/action/relist/', 'min_allowed_wait_interval', '1800');
        $moduleConfig->insert('/amazon/listing/product/action/revise_qty/', 'min_allowed_wait_interval', '900');
        $moduleConfig->insert('/amazon/listing/product/action/revise_price/', 'min_allowed_wait_interval', '1800');
        $moduleConfig->insert('/amazon/listing/product/action/revise_details/', 'min_allowed_wait_interval', '7200');
        $moduleConfig->insert('/amazon/listing/product/action/revise_images/', 'min_allowed_wait_interval', '7200');
        $moduleConfig->insert('/amazon/listing/product/action/stop/', 'min_allowed_wait_interval', '600');
        $moduleConfig->insert('/amazon/listing/product/action/delete/', 'min_allowed_wait_interval', '600');
        $moduleConfig->insert('/amazon/order/settings/marketplace_25/', 'use_first_street_line_as_company', '1');
        $moduleConfig->insert('/amazon/repricing/', 'mode', '1');
        $moduleConfig->insert('/amazon/repricing/', 'base_url', 'https://repricer.m2epro.com/connector/m2epro/');
        $moduleConfig->insert('/amazon/configuration/', 'business_mode', '0');

        $this->getConnection()->insertMultiple(
            $this->getFullTableName('marketplace'),
            [
                [
                    'id'             => 24,
                    'native_id'      => 4,
                    'title'          => 'Canada',
                    'code'           => 'CA',
                    'url'            => 'amazon.ca',
                    'status'         => 0,
                    'sorder'         => 4,
                    'group_title'    => 'America',
                    'component_mode' => 'amazon',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 25,
                    'native_id'      => 3,
                    'title'          => 'Germany',
                    'code'           => 'DE',
                    'url'            => 'amazon.de',
                    'status'         => 0,
                    'sorder'         => 3,
                    'group_title'    => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 26,
                    'native_id'      => 5,
                    'title'          => 'France',
                    'code'           => 'FR',
                    'url'            => 'amazon.fr',
                    'status'         => 0,
                    'sorder'         => 7,
                    'group_title'    => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 28,
                    'native_id'      => 2,
                    'title'          => 'United Kingdom',
                    'code'           => 'UK',
                    'url'            => 'amazon.co.uk',
                    'status'         => 0,
                    'sorder'         => 2,
                    'group_title'    => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 29,
                    'native_id'      => 1,
                    'title'          => 'United States',
                    'code'           => 'US',
                    'url'            => 'amazon.com',
                    'status'         => 0,
                    'sorder'         => 1,
                    'group_title'    => 'America',
                    'component_mode' => 'amazon',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 30,
                    'native_id'      => 7,
                    'title'          => 'Spain',
                    'code'           => 'ES',
                    'url'            => 'amazon.es',
                    'status'         => 0,
                    'sorder'         => 8,
                    'group_title'    => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 31,
                    'native_id'      => 8,
                    'title'          => 'Italy',
                    'code'           => 'IT',
                    'url'            => 'amazon.it',
                    'status'         => 0,
                    'sorder'         => 5,
                    'group_title'    => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 34,
                    'native_id'      => 9,
                    'title'          => 'Mexico',
                    'code'           => 'MX',
                    'url'            => 'amazon.com.mx',
                    'status'         => 0,
                    'sorder'         => 8,
                    'group_title'    => 'America',
                    'component_mode' => 'amazon',
                    'update_date'    => '2017-10-17 00:00:00',
                    'create_date'    => '2017-10-17 00:00:00'
                ],
                [
                    'id'             => 35,
                    'native_id'      => 10,
                    'title'          => 'Australia',
                    'code'           => 'AU',
                    'url'            => 'amazon.com.au',
                    'status'         => 0,
                    'sorder'         => 1,
                    'group_title'    => 'Asia / Pacific',
                    'component_mode' => 'amazon',
                    'update_date'    => '2017-10-17 00:00:00',
                    'create_date'    => '2017-10-17 00:00:00'
                ],
                [
                    'id'             => 39,
                    'native_id'      => 11,
                    'title'          => 'Netherlands',
                    'code'           => 'NL',
                    'url'            => 'amazon.nl',
                    'status'         => 0,
                    'sorder'         => 12,
                    'group_title'    => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date'    => '2020-03-26 00:00:00',
                    'create_date'    => '2020-03-26 00:00:00'
                ],
                [
                    'id'             => 40,
                    'native_id'      => 12,
                    'title'          => 'Turkey',
                    'code'           => 'TR',
                    'url'            => 'amazon.com.tr',
                    'status'         => 0,
                    'sorder'         => 14,
                    'group_title'    => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date'    => '2020-08-19 00:00:00',
                    'create_date'    => '2020-08-19 00:00:00'
                ],
                [
                    'id'             => 41,
                    'native_id'      => 13,
                    'title'          => 'Sweden',
                    'code'           => 'SE',
                    'url'            => 'amazon.se',
                    'status'         => 0,
                    'sorder'         => 15,
                    'group_title'    => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date'    => '2020-09-03 00:00:00',
                    'create_date'    => '2020-09-03 00:00:00'
                ],
                [
                    'id'             => 42,
                    'native_id'      => 14,
                    'title'          => 'Japan',
                    'code'           => 'JP',
                    'url'            => 'amazon.co.jp',
                    'status'         => 0,
                    'sorder'         => 16,
                    'group_title'    => 'Asia / Pacific',
                    'component_mode' => 'amazon',
                    'update_date'    => '2021-01-11 00:00:00',
                    'create_date'    => '2021-01-11 00:00:00'
                ],
                [
                    'id'             => 43,
                    'native_id'      => 15,
                    'title'          => 'Poland',
                    'code'           => 'PL',
                    'url'            => 'amazon.pl',
                    'status'         => 0,
                    'sorder'         => 17,
                    'group_title'    => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date'    => '2021-02-01 00:00:00',
                    'create_date'    => '2021-02-01 00:00:00'
                ]
            ]
        );

        $this->getConnection()->insertMultiple(
            $this->getFullTableName('amazon_marketplace'),
            [
                [
                    'marketplace_id'                          => 24,
                    'developer_key'                           => '8636-1433-4377',
                    'default_currency'                        => 'CAD',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 0,
                    'is_business_available'                   => 0,
                    'is_vat_calculation_service_available'    => 0,
                    'is_product_tax_code_policy_available'    => 0,
                ],
                [
                    'marketplace_id'                          => 25,
                    'developer_key'                           => '7078-7205-1944',
                    'default_currency'                        => 'EUR',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 1,
                    'is_business_available'                   => 1,
                    'is_vat_calculation_service_available'    => 1,
                    'is_product_tax_code_policy_available'    => 1,
                ],
                [
                    'marketplace_id'                          => 26,
                    'developer_key'                           => '7078-7205-1944',
                    'default_currency'                        => 'EUR',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 0,
                    'is_business_available'                   => 1,
                    'is_vat_calculation_service_available'    => 1,
                    'is_product_tax_code_policy_available'    => 1,
                ],
                [
                    'marketplace_id'                          => 28,
                    'developer_key'                           => '7078-7205-1944',
                    'default_currency'                        => 'GBP',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 1,
                    'is_business_available'                   => 1,
                    'is_vat_calculation_service_available'    => 1,
                    'is_product_tax_code_policy_available'    => 1,
                ],
                [
                    'marketplace_id'                          => 29,
                    'developer_key'                           => '8636-1433-4377',
                    'default_currency'                        => 'USD',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 1,
                    'is_business_available'                   => 1,
                    'is_vat_calculation_service_available'    => 0,
                    'is_product_tax_code_policy_available'    => 0,
                ],
                [
                    'marketplace_id'                          => 30,
                    'developer_key'                           => '7078-7205-1944',
                    'default_currency'                        => 'EUR',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 0,
                    'is_business_available'                   => 1,
                    'is_vat_calculation_service_available'    => 1,
                    'is_product_tax_code_policy_available'    => 1,
                ],
                [
                    'marketplace_id'                          => 31,
                    'developer_key'                           => '7078-7205-1944',
                    'default_currency'                        => 'EUR',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 0,
                    'is_business_available'                   => 1,
                    'is_vat_calculation_service_available'    => 1,
                    'is_product_tax_code_policy_available'    => 1,
                ],
                [
                    'marketplace_id'                          => 34,
                    'developer_key'                           => '8636-1433-4377',
                    'default_currency'                        => 'MXN',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 0,
                    'is_business_available'                   => 0,
                    'is_vat_calculation_service_available'    => 0,
                    'is_product_tax_code_policy_available'    => 0,
                ],
                [
                    'marketplace_id'                          => 35,
                    'developer_key'                           => '2770-5005-3793',
                    'default_currency'                        => 'AUD',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 0,
                    'is_business_available'                   => 0,
                    'is_vat_calculation_service_available'    => 0,
                    'is_product_tax_code_policy_available'    => 0,
                ],
                [
                    'marketplace_id'                          => 39,
                    'developer_key'                           => '7078-7205-1944',
                    'default_currency'                        => 'EUR',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 1,
                    'is_business_available'                   => 1,
                    'is_vat_calculation_service_available'    => 0,
                    'is_product_tax_code_policy_available'    => 1,
                ],
                [
                    'marketplace_id'                          => 40,
                    'developer_key'                           => '7078-7205-1944',
                    'default_currency'                        => 'TRY',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 1,
                    'is_business_available'                   => 0,
                    'is_vat_calculation_service_available'    => 0,
                    'is_product_tax_code_policy_available'    => 0,
                ],
                [
                    'marketplace_id'                          => 41,
                    'developer_key'                           => '7078-7205-1944',
                    'default_currency'                        => 'SEK',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 1,
                    'is_business_available'                   => 0,
                    'is_vat_calculation_service_available'    => 0,
                    'is_product_tax_code_policy_available'    => 0,
                ],
                [
                    'marketplace_id'                          => 42,
                    'developer_key'                           => '7078-7205-1944',
                    'default_currency'                        => 'JPY',
                    'is_new_asin_available'                   => 0,
                    'is_merchant_fulfillment_available'       => 1,
                    'is_business_available'                   => 0,
                    'is_vat_calculation_service_available'    => 0,
                    'is_product_tax_code_policy_available'    => 0,
                ],
                [
                    'marketplace_id'                          => 43,
                    'developer_key'                           => '7078-7205-1944',
                    'default_currency'                        => 'PLN',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 1,
                    'is_business_available'                   => 0,
                    'is_vat_calculation_service_available'    => 0,
                    'is_product_tax_code_policy_available'    => 0,
                ]
            ]
        );
    }

    /**
     * @return void
     * @throws \Zend_Db_Exception
     */
    private function installWalmartSchema()
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
                ['nullable' => true]
            )
            ->addColumn(
                'private_key',
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
                'create_magento_invoice',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'create_magento_shipment',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'other_carriers',
                Table::TYPE_TEXT,
                null,
                ['nullable' => true, 'default' => null]
            )
            ->addColumn(
                'orders_last_synchronization',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => true]
            )
            ->addColumn(
                'inventory_last_synchronization',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                'info',
                Table::TYPE_TEXT,
                null,
                ['nullable' => true]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->addIndex('browsenode_id', 'browsenode_id')
            ->addIndex('category_id', 'category_id')
            ->addIndex('is_leaf', 'is_leaf')
            ->addIndex('marketplace_id', 'marketplace_id')
            ->addIndex('path', [['name' => 'path', 'size' => 255]])
            ->addIndex('parent_category_id', 'parent_category_id')
            ->addIndex('title', 'title')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('type', 'INNODB')
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
            ->addIndex('max_occurs', 'max_occurs')
            ->addIndex('min_occurs', 'min_occurs')
            ->addIndex('parent_specific_id', 'parent_specific_id')
            ->addIndex('title', 'title')
            ->addIndex('type', 'type')
            ->addIndex('specific_id', 'specific_id')
            ->addIndex('xml_tag', 'xml_tag')
            ->addIndex('xpath', 'xpath')
            ->addIndex('product_data_nick', 'product_data_nick')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($walmartDictionarySpecificTable);

        /**
         * Create table 'm2epro_walmart_listing_product_indexer_variation_parent'
         */
        $walmartIndexerListingProductParent = $this->getConnection()
            ->newTable($this->getFullTableName('walmart_listing_product_indexer_variation_parent'))
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
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($walmartIndexerListingProductParent);

        /**
         * Create table 'm2epro_walmart_inventory_wpid'
         */
        $walmartInventoryWpidTable = $this->getConnection()->newTable($this->getFullTableName('walmart_inventory_wpid'))
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
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($walmartInventoryWpidTable);

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
            ->addIndex('marketplace_id', 'marketplace_id')
            ->addIndex('product_id', 'product_id')
            ->addIndex('sku', 'sku')
            ->addIndex('store_id', 'store_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->addIndex('template_selling_format_id', 'template_selling_format_id')
            ->addIndex('template_description_id', 'template_description_id')
            ->addIndex('template_synchronization_id', 'template_synchronization_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->addIndex('online_price', 'online_price')
            ->addIndex('online_qty', 'online_qty')
            ->addIndex('sku', 'sku')
            ->addIndex('gtin', 'gtin')
            ->addIndex('upc', 'upc')
            ->addIndex('ean', 'ean')
            ->addIndex('wpid', 'wpid')
            ->addIndex('item_id', 'item_id')
            ->addIndex('title', 'title')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                40,
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
                40,
                ['nullable' => true]
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
            ->addIndex('is_variation_product_matched', 'is_variation_product_matched')
            ->addIndex('is_variation_channel_matched', 'is_variation_channel_matched')
            ->addIndex('is_variation_product', 'is_variation_product')
            ->addIndex('online_price', 'online_price')
            ->addIndex('online_qty', 'online_qty')
            ->addIndex('sku', 'sku')
            ->addIndex('gtin', 'gtin')
            ->addIndex('upc', 'upc')
            ->addIndex('ean', 'ean')
            ->addIndex('isbn', 'isbn')
            ->addIndex('wpid', 'wpid')
            ->addIndex('item_id', 'item_id')
            ->addIndex('online_start_date', 'online_start_date')
            ->addIndex('online_end_date', 'online_end_date')
            ->addIndex('is_variation_parent', 'is_variation_parent')
            ->addIndex('variation_parent_need_processor', 'variation_parent_need_processor')
            ->addIndex('variation_parent_id', 'variation_parent_id')
            ->addIndex('template_category_id', 'template_category_id')
            ->addIndex('list_date', 'list_date')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($walmartListingProductTable);

        $walmartProcessingActionTable = $this->getConnection()->newTable(
            $this->getFullTableName('walmart_listing_product_action_processing')
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
                ['unsigned' => true, 'nullable' => true]
            )
            ->addColumn(
                'listing_product_id',
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
                'is_prepared',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'group_hash',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true, 'default' => null]
            )
            ->addColumn(
                'request_data',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['nullable' => true]
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
            ->addIndex('listing_product_id', 'listing_product_id')
            ->addIndex('processing_id', 'processing_id')
            ->addIndex('request_pending_single_id', 'request_pending_single_id')
            ->addIndex('type', 'type')
            ->addIndex('is_prepared', 'is_prepared')
            ->addIndex('group_hash', 'group_hash')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($walmartProcessingActionTable);

        $walmartProcessingActionListSku = $this->getConnection()->newTable(
            $this->getFullTableName('walmart_listing_product_action_processing_list')
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
                'listing_product_id',
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
                'stage',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                'relist_request_pending_single_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                'relist_request_data',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['default' => null]
            )
            ->addColumn(
                'relist_configurator_data',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['default' => null]
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
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addIndex('stage', 'stage')
            ->addIndex('listing_product_id', 'listing_product_id')
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                'shipping_date_to',
                Table::TYPE_DATETIME,
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
            ->addIndex('buyer_email', 'buyer_email')
            ->addIndex('buyer_name', 'buyer_name')
            ->addIndex('paid_amount', 'paid_amount')
            ->addIndex('is_tried_to_acknowledge', 'is_tried_to_acknowledge')
            ->addIndex('purchase_create_date', 'purchase_create_date')
            ->addIndex('shipping_date_to', 'shipping_date_to')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                null,
                ['default' => null]
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
                'qty_purchased',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'buyer_cancellation_requested',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addIndex('sku', 'sku')
            ->addIndex('title', 'title')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                ['unsigned' => true, 'nullable' => true]
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                ['unsigned' => true, 'nullable' => true, 'default' => 0]
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
                ['nullable' => false,]
            )
            ->addColumn(
                'model_number_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
                ['unsigned' => true, 'nullable' => true, 'default' => 0]
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
                'multipack_quantity_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => true, 'default' => 0]
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
                ['unsigned' => true, 'nullable' => true, 'default' => 0]
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
                ['unsigned' => true, 'nullable' => true, 'default' => 0]
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
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'keywords_custom_value',
                Table::TYPE_TEXT,
                4000,
                ['nullable' => true]
            )
            ->addColumn(
                'keywords_custom_attribute',
                Table::TYPE_TEXT,
                4000,
                ['nullable' => true]
            )
            ->addColumn(
                'attributes_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'attributes',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                ['unsigned' => true, 'nullable' => false]
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
                ['unsigned' => true, 'nullable' => false,]
            )
            ->addColumn(
                'price_vat_percent',
                Table::TYPE_DECIMAL,
                [10, 2],
                ['unsigned' => true, 'nullable' => true]
            )
            ->addColumn(
                'promotions_mode',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'lag_time_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'lag_time_value',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
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
                ['unsigned' => true, 'nullable' => false]
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
                ['unsigned' => true, 'nullable' => true, 'default' => 0]
            )
            ->addColumn(
                'item_weight_custom_value',
                Table::TYPE_DECIMAL,
                [10, 2],
                ['unsigned' => true, 'nullable' => true, 'scale' => '2']
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
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'must_ship_alone_value',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
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
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'ships_in_original_packaging_value',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
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
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'sale_time_start_date_mode',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
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
                ['unsigned' => true, 'nullable' => false]
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
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
                ['unsigned' => true, 'nullable' => false]
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
                ['unsigned' => true, 'nullable' => false]
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                ['unsigned' => true, 'nullable' => false]
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
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
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
                'revise_update_promotions',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'revise_update_details',
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
                ['unsigned' => true, 'nullable' => false]
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
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($walmartTemplateSynchronizationTable);
    }

    /**
     * @return void
     */
    private function installWalmartData()
    {
        $moduleConfig = $this->getConfigModifier();

        $moduleConfig->insert('/walmart/', 'application_name', 'M2ePro - Walmart Magento Integration');
        $moduleConfig->insert('/component/walmart/', 'mode', '1');
        $moduleConfig->insert('/cron/task/walmart/listing/product/process_instructions/', 'mode', '1');
        $moduleConfig->insert('/cron/task/walmart/listing/synchronize_inventory/', 'interval_per_account', '86400');
        $moduleConfig->insert('/listing/product/inspector/walmart/', 'max_allowed_instructions_count', '2000');
        $moduleConfig->insert('/walmart/configuration/', 'sku_mode', '1');
        $moduleConfig->insert('/walmart/configuration/', 'sku_custom_attribute');
        $moduleConfig->insert('/walmart/configuration/', 'sku_modification_mode', '0');
        $moduleConfig->insert('/walmart/configuration/', 'sku_modification_custom_value');
        $moduleConfig->insert('/walmart/configuration/', 'generate_sku_mode', '0');
        $moduleConfig->insert('/walmart/configuration/', 'product_id_override_mode', '0');
        $moduleConfig->insert('/walmart/configuration/', 'upc_mode', '0');
        $moduleConfig->insert('/walmart/configuration/', 'upc_custom_attribute');
        $moduleConfig->insert('/walmart/configuration/', 'ean_mode', '0');
        $moduleConfig->insert('/walmart/configuration/', 'ean_custom_attribute');
        $moduleConfig->insert('/walmart/configuration/', 'gtin_mode', '0');
        $moduleConfig->insert('/walmart/configuration/', 'gtin_custom_attribute');
        $moduleConfig->insert('/walmart/configuration/', 'isbn_mode', '0');
        $moduleConfig->insert('/walmart/configuration/', 'isbn_custom_attribute');
        $moduleConfig->insert('/walmart/configuration/', 'option_images_url_mode', '0');
        $moduleConfig->insert('/walmart/order/settings/marketplace_25/', 'use_first_street_line_as_company', '1');
        $moduleConfig->insert('/walmart/listing/product/action/scheduled_data/', 'limit', '20000');
        $moduleConfig->insert('/walmart/listing/product/instructions/cron/', 'listings_products_per_one_time', '1000');
        $moduleConfig->insert('/walmart/listing/product/action/list/', 'min_allowed_wait_interval', '3600');
        $moduleConfig->insert('/walmart/listing/product/action/relist/', 'min_allowed_wait_interval', '1800');
        $moduleConfig->insert('/walmart/listing/product/action/revise_qty/', 'min_allowed_wait_interval', '900');
        $moduleConfig->insert('/walmart/listing/product/action/revise_price/', 'min_allowed_wait_interval', '1800');
        $moduleConfig->insert('/walmart/listing/product/action/revise_details/', 'min_allowed_wait_interval', '7200');
        $moduleConfig->insert('/walmart/listing/product/action/revise_lag_time/', 'min_allowed_wait_interval', '7200');
        $moduleConfig->insert('/walmart/listing/product/action/stop/', 'min_allowed_wait_interval', '600');
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

        $this->getConnection()->insertMultiple(
            $this->getFullTableName('marketplace'),
            [
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
            ]
        );

        $this->getConnection()->insertMultiple(
            $this->getFullTableName('walmart_marketplace'),
            [
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
            ]
        );
    }

    /**
     * @return \Ess\M2ePro\Model\Setup
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getCurrentSetupObject(): \Ess\M2ePro\Model\Setup
    {
        return $this->setupResource
            ->initCurrentSetupObject(
                null,
                $this->getConfigVersion()
            );
    }

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private function getConnection(): AdapterInterface
    {
        return $this->installer->getConnection();
    }

    /**
     * @param string $tableName
     *
     * @return string
     */
    private function getFullTableName(string $tableName): string
    {
        return (string)$this->tablesHelper->getFullName($tableName);
    }

    /**
     * @return \Ess\M2ePro\Model\Setup\Database\Modifier\Config
     */
    protected function getConfigModifier(): \Ess\M2ePro\Model\Setup\Database\Modifier\Config
    {
        return $this->objectManager->create(
            \Ess\M2ePro\Model\Setup\Database\Modifier\Config::class,
            [
                'installer' => $this->installer,
                'tableName' => 'config',
            ]
        );
    }

    /**
     * @return string
     */
    private function getConfigVersion(): string
    {
        return $this->moduleList->getOne(\Ess\M2ePro\Helper\Module::IDENTIFIER)['setup_version'];
    }
}
