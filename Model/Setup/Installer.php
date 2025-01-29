<?php

namespace Ess\M2ePro\Model\Setup;

use Ess\M2ePro\Helper\Module\Database\Tables as TablesHelper;
use Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\Marketplace as AmazonDictionaryMarketplaceResoruce;
use Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType as AmazonDictionaryProductTypeResource;
use Ess\M2ePro\Model\ResourceModel\Ebay\Account as ebayAccountResource;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Setup\SetupInterface;
use Ess\M2ePro\Model\ResourceModel\Amazon\Order\Item as AmazonOrderItem;
use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product as EbayListingProduct;
use Ess\M2ePro\Model\ResourceModel\Ebay\Template\Description as EbayTemplateDescription;
use Ess\M2ePro\Model\ResourceModel\Ebay\Marketplace as EbayMarketplaceResource;
use Ess\M2ePro\Model\ResourceModel\Marketplace as MarketplaceResource;
use Ess\M2ePro\Model\ResourceModel\Amazon\Marketplace as AmazonMarketplaceResource;
use Ess\M2ePro\Model\ResourceModel\Ebay\Bundle\Options\Mapping as MappingResource;
use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard\Product as ListingWizardProductResource;
use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard\Step as ListingStepResource;
use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard as ListingWizardResource;
use Ess\M2ePro\Model\ResourceModel\AttributeMapping\Pair as PairResource;
use Ess\M2ePro\Model\ResourceModel\AttributeOptionMapping\Pair as OptionPairResource;
use Ess\M2ePro\Model\ResourceModel\Ebay\ComplianceDocuments as ComplianceDocumentsResource;
use Ess\M2ePro\Model\ResourceModel\Ebay\ComplianceDocuments\ListingProductRelation
    as EbayComplianceDocumentListingProductRelationResource;

class Installer
{
    public const LONG_COLUMN_SIZE = 16777217;

    /** @var SetupInterface $installer */
    private $installer;

    /** @var \Magento\Framework\App\DeploymentConfig */
    private $deploymentConfig;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var TablesHelper */
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
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param TablesHelper $tablesHelper
     * @param \Ess\M2ePro\Helper\Module\Maintenance $maintenance
     * @param \Ess\M2ePro\Model\ResourceModel\Setup $setupResource
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Ess\M2ePro\Setup\LoggerFactory $loggerFactory
     */
    public function __construct(
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        TablesHelper $tablesHelper,
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
                                      [
                                          'unsigned' => true,
                                          'primary' => true,
                                          'nullable' => false,
                                          'auto_increment' => true,
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
                                             [
                                                 'unsigned' => true,
                                                 'primary' => true,
                                                 'nullable' => false,
                                                 'auto_increment' => true,
                                             ]
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
                                                  [
                                                      'unsigned' => true,
                                                      'primary' => true,
                                                      'nullable' => false,
                                                      'auto_increment' => true,
                                                  ]
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
                                    [
                                        'unsigned' => true,
                                        'primary' => true,
                                        'nullable' => false,
                                        'auto_increment' => true,
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
                                      [
                                          'unsigned' => true,
                                          'primary' => true,
                                          'nullable' => false,
                                          'auto_increment' => true,
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
                                        [
                                            'unsigned' => true,
                                            'primary' => true,
                                            'nullable' => false,
                                            'auto_increment' => true,
                                        ]
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
                                        'last_blocking_error_date',
                                        Table::TYPE_DATETIME,
                                        null,
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
                                                       [
                                                           'unsigned' => true,
                                                           'primary' => true,
                                                           'nullable' => false,
                                                           'auto_increment' => true,
                                                       ]
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
                                                   ->addIndex(
                                                       'listing_product_variation_id',
                                                       'listing_product_variation_id'
                                                   )
                                                   ->addIndex('option', 'option')
                                                   ->addIndex('product_id', 'product_id')
                                                   ->addIndex('product_type', 'product_type')
                                                   ->setOption('type', 'INNODB')
                                                   ->setOption('charset', 'utf8')
                                                   ->setOption('collate', 'utf8_general_ci')
                                                   ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($listingProductVariationOptionTable);

        #region listing_wizard
        $listingWizardTable = $this
            ->getConnection()
            ->newTable($this->getFullTableName(TablesHelper::TABLE_NAME_EBAY_LISTING_WIZARD));

        $listingWizardTable
            ->addColumn(
                ListingWizardResource::COLUMN_ID,
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'primary' => true,
                    'nullable' => false,
                    'auto_increment' => true,
                ],
            )
            ->addColumn(
                ListingWizardResource::COLUMN_LISTING_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
            )
            ->addColumn(
                ListingWizardResource::COLUMN_TYPE,
                Table::TYPE_TEXT,
                50,
            )
            ->addColumn(
                ListingWizardResource::COLUMN_CURRENT_STEP_NICK,
                Table::TYPE_TEXT,
                150,
            )
            ->addColumn(
                ListingWizardResource::COLUMN_PRODUCT_COUNT_TOTAL,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
            )
            ->addColumn(
                ListingWizardResource::COLUMN_IS_COMPLETED,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
            )
            ->addColumn(
                ListingWizardResource::COLUMN_PROCESS_START_DATE,
                Table::TYPE_DATETIME,
                null,
                ['default' => null],
            )
            ->addColumn(
                ListingWizardResource::COLUMN_PROCESS_END_DATE,
                Table::TYPE_DATETIME,
                null,
                ['default' => null],
            )
            ->addColumn(
                ListingWizardResource::COLUMN_UPDATE_DATE,
                Table::TYPE_DATETIME,
                null,
                ['default' => null],
            )
            ->addColumn(
                ListingWizardResource::COLUMN_CREATE_DATE,
                Table::TYPE_DATETIME,
                null,
                ['default' => null],
            )
            ->addIndex('listing_id', ListingWizardResource::COLUMN_LISTING_ID)
            ->addIndex('is_completed', ListingWizardResource::COLUMN_IS_COMPLETED)
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($listingWizardTable);
        #endregion

        #region listing_wizard_step
        $stepTable = $this
            ->getConnection()
            ->newTable($this->getFullTableName(TablesHelper::TABLE_NAME_EBAY_LISTING_WIZARD_STEP));

        $stepTable
            ->addColumn(
                ListingStepResource::COLUMN_ID,
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'primary' => true,
                    'nullable' => false,
                    'auto_increment' => true,
                ],
            )
            ->addColumn(
                ListingStepResource::COLUMN_WIZARD_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
            )
            ->addColumn(
                ListingStepResource::COLUMN_NICK,
                Table::TYPE_TEXT,
                150,
            )
            ->addColumn(
                ListingStepResource::COLUMN_DATA,
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['default' => null],
            )
            ->addColumn(
                ListingStepResource::COLUMN_IS_COMPLETED,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
            )
            ->addColumn(
                ListingStepResource::COLUMN_IS_SKIPPED,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
            )
            ->addColumn(
                ListingStepResource::COLUMN_UPDATE_DATE,
                Table::TYPE_DATETIME,
                null,
                ['default' => null],
            )
            ->addColumn(
                ListingStepResource::COLUMN_CREATE_DATE,
                Table::TYPE_DATETIME,
                null,
                ['default' => null],
            )
            ->addIndex('wizard_id', ListingStepResource::COLUMN_WIZARD_ID)
            ->addIndex('is_completed', ListingStepResource::COLUMN_IS_COMPLETED)
            ->addIndex('is_skipped', ListingStepResource::COLUMN_IS_SKIPPED)
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($stepTable);
        #endregion

        #region listing_wizard_product
        $productTable = $this
            ->getConnection()
            ->newTable($this->getFullTableName(TablesHelper::TABLE_NAME_EBAY_LISTING_WIZARD_PRODUCT));

        $productTable
            ->addColumn(
                ListingWizardProductResource::COLUMN_ID,
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'primary' => true,
                    'nullable' => false,
                    'auto_increment' => true,
                ],
            )
            ->addColumn(
                ListingWizardProductResource::COLUMN_WIZARD_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
            )
            ->addColumn(
                ListingWizardProductResource::COLUMN_UNMANAGED_PRODUCT_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
            )
            ->addColumn(
                ListingWizardProductResource::COLUMN_MAGENTO_PRODUCT_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
            )
            ->addColumn(
                ListingWizardProductResource::COLUMN_TEMPLATE_CATEGORY_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
            )
            ->addColumn(
                ListingWizardProductResource::COLUMN_TEMPLATE_CATEGORY_SECONDARY_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
            )
            ->addColumn(
                ListingWizardProductResource::COLUMN_STORE_CATEGORY_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
            )
            ->addColumn(
                ListingWizardProductResource::COLUMN_STORE_CATEGORY_SECONDARY_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
            )
            ->addColumn(
                ListingWizardProductResource::COLUMN_EBAY_ITEM_ID,
                Table::TYPE_TEXT,
                50
            )
            ->addColumn(
                ListingWizardProductResource::COLUMN_VALIDATION_STATUS,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
            )
            ->addColumn(
                ListingWizardProductResource::COLUMN_VALIDATION_ERRORS,
                Table::TYPE_TEXT,
                null
            )
            ->addColumn(
                ListingWizardProductResource::COLUMN_IS_PROCESSED,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
            )
            ->addIndex('wizard_id', ListingWizardProductResource::COLUMN_WIZARD_ID)
            ->addIndex('store_category_id', ListingWizardProductResource::COLUMN_STORE_CATEGORY_ID)
            ->addIndex('template_category_id', ListingWizardProductResource::COLUMN_TEMPLATE_CATEGORY_ID)
            ->addIndex('ebay_item_id', ListingWizardProductResource::COLUMN_EBAY_ITEM_ID)
            ->addIndex('is_processed', ListingWizardProductResource::COLUMN_IS_PROCESSED)
            ->addIndex(
                'wizard_id_magento_product_id',
                [
                    ListingWizardProductResource::COLUMN_WIZARD_ID,
                    ListingWizardProductResource::COLUMN_MAGENTO_PRODUCT_ID
                ],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE],
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($productTable);
        #endregion

        $listingProductInstruction = $this->getConnection()->newTable(
            $this->getFullTableName('listing_product_instruction')
        )
                                          ->addColumn(
                                              'id',
                                              Table::TYPE_INTEGER,
                                              null,
                                              [
                                                  'identity' => true,
                                                  'unsigned' => true,
                                                  'nullable' => false,
                                                  'primary' => true,
                                              ]
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
                                                  [
                                                      'identity' => true,
                                                      'unsigned' => true,
                                                      'nullable' => false,
                                                      'primary' => true,
                                                  ]
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
                                      [
                                          'unsigned' => true,
                                          'primary' => true,
                                          'nullable' => false,
                                          'auto_increment' => true,
                                      ]
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
                                     [
                                         'unsigned' => true,
                                         'primary' => true,
                                         'nullable' => false,
                                         'auto_increment' => true,
                                     ]
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
                           ->addIndex(
                               'magento_order_creation_latest_attempt_date',
                               'magento_order_creation_latest_attempt_date'
                           )
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
                                     [
                                         'unsigned' => true,
                                         'primary' => true,
                                         'nullable' => false,
                                         'auto_increment' => true,
                                     ]
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
                                   [
                                       'unsigned' => true,
                                       'primary' => true,
                                       'nullable' => false,
                                       'auto_increment' => true,
                                   ]
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
                                   [
                                       'unsigned' => true,
                                       'primary' => true,
                                       'nullable' => false,
                                       'auto_increment' => true,
                                   ]
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
                                       [
                                           'unsigned' => true,
                                           'primary' => true,
                                           'nullable' => false,
                                           'auto_increment' => true,
                                       ]
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
                                    [
                                        'unsigned' => true,
                                        'primary' => true,
                                        'nullable' => false,
                                        'auto_increment' => true,
                                    ]
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
                                        [
                                            'unsigned' => true,
                                            'primary' => true,
                                            'nullable' => false,
                                            'auto_increment' => true,
                                        ]
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
                                              [
                                                  'unsigned' => true,
                                                  'primary' => true,
                                                  'nullable' => false,
                                                  'auto_increment' => true,
                                              ]
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
                                               [
                                                   'unsigned' => true,
                                                   'primary' => true,
                                                   'nullable' => false,
                                                   'auto_increment' => true,
                                               ]
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
                                                   [
                                                       'unsigned' => true,
                                                       'primary' => true,
                                                       'nullable' => false,
                                                       'auto_increment' => true,
                                                   ]
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
                                                         [
                                                             'unsigned' => true,
                                                             'primary' => true,
                                                             'nullable' => false,
                                                             'auto_increment' => true,
                                                         ]
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
                                                     ->addIndex(
                                                         'request_pending_single_id',
                                                         'request_pending_single_id'
                                                     )
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
                                                          [
                                                              'unsigned' => true,
                                                              'primary' => true,
                                                              'nullable' => false,
                                                              'auto_increment' => true,
                                                          ]
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
                                                      ->addIndex(
                                                          'request_pending_partial_id',
                                                          'request_pending_partial_id'
                                                      )
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
                                                      [
                                                          'unsigned' => true,
                                                          'primary' => true,
                                                          'nullable' => false,
                                                          'auto_increment' => true,
                                                      ]
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
                                   [
                                       'unsigned' => true,
                                       'primary' => true,
                                       'nullable' => false,
                                       'auto_increment' => true,
                                   ]
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
                                            [
                                                'unsigned' => true,
                                                'primary' => true,
                                                'nullable' => false,
                                                'auto_increment' => true,
                                            ]
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
                                   [
                                       'unsigned' => true,
                                       'primary' => true,
                                       'nullable' => false,
                                       'auto_increment' => true,
                                   ]
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
                                          [
                                              'unsigned' => true,
                                              'primary' => true,
                                              'nullable' => false,
                                              'auto_increment' => true,
                                          ]
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
                                               [
                                                   'unsigned' => true,
                                                   'primary' => true,
                                                   'nullable' => false,
                                                   'auto_increment' => true,
                                               ]
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
                                                 [
                                                     'unsigned' => true,
                                                     'primary' => true,
                                                     'nullable' => false,
                                                     'auto_increment' => true,
                                                 ]
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
                                             [
                                                 'unsigned' => true,
                                                 'primary' => true,
                                                 'nullable' => false,
                                                 'auto_increment' => true,
                                             ]
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

        $registryTable = $this->getConnection()->newTable($this->getFullTableName(TablesHelper::TABLE_REGISTRY))
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
                                   [
                                       'unsigned' => true,
                                       'primary' => true,
                                       'nullable' => false,
                                       'auto_increment' => true,
                                   ]
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

        # region attribute_mapping
        $attributeMappingTable = $this->getConnection()->newTable(
            $this->getFullTableName(TablesHelper::TABLE_ATTRIBUTE_MAPPING)
        )
                                      ->addColumn(
                                          PairResource::COLUMN_ID,
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
                                          PairResource::COLUMN_COMPONENT,
                                          Table::TYPE_TEXT,
                                          50,
                                          ['nullable' => false]
                                      )
                                      ->addColumn(
                                          PairResource::COLUMN_TYPE,
                                          Table::TYPE_TEXT,
                                          100,
                                          ['nullable' => false]
                                      )
                                      ->addColumn(
                                          PairResource::COLUMN_CHANNEL_ATTRIBUTE_TITLE,
                                          Table::TYPE_TEXT,
                                          255,
                                          ['nullable' => false]
                                      )
                                      ->addColumn(
                                          PairResource::COLUMN_CHANNEL_ATTRIBUTE_CODE,
                                          Table::TYPE_TEXT,
                                          255,
                                          ['nullable' => false]
                                      )
                                      ->addColumn(
                                          PairResource::COLUMN_VALUE_MODE,
                                          Table::TYPE_INTEGER,
                                          null,
                                          ['nullable' => false, 'default' => 0]
                                      )
                                      ->addColumn(
                                          PairResource::COLUMN_VALUE,
                                          Table::TYPE_TEXT,
                                          null,
                                          ['nullable' => false]
                                      )
                                      ->addColumn(
                                          PairResource::COLUMN_UPDATE_DATE,
                                          Table::TYPE_DATETIME,
                                          null,
                                          ['default' => null]
                                      )
                                      ->addColumn(
                                          PairResource::COLUMN_CREATE_DATE,
                                          Table::TYPE_DATETIME,
                                          null,
                                          ['default' => null]
                                      )
                                      ->addIndex('component', PairResource::COLUMN_COMPONENT)
                                      ->addIndex('type', PairResource::COLUMN_TYPE)
                                      ->addIndex('create_date', PairResource::COLUMN_CREATE_DATE)
                                      ->setOption('type', 'INNODB')
                                      ->setOption('charset', 'utf8')
                                      ->setOption('collate', 'utf8_general_ci')
                                      ->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($attributeMappingTable);
        # endregion

        //region attribute_option_mapping
        $tableName = $this->getFullTableName(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_ATTRIBUTE_OPTION_MAPPING
        );

        $newTable = $this->getConnection()->newTable($tableName);
        $newTable
            ->addColumn(
                OptionPairResource::COLUMN_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'primary' => true,
                    'nullable' => false,
                    'auto_increment' => true,
                ]
            )
            ->addColumn(
                OptionPairResource::COLUMN_COMPONENT,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                50,
                ['nullable' => false]
            )
            ->addColumn(
                OptionPairResource::COLUMN_TYPE,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                100,
                ['nullable' => false]
            )
            ->addColumn(
                OptionPairResource::COLUMN_PRODUCT_TYPE_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                OptionPairResource::COLUMN_CHANNEL_ATTRIBUTE_TITLE,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                OptionPairResource::COLUMN_CHANNEL_ATTRIBUTE_CODE,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                OptionPairResource::COLUMN_CHANNEL_OPTION_TITLE,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                OptionPairResource::COLUMN_CHANNEL_OPTION_CODE,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                OptionPairResource::COLUMN_MAGENTO_ATTRIBUTE_CODE,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                OptionPairResource::COLUMN_MAGENTO_OPTION_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'unsigned' => true]
            )
            ->addColumn(
                OptionPairResource::COLUMN_MAGENTO_OPTION_TITLE,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                OptionPairResource::COLUMN_UPDATE_DATE,
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                OptionPairResource::COLUMN_CREATE_DATE,
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
        ;

        $newTable
            ->addIndex('component', OptionPairResource::COLUMN_COMPONENT)
            ->addIndex('type', OptionPairResource::COLUMN_TYPE)
            ->addIndex('create_date', OptionPairResource::COLUMN_CREATE_DATE);

        $newTable
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($newTable);
        //endregion

        # region tag
        $tagTable = $this->getConnection()->newTable($this->getFullTableName('tag'));
        $tagTable->addColumn(
            'id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
        );
        $tagTable->addColumn(
            'error_code',
            Table::TYPE_TEXT,
            100,
            ['nullable' => false]
        );
        $tagTable->addColumn(
            'text',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false]
        );
        $tagTable->addColumn(
            'create_date',
            Table::TYPE_DATETIME,
            null,
            ['nullable' => false]
        );
        $tagTable->addIndex(
            'error_code',
            ['error_code'],
            ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
        );
        $tagTable->setOption('type', 'INNODB');
        $tagTable->setOption('charset', 'utf8');
        $tagTable->setOption('collate', 'utf8_general_ci');
        $tagTable->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($tagTable);
        #endregion

        # region listing_product_tag_relation
        $listingProductTagRelationTable = $this->getConnection()->newTable(
            $this->getFullTableName('listing_product_tag_relation')
        );
        $listingProductTagRelationTable->addColumn(
            'id',
            Table::TYPE_INTEGER,
            null,
            [
                'unsigned' => true,
                'primary' => true,
                'nullable' => false,
                'auto_increment' => true,
            ]
        );
        $listingProductTagRelationTable->addColumn(
            'listing_product_id',
            Table::TYPE_INTEGER,
            null,
            [
                'unsigned' => true,
                'nullable' => false,
            ]
        );
        $listingProductTagRelationTable->addColumn(
            'tag_id',
            Table::TYPE_INTEGER,
            null,
            [
                'unsigned' => true,
                'nullable' => false,
            ]
        );
        $listingProductTagRelationTable->addColumn(
            'create_date',
            Table::TYPE_DATETIME,
            null,
            ['nullable' => false]
        );
        $listingProductTagRelationTable->addIndex('listing_product_id', 'listing_product_id');
        $listingProductTagRelationTable->addIndex('tag_id', 'tag_id');
        $listingProductTagRelationTable->setOption('type', 'INNODB');
        $listingProductTagRelationTable->setOption('charset', 'utf8');
        $listingProductTagRelationTable->setOption('collate', 'utf8_general_ci');
        $listingProductTagRelationTable->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($listingProductTagRelationTable);
        #endregion

        #region listing_product_advanced_filter
        $listingProductAdvancedFilterTable = $this->getConnection()->newTable(
            $this->getFullTableName('listing_product_advanced_filter')
        );
        $listingProductAdvancedFilterTable->addColumn(
            'id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
        );
        $listingProductAdvancedFilterTable->addColumn(
            'model_nick',
            Table::TYPE_TEXT,
            100,
            ['nullable' => false]
        );
        $listingProductAdvancedFilterTable->addColumn(
            'title',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false]
        );
        $listingProductAdvancedFilterTable->addColumn(
            'conditionals',
            Table::TYPE_TEXT,
            self::LONG_COLUMN_SIZE,
            ['nullable' => false]
        );
        $listingProductAdvancedFilterTable->addColumn(
            'update_date',
            Table::TYPE_DATETIME,
            null,
            ['nullable' => false]
        );
        $listingProductAdvancedFilterTable->addColumn(
            'create_date',
            Table::TYPE_DATETIME,
            null,
            ['nullable' => false]
        );
        $listingProductAdvancedFilterTable->setOption('type', 'INNODB');
        $listingProductAdvancedFilterTable->setOption('charset', 'utf8');
        $listingProductAdvancedFilterTable->setOption('collate', 'utf8_general_ci');
        $listingProductAdvancedFilterTable->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($listingProductAdvancedFilterTable);
        #endregion
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
        $moduleConfig->insert('/license/', 'key');
        $moduleConfig->insert('/license/domain/', 'real');
        $moduleConfig->insert('/license/domain/', 'valid');
        $moduleConfig->insert('/license/domain/', 'is_valid');
        $moduleConfig->insert('/license/ip/', 'real');
        $moduleConfig->insert('/license/ip/', 'valid');
        $moduleConfig->insert('/license/ip/', 'is_valid');
        $moduleConfig->insert('/license/info/', 'email');
        $moduleConfig->insert('/server/', 'application_key', '02edcc129b6128f5fa52d4ad1202b427996122b6');
        $moduleConfig->insert('/server/', 'host', 'https://api.m2epro.com/');
        $moduleConfig->insert('/cron/', 'mode', '1');
        $moduleConfig->insert('/cron/', 'runner', 'magento');
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
        $moduleConfig->insert('/logs/listings/', 'last_action_id', '0');
        $moduleConfig->insert('/logs/grouped/', 'max_records_count', '100000');
        $moduleConfig->insert('/support/', 'documentation_url', 'https://docs-m2.m2epro.com/');
        $moduleConfig->insert('/support/', 'accounts_url', 'https://accounts.m2e.cloud/');
        $moduleConfig->insert('/support/', 'website_url', 'https://m2epro.com/');
        $moduleConfig->insert('/support/', 'magento_marketplace_url', $magentoMarketplaceUrl);
        $moduleConfig->insert('/support/', 'contact_email', 'support@m2epro.com');
        $moduleConfig->insert('/general/configuration/', 'listing_product_inspector_mode', '0');
        $moduleConfig->insert('/general/configuration/', 'view_show_block_notices_mode', '1');
        $moduleConfig->insert('/general/configuration/', 'view_show_products_thumbnails_mode', '1');
        $moduleConfig->insert('/general/configuration/', 'view_products_grid_use_alternative_mysql_select_mode', '0');
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
            $this->getFullTableName(TablesHelper::TABLE_WIZARD),
            [
                [
                    'nick' => 'installationEbay',
                    'view' => 'ebay',
                    'status' => 0,
                    'step' => null,
                    'type' => 1,
                    'priority' => 2,
                ],
                [
                    'nick' => 'installationAmazon',
                    'view' => 'amazon',
                    'status' => 0,
                    'step' => null,
                    'type' => 1,
                    'priority' => 3,
                ],
                [
                    'nick' => 'installationWalmart',
                    'view' => 'walmart',
                    'status' => 0,
                    'step' => null,
                    'type' => 1,
                    'priority' => 4,
                ],
                [
                    'nick' => 'migrationFromMagento1',
                    'view' => '*',
                    'status' => 2,
                    'step' => null,
                    'type' => 1,
                    'priority' => 1,
                ],
                [
                    'nick' => 'migrationToInnodb',
                    'view' => '*',
                    'status' => 3,
                    'step' => null,
                    'type' => 1,
                    'priority' => 5,
                ],
                [
                    'nick' => 'amazonMigrationToProductTypes',
                    'view' => 'amazon',
                    'status' => 3,
                    'step' => null,
                    'type' => 1,
                    'priority' => 6,
                ],
                [
                    'nick' => 'versionDowngrade',
                    'view' => '*',
                    'status' => 3,
                    'step' => null,
                    'type' => 1,
                    'priority' => 7,
                ],
                [
                    'nick' => 'walmartMigrationToProductTypes',
                    'view' => 'walmart',
                    'status' => 3,
                    'step' => null,
                    'type' => 1,
                    'priority' => 8,
                ]
            ]
        );

        #region tag
        $tagCreateDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $tagCreateDate = $tagCreateDate->format('Y-m-d H:i:s');

        $this->getConnection()->insertMultiple(
            $this->getFullTableName('tag'),
            [
                [
                    'error_code' => 'has_error',
                    'text' => 'Has error',
                    'create_date' => $tagCreateDate,
                ],
                [
                    'error_code' => '21919303',
                    'text' => 'Required Item Specifics are missing',
                    'create_date' => $tagCreateDate,
                ],
            ]
        );
        #endregion
    }

    /**
     * @return void
     * @throws \Zend_Db_Exception
     */
    private function installEbaySchema()
    {
        $tableName = $this->getFullTableName(TablesHelper::TABLE_EBAY_ACCOUNT);

        $ebayAccountTable = $this->getConnection()->newTable($tableName);

        $ebayAccountTable
            ->addColumn(
                ebayAccountResource::COLUMN_ACCOUNT_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_MODE,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_SERVER_HASH,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_USER_ID,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_IS_TOKEN_EXIST,
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 0]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_SELL_API_TOKEN_EXPIRED_DATE,
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_MARKETPLACES_DATA,
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_INVENTORY_LAST_SYNCHRONIZATION,
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_OTHER_LISTINGS_SYNCHRONIZATION,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_OTHER_LISTINGS_MAPPING_MODE,
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 0]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_OTHER_LISTINGS_MAPPING_SETTINGS,
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_OTHER_LISTINGS_LAST_SYNCHRONIZATION,
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_FEEDBACKS_RECEIVE,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_FEEDBACKS_AUTO_RESPONSE,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_FEEDBACKS_AUTO_RESPONSE_ONLY_POSITIVE,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_FEEDBACKS_LAST_USED_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_EBAY_SITE,
                Table::TYPE_TEXT,
                20,
                ['nullable' => false]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_EBAY_STORE_TITLE,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_EBAY_STORE_URL,
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_EBAY_STORE_SUBSCRIPTION_LEVEL,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_EBAY_STORE_DESCRIPTION,
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_INFO,
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_USER_PREFERENCES,
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_RATE_TABLES,
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_EBAY_SHIPPING_DISCOUNT_PROFILES,
                Table::TYPE_TEXT,
                null,
                ['default' => null]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_JOB_TOKEN,
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_ORDERS_LAST_SYNCHRONIZATION,
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_MAGENTO_ORDERS_SETTINGS,
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_CREATE_MAGENTO_INVOICE,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_CREATE_MAGENTO_SHIPMENT,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_SKIP_EVTIN,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                ebayAccountResource::COLUMN_MESSAGES_RECEIVE,
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 0]
            );

        $ebayAccountTable
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
                                              ->addIndex(
                                                  'primary',
                                                  ['account_id', 'category_id'],
                                                  ['type' => AdapterInterface::INDEX_TYPE_PRIMARY]
                                              )
                                              ->addIndex('parent_id', 'parent_id')
                                              ->addIndex('sorder', 'sorder')
                                              ->addIndex('title', 'title')
                                              ->setOption('type', 'INNODB')
                                              ->setOption('charset', 'utf8')
                                              ->setOption('collate', 'utf8_general_ci')
                                              ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($ebayAccountStoreCategoryTable);

        $ebayCategorySpecificValidationResult = $this
            ->getConnection()
            ->newTable($this->getFullTableName('ebay_category_specific_validation_result'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'primary' => true,
                    'nullable' => false,
                    'auto_increment' => true
                ],
                'ID'
            )
            ->addColumn(
                'listing_product_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false
                ],
                'Listing Product ID'
            )
            ->addColumn(
                'status',
                Table::TYPE_SMALLINT,
                null,
                [
                    'nullable' => false,
                    'default' => 0
                ],
                'Status'
            )
            ->addColumn(
                'error_messages',
                Table::TYPE_TEXT,
                null,
                [],
                'Error Messages'
            )
            ->addColumn(
                'create_date',
                Table::TYPE_DATETIME,
                null,
                [],
                'Create Date'
            )
            ->addColumn(
                'update_date',
                Table::TYPE_DATETIME,
                null,
                [],
                'Update Date'
            )
            ->setComment('eBay categories specific validation result')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this
            ->getConnection()
            ->createTable($ebayCategorySpecificValidationResult);

        $ebayProcessingActionTable = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_listing_product_action_processing')
        )
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
                                           [
                                               'unsigned' => true,
                                               'primary' => true,
                                               'nullable' => false,
                                               'auto_increment' => true,
                                           ]
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
                                              [
                                                  'unsigned' => true,
                                                  'primary' => true,
                                                  'nullable' => false,
                                                  'auto_increment' => true,
                                              ]
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
                                                [
                                                    'unsigned' => true,
                                                    'primary' => true,
                                                    'nullable' => false,
                                                    'auto_increment' => true,
                                                ]
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
                                      [
                                          'unsigned' => true,
                                          'primary' => true,
                                          'nullable' => false,
                                          'auto_increment' => true,
                                      ]
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
                                      'is_critical_error_received',
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
                                    'add_product_mode',
                                    Table::TYPE_TEXT,
                                    10,
                                    ['default' => null]
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
                                     self::LONG_COLUMN_SIZE,
                                     ['default' => null]
                                 )
                                 ->addColumn(
                                     'parts_compatibility_mode',
                                     Table::TYPE_TEXT,
                                     10,
                                     ['default' => null]
                                 )
                                 ->addIndex(
                                     'auto_global_adding_template_category_id',
                                     'auto_global_adding_template_category_id'
                                 )
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
                                 ->addIndex(
                                     'auto_website_adding_template_category_id',
                                     'auto_website_adding_template_category_id'
                                 )
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
                                             ->addIndex(
                                                 'adding_template_category_secondary_id',
                                                 'adding_template_category_secondary_id'
                                             )
                                             ->addIndex(
                                                 'adding_template_store_category_id',
                                                 'adding_template_store_category_id'
                                             )
                                             ->addIndex(
                                                 'adding_template_store_category_secondary_id',
                                                 'adding_template_store_category_secondary_id'
                                             )
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

        #reigon ebay_listing_product
        $ebayListingProductTableName = $this->getFullTableName(
            TablesHelper::TABLE_EBAY_LISTING_PRODUCT
        );
        $ebayListingProductTable = $this->getConnection()->newTable($ebayListingProductTableName)
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
                                            'online_product_identifiers_hash',
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
                                            'online_best_offer',
                                            Table::TYPE_TEXT,
                                            32,
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
                                            EbayListingProduct::COLUMN_PRICE_LAST_UPDATE_DATE,
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
                                        )->addColumn(
                                            'ktypes_resolve_status',
                                            Table::TYPE_SMALLINT,
                                            null,
                                            ['unsigned' => true, 'nullable' => false, 'default' => 0]
                                        )->addColumn(
                                            'ktypes_resolve_last_try_date',
                                            Table::TYPE_DATETIME,
                                            null,
                                            ['default' => null]
                                        )->addColumn(
                                            'ktypes_resolve_attempt',
                                            Table::TYPE_SMALLINT,
                                            null,
                                            ['unsigned' => true, 'nullable' => false, 'default' => 0]
                                        )->addColumn(
                                            EbayListingProduct::COLUMN_VIDEO_URL,
                                            Table::TYPE_TEXT,
                                            null,
                                            ['default' => null]
                                        )->addColumn(
                                            EbayListingProduct::COLUMN_VIDEO_ID,
                                            Table::TYPE_TEXT,
                                            255,
                                            ['default' => null]
                                        )->addColumn(
                                            EbayListingProduct::COLUMN_ONLINE_VIDEO_ID,
                                            Table::TYPE_TEXT,
                                            255,
                                            ['default' => null]
                                        )
                                        ->addColumn(
                                            EbayListingProduct::COLUMN_COMPLIANCE_DOCUMENTS,
                                            Table::TYPE_TEXT,
                                            null,
                                            ['default' => null]
                                        )
                                        ->addColumn(
                                            EbayListingProduct::COLUMN_ONLINE_COMPLIANCE_DOCUMENTS,
                                            Table::TYPE_TEXT,
                                            null,
                                            ['default' => null]
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
                                        ->addIndex(
                                            'template_store_category_secondary_id',
                                            'template_store_category_secondary_id'
                                        )
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
        #endregion

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
                                                                  [
                                                                      'unsigned' => true,
                                                                      'primary' => true,
                                                                      'nullable' => false,
                                                                  ]
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
                                                                  [
                                                                      'unsigned' => true,
                                                                      'nullable' => false,
                                                                      'default' => '0.0000',
                                                                  ]
                                                              )
                                                              ->addColumn(
                                                                  'max_price',
                                                                  Table::TYPE_DECIMAL,
                                                                  [12, 4],
                                                                  [
                                                                      'unsigned' => true,
                                                                      'nullable' => false,
                                                                      'default' => '0.0000',
                                                                  ]
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

        $ebayListingProductScheduledStopActionTable = $this->getConnection()
                                                           ->newTable(
                                                               $this->getFullTableName(
                                                                   'ebay_listing_product_scheduled_stop_action'
                                                               )
                                                           )
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
                                                                   'nullable' => false,
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
        $this->getConnection()->createTable($ebayListingProductScheduledStopActionTable);

        $ebayMarketplaceTable = $this->getConnection()->newTable(
            $this->getFullTableName(TablesHelper::TABLE_EBAY_MARKETPLACE)
        )
                                     ->addColumn(
                                         EbayMarketplaceResource::COLUMN_MARKETPLACE_ID,
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
                                         EbayMarketplaceResource::COLUMN_IS_INTERNATIONAL_SHIPPING_RATE_TABLE,
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
                                         'is_global_shipping_program',
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
                                     ->addIndex('is_english_measurement_system', 'is_english_measurement_system')
                                     ->addIndex('is_freight_shipping', 'is_freight_shipping')
                                     ->addIndex(
                                         'is_international_shipping_rate_table',
                                         'is_international_shipping_rate_table'
                                     )
                                     ->addIndex('is_local_shipping_rate_table', 'is_local_shipping_rate_table')
                                     ->addIndex('is_metric_measurement_system', 'is_metric_measurement_system')
                                     ->addIndex('is_tax_table', 'is_tax_table')
                                     ->addIndex('is_vat', 'is_vat')
                                     ->addIndex('is_stp', 'is_stp')
                                     ->addIndex('is_stp_advanced', 'is_stp_advanced')
                                     ->addIndex('is_map', 'is_map')
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
                                                [
                                                    'unsigned' => true,
                                                    'primary' => true,
                                                    'nullable' => false,
                                                    'auto_increment' => true,
                                                ]
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
                                                  [
                                                      'unsigned' => true,
                                                      'primary' => true,
                                                      'nullable' => false,
                                                      'auto_increment' => true,
                                                  ]
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
                                         [
                                             'unsigned' => true,
                                             'primary' => true,
                                             'nullable' => false,
                                             'auto_increment' => true,
                                         ]
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
                                   [
                                       'unsigned' => true,
                                       'primary' => true,
                                       'nullable' => false,
                                       'auto_increment' => true,
                                   ]
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
                                                [
                                                    'unsigned' => true,
                                                    'primary' => true,
                                                    'nullable' => false,
                                                    'auto_increment' => true,
                                                ]
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
                                   'buyer_cancellation_status',
                                   Table::TYPE_SMALLINT,
                                   null,
                                   ['unsigned' => true, 'nullable' => false, 'default' => 0]
                               )
                               ->addColumn(
                                   'buyer_return_requested',
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
                                                      [
                                                          'unsigned' => true,
                                                          'primary' => true,
                                                          'nullable' => false,
                                                          'auto_increment' => true,
                                                      ]
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
                                              [
                                                  'unsigned' => true,
                                                  'primary' => true,
                                                  'nullable' => false,
                                                  'auto_increment' => true,
                                              ]
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
                                                      [
                                                          'unsigned' => true,
                                                          'primary' => true,
                                                          'nullable' => false,
                                                          'auto_increment' => true,
                                                      ]
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
                                                      null,
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
                                                 EbayTemplateDescription::COLUMN_USE_SUPERSIZE_IMAGES,
                                                 Table::TYPE_SMALLINT,
                                                 null,
                                                 ['unsigned' => true, 'nullable' => false, 'default' => 0]
                                             )
                                             ->addColumn(
                                                 EbayTemplateDescription::COLUMN_VIDEO_MODE,
                                                 Table::TYPE_SMALLINT,
                                                 null,
                                                 ['unsigned' => true, 'nullable' => false, 'default' => 0]
                                             )
                                             ->addColumn(
                                                 EbayTemplateDescription::COLUMN_VIDEO_ATTRIBUTE,
                                                 Table::TYPE_TEXT,
                                                 255,
                                                 ['default' => null]
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
                                             ->addColumn(
                                                 EbayTemplateDescription::COLUMN_COMPLIANCE_DOCUMENTS,
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
                                                   [
                                                       'unsigned' => true,
                                                       'primary' => true,
                                                       'nullable' => false,
                                                       'auto_increment' => true,
                                                   ]
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
                                                  [
                                                      'unsigned' => true,
                                                      'primary' => true,
                                                      'nullable' => false,
                                                      'auto_increment' => true,
                                                  ]
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
                                                   'fixed_price_rounding_option',
                                                   Table::TYPE_SMALLINT,
                                                   null,
                                                   ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
                                                   'start_price_rounding_option',
                                                   Table::TYPE_SMALLINT,
                                                   null,
                                                   ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
                                                   'reserve_price_rounding_option',
                                                   Table::TYPE_SMALLINT,
                                                   null,
                                                   ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
                                                   'buyitnow_price_rounding_option',
                                                   Table::TYPE_SMALLINT,
                                                   null,
                                                   ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
                                                   'ignore_variations',
                                                   Table::TYPE_SMALLINT,
                                                   null,
                                                   ['unsigned' => true, 'nullable' => false, 'default' => 0]
                                               )
                                               ->addColumn(
                                                   'paypal_immediate_payment',
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
                                              [
                                                  'unsigned' => true,
                                                  'primary' => true,
                                                  'nullable' => false,
                                                  'auto_increment' => true,
                                              ]
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
                                              'shipping_irregular',
                                              Table::TYPE_SMALLINT,
                                              null,
                                              ['unsigned' => true, 'nullable' => false, 'default' => 0]
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
                                                     [
                                                         'unsigned' => true,
                                                         'primary' => true,
                                                         'nullable' => false,
                                                         'auto_increment' => true,
                                                     ]
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

        #region ebay_template_synchronization
        $ebayTemplateSynchronizationTableName = $this->getFullTableName(
            TablesHelper::TABLE_EBAY_TEMPLATE_SYNCHRONIZATION
        );
        $ebayTemplateSynchronizationTable = $this->getConnection()
                                                 ->newTable($ebayTemplateSynchronizationTableName)
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
                                                    'revise_update_product_identifiers',
                                                    Table::TYPE_SMALLINT,
                                                    null,
                                                    ['unsigned' => true, 'default' => 0]
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
        #endregion

        $ebayPromotionTableName = $this->getFullTableName(
            TablesHelper::TABLE_EBAY_PROMOTION
        );
        $ebayPromotionTable = $this->getConnection()
                                   ->newTable($ebayPromotionTableName)
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
                                       'promotion_id',
                                       Table::TYPE_TEXT,
                                       255,
                                       ['nullable' => false]
                                   )
                                   ->addColumn(
                                       'name',
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
                                   ->addColumn(
                                       'status',
                                       Table::TYPE_TEXT,
                                       255,
                                       ['nullable' => false]
                                   )
                                   ->addColumn(
                                       'priority',
                                       Table::TYPE_TEXT,
                                       255,
                                       ['nullable' => false]
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
        $this->getConnection()->createTable($ebayPromotionTable);

        $ebayListingProductPromotionTableName = $this->getFullTableName(
            TablesHelper::TABLE_EBAY_LISTING_PRODUCT_PROMOTION
        );

        $ebayListingProductPromotionTable = $this->getConnection()
                                   ->newTable($ebayListingProductPromotionTableName)
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
                                       'listing_product_id',
                                       Table::TYPE_INTEGER,
                                       null,
                                       ['unsigned' => true, 'nullable' => false]
                                   )
                                   ->addColumn(
                                       'promotion_id',
                                       Table::TYPE_INTEGER,
                                       null,
                                       ['unsigned' => true, 'nullable' => false]
                                   )
                                   ->addColumn(
                                       'discount_id',
                                       Table::TYPE_INTEGER,
                                       null,
                                       ['unsigned' => true, 'nullable' => true]
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
        $this->getConnection()->createTable($ebayListingProductPromotionTable);

        $ebayVideoTableName = $this->getFullTableName(
            TablesHelper::TABLE_EBAY_VIDEO
        );
        $ebayVideoTable = $this->getConnection()
                               ->newTable($ebayVideoTableName)
                               ->addColumn(
                                   \Ess\M2ePro\Model\ResourceModel\Ebay\Video::COLUMN_ID,
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
                                   \Ess\M2ePro\Model\ResourceModel\Ebay\Video::COLUMN_ACCOUNT_ID,
                                   Table::TYPE_INTEGER,
                                   null,
                                   ['unsigned' => true, 'nullable' => false]
                               )
                               ->addColumn(
                                   \Ess\M2ePro\Model\ResourceModel\Ebay\Video::COLUMN_URL,
                                   Table::TYPE_TEXT,
                                   null,
                                   ['nullable' => false]
                               )
                               ->addColumn(
                                   \Ess\M2ePro\Model\ResourceModel\Ebay\Video::COLUMN_STATUS,
                                   Table::TYPE_INTEGER,
                                   null,
                                   ['nullable' => false]
                               )
                               ->addColumn(
                                   \Ess\M2ePro\Model\ResourceModel\Ebay\Video::COLUMN_VIDEO_ID,
                                   Table::TYPE_TEXT,
                                   255,
                                   ['default' => null]
                               )
                               ->addColumn(
                                   \Ess\M2ePro\Model\ResourceModel\Ebay\Video::COLUMN_ERROR,
                                   Table::TYPE_TEXT,
                                   255,
                                   ['default' => null]
                               )
                               ->addColumn(
                                   \Ess\M2ePro\Model\ResourceModel\Ebay\Video::COLUMN_UPDATE_DATE,
                                   Table::TYPE_DATETIME,
                                   null,
                                   ['default' => null]
                               )
                               ->addColumn(
                                   \Ess\M2ePro\Model\ResourceModel\Ebay\Video::COLUMN_CREATE_DATE,
                                   Table::TYPE_DATETIME,
                                   null,
                                   ['default' => null]
                               )
                               ->setOption('type', 'INNODB')
                               ->setOption('charset', 'utf8')
                               ->setOption('collate', 'utf8_general_ci')
                               ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($ebayVideoTable);

        //region ebay_compliance_document
        $ebayComplianceDocumentsTableName = $this->getFullTableName(TablesHelper::TABLE_EBAY_COMPLIANCE_DOCUMENTS);

        $ebayComplianceDocumentsTable = $this->getConnection()->newTable($ebayComplianceDocumentsTableName);

        $ebayComplianceDocumentsTable
            ->addColumn(
                ComplianceDocumentsResource::COLUMN_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                ComplianceDocumentsResource::COLUMN_ACCOUNT_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                ComplianceDocumentsResource::COLUMN_HASH,
                Table::TYPE_TEXT,
                32,
                ['nullable' => false]
            )
            ->addColumn(
                ComplianceDocumentsResource::COLUMN_TYPE,
                Table::TYPE_TEXT,
                100,
                ['nullable' => false]
            )
            ->addColumn(
                ComplianceDocumentsResource::COLUMN_URL,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                ComplianceDocumentsResource::COLUMN_STATUS,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                ComplianceDocumentsResource::COLUMN_DOCUMENT_ID,
                Table::TYPE_TEXT,
                255
            )
            ->addColumn(
                ComplianceDocumentsResource::COLUMN_ERROR,
                Table::TYPE_TEXT,
                255
            )
            ->addColumn(
                ComplianceDocumentsResource::COLUMN_LANGUAGES,
                Table::TYPE_TEXT
            )
            ->addColumn(
                ComplianceDocumentsResource::COLUMN_CREATE_DATE,
                Table::TYPE_DATETIME
            )
            ->addColumn(
                ComplianceDocumentsResource::COLUMN_UPDATE_DATE,
                Table::TYPE_DATETIME
            );

        $ebayComplianceDocumentsTable
            ->addIndex(
                'account_id__type__url',
                [
                    ComplianceDocumentsResource::COLUMN_ACCOUNT_ID,
                    ComplianceDocumentsResource::COLUMN_TYPE,
                    ComplianceDocumentsResource::COLUMN_URL,
                ],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            );

        $ebayComplianceDocumentsTable
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($ebayComplianceDocumentsTable);
        //endregion

        //region ebay_compliance_document_listing_product
        $ebayComplianceDocumentListingProductTableName = $this->getFullTableName(
            TablesHelper::TABLE_EBAY_COMPLIANCE_DOCUMENTS_LISTING_PRODUCT
        );

        $ebayComplianceDocumentListingProductTable = $this
            ->getConnection()
            ->newTable($ebayComplianceDocumentListingProductTableName);

        $ebayComplianceDocumentListingProductTable
            ->addColumn(
                EbayComplianceDocumentListingProductRelationResource::COLUMN_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                EbayComplianceDocumentListingProductRelationResource::COLUMN_COMPLIANCE_DOCUMENT_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                EbayComplianceDocumentListingProductRelationResource::COLUMN_LISTING_PRODUCT_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            );

        $ebayComplianceDocumentListingProductTable->addIndex(
            'listing_product_id',
            EbayComplianceDocumentListingProductRelationResource::COLUMN_LISTING_PRODUCT_ID
        );

        $ebayComplianceDocumentListingProductTable
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($ebayComplianceDocumentListingProductTable);
        //endregion

        //region ebay_bundle_options_mapping
        $ebayBundleOptionsMappingTableName = $this->getFullTableName(
            TablesHelper::TABLE_EBAY_BUNDLE_OPTIONS_MAPPING
        );

        $ebayBundleOptionsMappingTable = $this
            ->getConnection()
            ->newTable($ebayBundleOptionsMappingTableName);

        $ebayBundleOptionsMappingTable
            ->addColumn(
                MappingResource::COLUMN_ID,
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
                MappingResource::COLUMN_OPTION_TITLE,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                MappingResource::COLUMN_ATTRIBUTE_CODE,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addIndex('option_title', MappingResource::COLUMN_OPTION_TITLE)
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($ebayBundleOptionsMappingTable);
        //endregion
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
        $moduleConfig->insert('/ebay/configuration/', 'ignore_variation_mpn_in_resolver', '0');
        $moduleConfig->insert('/ebay/configuration/', 'motors_epids_attribute');
        $moduleConfig->insert('/ebay/configuration/', 'uk_epids_attribute');
        $moduleConfig->insert('/ebay/configuration/', 'de_epids_attribute');
        $moduleConfig->insert('/ebay/configuration/', 'au_epids_attribute');
        $moduleConfig->insert('/ebay/configuration/', 'it_epids_attribute');
        $moduleConfig->insert('/ebay/configuration/', 'ktypes_attribute');
        $moduleConfig->insert('/ebay/configuration/', 'tecdoc_ktypes_product_mpn_attribute');
        $moduleConfig->insert('/ebay/configuration/', 'tecdoc_ktypes_it_vat_id');
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
                    'id' => 1,
                    'native_id' => 0,
                    'title' => 'United States',
                    'code' => 'US',
                    'url' => 'ebay.com',
                    'status' => 0,
                    'sorder' => 1,
                    'group_title' => 'America',
                    'component_mode' => 'ebay',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 2,
                    'native_id' => 2,
                    'title' => 'Canada',
                    'code' => 'Canada',
                    'url' => 'ebay.ca',
                    'status' => 0,
                    'sorder' => 8,
                    'group_title' => 'America',
                    'component_mode' => 'ebay',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 3,
                    'native_id' => 3,
                    'title' => 'United Kingdom',
                    'code' => 'UK',
                    'url' => 'ebay.co.uk',
                    'status' => 0,
                    'sorder' => 2,
                    'group_title' => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 4,
                    'native_id' => 15,
                    'title' => 'Australia',
                    'code' => 'Australia',
                    'url' => 'ebay.com.au',
                    'status' => 0,
                    'sorder' => 4,
                    'group_title' => 'Asia / Pacific',
                    'component_mode' => 'ebay',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 5,
                    'native_id' => 16,
                    'title' => 'Austria',
                    'code' => 'Austria',
                    'url' => 'ebay.at',
                    'status' => 0,
                    'sorder' => 5,
                    'group_title' => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 6,
                    'native_id' => 23,
                    'title' => 'Belgium (French)',
                    'code' => 'Belgium_French',
                    'url' => 'befr.ebay.be',
                    'status' => 0,
                    'sorder' => 7,
                    'group_title' => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 7,
                    'native_id' => 71,
                    'title' => 'France',
                    'code' => 'France',
                    'url' => 'ebay.fr',
                    'status' => 0,
                    'sorder' => 10,
                    'group_title' => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 8,
                    'native_id' => 77,
                    'title' => 'Germany',
                    'code' => 'Germany',
                    'url' => 'ebay.de',
                    'status' => 0,
                    'sorder' => 3,
                    'group_title' => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 9,
                    'native_id' => 100,
                    'title' => 'eBay Motors',
                    'code' => 'eBayMotors',
                    'url' => 'ebay.com/motors',
                    'status' => 0,
                    'sorder' => 23,
                    'group_title' => 'Other',
                    'component_mode' => 'ebay',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 10,
                    'native_id' => 101,
                    'title' => 'Italy',
                    'code' => 'Italy',
                    'url' => 'ebay.it',
                    'status' => 0,
                    'sorder' => 14,
                    'group_title' => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 11,
                    'native_id' => 123,
                    'title' => 'Belgium (Dutch)',
                    'code' => 'Belgium_Dutch',
                    'url' => 'benl.ebay.be',
                    'status' => 0,
                    'sorder' => 6,
                    'group_title' => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 12,
                    'native_id' => 146,
                    'title' => 'Netherlands',
                    'code' => 'Netherlands',
                    'url' => 'ebay.nl',
                    'status' => 0,
                    'sorder' => 16,
                    'group_title' => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 13,
                    'native_id' => 186,
                    'title' => 'Spain',
                    'code' => 'Spain',
                    'url' => 'ebay.es',
                    'status' => 0,
                    'sorder' => 19,
                    'group_title' => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 14,
                    'native_id' => 193,
                    'title' => 'Switzerland',
                    'code' => 'Switzerland',
                    'url' => 'ebay.ch',
                    'status' => 0,
                    'sorder' => 22,
                    'group_title' => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 15,
                    'native_id' => 201,
                    'title' => 'Hong Kong',
                    'code' => 'HongKong',
                    'url' => 'ebay.com.hk',
                    'status' => 0,
                    'sorder' => 11,
                    'group_title' => 'Asia / Pacific',
                    'component_mode' => 'ebay',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 16,
                    'native_id' => 203,
                    'title' => 'India',
                    'code' => 'India',
                    'url' => 'ebay.in',
                    'status' => 0,
                    'sorder' => 12,
                    'group_title' => 'Asia / Pacific',
                    'component_mode' => 'ebay',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 17,
                    'native_id' => 205,
                    'title' => 'Ireland',
                    'code' => 'Ireland',
                    'url' => 'ebay.ie',
                    'status' => 0,
                    'sorder' => 13,
                    'group_title' => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 18,
                    'native_id' => 207,
                    'title' => 'Malaysia',
                    'code' => 'Malaysia',
                    'url' => 'ebay.com.my',
                    'status' => 0,
                    'sorder' => 15,
                    'group_title' => 'Asia / Pacific',
                    'component_mode' => 'ebay',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 19,
                    'native_id' => 210,
                    'title' => 'Canada (French)',
                    'code' => 'CanadaFrench',
                    'url' => 'cafr.ebay.ca',
                    'status' => 0,
                    'sorder' => 9,
                    'group_title' => 'America',
                    'component_mode' => 'ebay',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 20,
                    'native_id' => 211,
                    'title' => 'Philippines',
                    'code' => 'Philippines',
                    'url' => 'ebay.ph',
                    'status' => 0,
                    'sorder' => 17,
                    'group_title' => 'Asia / Pacific',
                    'component_mode' => 'ebay',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 21,
                    'native_id' => 212,
                    'title' => 'Poland',
                    'code' => 'Poland',
                    'url' => 'ebay.pl',
                    'status' => 0,
                    'sorder' => 18,
                    'group_title' => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 22,
                    'native_id' => 216,
                    'title' => 'Singapore',
                    'code' => 'Singapore',
                    'url' => 'ebay.com.sg',
                    'status' => 0,
                    'sorder' => 20,
                    'group_title' => 'Asia / Pacific',
                    'component_mode' => 'ebay',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
            ]
        );

        $this->getConnection()->insertMultiple(
            $this->getFullTableName(TablesHelper::TABLE_EBAY_MARKETPLACE),
            [
                [
                    'marketplace_id' => 1,
                    'currency' => 'USD',
                    'origin_country' => 'us',
                    'language_code' => 'en_US',
                    'is_multivariation' => 1,
                    'is_freight_shipping' => 1,
                    'is_calculated_shipping' => 1,
                    'is_tax_table' => 1,
                    'is_vat' => 0,
                    'is_stp' => 1,
                    'is_stp_advanced' => 0,
                    'is_map' => 1,
                    'is_local_shipping_rate_table' => 1,
                    'is_international_shipping_rate_table' => 1,
                    'is_english_measurement_system' => 1,
                    'is_metric_measurement_system' => 0,
                    'is_managed_payments' => 1,
                    'is_global_shipping_program' => 1,
                    'is_return_description' => 0,
                    'is_epid' => 0,
                    'is_ktype' => 0,
                ],
                [
                    'marketplace_id' => 2,
                    'currency' => 'CAD',
                    'origin_country' => 'ca',
                    'language_code' => 'en_CA',
                    'is_multivariation' => 1,
                    'is_freight_shipping' => 1,
                    'is_calculated_shipping' => 1,
                    'is_tax_table' => 1,
                    'is_vat' => 0,
                    'is_stp' => 1,
                    'is_stp_advanced' => 0,
                    'is_map' => 0,
                    'is_local_shipping_rate_table' => 1,
                    'is_international_shipping_rate_table' => 1,
                    'is_english_measurement_system' => 1,
                    'is_metric_measurement_system' => 1,
                    'is_managed_payments' => 1,
                    'is_global_shipping_program' => 0,
                    'is_return_description' => 0,
                    'is_epid' => 0,
                    'is_ktype' => 0,
                ],
                [
                    'marketplace_id' => 3,
                    'currency' => 'GBP',
                    'origin_country' => 'gb',
                    'language_code' => 'en_GB',
                    'is_multivariation' => 1,
                    'is_freight_shipping' => 1,
                    'is_calculated_shipping' => 0,
                    'is_tax_table' => 0,
                    'is_vat' => 1,
                    'is_stp' => 1,
                    'is_stp_advanced' => 1,
                    'is_map' => 0,
                    'is_local_shipping_rate_table' => 1,
                    'is_international_shipping_rate_table' => 1,
                    'is_english_measurement_system' => 0,
                    'is_metric_measurement_system' => 1,
                    'is_managed_payments' => 1,
                    'is_global_shipping_program' => 1,
                    'is_return_description' => 0,
                    'is_epid' => 1,
                    'is_ktype' => 1,
                ],
                [
                    'marketplace_id' => 4,
                    'currency' => 'AUD',
                    'origin_country' => 'au',
                    'language_code' => 'en_AU',
                    'is_multivariation' => 1,
                    'is_freight_shipping' => 1,
                    'is_calculated_shipping' => 1,
                    'is_tax_table' => 0,
                    'is_vat' => 0,
                    'is_stp' => 1,
                    'is_stp_advanced' => 0,
                    'is_map' => 0,
                    'is_local_shipping_rate_table' => 1,
                    'is_international_shipping_rate_table' => 1,
                    'is_english_measurement_system' => 0,
                    'is_metric_measurement_system' => 1,
                    'is_managed_payments' => 1,
                    'is_global_shipping_program' => 0,
                    'is_return_description' => 0,
                    'is_epid' => 1,
                    'is_ktype' => 1,
                ],
                [
                    'marketplace_id' => 5,
                    'currency' => 'EUR',
                    'origin_country' => 'at',
                    'language_code' => 'de_AT',
                    'is_multivariation' => 1,
                    'is_freight_shipping' => 0,
                    'is_calculated_shipping' => 0,
                    'is_tax_table' => 0,
                    'is_vat' => 1,
                    'is_stp' => 0,
                    'is_stp_advanced' => 0,
                    'is_map' => 0,
                    'is_local_shipping_rate_table' => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system' => 0,
                    'is_metric_measurement_system' => 1,
                    'is_managed_payments' => 0,
                    'is_global_shipping_program' => 0,
                    'is_return_description' => 1,
                    'is_epid' => 0,
                    'is_ktype' => 0,
                ],
                [
                    'marketplace_id' => 6,
                    'currency' => 'EUR',
                    'origin_country' => 'be',
                    'language_code' => 'nl_BE',
                    'is_multivariation' => 0,
                    'is_freight_shipping' => 0,
                    'is_calculated_shipping' => 0,
                    'is_tax_table' => 0,
                    'is_vat' => 1,
                    'is_stp' => 0,
                    'is_stp_advanced' => 0,
                    'is_map' => 0,
                    'is_local_shipping_rate_table' => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system' => 0,
                    'is_metric_measurement_system' => 1,
                    'is_managed_payments' => 0,
                    'is_global_shipping_program' => 0,
                    'is_return_description' => 0,
                    'is_epid' => 0,
                    'is_ktype' => 0,
                ],
                [
                    'marketplace_id' => 7,
                    'currency' => 'EUR',
                    'origin_country' => 'fr',
                    'language_code' => 'fr_FR',
                    'is_multivariation' => 1,
                    'is_freight_shipping' => 0,
                    'is_calculated_shipping' => 0,
                    'is_tax_table' => 0,
                    'is_vat' => 1,
                    'is_stp' => 1,
                    'is_stp_advanced' => 0,
                    'is_map' => 0,
                    'is_local_shipping_rate_table' => 1,
                    'is_international_shipping_rate_table' => 1,
                    'is_english_measurement_system' => 0,
                    'is_metric_measurement_system' => 1,
                    'is_managed_payments' => 1,
                    'is_global_shipping_program' => 0,
                    'is_return_description' => 1,
                    'is_epid' => 0,
                    'is_ktype' => 1,
                ],
                [
                    'marketplace_id' => 8,
                    'currency' => 'EUR',
                    'origin_country' => 'de',
                    'language_code' => 'de_DE',
                    'is_multivariation' => 1,
                    'is_freight_shipping' => 0,
                    'is_calculated_shipping' => 0,
                    'is_tax_table' => 0,
                    'is_vat' => 1,
                    'is_stp' => 1,
                    'is_stp_advanced' => 1,
                    'is_map' => 0,
                    'is_local_shipping_rate_table' => 1,
                    'is_international_shipping_rate_table' => 1,
                    'is_english_measurement_system' => 0,
                    'is_metric_measurement_system' => 1,
                    'is_managed_payments' => 1,
                    'is_global_shipping_program' => 0,
                    'is_return_description' => 1,
                    'is_epid' => 1,
                    'is_ktype' => 1,
                ],
                [
                    'marketplace_id' => 9,
                    'currency' => 'USD',
                    'origin_country' => 'us',
                    'language_code' => 'en_US',
                    'is_multivariation' => 1,
                    'is_freight_shipping' => 0,
                    'is_calculated_shipping' => 1,
                    'is_tax_table' => 1,
                    'is_vat' => 0,
                    'is_stp' => 1,
                    'is_stp_advanced' => 0,
                    'is_map' => 0,
                    'is_local_shipping_rate_table' => 1,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system' => 1,
                    'is_metric_measurement_system' => 0,
                    'is_managed_payments' => 1,
                    'is_global_shipping_program' => 1,
                    'is_return_description' => 0,
                    'is_epid' => 1,
                    'is_ktype' => 0,
                ],
                [
                    'marketplace_id' => 10,
                    'currency' => 'EUR',
                    'origin_country' => 'it',
                    'language_code' => 'it_IT',
                    'is_multivariation' => 1,
                    'is_freight_shipping' => 0,
                    'is_calculated_shipping' => 0,
                    'is_tax_table' => 0,
                    'is_vat' => 1,
                    'is_stp' => 1,
                    'is_stp_advanced' => 0,
                    'is_map' => 0,
                    'is_local_shipping_rate_table' => 1,
                    'is_international_shipping_rate_table' => 1,
                    'is_english_measurement_system' => 0,
                    'is_metric_measurement_system' => 1,
                    'is_managed_payments' => 1,
                    'is_global_shipping_program' => 0,
                    'is_return_description' => 1,
                    'is_epid' => 1,
                    'is_ktype' => 1,
                ],
                [
                    'marketplace_id' => 11,
                    'currency' => 'EUR',
                    'origin_country' => 'be',
                    'language_code' => 'fr_BE',
                    'is_multivariation' => 0,
                    'is_freight_shipping' => 0,
                    'is_calculated_shipping' => 0,
                    'is_tax_table' => 0,
                    'is_vat' => 1,
                    'is_stp' => 0,
                    'is_stp_advanced' => 0,
                    'is_map' => 0,
                    'is_local_shipping_rate_table' => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system' => 0,
                    'is_metric_measurement_system' => 1,
                    'is_managed_payments' => 0,
                    'is_global_shipping_program' => 0,
                    'is_return_description' => 0,
                    'is_epid' => 0,
                    'is_ktype' => 0,
                ],
                [
                    'marketplace_id' => 12,
                    'currency' => 'EUR',
                    'origin_country' => 'nl',
                    'language_code' => 'nl_NL',
                    'is_multivariation' => 1,
                    'is_freight_shipping' => 0,
                    'is_calculated_shipping' => 0,
                    'is_tax_table' => 0,
                    'is_vat' => 1,
                    'is_stp' => 0,
                    'is_stp_advanced' => 0,
                    'is_map' => 0,
                    'is_local_shipping_rate_table' => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system' => 0,
                    'is_metric_measurement_system' => 1,
                    'is_managed_payments' => 0,
                    'is_global_shipping_program' => 0,
                    'is_return_description' => 0,
                    'is_epid' => 0,
                    'is_ktype' => 0,
                ],
                [
                    'marketplace_id' => 13,
                    'currency' => 'EUR',
                    'origin_country' => 'es',
                    'language_code' => 'es_ES',
                    'is_multivariation' => 1,
                    'is_freight_shipping' => 0,
                    'is_calculated_shipping' => 0,
                    'is_tax_table' => 0,
                    'is_vat' => 1,
                    'is_stp' => 1,
                    'is_stp_advanced' => 0,
                    'is_map' => 0,
                    'is_local_shipping_rate_table' => 1,
                    'is_international_shipping_rate_table' => 1,
                    'is_english_measurement_system' => 0,
                    'is_metric_measurement_system' => 1,
                    'is_managed_payments' => 1,
                    'is_global_shipping_program' => 0,
                    'is_return_description' => 1,
                    'is_epid' => 0,
                    'is_ktype' => 1,
                ],
                [
                    'marketplace_id' => 14,
                    'currency' => 'CHF',
                    'origin_country' => 'ch',
                    'language_code' => 'fr_CH',
                    'is_multivariation' => 1,
                    'is_freight_shipping' => 0,
                    'is_calculated_shipping' => 0,
                    'is_tax_table' => 0,
                    'is_vat' => 1,
                    'is_stp' => 0,
                    'is_stp_advanced' => 0,
                    'is_map' => 0,
                    'is_local_shipping_rate_table' => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system' => 0,
                    'is_metric_measurement_system' => 1,
                    'is_managed_payments' => 0,
                    'is_global_shipping_program' => 0,
                    'is_return_description' => 0,
                    'is_epid' => 0,
                    'is_ktype' => 0,
                ],
                [
                    'marketplace_id' => 15,
                    'currency' => 'HKD',
                    'origin_country' => 'hk',
                    'language_code' => 'zh_HK',
                    'is_multivariation' => 0,
                    'is_freight_shipping' => 0,
                    'is_calculated_shipping' => 0,
                    'is_tax_table' => 0,
                    'is_vat' => 0,
                    'is_stp' => 0,
                    'is_stp_advanced' => 0,
                    'is_map' => 0,
                    'is_local_shipping_rate_table' => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system' => 0,
                    'is_metric_measurement_system' => 1,
                    'is_managed_payments' => 0,
                    'is_global_shipping_program' => 0,
                    'is_return_description' => 0,
                    'is_epid' => 0,
                    'is_ktype' => 0,
                ],
                [
                    'marketplace_id' => 16,
                    'currency' => 'INR',
                    'origin_country' => 'in',
                    'language_code' => 'hi_IN',
                    'is_multivariation' => 1,
                    'is_freight_shipping' => 0,
                    'is_calculated_shipping' => 0,
                    'is_tax_table' => 0,
                    'is_vat' => 1,
                    'is_stp' => 0,
                    'is_stp_advanced' => 0,
                    'is_map' => 0,
                    'is_local_shipping_rate_table' => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system' => 0,
                    'is_metric_measurement_system' => 1,
                    'is_managed_payments' => 0,
                    'is_global_shipping_program' => 0,
                    'is_return_description' => 0,
                    'is_epid' => 0,
                    'is_ktype' => 0,
                ],
                [
                    'marketplace_id' => 17,
                    'currency' => 'EUR',
                    'origin_country' => 'ie',
                    'language_code' => 'en_IE',
                    'is_multivariation' => 1,
                    'is_freight_shipping' => 0,
                    'is_calculated_shipping' => 0,
                    'is_tax_table' => 0,
                    'is_vat' => 1,
                    'is_stp' => 0,
                    'is_stp_advanced' => 0,
                    'is_map' => 0,
                    'is_local_shipping_rate_table' => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system' => 0,
                    'is_metric_measurement_system' => 1,
                    'is_managed_payments' => 0,
                    'is_global_shipping_program' => 0,
                    'is_return_description' => 0,
                    'is_epid' => 0,
                    'is_ktype' => 0,
                ],
                [
                    'marketplace_id' => 18,
                    'currency' => 'MYR',
                    'origin_country' => 'my',
                    'language_code' => 'ms_MY',
                    'is_multivariation' => 1,
                    'is_freight_shipping' => 0,
                    'is_calculated_shipping' => 0,
                    'is_tax_table' => 0,
                    'is_vat' => 0,
                    'is_stp' => 0,
                    'is_stp_advanced' => 0,
                    'is_map' => 0,
                    'is_local_shipping_rate_table' => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system' => 0,
                    'is_metric_measurement_system' => 1,
                    'is_managed_payments' => 0,
                    'is_global_shipping_program' => 0,
                    'is_return_description' => 0,
                    'is_epid' => 0,
                    'is_ktype' => 0,
                ],
                [
                    'marketplace_id' => 19,
                    'currency' => 'CAD',
                    'origin_country' => 'ca',
                    'language_code' => 'fr_CA',
                    'is_multivariation' => 0,
                    'is_freight_shipping' => 1,
                    'is_calculated_shipping' => 1,
                    'is_tax_table' => 1,
                    'is_vat' => 0,
                    'is_stp' => 1,
                    'is_stp_advanced' => 0,
                    'is_map' => 0,
                    'is_local_shipping_rate_table' => 1,
                    'is_international_shipping_rate_table' => 1,
                    'is_english_measurement_system' => 1,
                    'is_metric_measurement_system' => 1,
                    'is_managed_payments' => 0,
                    'is_global_shipping_program' => 0,
                    'is_return_description' => 0,
                    'is_epid' => 0,
                    'is_ktype' => 0,
                ],
                [
                    'marketplace_id' => 20,
                    'currency' => 'PHP',
                    'origin_country' => 'ph',
                    'language_code' => 'fil_PH',
                    'is_multivariation' => 1,
                    'is_freight_shipping' => 0,
                    'is_calculated_shipping' => 0,
                    'is_tax_table' => 0,
                    'is_vat' => 0,
                    'is_stp' => 0,
                    'is_stp_advanced' => 0,
                    'is_map' => 0,
                    'is_local_shipping_rate_table' => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system' => 0,
                    'is_metric_measurement_system' => 1,
                    'is_managed_payments' => 0,
                    'is_global_shipping_program' => 0,
                    'is_return_description' => 0,
                    'is_epid' => 0,
                    'is_ktype' => 0,
                ],
                [
                    'marketplace_id' => 21,
                    'currency' => 'PLN',
                    'origin_country' => 'pl',
                    'language_code' => 'pl_PL',
                    'is_multivariation' => 0,
                    'is_freight_shipping' => 0,
                    'is_calculated_shipping' => 0,
                    'is_tax_table' => 0,
                    'is_vat' => 1,
                    'is_stp' => 0,
                    'is_stp_advanced' => 0,
                    'is_map' => 0,
                    'is_local_shipping_rate_table' => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system' => 0,
                    'is_metric_measurement_system' => 1,
                    'is_managed_payments' => 0,
                    'is_global_shipping_program' => 0,
                    'is_return_description' => 0,
                    'is_epid' => 0,
                    'is_ktype' => 0,
                ],
                [
                    'marketplace_id' => 22,
                    'currency' => 'SGD',
                    'origin_country' => 'sg',
                    'language_code' => 'zh_SG',
                    'is_multivariation' => 0,
                    'is_freight_shipping' => 0,
                    'is_calculated_shipping' => 0,
                    'is_tax_table' => 0,
                    'is_vat' => 0,
                    'is_stp' => 0,
                    'is_stp_advanced' => 0,
                    'is_map' => 0,
                    'is_local_shipping_rate_table' => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system' => 0,
                    'is_metric_measurement_system' => 1,
                    'is_managed_payments' => 0,
                    'is_global_shipping_program' => 0,
                    'is_return_description' => 0,
                    'is_epid' => 0,
                    'is_ktype' => 0,
                ],
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
                                       'create_magento_shipment_fba_orders',
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

        #region amazon_account_merchant_setting
        $amazonAccountMerchantSettingTableName = $this->getFullTableName(
            TablesHelper::TABLE_AMAZON_ACCOUNT_MERCHANT_SETTING
        );
        $amazonAccountMerchantSettingTable = $this->getConnection()->newTable($amazonAccountMerchantSettingTableName);
        $amazonAccountMerchantSettingTable->addColumn(
            'merchant_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['primary' => true, 'nullable' => false,]
        );
        $amazonAccountMerchantSettingTable->addColumn(
            'fba_inventory_mode',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 0]
        );
        $amazonAccountMerchantSettingTable->addColumn(
            'fba_inventory_source_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['default' => null]
        );
        $amazonAccountMerchantSettingTable->addColumn(
            'update_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            ['default' => null]
        );
        $amazonAccountMerchantSettingTable->addColumn(
            'create_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            ['default' => null]
        );

        $amazonAccountMerchantSettingTable->setOption('type', 'INNODB');
        $amazonAccountMerchantSettingTable->setOption('charset', 'utf8');
        $amazonAccountMerchantSettingTable->setOption('collate', 'utf8_general_ci');
        $amazonAccountMerchantSettingTable->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($amazonAccountMerchantSettingTable);
        #endregion

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
                                                'invalid',
                                                Table::TYPE_SMALLINT,
                                                null,
                                                ['unsigned' => true, 'nullable' => false, 'default' => 0]
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

        $shippingMethodsTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_shipping_map')
        )
                                          ->addColumn(
                                              'id',
                                              Table::TYPE_INTEGER,
                                              null,
                                              [
                                                  'unsigned' => true,
                                                  'primary' => true,
                                                  'nullable' => false,
                                                  'auto_increment' => true,
                                              ],
                                              'ID'
                                          )
                                          ->addColumn(
                                              'marketplace_id',
                                              Table::TYPE_INTEGER,
                                              null,
                                              ['unsigned' => true, 'nullable' => false],
                                              'Marketplace ID'
                                          )
                                          ->addColumn(
                                              'location',
                                              Table::TYPE_TEXT,
                                              255,
                                              ['nullable' => false],
                                              'Location (Domestic/International)'
                                          )
                                          ->addColumn(
                                              'amazon_code',
                                              Table::TYPE_TEXT,
                                              255,
                                              ['nullable' => false],
                                              'Amazon shipping code'
                                          )
                                          ->addColumn(
                                              'magento_code',
                                              Table::TYPE_TEXT,
                                              255,
                                              ['nullable' => false],
                                              'Magento shipping code'
                                          )
                                          ->setComment('Shipping Methods Table')
                                          ->setOption('type', 'INNODB')
                                          ->setOption('charset', 'utf8')
                                          ->setOption('collate', 'utf8_general_ci')
                                          ->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($shippingMethodsTable);

        # region amazon_dictionary_marketplace
        $amazonDictionaryMarketplaceTable = $this->getConnection()->newTable(
            $this->getFullTableName(TablesHelper::TABLE_AMAZON_DICTIONARY_MARKETPLACE)
        )
                                                 ->addColumn(
                                                     AmazonDictionaryMarketplaceResoruce::COLUMN_ID,
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
                                                     AmazonDictionaryMarketplaceResoruce::COLUMN_MARKETPLACE_ID,
                                                     Table::TYPE_INTEGER,
                                                     null,
                                                     ['unsigned' => true, 'nullable' => false]
                                                 )
                                                 ->addColumn(
                                                     AmazonDictionaryMarketplaceResoruce::COLUMN_PRODUCT_TYPES,
                                                     Table::TYPE_TEXT,
                                                     self::LONG_COLUMN_SIZE,
                                                     ['default' => null]
                                                 )
                                                 ->addIndex(
                                                     'marketplace_id',
                                                     AmazonDictionaryMarketplaceResoruce::COLUMN_MARKETPLACE_ID
                                                 )
                                                 ->setOption('type', 'INNODB')
                                                 ->setOption('charset', 'utf8')
                                                 ->setOption('collate', 'utf8_general_ci')
                                                 ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($amazonDictionaryMarketplaceTable);
        # endregion

        #region amazon_dictionary_product_type
        $amazonDictionaryProductTypeTable = $this->getConnection()->newTable(
            $this->getFullTableName(TablesHelper::TABLE_AMAZON_DICTIONARY_PRODUCT_TYPE)
        )
                                                 ->addColumn(
                                                     AmazonDictionaryProductTypeResource::COLUMN_ID,
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
                                                     AmazonDictionaryProductTypeResource::COLUMN_MARKETPLACE_ID,
                                                     Table::TYPE_INTEGER,
                                                     null,
                                                     ['unsigned' => true, 'nullable' => false]
                                                 )
                                                 ->addColumn(
                                                     AmazonDictionaryProductTypeResource::COLUMN_NICK,
                                                     Table::TYPE_TEXT,
                                                     255,
                                                     ['nullable' => false]
                                                 )
                                                 ->addColumn(
                                                     AmazonDictionaryProductTypeResource::COLUMN_TITLE,
                                                     Table::TYPE_TEXT,
                                                     255,
                                                     ['nullable' => false]
                                                 )
                                                 ->addColumn(
                                                     AmazonDictionaryProductTypeResource::COLUMN_SCHEMA,
                                                     Table::TYPE_TEXT,
                                                     self::LONG_COLUMN_SIZE,
                                                     ['nullable' => false]
                                                 )
                                                 ->addColumn(
                                                     AmazonDictionaryProductTypeResource::COLUMN_VARIATION_THEMES,
                                                     Table::TYPE_TEXT,
                                                     self::LONG_COLUMN_SIZE,
                                                     ['nullable' => false],
                                                 )
                                                 ->addColumn(
                                                     AmazonDictionaryProductTypeResource::COLUMN_ATTRIBUTES_GROUPS,
                                                     Table::TYPE_TEXT,
                                                     self::LONG_COLUMN_SIZE,
                                                     ['nullable' => false],
                                                 )
                                                 ->addColumn(
                                                     AmazonDictionaryProductTypeResource::COLUMN_CLIENT_DETAILS_LAST_UPDATE_DATE,
                                                     Table::TYPE_DATETIME,
                                                     null,
                                                     ['nullable' => false],
                                                 )
                                                 ->addColumn(
                                                     AmazonDictionaryProductTypeResource::COLUMN_SERVER_DETAILS_LAST_UPDATE_DATE,
                                                     Table::TYPE_DATETIME,
                                                     null,
                                                     ['nullable' => false],
                                                 )
                                                 ->addColumn(
                                                     AmazonDictionaryProductTypeResource::COLUMN_INVALID,
                                                     Table::TYPE_SMALLINT,
                                                     null,
                                                     ['unsigned' => true, 'nullable' => false, 'default' => 0]
                                                 )
                                                 ->addIndex(
                                                     'marketplace_id_nick',
                                                     ['marketplace_id', 'nick'],
                                                     ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
                                                 )
                                                 ->setOption('type', 'INNODB')
                                                 ->setOption('charset', 'utf8')
                                                 ->setOption('collate', 'utf8_general_ci')
                                                 ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($amazonDictionaryProductTypeTable);
        # endregion

        $amazonInventorySkuTable = $this->getConnection()->newTable($this->getFullTableName('amazon_inventory_sku'))
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
                                    [
                                        'unsigned' => true,
                                        'primary' => true,
                                        'nullable' => false,
                                        'auto_increment' => true,
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

        #region amazon_listing
        $amazonListingTable = $this->getConnection()->newTable(
            $this->getFullTableName(TablesHelper::TABLE_AMAZON_LISTING)
        )
                                   ->addColumn(
                                       'listing_id',
                                       Table::TYPE_INTEGER,
                                       null,
                                       ['unsigned' => true, 'primary' => true, 'nullable' => false]
                                   )
                                   ->addColumn(
                                       'auto_global_adding_product_type_template_id',
                                       Table::TYPE_INTEGER,
                                       null,
                                       ['unsigned' => true, 'default' => null]
                                   )
                                   ->addColumn(
                                       'auto_website_adding_product_type_template_id',
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
                                       \Ess\M2ePro\Model\ResourceModel\Amazon\Listing::COLUMN_OFFER_IMAGES,
                                       Table::TYPE_TEXT,
                                       self::LONG_COLUMN_SIZE,
                                       ['default' => null]
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
                                       \Ess\M2ePro\Model\ResourceModel\Amazon\Listing::COLUMN_RESTOCK_DATE_CUSTOM_ATTRIBUTE,
                                       Table::TYPE_TEXT,
                                       255,
                                       ['nullable' => false]
                                   )
                                   ->addColumn(
                                       \Ess\M2ePro\Model\ResourceModel\Amazon\Listing::COLUMN_GENERAL_ID_ATTRIBUTE,
                                       Table::TYPE_TEXT,
                                       255,
                                       ['default' => null]
                                   )
                                   ->addColumn(
                                       \Ess\M2ePro\Model\ResourceModel\Amazon\Listing::COLUMN_WORLDWIDE_ID_ATTRIBUTE,
                                       Table::TYPE_TEXT,
                                       255,
                                       ['default' => null]
                                   )
                                   ->addColumn(
                                       'product_add_ids',
                                       Table::TYPE_TEXT,
                                       self::LONG_COLUMN_SIZE,
                                       ['default' => null]
                                   )
                                   ->addIndex(
                                       'auto_global_adding_product_type_template_id`',
                                       'auto_global_adding_product_type_template_id'
                                   )
                                   ->addIndex(
                                       'auto_website_adding_product_type_template_id',
                                       'auto_website_adding_product_type_template_id'
                                   )
                                   ->addIndex('generate_sku_mode', 'generate_sku_mode')
                                   ->addIndex('template_selling_format_id', 'template_selling_format_id')
                                   ->addIndex('template_synchronization_id', 'template_synchronization_id')
                                   ->addIndex('template_shipping_id', 'template_shipping_id')
                                   ->setOption('type', 'INNODB')
                                   ->setOption('charset', 'utf8')
                                   ->setOption('collate', 'utf8_general_ci')
                                   ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($amazonListingTable);
        #endregion

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
                                                        'adding_product_type_template_id',
                                                        Table::TYPE_INTEGER,
                                                        null,
                                                        ['unsigned' => true, 'default' => null]
                                                    )
                                                    ->addIndex(
                                                        'adding_product_type_template_id',
                                                        'adding_product_type_template_id'
                                                    )
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
                                            'online_afn_qty',
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

        #region amazon_listing_product
        $amazonListingProductTableName = $this->getFullTableName(
            TablesHelper::TABLE_AMAZON_LISTING_PRODUCT
        );
        $amazonListingProductTable = $this->getConnection()->newTable($amazonListingProductTableName);
        $amazonListingProductTable->addColumn(
            'listing_product_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'primary' => true, 'nullable' => false]
        );
        $amazonListingProductTable->addColumn(
            'template_product_type_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'default' => null]
        );
        $amazonListingProductTable->addColumn(
            'template_description_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'default' => null]
        );
        $amazonListingProductTable->addColumn(
            'template_shipping_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'default' => null]
        );
        $amazonListingProductTable->addColumn(
            'template_product_tax_code_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'default' => null]
        );
        $amazonListingProductTable->addColumn(
            'is_variation_product',
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 0]
        );
        $amazonListingProductTable->addColumn(
            'is_variation_product_matched',
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 0]
        );
        $amazonListingProductTable->addColumn(
            'is_variation_channel_matched',
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 0]
        );
        $amazonListingProductTable->addColumn(
            'is_variation_parent',
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 0]
        );
        $amazonListingProductTable->addColumn(
            'variation_parent_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'default' => null]
        );
        $amazonListingProductTable->addColumn(
            'variation_parent_need_processor',
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 0]
        );
        $amazonListingProductTable->addColumn(
            'variation_child_statuses',
            Table::TYPE_TEXT,
            null,
            ['default' => null]
        );
        $amazonListingProductTable->addColumn(
            'general_id',
            Table::TYPE_TEXT,
            255,
            ['default' => null]
        );
        $amazonListingProductTable->addColumn(
            'general_id_search_info',
            Table::TYPE_TEXT,
            null,
            ['default' => null]
        );
        $amazonListingProductTable->addColumn(
            'search_settings_status',
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'default' => null]
        );
        $amazonListingProductTable->addColumn(
            'search_settings_data',
            Table::TYPE_TEXT,
            Table::MAX_TEXT_SIZE,
            ['default' => null]
        );
        $amazonListingProductTable->addColumn(
            'sku',
            Table::TYPE_TEXT,
            255,
            ['default' => null]
        );
        $amazonListingProductTable->addColumn(
            'online_regular_price',
            Table::TYPE_DECIMAL,
            [12, 4],
            ['unsigned' => true, 'default' => null]
        );
        $amazonListingProductTable->addColumn(
            'online_regular_map_price',
            Table::TYPE_DECIMAL,
            [12, 4],
            ['unsigned' => true, 'default' => null]
        );
        $amazonListingProductTable->addColumn(
            'online_regular_sale_price',
            Table::TYPE_DECIMAL,
            [12, 4],
            ['unsigned' => true, 'default' => null]
        );
        $amazonListingProductTable->addColumn(
            'online_regular_sale_price_start_date',
            Table::TYPE_DATETIME,
            null,
            ['default' => null]
        );
        $amazonListingProductTable->addColumn(
            'online_regular_sale_price_end_date',
            Table::TYPE_DATETIME,
            null,
            ['default' => null]
        );
        $amazonListingProductTable->addColumn(
            'online_business_price',
            Table::TYPE_DECIMAL,
            [12, 4],
            ['unsigned' => true, 'default' => null]
        );
        $amazonListingProductTable->addColumn(
            'online_business_discounts',
            Table::TYPE_TEXT,
            null,
            ['default' => null]
        );
        $amazonListingProductTable->addColumn(
            'online_qty',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'default' => null]
        );
        $amazonListingProductTable->addColumn(
            'online_afn_qty',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'default' => null]
        );
        $amazonListingProductTable->addColumn(
            'online_handling_time',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'default' => null]
        );
        $amazonListingProductTable->addColumn(
            'online_restock_date',
            Table::TYPE_DATETIME,
            null,
            ['default' => null]
        );
        $amazonListingProductTable->addColumn(
            'online_details_data',
            Table::TYPE_TEXT,
            40,
            ['default' => null]
        );
        $amazonListingProductTable->addColumn(
            'is_repricing',
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 0]
        );
        $amazonListingProductTable->addColumn(
            'is_afn_channel',
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 0]
        );
        $amazonListingProductTable->addColumn(
            'is_isbn_general_id',
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'default' => null]
        );
        $amazonListingProductTable->addColumn(
            'is_general_id_owner',
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 0]
        );
        $amazonListingProductTable->addColumn(
            'is_stopped_manually',
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 0]
        );
        $amazonListingProductTable->addColumn(
            'variation_parent_afn_state',
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'default' => null]
        );
        $amazonListingProductTable->addColumn(
            'variation_parent_repricing_state',
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'default' => null]
        );
        $amazonListingProductTable->addColumn(
            'defected_messages',
            Table::TYPE_TEXT,
            null,
            ['default' => null]
        );
        $amazonListingProductTable->addColumn(
            'list_date',
            Table::TYPE_DATETIME,
            null,
            ['nullable' => true]
        );
        $amazonListingProductTable->addIndex('general_id', 'general_id');
        $amazonListingProductTable->addIndex('search_settings_status', 'search_settings_status');
        $amazonListingProductTable->addIndex('is_repricing', 'is_repricing');
        $amazonListingProductTable->addIndex('is_afn_channel', 'is_afn_channel');
        $amazonListingProductTable->addIndex('is_isbn_general_id', 'is_isbn_general_id');
        $amazonListingProductTable->addIndex('is_variation_product_matched', 'is_variation_product_matched');
        $amazonListingProductTable->addIndex('is_variation_channel_matched', 'is_variation_channel_matched');
        $amazonListingProductTable->addIndex('is_variation_product', 'is_variation_product');
        $amazonListingProductTable->addIndex('online_regular_price', 'online_regular_price');
        $amazonListingProductTable->addIndex('online_qty', 'online_qty');
        $amazonListingProductTable->addIndex('online_regular_sale_price', 'online_regular_sale_price');
        $amazonListingProductTable->addIndex('online_business_price', 'online_business_price');
        $amazonListingProductTable->addIndex('sku', 'sku');
        $amazonListingProductTable->addIndex('is_variation_parent', 'is_variation_parent');
        $amazonListingProductTable->addIndex('variation_parent_need_processor', 'variation_parent_need_processor');
        $amazonListingProductTable->addIndex('variation_parent_id', 'variation_parent_id');
        $amazonListingProductTable->addIndex('is_general_id_owner', 'is_general_id_owner');
        $amazonListingProductTable->addIndex('variation_parent_afn_state', 'variation_parent_afn_state');
        $amazonListingProductTable->addIndex('variation_parent_repricing_state', 'variation_parent_repricing_state');
        $amazonListingProductTable->addIndex('template_shipping_id', 'template_shipping_id');
        $amazonListingProductTable->addIndex('template_product_tax_code_id', 'template_product_tax_code_id');
        $amazonListingProductTable->addIndex('template_product_type_id', 'template_product_type_id');
        $amazonListingProductTable->addIndex('list_date', 'list_date');
        $amazonListingProductTable->setOption('type', 'INNODB');
        $amazonListingProductTable->setOption('charset', 'utf8');
        $amazonListingProductTable->setOption('collate', 'utf8_general_ci');
        $amazonListingProductTable->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($amazonListingProductTable);
        #endregion

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
                                                             [
                                                                 'unsigned' => true,
                                                                 'primary' => true,
                                                                 'nullable' => false,
                                                             ]
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
                                                                    [
                                                                        'unsigned' => true,
                                                                        'primary' => true,
                                                                        'nullable' => false,
                                                                    ]
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

        $amazonMarketplaceTable = $this->getConnection()->newTable(
            $this->getFullTableName(TablesHelper::TABLE_AMAZON_MARKETPLACE)
        )
                                       ->addColumn(
                                           'marketplace_id',
                                           Table::TYPE_INTEGER,
                                           null,
                                           ['unsigned' => true, 'primary' => true, 'nullable' => false]
                                       )
                                       ->addColumn(
                                           'default_currency',
                                           Table::TYPE_TEXT,
                                           255,
                                           ['nullable' => false]
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
                                       ->addIndex(
                                           'is_merchant_fulfillment_available',
                                           'is_merchant_fulfillment_available'
                                       )
                                       ->addIndex('is_business_available', 'is_business_available')
                                       ->addIndex(
                                           'is_vat_calculation_service_available',
                                           'is_vat_calculation_service_available'
                                       )
                                       ->addIndex(
                                           'is_product_tax_code_policy_available',
                                           'is_product_tax_code_policy_available'
                                       )
                                       ->setOption('type', 'INNODB')
                                       ->setOption('charset', 'utf8')
                                       ->setOption('collate', 'utf8_general_ci')
                                       ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($amazonMarketplaceTable);

        #region amazon_order
        $amazonOrderTable = $this->getConnection()->newTable(
            $this->getFullTableName(TablesHelper::TABLE_AMAZON_ORDER)
        )
                                 ->addColumn(
                                     \Ess\M2ePro\Model\ResourceModel\Amazon\Order::COLUMN_ORDER_ID,
                                     Table::TYPE_INTEGER,
                                     null,
                                     ['unsigned' => true, 'primary' => true, 'nullable' => false]
                                 )
                                 ->addColumn(
                                     \Ess\M2ePro\Model\ResourceModel\Amazon\Order::COLUMN_AMAZON_ORDER_ID,
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
                                     'is_sold_by_amazon',
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
                                     'is_replacement',
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
                                     \Ess\M2ePro\Model\ResourceModel\Amazon\Order::COLUMN_IS_INVOICE_SENT,
                                     Table::TYPE_SMALLINT,
                                     null,
                                     ['unsigned' => true, 'nullable' => false, 'default' => 0]
                                 )
                                 ->addColumn(
                                     \Ess\M2ePro\Model\ResourceModel\Amazon\Order::COLUMN_DATE_OF_INVOICE_SENDING,
                                     Table::TYPE_DATETIME,
                                     null,
                                     ['default' => null]
                                 )
                                 ->addColumn(
                                     'is_credit_memo_sent',
                                     Table::TYPE_SMALLINT,
                                     null,
                                     ['unsigned' => true, 'nullable' => false, 'default' => 0]
                                 )
                                ->addColumn(
                                    'is_get_delivery_preferences',
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
                                    'shipping_category',
                                    Table::TYPE_TEXT,
                                    255,
                                    ['default' => null]
                                )
                                ->addColumn(
                                    'shipping_mapping',
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
                                     \Ess\M2ePro\Model\ResourceModel\Amazon\Order::COLUMN_DELIVERY_DATE_FROM,
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
                                     \Ess\M2ePro\Model\ResourceModel\Amazon\Order::COLUMN_PAYMENT_METHOD_DETAILS,
                                     Table::TYPE_TEXT,
                                     self::LONG_COLUMN_SIZE,
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
                                ->addColumn(
                                    'final_fees',
                                    Table::TYPE_TEXT,
                                    null,
                                    ['default' => null]
                                )
                                ->addColumn(
                                    'replaced_amazon_order_id',
                                    Table::TYPE_TEXT,
                                    255,
                                    ['default' => null]
                                )
                                 ->addIndex('amazon_order_id', 'amazon_order_id')
                                 ->addIndex('seller_order_id', 'seller_order_id')
                                 ->addIndex('is_prime', 'is_prime')
                                 ->addIndex('is_business', 'is_business')
                                 ->addIndex(
                                     'is_invoice_sent',
                                     \Ess\M2ePro\Model\ResourceModel\Amazon\Order::COLUMN_IS_INVOICE_SENT,
                                 )
                                 ->addIndex('is_credit_memo_sent', 'is_credit_memo_sent')
                                 ->addIndex('buyer_email', 'buyer_email')
                                 ->addIndex('buyer_name', 'buyer_name')
                                 ->addIndex('paid_amount', 'paid_amount')
                                 ->addIndex('purchase_create_date', 'purchase_create_date')
                                 ->addIndex('shipping_date_to', 'shipping_date_to')
                                 ->addIndex('replaced_amazon_order_id', 'replaced_amazon_order_id')
                                 ->setOption('type', 'INNODB')
                                 ->setOption('charset', 'utf8')
                                 ->setOption('collate', 'utf8_general_ci')
                                 ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($amazonOrderTable);
        #endregion

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
                                     ->addColumn(
                                         'buyer_customized_info',
                                         Table::TYPE_TEXT,
                                         null,
                                         ['default' => null]
                                     )
                                     ->addColumn(
                                         AmazonOrderItem::COLUMN_IS_SHIPPING_PALLET_DELIVERY,
                                         Table::TYPE_SMALLINT,
                                         null,
                                         ['unsigned' => true, 'nullable' => false, 'default' => 0]
                                     )
                                     ->addIndex('general_id', 'general_id')
                                     ->addIndex('sku', 'sku')
                                     ->addIndex('title', 'title')
                                     ->setOption('type', 'INNODB')
                                     ->setOption('charset', 'utf8')
                                     ->setOption('collate', 'utf8_general_ci')
                                     ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($amazonOrderItemTable);

        $amazonProductTypeAttributeMapping = $this
            ->getConnection()
            ->newTable($this->getFullTableName('amazon_product_type_attribute_mapping'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'product_type_attribute_code',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Product Type attribute code'
            )
            ->addColumn(
                'product_type_attribute_name',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Product Type attribute name'
            )
            ->addColumn(
                'magento_attribute_code',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                'Magento attribute code'
            )
            ->setComment('Amazon Product Type Attribute Mapping Table')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($amazonProductTypeAttributeMapping);

        $amazonProductTypeValidationTable = $this
            ->getConnection()
            ->newTable($this->getFullTableName('amazon_product_type_validation'))
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
                'status',
                Table::TYPE_INTEGER,
                1,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'error_messages',
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
            ->addColumn(
                'update_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addIndex('listing_product_id', 'listing_product_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');

        $this->getConnection()->createTable($amazonProductTypeValidationTable);

        $amazonOrderInvoiceTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_order_invoice')
        )
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
                                                [
                                                    'unsigned' => true,
                                                    'primary' => true,
                                                    'nullable' => false,
                                                    'auto_increment' => true,
                                                ]
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
                                                  [
                                                      'unsigned' => true,
                                                      'primary' => true,
                                                      'nullable' => false,
                                                      'auto_increment' => true,
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
                                                [
                                                    'identity' => true,
                                                    'unsigned' => true,
                                                    'nullable' => false,
                                                    'primary' => true,
                                                ]
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
                                                [
                                                    'unsigned' => true,
                                                    'primary' => true,
                                                    'nullable' => false,
                                                    'auto_increment' => true,
                                                ]
                                            )
                                            ->addColumn(
                                                'title',
                                                Table::TYPE_TEXT,
                                                255,
                                                ['nullable' => false]
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
                                                'template_id',
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

        $amazonDictionaryTemplateShippingTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_dictionary_template_shipping')
        )
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
                                                'account_id',
                                                Table::TYPE_INTEGER,
                                                null,
                                                ['unsigned' => true, 'nullable' => false]
                                            )
                                            ->addColumn(
                                                'template_id',
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
                                            ->addIndex('account_id', 'account_id')
                                            ->setOption('type', 'INNODB')
                                            ->setOption('charset', 'utf8')
                                            ->setOption('collate', 'utf8_general_ci')
                                            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($amazonDictionaryTemplateShippingTable);

        $amazonTemplateProductTaxCodeTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_template_product_tax_code')
        )
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

        $amazonTemplateProductTypeTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_template_product_type')
        )
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
                                                   'title',
                                                   Table::TYPE_TEXT,
                                                   255,
                                                   ['default' => null]
                                               )
                                                ->addColumn(
                                                    'view_mode',
                                                    Table::TYPE_SMALLINT,
                                                    null,
                                                    ['unsigned' => true, 'nullable' => false, 'default' => 0]
                                                )
                                               ->addColumn(
                                                   'dictionary_product_type_id',
                                                   Table::TYPE_INTEGER,
                                                   null,
                                                   ['unsigned' => true, 'nullable' => false]
                                               )
                                               ->addColumn(
                                                   'settings',
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
                                               ->addIndex(
                                                   'dictionary_product_type_id',
                                                   ['dictionary_product_type_id'],
                                                   ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
                                               )
                                               ->addIndex('title', 'title')
                                               ->setOption('type', 'INNODB')
                                               ->setOption('charset', 'utf8')
                                               ->setOption('collate', 'utf8_general_ci')
                                               ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($amazonTemplateProductTypeTable);

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
                                                     'price_rounding_option',
                                                     Table::TYPE_SMALLINT,
                                                     null,
                                                     ['unsigned' => true, 'nullable' => false, 'default' => 0]
                                                 )
                                                 ->addColumn(
                                                     'regular_price_modifier',
                                                     Table::TYPE_TEXT,
                                                     null,
                                                     ['nullable' => true, 'default' => null]
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
                                                     'regular_sale_price_modifier',
                                                     Table::TYPE_TEXT,
                                                     null,
                                                     ['nullable' => true, 'default' => null]
                                                 )
                                                 ->addColumn(
                                                     'regular_price_variation_mode',
                                                     Table::TYPE_SMALLINT,
                                                     null,
                                                     ['unsigned' => true, 'nullable' => false]
                                                 )
                                                 ->addColumn(
                                                     'regular_list_price_mode',
                                                     Table::TYPE_SMALLINT,
                                                     null,
                                                     ['unsigned' => true, 'nullable' => false, 'default' => 0]
                                                 )
                                                 ->addColumn(
                                                     'regular_list_price_custom_attribute',
                                                     Table::TYPE_TEXT,
                                                     255,
                                                     ['nullable' => true, 'default' => null]
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
                                                     'business_price_modifier',
                                                     Table::TYPE_TEXT,
                                                     null,
                                                     ['nullable' => true, 'default' => null]
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
                                                     'business_discounts_tier_modifier',
                                                     Table::TYPE_TEXT,
                                                     null,
                                                     ['nullable' => true, 'default' => null]
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
                                                                     [
                                                                         'unsigned' => true,
                                                                         'primary' => true,
                                                                         'nullable' => false,
                                                                         'auto_increment' => true,
                                                                     ]
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
                                                                 ->addIndex(
                                                                     'template_selling_format_id',
                                                                     'template_selling_format_id'
                                                                 )
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
        $moduleConfig->insert('/amazon/listing/product/action/stop/', 'min_allowed_wait_interval', '600');
        $moduleConfig->insert('/amazon/listing/product/action/delete/', 'min_allowed_wait_interval', '600');
        $moduleConfig->insert('/amazon/order/settings/marketplace_25/', 'use_first_street_line_as_company', '1');
        $moduleConfig->insert('/amazon/repricing/', 'base_url', 'https://repricer.m2e.cloud/connector/m2epro/');
        $moduleConfig->insert('/amazon/configuration/', 'business_mode', '0');
        $moduleConfig->insert('/amazon/configuration/', 'worldwide_id_mode', '0');
        $moduleConfig->insert('/amazon/configuration/', 'worldwide_id_custom_attribute');
        $moduleConfig->insert('/amazon/configuration/', 'general_id_mode', '0');
        $moduleConfig->insert('/amazon/configuration/', 'general_id_custom_attribute');

        $this->getConnection()->insertMultiple(
            $this->getFullTableName(TablesHelper::TABLE_MARKETPLACE),
            [
                [
                    'id' => 24,
                    'native_id' => 4,
                    'title' => 'Canada',
                    'code' => 'CA',
                    'url' => 'amazon.ca',
                    'status' => 0,
                    'sorder' => 4,
                    'group_title' => 'America',
                    'component_mode' => 'amazon',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 25,
                    'native_id' => 3,
                    'title' => 'Germany',
                    'code' => 'DE',
                    'url' => 'amazon.de',
                    'status' => 0,
                    'sorder' => 3,
                    'group_title' => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 26,
                    'native_id' => 5,
                    'title' => 'France',
                    'code' => 'FR',
                    'url' => 'amazon.fr',
                    'status' => 0,
                    'sorder' => 7,
                    'group_title' => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 28,
                    'native_id' => 2,
                    'title' => 'United Kingdom',
                    'code' => 'UK',
                    'url' => 'amazon.co.uk',
                    'status' => 0,
                    'sorder' => 2,
                    'group_title' => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 29,
                    'native_id' => 1,
                    'title' => 'United States',
                    'code' => 'US',
                    'url' => 'amazon.com',
                    'status' => 0,
                    'sorder' => 1,
                    'group_title' => 'America',
                    'component_mode' => 'amazon',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 30,
                    'native_id' => 7,
                    'title' => 'Spain',
                    'code' => 'ES',
                    'url' => 'amazon.es',
                    'status' => 0,
                    'sorder' => 8,
                    'group_title' => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 31,
                    'native_id' => 8,
                    'title' => 'Italy',
                    'code' => 'IT',
                    'url' => 'amazon.it',
                    'status' => 0,
                    'sorder' => 5,
                    'group_title' => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 34,
                    'native_id' => 9,
                    'title' => 'Mexico',
                    'code' => 'MX',
                    'url' => 'amazon.com.mx',
                    'status' => 0,
                    'sorder' => 8,
                    'group_title' => 'America',
                    'component_mode' => 'amazon',
                    'update_date' => '2017-10-17 00:00:00',
                    'create_date' => '2017-10-17 00:00:00',
                ],
                [
                    'id' => 35,
                    'native_id' => 10,
                    'title' => 'Australia',
                    'code' => 'AU',
                    'url' => 'amazon.com.au',
                    'status' => 0,
                    'sorder' => 1,
                    'group_title' => 'Asia / Pacific',
                    'component_mode' => 'amazon',
                    'update_date' => '2017-10-17 00:00:00',
                    'create_date' => '2017-10-17 00:00:00',
                ],
                [
                    'id' => 39,
                    'native_id' => 11,
                    'title' => 'Netherlands',
                    'code' => 'NL',
                    'url' => 'amazon.nl',
                    'status' => 0,
                    'sorder' => 12,
                    'group_title' => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date' => '2020-03-26 00:00:00',
                    'create_date' => '2020-03-26 00:00:00',
                ],
                [
                    'id' => 40,
                    'native_id' => 12,
                    'title' => 'Turkey',
                    'code' => 'TR',
                    'url' => 'amazon.com.tr',
                    'status' => 0,
                    'sorder' => 14,
                    'group_title' => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date' => '2020-08-19 00:00:00',
                    'create_date' => '2020-08-19 00:00:00',
                ],
                [
                    'id' => 41,
                    'native_id' => 13,
                    'title' => 'Sweden',
                    'code' => 'SE',
                    'url' => 'amazon.se',
                    'status' => 0,
                    'sorder' => 15,
                    'group_title' => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date' => '2020-09-03 00:00:00',
                    'create_date' => '2020-09-03 00:00:00',
                ],
                [
                    'id' => 42,
                    'native_id' => 14,
                    'title' => 'Japan',
                    'code' => 'JP',
                    'url' => 'amazon.co.jp',
                    'status' => 0,
                    'sorder' => 16,
                    'group_title' => 'Asia / Pacific',
                    'component_mode' => 'amazon',
                    'update_date' => '2021-01-11 00:00:00',
                    'create_date' => '2021-01-11 00:00:00',
                ],
                [
                    'id' => 43,
                    'native_id' => 15,
                    'title' => 'Poland',
                    'code' => 'PL',
                    'url' => 'amazon.pl',
                    'status' => 0,
                    'sorder' => 17,
                    'group_title' => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date' => '2021-02-01 00:00:00',
                    'create_date' => '2021-02-01 00:00:00',
                ],
                [
                    'id' => 44,
                    'native_id' => 16,
                    'title' => 'Brazil',
                    'code' => 'BR',
                    'url' => 'amazon.com.br',
                    'status' => 0,
                    'sorder' => 18,
                    'group_title' => 'America',
                    'component_mode' => 'amazon',
                    'update_date' => '2022-08-15 00:00:00',
                    'create_date' => '2022-08-15 00:00:00',
                ],
                [
                    'id' => 45,
                    'native_id' => 17,
                    'title' => 'Singapore',
                    'code' => 'SG',
                    'url' => 'amazon.sg',
                    'status' => 0,
                    'sorder' => 19,
                    'group_title' => 'Asia / Pacific',
                    'component_mode' => 'amazon',
                    'update_date' => '2022-08-15 00:00:00',
                    'create_date' => '2022-08-15 00:00:00',
                ],
                [
                    'id' => 46,
                    'native_id' => 18,
                    'title' => 'India',
                    'code' => 'IN',
                    'url' => 'amazon.in',
                    'status' => 0,
                    'sorder' => 20,
                    'group_title' => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date' => '2022-08-15 00:00:00',
                    'create_date' => '2022-08-15 00:00:00',
                ],
                [
                    'id' => 47,
                    'native_id' => 19,
                    'title' => 'United Arab Emirates',
                    'code' => 'AE',
                    'url' => 'amazon.ae',
                    'status' => 0,
                    'sorder' => 21,
                    'group_title' => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date' => '2022-08-15 00:00:00',
                    'create_date' => '2022-08-15 00:00:00',
                ],
                [
                    'id' => 48,
                    'native_id' => 20,
                    'title' => 'Belgium',
                    'code' => 'BE',
                    'url' => 'amazon.com.be',
                    'status' => 0,
                    'sorder' => 22,
                    'group_title' => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date' => '2022-09-01 00:00:00',
                    'create_date' => '2022-09-01 00:00:00',
                ],
                [
                    'id' => 49,
                    'native_id' => 21,
                    'title' => 'South Africa',
                    'code' => 'ZA',
                    'url' => 'amazon.co.za',
                    'status' => 0,
                    'sorder' => 23,
                    'group_title' => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date' => '2023-12-14 00:00:00',
                    'create_date' => '2023-12-14 00:00:00',
                ],
                [
                    MarketplaceResource::COLUMN_ID => 50,
                    MarketplaceResource::COLUMN_NATIVE_ID => 22,
                    MarketplaceResource::COLUMN_TITLE => 'Saudi Arabia',
                    MarketplaceResource::COLUMN_CODE => 'SA',
                    MarketplaceResource::COLUMN_URL => 'amazon.sa',
                    MarketplaceResource::COLUMN_STATUS => 0,
                    MarketplaceResource::COLUMN_SORDER => 23,
                    MarketplaceResource::COLUMN_GROUP_TITLE => 'Europe',
                    MarketplaceResource::COLUMN_COMPONENT_MODE => 'amazon',
                    'update_date' => '2023-06-25 00:00:00',
                    'create_date' => '2023-06-25 00:00:00',
                ],
                [
                    MarketplaceResource::COLUMN_ID => \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_IE,
                    MarketplaceResource::COLUMN_NATIVE_ID
                    => \Ess\M2ePro\Helper\Component\Amazon::NATIVE_ID_MARKETPLACE_IE,
                    MarketplaceResource::COLUMN_TITLE => 'Ireland',
                    MarketplaceResource::COLUMN_CODE => 'IE',
                    MarketplaceResource::COLUMN_URL => 'amazon.ie',
                    MarketplaceResource::COLUMN_STATUS => 0,
                    MarketplaceResource::COLUMN_SORDER => 24,
                    MarketplaceResource::COLUMN_GROUP_TITLE => 'Europe',
                    MarketplaceResource::COLUMN_COMPONENT_MODE => 'amazon',
                    'update_date' => '2024-12-20 00:00:00',
                    'create_date' => '2023-12-20 00:00:00',
                ],
            ]
        );

        $this->getConnection()->insertMultiple(
            $this->getFullTableName(TablesHelper::TABLE_AMAZON_MARKETPLACE),
            [
                [
                    'marketplace_id' => 24,
                    'default_currency' => 'CAD',
                    'is_merchant_fulfillment_available' => 1,
                    'is_business_available' => 0,
                    'is_vat_calculation_service_available' => 0,
                    'is_product_tax_code_policy_available' => 0,
                ],
                [
                    'marketplace_id' => 25,
                    'default_currency' => 'EUR',
                    'is_merchant_fulfillment_available' => 1,
                    'is_business_available' => 1,
                    'is_vat_calculation_service_available' => 1,
                    'is_product_tax_code_policy_available' => 1,
                ],
                [
                    'marketplace_id' => 26,
                    'default_currency' => 'EUR',
                    'is_merchant_fulfillment_available' => 1,
                    'is_business_available' => 1,
                    'is_vat_calculation_service_available' => 1,
                    'is_product_tax_code_policy_available' => 1,
                ],
                [
                    'marketplace_id' => 28,
                    'default_currency' => 'GBP',
                    'is_merchant_fulfillment_available' => 1,
                    'is_business_available' => 1,
                    'is_vat_calculation_service_available' => 1,
                    'is_product_tax_code_policy_available' => 1,
                ],
                [
                    'marketplace_id' => 29,
                    'default_currency' => 'USD',
                    'is_merchant_fulfillment_available' => 1,
                    'is_business_available' => 1,
                    'is_vat_calculation_service_available' => 0,
                    'is_product_tax_code_policy_available' => 0,
                ],
                [
                    'marketplace_id' => 30,
                    'default_currency' => 'EUR',
                    'is_merchant_fulfillment_available' => 1,
                    'is_business_available' => 1,
                    'is_vat_calculation_service_available' => 1,
                    'is_product_tax_code_policy_available' => 1,
                ],
                [
                    'marketplace_id' => 31,
                    'default_currency' => 'EUR',
                    'is_merchant_fulfillment_available' => 1,
                    'is_business_available' => 1,
                    'is_vat_calculation_service_available' => 1,
                    'is_product_tax_code_policy_available' => 1,
                ],
                [
                    'marketplace_id' => 34,
                    'default_currency' => 'MXN',
                    'is_merchant_fulfillment_available' => 1,
                    'is_business_available' => 0,
                    'is_vat_calculation_service_available' => 0,
                    'is_product_tax_code_policy_available' => 0,
                ],
                [
                    'marketplace_id' => 35,
                    'default_currency' => 'AUD',
                    'is_merchant_fulfillment_available' => 0,
                    'is_business_available' => 0,
                    'is_vat_calculation_service_available' => 0,
                    'is_product_tax_code_policy_available' => 0,
                ],
                [
                    'marketplace_id' => 39,
                    'default_currency' => 'EUR',
                    'is_merchant_fulfillment_available' => 1,
                    'is_business_available' => 1,
                    'is_vat_calculation_service_available' => 1,
                    'is_product_tax_code_policy_available' => 1,
                ],
                [
                    'marketplace_id' => 40,
                    'default_currency' => 'TRY',
                    'is_merchant_fulfillment_available' => 1,
                    'is_business_available' => 0,
                    'is_vat_calculation_service_available' => 0,
                    'is_product_tax_code_policy_available' => 0,
                ],
                [
                    'marketplace_id' => 41,
                    'default_currency' => 'SEK',
                    'is_merchant_fulfillment_available' => 1,
                    'is_business_available' => 0,
                    'is_vat_calculation_service_available' => 1,
                    'is_product_tax_code_policy_available' => 0,
                ],
                [
                    'marketplace_id' => 42,
                    'default_currency' => 'JPY',
                    'is_merchant_fulfillment_available' => 1,
                    'is_business_available' => 0,
                    'is_vat_calculation_service_available' => 0,
                    'is_product_tax_code_policy_available' => 0,
                ],
                [
                    'marketplace_id' => 43,
                    'default_currency' => 'PLN',
                    'is_merchant_fulfillment_available' => 1,
                    'is_business_available' => 0,
                    'is_vat_calculation_service_available' => 1,
                    'is_product_tax_code_policy_available' => 0,
                ],
                [
                    'marketplace_id' => 44,
                    'default_currency' => 'BRL',
                    'is_merchant_fulfillment_available' => 1,
                    'is_business_available' => 1,
                    'is_vat_calculation_service_available' => 0,
                    'is_product_tax_code_policy_available' => 0,
                ],
                [
                    'marketplace_id' => 45,
                    'default_currency' => 'SGD',
                    'is_merchant_fulfillment_available' => 1,
                    'is_business_available' => 1,
                    'is_vat_calculation_service_available' => 0,
                    'is_product_tax_code_policy_available' => 0,
                ],
                [
                    'marketplace_id' => 46,
                    'default_currency' => 'INR',
                    'is_merchant_fulfillment_available' => 1,
                    'is_business_available' => 1,
                    'is_vat_calculation_service_available' => 0,
                    'is_product_tax_code_policy_available' => 0,
                ],
                [
                    'marketplace_id' => 47,
                    'default_currency' => 'AED',
                    'is_merchant_fulfillment_available' => 1,
                    'is_business_available' => 1,
                    'is_vat_calculation_service_available' => 0,
                    'is_product_tax_code_policy_available' => 0,
                ],
                [
                    'marketplace_id' => 48,
                    'default_currency' => 'EUR',
                    'is_merchant_fulfillment_available' => 1,
                    'is_business_available' => 1,
                    'is_vat_calculation_service_available' => 1,
                    'is_product_tax_code_policy_available' => 0,
                ],
                [
                    'marketplace_id' => 49,
                    'default_currency' => 'ZAR',
                    'is_merchant_fulfillment_available' => 1,
                    'is_business_available' => 1,
                    'is_vat_calculation_service_available' => 1,
                    'is_product_tax_code_policy_available' => 1,
                ],
                [
                    AmazonMarketplaceResource::COLUMN_MARKETPLACE_ID => 50,
                    AmazonMarketplaceResource::COLUMN_DEFAULT_CURRENCY => 'SAR',
                    AmazonMarketplaceResource::COLUMN_IS_MERCHANT_FULFILLMENT_AVAILABLE => 1,
                    AmazonMarketplaceResource::COLUMN_IS_BUSINESS_AVAILABLE => 1,
                    AmazonMarketplaceResource::COLUMN_IS_VAT_CALCULATION_SERVICE_AVAILABLE => 1,
                    AmazonMarketplaceResource::COLUMN_IS_PRODUCT_TAX_CODE_POLICY_AVAILABLE => 0,
                ],
                [
                    AmazonMarketplaceResource::COLUMN_MARKETPLACE_ID
                    => \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_IE,
                    AmazonMarketplaceResource::COLUMN_DEFAULT_CURRENCY => 'EUR',
                    AmazonMarketplaceResource::COLUMN_IS_MERCHANT_FULFILLMENT_AVAILABLE => 1,
                    AmazonMarketplaceResource::COLUMN_IS_BUSINESS_AVAILABLE => 1,
                    AmazonMarketplaceResource::COLUMN_IS_VAT_CALCULATION_SERVICE_AVAILABLE => 1,
                    AmazonMarketplaceResource::COLUMN_IS_PRODUCT_TAX_CODE_POLICY_AVAILABLE => 0,
                ],
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
                                        'orders_wfs_last_synchronization',
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

        #region walmart_dictionary_category
        $walmartDictionaryCategoryTableName = $this->getFullTableName(
            TablesHelper::TABLE_WALMART_DICTIONARY_CATEGORY
        );
        $walmartDictionaryCategoryTable = $this->getConnection()
                                               ->newTable($walmartDictionaryCategoryTableName);
        $walmartDictionaryCategoryTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category::COLUMN_ID,
            Table::TYPE_INTEGER,
            null,
            [
                'unsigned' => true,
                'primary' => true,
                'nullable' => false,
                'auto_increment' => true,
            ]
        );
        $walmartDictionaryCategoryTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category::COLUMN_MARKETPLACE_ID,
            Table::TYPE_INTEGER,
            null,
            ['nullable' => false, 'unsigned' => true]
        );
        $walmartDictionaryCategoryTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category::COLUMN_CATEGORY_ID,
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false]
        );
        $walmartDictionaryCategoryTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category::COLUMN_PARENT_CATEGORY_ID,
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => true]
        );
        $walmartDictionaryCategoryTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category::COLUMN_TITLE,
            Table::TYPE_TEXT,
            255,
            ['nullable' => false]
        );
        $walmartDictionaryCategoryTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category::COLUMN_PRODUCT_TYPE_NICK,
            Table::TYPE_TEXT,
            255,
            ['defaul' => null]
        );
        $walmartDictionaryCategoryTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category::COLUMN_PRODUCT_TYPE_TITLE,
            Table::TYPE_TEXT,
            255,
            ['defaul' => null]
        );
        $walmartDictionaryCategoryTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category::COLUMN_IS_LEAF,
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 0]
        );
        $walmartDictionaryCategoryTable->addIndex(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category::COLUMN_CATEGORY_ID,
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category::COLUMN_CATEGORY_ID
        );
        $walmartDictionaryCategoryTable->addIndex(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category::COLUMN_MARKETPLACE_ID,
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category::COLUMN_MARKETPLACE_ID
        );
        $walmartDictionaryCategoryTable->addIndex(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category::COLUMN_PARENT_CATEGORY_ID,
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category::COLUMN_PARENT_CATEGORY_ID
        );
        $walmartDictionaryCategoryTable->addIndex(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category::COLUMN_TITLE,
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category::COLUMN_TITLE
        );
        $walmartDictionaryCategoryTable->addIndex(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category::COLUMN_IS_LEAF,
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category::COLUMN_IS_LEAF
        );
        $walmartDictionaryCategoryTable->setOption('type', 'INNODB');
        $walmartDictionaryCategoryTable->setOption('charset', 'utf8');
        $walmartDictionaryCategoryTable->setOption('collate', 'utf8_general_ci');
        $walmartDictionaryCategoryTable->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($walmartDictionaryCategoryTable);
        #endregion

        #region walmart_dictionary_marketplace
        $walmartDictionaryMarketplaceTableName = $this->getFullTableName(
            TablesHelper::TABLE_WALMART_DICTIONARY_MARKETPLACE
        );
        $walmartDictionaryMarketplaceTable = $this->getConnection()
                                                  ->newTable($walmartDictionaryMarketplaceTableName);
        $walmartDictionaryMarketplaceTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Marketplace::COLUMN_ID,
            Table::TYPE_INTEGER,
            null,
            [
                'unsigned' => true,
                'primary' => true,
                'nullable' => false,
                'auto_increment' => true,
            ]
        );
        $walmartDictionaryMarketplaceTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Marketplace::COLUMN_MARKETPLACE_ID,
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false]
        );
        $walmartDictionaryMarketplaceTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Marketplace::COLUMN_CLIENT_DETAILS_LAST_UPDATE_DATE,
            Table::TYPE_DATETIME,
            null,
            ['default' => null]
        );
        $walmartDictionaryMarketplaceTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Marketplace::COLUMN_SERVER_DETAILS_LAST_UPDATE_DATE,
            Table::TYPE_DATETIME,
            null,
            ['default' => null]
        );
        $walmartDictionaryMarketplaceTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Marketplace::COLUMN_PRODUCT_TYPES,
            Table::TYPE_TEXT,
            self::LONG_COLUMN_SIZE,
            ['default' => null]
        );
        $walmartDictionaryMarketplaceTable->addIndex(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Marketplace::COLUMN_MARKETPLACE_ID,
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Marketplace::COLUMN_MARKETPLACE_ID
        );
        $walmartDictionaryMarketplaceTable->setOption('type', 'INNODB');
        $walmartDictionaryMarketplaceTable->setOption('charset', 'utf8');
        $walmartDictionaryMarketplaceTable->setOption('collate', 'utf8_general_ci');

        $this->getConnection()->createTable($walmartDictionaryMarketplaceTable);
        #endregion

        #region walmart_dictionary_product_type
        $walmartDictionaryProductTypeTableName = $this->getFullTableName(
            TablesHelper::TABLE_WALMART_DICTIONARY_PRODUCT_TYPE
        );
        $walmartDictionaryProductTypeTable = $this->getConnection()->newTable(
            $walmartDictionaryProductTypeTableName
        );
        $walmartDictionaryProductTypeTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType::COLUMN_ID,
            Table::TYPE_INTEGER,
            null,
            [
                'unsigned' => true,
                'primary' => true,
                'nullable' => false,
                'auto_increment' => true,
            ]
        );
        $walmartDictionaryProductTypeTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType::COLUMN_MARKETPLACE_ID,
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false]
        );
        $walmartDictionaryProductTypeTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType::COLUMN_NICK,
            Table::TYPE_TEXT,
            255,
            ['nullable' => false]
        );
        $walmartDictionaryProductTypeTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType::COLUMN_TITLE,
            Table::TYPE_TEXT,
            255,
            ['nullable' => false]
        );
        $walmartDictionaryProductTypeTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType::COLUMN_ATTRIBUTES,
            Table::TYPE_TEXT,
            self::LONG_COLUMN_SIZE,
            ['nullable' => false]
        );
        $walmartDictionaryProductTypeTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType::COLUMN_VARIATION_ATTRIBUTES,
            Table::TYPE_TEXT,
            self::LONG_COLUMN_SIZE,
            ['nullable' => false]
        );
        $walmartDictionaryProductTypeTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType::COLUMN_INVALID,
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 0]
        );
        $walmartDictionaryProductTypeTable->addIndex(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType::COLUMN_MARKETPLACE_ID
            . '__'
            . \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType::COLUMN_NICK,
            [
                \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType::COLUMN_MARKETPLACE_ID,
                \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType::COLUMN_NICK,
            ],
            ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
        );
        $walmartDictionaryProductTypeTable->setOption('type', 'INNODB');
        $walmartDictionaryProductTypeTable->setOption('charset', 'utf8');
        $walmartDictionaryProductTypeTable->setOption('collate', 'utf8_general_ci');
        $walmartDictionaryProductTypeTable->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($walmartDictionaryProductTypeTable);
        #endregion

        /**
         * Create table 'm2epro_walmart_listing_product_indexer_variation_parent'
         */
        $walmartIndexerListingProductParent = $this->getConnection()
                                                   ->newTable(
                                                       $this->getFullTableName(
                                                           'walmart_listing_product_indexer_variation_parent'
                                                       )
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

        #region walmart_listing
        $walmartListingTableName = $this->getFullTableName(
            TablesHelper::TABLE_WALMART_LISTING
        );
        $walmartListingTable = $this->getConnection()->newTable($walmartListingTableName);
        $walmartListingTable->addColumn(
            'listing_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'primary' => true, 'nullable' => false]
        );
        $walmartListingTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_AUTO_GLOBAL_ADDING_PRODUCT_TYPE_ID,
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'default' => null]
        );
        $walmartListingTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_AUTO_WEBSITE_ADDING_PRODUCT_TYPE_ID,
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'default' => null]
        );
        $walmartListingTable->addColumn(
            'template_description_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false]
        );
        $walmartListingTable->addColumn(
            'template_selling_format_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false]
        );
        $walmartListingTable->addColumn(
            'template_synchronization_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false]
        );
        $walmartListingTable->addIndex(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_AUTO_GLOBAL_ADDING_PRODUCT_TYPE_ID,
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_AUTO_GLOBAL_ADDING_PRODUCT_TYPE_ID
        );
        $walmartListingTable->addIndex(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_AUTO_WEBSITE_ADDING_PRODUCT_TYPE_ID,
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_AUTO_WEBSITE_ADDING_PRODUCT_TYPE_ID
        );
        $walmartListingTable->addIndex('template_selling_format_id', 'template_selling_format_id');
        $walmartListingTable->addIndex('template_description_id', 'template_description_id');
        $walmartListingTable->addIndex('template_synchronization_id', 'template_synchronization_id');
        $walmartListingTable->setOption('type', 'INNODB');
        $walmartListingTable->setOption('charset', 'utf8');
        $walmartListingTable->setOption('collate', 'utf8_general_ci');
        $walmartListingTable->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($walmartListingTable);
        #endregion

        #region walmart_listing_auto_category_group
        $walmartListingAutoCategoryGroupTableName = $this->getFullTableName(
            TablesHelper::TABLE_WALMART_LISTING_AUTO_CATEGORY_GROUP
        );
        $walmartListingAutoCategoryGroupTable = $this->getConnection()->newTable(
            $walmartListingAutoCategoryGroupTableName
        );
        $walmartListingAutoCategoryGroupTable->addColumn(
            'listing_auto_category_group_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'primary' => true, 'nullable' => false]
        );
        $walmartListingAutoCategoryGroupTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Auto\Category\Group::COLUMN_ADDING_PRODUCT_TYPE_ID,
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'default' => null]
        );
        $walmartListingAutoCategoryGroupTable->addIndex(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Auto\Category\Group::COLUMN_ADDING_PRODUCT_TYPE_ID,
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Auto\Category\Group::COLUMN_ADDING_PRODUCT_TYPE_ID,
        );
        $walmartListingAutoCategoryGroupTable->setOption('type', 'INNODB');
        $walmartListingAutoCategoryGroupTable->setOption('charset', 'utf8');
        $walmartListingAutoCategoryGroupTable->setOption('collate', 'utf8_general_ci');

        $this->getConnection()->createTable($walmartListingAutoCategoryGroupTable);
        #endregion

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

        # region walmart_listing_product
        $walmartListingProductTable = $this->getConnection()->newTable(
            $this->getFullTableName('walmart_listing_product')
        );
        $walmartListingProductTable->addColumn(
            'listing_product_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'primary' => true, 'nullable' => false]
        );
        $walmartListingProductTable->addColumn(
            'template_category_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'default' => null]
        );
        $walmartListingProductTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product::COLUMN_PRODUCT_TYPE_ID,
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'default' => null]
        );
        $walmartListingProductTable->addColumn(
            'is_variation_product',
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 0]
        );
        $walmartListingProductTable->addColumn(
            'is_variation_product_matched',
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 0]
        );
        $walmartListingProductTable->addColumn(
            'is_variation_channel_matched',
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 0]
        );
        $walmartListingProductTable->addColumn(
            'is_variation_parent',
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 0]
        );
        $walmartListingProductTable->addColumn(
            'variation_parent_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'default' => null]
        );
        $walmartListingProductTable->addColumn(
            'variation_parent_need_processor',
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 0]
        );
        $walmartListingProductTable->addColumn(
            'variation_child_statuses',
            Table::TYPE_TEXT,
            null,
            ['default' => null]
        );
        $walmartListingProductTable->addColumn(
            'sku',
            Table::TYPE_TEXT,
            255,
            ['default' => null]
        );
        $walmartListingProductTable->addColumn(
            'gtin',
            Table::TYPE_TEXT,
            255,
            ['default' => null]
        );
        $walmartListingProductTable->addColumn(
            'upc',
            Table::TYPE_TEXT,
            255,
            ['default' => null]
        );
        $walmartListingProductTable->addColumn(
            'ean',
            Table::TYPE_TEXT,
            255,
            ['default' => null]
        );
        $walmartListingProductTable->addColumn(
            'isbn',
            Table::TYPE_TEXT,
            255,
            ['default' => null]
        );
        $walmartListingProductTable->addColumn(
            'wpid',
            Table::TYPE_TEXT,
            255,
            ['default' => null]
        );
        $walmartListingProductTable->addColumn(
            'item_id',
            Table::TYPE_TEXT,
            255,
            ['default' => null]
        );
        $walmartListingProductTable->addColumn(
            'publish_status',
            Table::TYPE_TEXT,
            255,
            ['default' => null]
        );
        $walmartListingProductTable->addColumn(
            'lifecycle_status',
            Table::TYPE_TEXT,
            255,
            ['default' => null]
        );
        $walmartListingProductTable->addColumn(
            'status_change_reasons',
            Table::TYPE_TEXT,
            null,
            ['default' => null]
        );
        $walmartListingProductTable->addColumn(
            'is_stopped_manually',
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 0]
        );
        $walmartListingProductTable->addColumn(
            'online_price',
            Table::TYPE_DECIMAL,
            [12, 4],
            ['unsigned' => true, 'default' => null]
        );
        $walmartListingProductTable->addColumn(
            'is_online_price_invalid',
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 0]
        );
        $walmartListingProductTable->addColumn(
            'online_promotions',
            Table::TYPE_TEXT,
            40,
            ['nullable' => true]
        );
        $walmartListingProductTable->addColumn(
            'online_qty',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'default' => null]
        );
        $walmartListingProductTable->addColumn(
            'online_lag_time',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => true]
        );
        $walmartListingProductTable->addColumn(
            'online_details_data',
            Table::TYPE_TEXT,
            40,
            ['nullable' => true]
        );
        $walmartListingProductTable->addColumn(
            'online_start_date',
            Table::TYPE_DATETIME,
            null,
            ['nullable' => true]
        );
        $walmartListingProductTable->addColumn(
            'online_end_date',
            Table::TYPE_DATETIME,
            null,
            ['nullable' => true]
        );
        $walmartListingProductTable->addColumn(
            'is_missed_on_channel',
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 0]
        );
        $walmartListingProductTable->addColumn(
            'list_date',
            Table::TYPE_DATETIME,
            null,
            ['nullable' => true]
        );
        $walmartListingProductTable->addIndex('is_variation_product_matched', 'is_variation_product_matched');
        $walmartListingProductTable->addIndex('is_variation_channel_matched', 'is_variation_channel_matched');
        $walmartListingProductTable->addIndex('is_variation_product', 'is_variation_product');
        $walmartListingProductTable->addIndex('online_price', 'online_price');
        $walmartListingProductTable->addIndex('online_qty', 'online_qty');
        $walmartListingProductTable->addIndex('sku', 'sku');
        $walmartListingProductTable->addIndex('gtin', 'gtin');
        $walmartListingProductTable->addIndex('upc', 'upc');
        $walmartListingProductTable->addIndex('ean', 'ean');
        $walmartListingProductTable->addIndex('isbn', 'isbn');
        $walmartListingProductTable->addIndex('wpid', 'wpid');
        $walmartListingProductTable->addIndex('item_id', 'item_id');
        $walmartListingProductTable->addIndex('online_start_date', 'online_start_date');
        $walmartListingProductTable->addIndex('online_end_date', 'online_end_date');
        $walmartListingProductTable->addIndex('is_variation_parent', 'is_variation_parent');
        $walmartListingProductTable->addIndex('variation_parent_need_processor', 'variation_parent_need_processor');
        $walmartListingProductTable->addIndex('variation_parent_id', 'variation_parent_id');
        $walmartListingProductTable->addIndex('template_category_id', 'template_category_id');
        $walmartListingProductTable->addIndex('list_date', 'list_date');
        $walmartListingProductTable->setOption('type', 'INNODB');
        $walmartListingProductTable->setOption('charset', 'utf8');
        $walmartListingProductTable->setOption('collate', 'utf8_general_ci');
        $walmartListingProductTable->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($walmartListingProductTable);
        #endregion

        $walmartProcessingActionTable = $this->getConnection()->newTable(
            $this->getFullTableName('walmart_listing_product_action_processing')
        )
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
                                                   [
                                                       'unsigned' => true,
                                                       'primary' => true,
                                                       'nullable' => false,
                                                       'auto_increment' => true,
                                                   ]
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
                                                    ->newTable(
                                                        $this->getFullTableName('walmart_listing_product_variation')
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
        $this->getConnection()->createTable($walmartListingProductVariationTable);

        /**
         * Create table 'm2epro_walmart_listing_product_variation_option'
         */
        $walmartListingProductVariationOptionTable = $this->getConnection()
                                                          ->newTable(
                                                              $this->getFullTableName(
                                                                  'walmart_listing_product_variation_option'
                                                              )
                                                          )
                                                          ->addColumn(
                                                              'listing_product_variation_option_id',
                                                              Table::TYPE_INTEGER,
                                                              null,
                                                              [
                                                                  'unsigned' => true,
                                                                  'primary' => true,
                                                                  'nullable' => false,
                                                              ]
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
                                      'customer_order_id',
                                      Table::TYPE_TEXT,
                                      255,
                                      ['nullable' => false, 'default' => '']
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
                                  ->addColumn(
                                      'is_wfs',
                                      Table::TYPE_SMALLINT,
                                      null,
                                      ['unsigned' => true, 'nullable' => false, 'default' => 0]
                                  )
                                  ->addIndex('walmart_order_id', 'walmart_order_id')
                                  ->addIndex('customer_order_id', 'customer_order_id')
                                  ->addIndex('buyer_email', 'buyer_email')
                                  ->addIndex('buyer_name', 'buyer_name')
                                  ->addIndex('paid_amount', 'paid_amount')
                                  ->addIndex('is_tried_to_acknowledge', 'is_tried_to_acknowledge')
                                  ->addIndex('purchase_create_date', 'purchase_create_date')
                                  ->addIndex('shipping_date_to', 'shipping_date_to')
                                  ->addIndex('is_wfs', 'is_wfs')
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
                                          'tracking_details',
                                          Table::TYPE_TEXT,
                                          null,
                                          ['default' => null]
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
                                                 [
                                                     'unsigned' => true,
                                                     'primary' => true,
                                                     'nullable' => false,
                                                     'auto_increment' => true,
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
                                                     ->newTable(
                                                         $this->getFullTableName('walmart_template_category_specific')
                                                     )
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
                                                ->newTable($this->getFullTableName(TablesHelper::TABLE_WALMART_TEMPLATE_DESCRIPTION))
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
                                                ->setOption('type', 'INNODB')
                                                ->setOption('charset', 'utf8')
                                                ->setOption('collate', 'utf8_general_ci')
                                                ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($walmartTemplateDescriptionTable);

        /**
         * Create table 'm2epro_walmart_template_selling_format'
         */
        $walmartTemplateSellingFormatTable = $this->getConnection()
                                                  ->newTable($this->getFullTableName(TablesHelper::TABLE_WALMART_TEMPLATE_SELLING_FORMAT))
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
                                                      'price_rounding_option',
                                                      Table::TYPE_SMALLINT,
                                                      null,
                                                      ['unsigned' => true, 'nullable' => false, 'default' => 0]
                                                  )
                                                  ->addColumn(
                                                      'price_modifier',
                                                      Table::TYPE_TEXT,
                                                      null,
                                                      ['nullable' => true, 'default' => null]
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
                                                           ->newTable(
                                                               $this->getFullTableName(
                                                                   'walmart_template_selling_format_promotion'
                                                               )
                                                           )
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
                                                           ->addIndex(
                                                               'template_selling_format_id',
                                                               'template_selling_format_id'
                                                           )
                                                           ->setOption('type', 'INNODB')
                                                           ->setOption('charset', 'utf8')
                                                           ->setOption('collate', 'utf8_general_ci')
                                                           ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($walmartTemplateSellingFormatPromotionTable);

        /**
         * Create table 'm2epro_walmart_template_selling_format_shipping_override'
         */
        $walmartTemplateSellingFormatShippingOverrideTable = $this->getConnection()
                                                                  ->newTable(
                                                                      $this->getFullTableName(
                                                                          'walmart_template_selling_format_shipping_override'
                                                                      )
                                                                  )
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
                                                                      [
                                                                          'unsigned' => true,
                                                                          'nullable' => false,
                                                                          'default' => 0,
                                                                      ]
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
                                                                  ->addIndex(
                                                                      'template_selling_format_id',
                                                                      'template_selling_format_id'
                                                                  )
                                                                  ->setOption('type', 'INNODB')
                                                                  ->setOption('charset', 'utf8')
                                                                  ->setOption('collate', 'utf8_general_ci')
                                                                  ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($walmartTemplateSellingFormatShippingOverrideTable);

        /**
         * Create table 'm2epro_walmart_template_synchronization'
         */
        $walmartTemplateSynchronizationTable = $this->getConnection()
                                                    ->newTable(
                                                        $this->getFullTableName('walmart_template_synchronization')
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

        #region walmart_product_type
        $walmartProductTypeTableName = $this->getFullTableName(
            TablesHelper::TABLE_WALMART_PRODUCT_TYPE
        );
        $walmartProductTypeTable = $this->getConnection()->newTable(
            $this->getFullTableName($walmartProductTypeTableName)
        );
        $walmartProductTypeTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType::COLUMN_ID,
            Table::TYPE_INTEGER,
            null,
            [
                'unsigned' => true,
                'primary' => true,
                'nullable' => false,
                'auto_increment' => true,
            ]
        );
        $walmartProductTypeTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType::COLUMN_TITLE,
            Table::TYPE_TEXT,
            255,
            ['default' => null]
        );
        $walmartProductTypeTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType::COLUMN_DICTIONARY_PRODUCT_TYPE_ID,
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false]
        );
        $walmartProductTypeTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType::COLUMN_ATTRIBUTES_SETTINGS,
            Table::TYPE_TEXT,
            self::LONG_COLUMN_SIZE,
            ['nullable' => false]
        );
        $walmartProductTypeTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType::COLUMN_UPDATE_DATE,
            Table::TYPE_DATETIME,
            null,
            ['default' => null]
        );
        $walmartProductTypeTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType::COLUMN_CREATE_DATE,
            Table::TYPE_DATETIME,
            null,
            ['default' => null]
        );
        $walmartProductTypeTable->addIndex(
            \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType::COLUMN_DICTIONARY_PRODUCT_TYPE_ID,
            \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType::COLUMN_DICTIONARY_PRODUCT_TYPE_ID,
            ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
        );
        $walmartProductTypeTable->addIndex(
            \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType::COLUMN_TITLE,
            \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType::COLUMN_TITLE
        );
        $walmartProductTypeTable->setOption('type', 'INNODB');
        $walmartProductTypeTable->setOption('charset', 'utf8');
        $walmartProductTypeTable->setOption('collate', 'utf8_general_ci');
        $walmartProductTypeTable->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($walmartProductTypeTable);
        #endregion
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
        $moduleConfig->insert('/walmart/configuration/', 'product_id_mode', '0');
        $moduleConfig->insert('/walmart/configuration/', 'product_id_custom_attribute');
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
                    'id' => 37,
                    'native_id' => 1,
                    'title' => 'United States',
                    'code' => 'US',
                    'url' => 'walmart.com',
                    'status' => 0,
                    'sorder' => 3,
                    'group_title' => 'America',
                    'component_mode' => 'walmart',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
                [
                    'id' => 38,
                    'native_id' => 2,
                    'title' => 'Canada',
                    'code' => 'CA',
                    'url' => 'walmart.ca',
                    'status' => 0,
                    'sorder' => 4,
                    'group_title' => 'America',
                    'component_mode' => 'walmart',
                    'update_date' => '2013-05-08 00:00:00',
                    'create_date' => '2013-05-08 00:00:00',
                ],
            ]
        );

        $this->getConnection()->insertMultiple(
            $this->getFullTableName('walmart_marketplace'),
            [
                [
                    'marketplace_id' => 37,
                    'developer_key' => '8636-1433-4377',
                    'default_currency' => 'USD',
                ],
                [
                    'marketplace_id' => 38,
                    'developer_key' => '7078-7205-1944',
                    'default_currency' => 'CAD',
                ],
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
