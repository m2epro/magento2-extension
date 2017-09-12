<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\App\ResourceConnection;
use Magento\Setup\Module\Setup as MagentoSetup;

class MigrationFromMagento1
{
    const LOGS_TASK_DELETE            = 'delete';
    const LOGS_TASK_ACTION_ID         = 'action_id';
    const LOGS_TASK_MODIFY_ACTION_ID  = 'modify_action_id';
    const LOGS_TASK_MODIFY_ENTITY_ID  = 'modify_entity_id';
    const LOGS_TASK_INDEX             = 'index';
    const LOGS_TASK_COLUMNS           = 'columns';

    const BACKUP_TABLE_SUFFIX = '_backup_mv1_';

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

    public function prepareTablesPrefixes()
    {
        $oldTablesPrefix = $this->getOldTablesPrefix();
        $currentTablesPrefix = (string)$this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX);

        if (trim($oldTablesPrefix) == trim($currentTablesPrefix)) {
            return;
        }

        foreach ($this->installer->getConnection()->getTables($oldTablesPrefix.'m2epro_%') as $oldTableName) {
            $clearTableName = str_replace($oldTablesPrefix.'m2epro_', '', $oldTableName);
            $this->getConnection()->renameTable($oldTableName, $this->getFullTableName($clearTableName));
        }
    }

    public function getOldTablesPrefix()
    {
        $prefix = false;
        $primaryConfigTables = $this->installer->getConnection()->getTables('%m2epro_primary_config');

        if (count($primaryConfigTables) === 1) {

            $prefix = $this->installer->getConnection()
                ->select()
                ->from(reset($primaryConfigTables), ['value'])
                ->where('`group` = ?', '/migrationToMagento2/source/magento/')
                ->where('`key` = ?', 'tables_prefix')
                ->query()->fetchColumn();
        }

        if ($prefix === false) {

            $allM2eProTables = $this->installer->getConnection()->getTables('%m2epro_%');
            $prefix = (string)preg_replace('/m2epro_[A-Za-z0-9_]+$/', '', reset($allM2eProTables));
        }

        return (string)$prefix;
    }

    //########################################

    public function process()
    {
        /**
         * convert FLOAT UNSIGNED columns to FLOAT because of zend framework bug in ->createTableByDdl method,
         * that does not support 'FLOAT UNSIGNED' column type
         */
        $this->prepareFloatUnsignedColumns();

        $this->migrateModuleConfig();
        $this->migrateServerLocation();
        $this->migrateModuleName();
        $this->migrateInfrastructureUrls();
        $this->migrateInStorePickupGlobalKey();
        $this->migrateProductCustomTypes();
        $this->migrateHealthStatus();
        $this->migrateArchivedEntity();

        $this->migrateProcessing();
        $this->migrateLockItem();
        $this->migrateLogs();
        $this->migrateWizards();
        $this->migrateGridsPerformanceStructure();
        $this->migrateSynchronizationTemplateAdvancedConditions();

        $this->migrateEbayMarketplaces();
        $this->migrateEbayReturnTemplate();
        $this->migrateEbaySynchronizationTemplate();
        $this->migrateEbayCharity();

        $this->migrateAmazonMarketplaces();
        $this->migrateAmazonListingProduct();

        $this->removeAndBackupBuyData();

        $this->migrateOther();
    }

    //########################################

    private function prepareFloatUnsignedColumns()
    {
        $this->getTableModifier('ebay_template_selling_format')
            ->changeColumn('vat_percent', 'FLOAT NOT NULL', 0);

        $this->getTableModifier('amazon_template_selling_format')
            ->changeColumn('price_vat_percent', 'FLOAT NOT NULL', 0);

        $this->getTableModifier('buy_template_selling_format')
            ->changeColumn('price_vat_percent', 'FLOAT NOT NULL', 0);
    }

    // ---------------------------------------

    private function migrateModuleConfig()
    {
        $this->getConnection()->renameTable(
            $this->getFullTableName('config'),
            $this->getFullTableName('module_config')
        );
    }

    private function migrateServerLocation()
    {
        $primaryConfigModifier = $this->getConfigModifier('primary');

        $primaryConfigModifier->getEntity('/server/', 'default_baseurl_index')->updateGroup('/server/location/');
        $primaryConfigModifier->getEntity('/server/location/', 'default_baseurl_index')->updateKey('default_index');

        $query = $this->getConnection()->select()
            ->from($this->getFullTableName('primary_config'))
            ->where("`group` = '/server/' AND (`key` LIKE 'baseurl_%' OR `key` LIKE 'hostname_%')");

        $result = $this->getConnection()->fetchAll($query);

        foreach ($result as $row) {

            $key = (strpos($row['key'], 'baseurl') !== false) ? 'baseurl' : 'hostname';
            $index = str_replace($key.'_', '', $row['key']);
            $group = "/server/location/{$index}/";

            $primaryConfigModifier->getEntity('/server/', $row['key'])->updateGroup($group);
            $primaryConfigModifier->getEntity($group, $row['key'])->updateKey($key);
        }
    }

    private function migrateModuleName()
    {
        $primaryConfigModifier = $this->getConfigModifier('primary');

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

    private function migrateInfrastructureUrls()
    {
        $moduleConfigModifier = $this->getConfigModifier('module');

        $moduleConfigModifier->insert('/support/', 'forum_url', 'https://community.m2epro.com/', NULL);

        $moduleConfigModifier->getEntity('/support/', 'main_website_url')->updateKey('website_url');
        $moduleConfigModifier->getEntity('/support/', 'main_support_url')->updateKey('support_url');

        $moduleConfigModifier->getEntity('/support/', 'knowledge_base_url')->delete();
        $moduleConfigModifier->getEntity('/support/', 'ideas')->delete();

        $moduleConfigModifier->getEntity('/support/', 'magento_connect_url')->updateKey('magento_marketplace_url');
        $marketplaceUrl = 'https://marketplace.magento.com/'
            . 'm2epro-ebay-amazon-rakuten-sears-magento-integration-order-import-and-stock-level-synchronization.html';
        $moduleConfigModifier->getEntity('/support/', 'magento_marketplace_url')->updateValue($marketplaceUrl);
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
            '/ebay/in_store_pickup/', 'mode', $isPickupStoreEnabled, '0 - disable,\r\n1 - enable'
        );
    }

    private function migrateProductCustomTypes()
    {
        $this->getConfigModifier('module')->insert(
            '/magento/product/simple_type/', 'custom_types', '', 'Magento product custom types'
        );
        $this->getConfigModifier('module')->insert(
            '/magento/product/configurable_type/', 'custom_types', '', 'Magento product custom types'
        );
        $this->getConfigModifier('module')->insert(
            '/magento/product/bundle_type/', 'custom_types', '', 'Magento product custom types'
        );
        $this->getConfigModifier('module')->insert(
            '/magento/product/grouped_type/', 'custom_types', '', 'Magento product custom types'
        );
    }

    private function migrateHealthStatus()
    {
        $this->getConfigModifier('module')->insert(
            '/cron/task/health_status/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $this->getConfigModifier('module')->insert(
            '/cron/task/health_status/', 'interval', '3600', 'in seconds'
        );
        $this->getConfigModifier('module')->insert(
            '/cron/task/health_status/', 'last_access', NULL, 'date of last access'
        );
        $this->getConfigModifier('module')->insert(
            '/cron/task/health_status/', 'last_run', NULL, 'date of last run'
        );

        $this->getConfigModifier('module')->insert('/health_status/notification/', 'mode', 1);
        $this->getConfigModifier('module')->insert('/health_status/notification/', 'email', '');
        $this->getConfigModifier('module')->insert('/health_status/notification/', 'level', 40);
    }

    private function migrateArchivedEntity()
    {
        $archivedEntity = $this->getConnection()->newTable(
            $this->getFullTableName('archived_entity')
        )
            ->addColumn(
                'id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'origin_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'name', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255,
                ['nullable' => false]
            )
            ->addColumn(
                'data', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, InstallSchema::LONG_COLUMN_SIZE,
                ['nullable' => false]
            )
            ->addColumn(
                'create_date', \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME, NULL,
                ['default' => NULL]
            )
            ->addIndex('origin_id__name', ['origin_id', 'name'])
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($archivedEntity);

        //----------------------------------------

        $this->getConfigModifier('module')->insert(
            '/cron/task/archive_orders_entities/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $this->getConfigModifier('module')->insert(
            '/cron/task/archive_orders_entities/', 'interval', '3600', 'in seconds'
        );
        $this->getConfigModifier('module')->insert(
            '/cron/task/archive_orders_entities/', 'last_access', NULL, 'date of last access'
        );
        $this->getConfigModifier('module')->insert(
            '/cron/task/archive_orders_entities/', 'last_run', NULL, 'date of last run'
        );

        $this->getTableModifier('amazon_order')->addIndex('purchase_create_date');
        $this->getTableModifier('ebay_order')->addIndex('purchase_create_date');
    }

    private function migrateProcessing()
    {
        $this->modifyTableRows($this->getFullTableName('processing'), function ($row) {
            $params = (array)json_decode($row['params'], true);

            if (!empty($params['responser_model_name'])) {
                $params['responser_model_name'] = $this->modifyModelName($params['responser_model_name']);
            }

            $row['params'] = json_encode($params);
            $row['model']  = $this->modifyModelName($row['model']);

            return $row;
        });

        // ---------------------------------------

        $this->modifyTableRows($this->getFullTableName('processing_lock'), function ($row) {
            $row['model_name'] = $this->modifyModelName($row['model_name']);
            return $row;
        });
    }

    private function migrateLockItem()
    {
        $this->getTableModifier('lock_item')->dropColumn('kill_now');

        $lockTransactional = $this->getConnection()->newTable(
            $this->getFullTableName('lock_transactional')
        )
            ->addColumn(
                'id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'nick', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255,
                ['nullable' => false]
            )
            ->addColumn(
                'create_date', \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME, NULL,
                ['default' => NULL]
            )
            ->addIndex('nick', 'nick')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($lockTransactional);
    }

    private function migrateLogs()
    {
        $subjects = [
            [
                'params' => [
                    'table' => 'listing_log',
                    'config' => '/logs/listings/',
                    'entity_table' => 'listing',
                    'entity_id_field' => 'listing_id'
                ],
                'tasks' => [
                    self::LOGS_TASK_DELETE,
                    self::LOGS_TASK_ACTION_ID,
                    self::LOGS_TASK_MODIFY_ACTION_ID,
                    self::LOGS_TASK_MODIFY_ENTITY_ID,
                    self::LOGS_TASK_INDEX,
                    self::LOGS_TASK_COLUMNS
                ]
            ],
            [
                'params' => [
                    'table' => 'listing_other_log',
                    'config' => '/logs/other_listings/',
                    'entity_table' => 'listing_other',
                    'entity_id_field' => 'listing_other_id'
                ],
                'tasks' => [
                    self::LOGS_TASK_DELETE,
                    self::LOGS_TASK_ACTION_ID,
                    self::LOGS_TASK_MODIFY_ACTION_ID,
                    self::LOGS_TASK_MODIFY_ENTITY_ID,
                    self::LOGS_TASK_INDEX,
                    self::LOGS_TASK_COLUMNS
                ]
            ],
            [
                'params' => [
                    'table' => 'ebay_account_pickup_store_log',
                    'config' => '/logs/ebay_pickup_store/',
                ],
                'tasks' => [
                    self::LOGS_TASK_DELETE,
                    self::LOGS_TASK_ACTION_ID,
                    self::LOGS_TASK_MODIFY_ACTION_ID,
                    self::LOGS_TASK_INDEX
                ]
            ],
            [
                'params' => [
                    'table' => 'order_log',
                    'entity_table' => 'order',
                    'entity_id_field' => 'order_id'
                ],
                'tasks' => [
                    self::LOGS_TASK_DELETE,
                    self::LOGS_TASK_MODIFY_ENTITY_ID,
                    self::LOGS_TASK_INDEX,
                    self::LOGS_TASK_COLUMNS
                ]
            ],
            [
                'params' => [
                    'table' => 'synchronization_log',
                ],
                'tasks' => [
                    self::LOGS_TASK_DELETE,
                    self::LOGS_TASK_INDEX
                ]
            ]
        ];

        foreach ($subjects as $subject) {
            foreach ($subject['tasks'] as $task) {
                switch ($task) {
                    case self::LOGS_TASK_DELETE:
                        $this->processLogsDeleteTask($subject['params']['table']);
                        break;
                    case self::LOGS_TASK_ACTION_ID:
                        $this->processLogsActionIdTask($subject['params']['table'], $subject['params']['config']);
                        break;
                    case self::LOGS_TASK_MODIFY_ACTION_ID:
                        $this->processLogsModifyActionIdTask($subject['params']['table']);
                        break;
                    case self::LOGS_TASK_MODIFY_ENTITY_ID:
                        $this->processLogsModifyEntityIdTask(
                            $subject['params']['table'], $subject['params']['entity_id_field']
                        );
                        break;
                    case self::LOGS_TASK_INDEX:
                        $this->processLogsIndexTask($subject['params']['table']);
                        break;
                    case self::LOGS_TASK_COLUMNS:
                        $this->processLogsColumnsTask(
                            $subject['params']['table'],
                            $subject['params']['entity_table'],
                            $subject['params']['entity_id_field']
                        );
                        break;
                }
            }
        }

        //----------------------------------------

        $this->getConfigModifier('module')->insert(
            '/logs/view/grouped/', 'max_last_handled_records_count', 100000
        );

        $this->getConnection()->update(
            $this->getFullTableName('module_config'),
            ['value' => 90],
            new \Zend_Db_Expr('`group` LIKE "/logs/clearing/%" AND `key` = "days" AND `value` > 90')
        );
    }

    private function migrateWizards()
    {
        $wizardsData = [
            [
                'nick'     => 'migrationFromMagento1',
                'view'     => '*',
                'status'   => 0,
                'step'     => NULL,
                'type'     => 1,
                'priority' => 1,
            ],
            [
                'nick'     => 'installationEbay',
                'view'     => 'ebay',
                'status'   => empty($this->getTableRows($this->getFullTableName('ebay_account')))
                    ? 0 : 2,
                'step'     => NULL,
                'type'     => 1,
                'priority' => 2,
            ],
            [
                'nick'     => 'installationAmazon',
                'view'     => 'amazon',
                'status'   => empty($this->getTableRows($this->getFullTableName('amazon_account')))
                    ? 0 : 2,
                'step'     => NULL,
                'type'     => 1,
                'priority' => 3,
            ]
        ];

        $this->getConnection()->truncateTable($this->getFullTableName('wizard'));
        $this->getConnection()->insertMultiple($this->getFullTableName('wizard'), $wizardsData);
    }

    private function migrateGridsPerformanceStructure()
    {
        $this->getConnection()->renameTable(
            $this->getFullTableName('indexer_listing_product_parent'),
            $this->getFullTableName('indexer_listing_product_variation_parent')
        );
    }

    private function migrateSynchronizationTemplateAdvancedConditions()
    {
        $this->getTableModifier('amazon_template_synchronization')
            ->addColumn('list_advanced_rules_mode','SMALLINT(4) UNSIGNED NOT NULL',NULL,'list_qty_calculated_value_max')
            ->addColumn(
                'relist_advanced_rules_mode','SMALLINT(4) UNSIGNED NOT NULL',NULL,'relist_qty_calculated_value_max'
            )
            ->addColumn('stop_advanced_rules_mode','SMALLINT(4) UNSIGNED NOT NULL',NULL,'stop_qty_calculated_value_max')
            ->addColumn('list_advanced_rules_filters','TEXT',NULL,'list_advanced_rules_mode')
            ->addColumn('relist_advanced_rules_filters','TEXT',NULL,'relist_advanced_rules_mode')
            ->addColumn('stop_advanced_rules_filters','TEXT',NULL,'stop_advanced_rules_mode');

        $this->getTableModifier('ebay_template_synchronization')
            ->addColumn('list_advanced_rules_mode','SMALLINT(4) UNSIGNED NOT NULL',NULL,'list_qty_calculated_value_max')
            ->addColumn(
                'relist_advanced_rules_mode','SMALLINT(4) UNSIGNED NOT NULL',NULL,'relist_qty_calculated_value_max'
            )
            ->addColumn('stop_advanced_rules_mode','SMALLINT(4) UNSIGNED NOT NULL',NULL,'stop_qty_calculated_value_max')
            ->addColumn('list_advanced_rules_filters','TEXT',NULL,'list_advanced_rules_mode')
            ->addColumn('relist_advanced_rules_filters','TEXT',NULL,'relist_advanced_rules_mode')
            ->addColumn('stop_advanced_rules_filters','TEXT',NULL,'stop_advanced_rules_mode');
    }

    private function migrateEbayMarketplaces()
    {
        $this->getConnection()->update(
            $this->getFullTableName('ebay_marketplace'),
            ['is_ktype' => 1],
            ['marketplace_id = ?' => [13]] // Spain
        );
    }

    private function migrateEbayReturnTemplate()
    {
        $this->getConnection()->renameTable(
            $this->getFullTableName('ebay_template_return'),
            $this->getFullTableName('ebay_template_return_policy')
        );

        // ---------------------------------------

        $ebayListingTableModifier = $this->getTableModifier('ebay_listing');

        $ebayListingTableModifier->renameColumn(
            'template_return_mode', 'template_return_policy_mode', true, false
        );
        $ebayListingTableModifier->renameColumn(
            'template_return_id', 'template_return_policy_id', true, false
        );
        $ebayListingTableModifier->renameColumn(
            'template_return_custom_id', 'template_return_policy_custom_id', true, false
        );
        $ebayListingTableModifier->commit();

        // ---------------------------------------

        $ebayListingProductTableModifier = $this->getTableModifier('ebay_listing_product');

        $ebayListingProductTableModifier->renameColumn(
            'template_return_mode', 'template_return_policy_mode', true, false
        );
        $ebayListingProductTableModifier->renameColumn(
            'template_return_id', 'template_return_policy_id', true, false
        );
        $ebayListingProductTableModifier->renameColumn(
            'template_return_custom_id', 'template_return_policy_custom_id', true, false
        );
        $ebayListingProductTableModifier->commit();

        // ---------------------------------------

        $ebayTemplateSynchronizationTableModifier = $this->getTableModifier('ebay_template_synchronization');
        $ebayTemplateSynchronizationTableModifier->renameColumn(
            'revise_change_return_template', 'revise_change_return_policy_template', false, true
        );
    }

    private function migrateEbaySynchronizationTemplate()
    {
        $this->getTableModifier('ebay_template_synchronization')->dropColumn('schedule_mode');
        $this->getTableModifier('ebay_template_synchronization')->dropColumn('schedule_interval_settings');
        $this->getTableModifier('ebay_template_synchronization')->dropColumn('schedule_week_settings');

        $this->getTableModifier('ebay_template_synchronization')
            ->addColumn('revise_update_specifics', 'SMALLINT(4) UNSIGNED NOT NULL', NULL, 'revise_update_images');

        $this->getTableModifier('ebay_template_synchronization')
            ->addColumn(
                'revise_update_shipping_services','SMALLINT(4) UNSIGNED NOT NULL',NULL,'revise_update_specifics'
            );
    }

    private function migrateEbayCharity()
    {
        $this->getTableModifier('ebay_template_selling_format')
            ->changeColumn(
                'charity', 'TEXT', NULL, 'best_offer_reject_attribute', true
            );

        $this->getConnection()->update(
            $this->getFullTableName('ebay_template_selling_format'),
            ['charity' => NULL],
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
            ['charity' => NULL],
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

            $newCharity = [];
            $newCharity[$sellingFormatTemplate['marketplace_id']] = [
                'marketplace_id' => $sellingFormatTemplate['marketplace_id'],
                'organization_id' => $oldCharity['id'],
                'organization_name' => $oldCharity['name'],
                'organization_custom' => 1,
                'percentage' => $oldCharity['percentage'],
            ];

            $this->getConnection()->update(
                $this->getFullTableName('ebay_template_selling_format'),
                ['charity' => json_encode($newCharity)],
                $this->getConnection()->quoteInto(
                    '`template_selling_format_id` = ?', $sellingFormatTemplate['template_selling_format_id']
                )
            );
        }
    }

    private function migrateAmazonMarketplaces()
    {
        $this->getConnection()->delete($this->getFullTableName('marketplace'), [
            'id IN (?)' => [27, 32]
        ]);
        $this->getConnection()->delete($this->getFullTableName('amazon_marketplace'), [
            'marketplace_id IN (?)' => [27, 32]
        ]);
    }

    private function migrateAmazonListingProduct()
    {
        $this->getTableModifier('amazon_listing_product')
            ->addColumn('variation_parent_afn_state', 'SMALLINT(5) UNSIGNED',
                'NULL', 'is_general_id_owner', true, false)
            ->addColumn('variation_parent_repricing_state', 'SMALLINT(5) UNSIGNED',
                'NULL', 'variation_parent_afn_state', true, false)
            ->changeColumn('search_settings_data', 'LONGTEXT', 'NULL', NULL, false)
            ->commit();
    }

    private function removeAndBackupBuyData()
    {
        $needBackup = !empty($this->getTableRows($this->getFullTableName('buy_account')));

        $wholeBackupTables = [
            'primary_config',
            'module_config',
            'synchronization_config',

            'listing_auto_category',

            'buy_account',
            'buy_item',
            'buy_listing',
            'buy_listing_auto_category_group',
            'buy_listing_other',
            'buy_listing_product',
            'buy_listing_product_variation',
            'buy_listing_product_variation_option',
            'buy_marketplace',
            'buy_order',
            'buy_order_item',
            'buy_template_selling_format',
            'buy_template_synchronization',
        ];

        foreach ($wholeBackupTables as $tableName) {
            if ($needBackup) {
                $resultTableName = $this->getBackupTableName($tableName);

                $backupTable = $this->getConnection()->createTableByDdl(
                    $this->getFullTableName($tableName), $resultTableName
                );
                $this->getConnection()->createTable($backupTable);

                $select = $this->getConnection()->select()->from($this->getFullTableName($tableName));
                $this->getConnection()->query($this->getConnection()->insertFromSelect($select, $resultTableName));
            }

            if (strpos($tableName, 'buy_') === 0) {
                $this->getConnection()->dropTable($this->getFullTableName($tableName));
            }
        }

        $byComponentModeBackupTables = [
            'account',
            'marketplace',

            'listing',
            'listing_auto_category_group',
            'listing_other',
            'listing_product',
            'listing_product_variation',
            'listing_product_variation_option',

            'template_selling_format',
            'template_synchronization',

            'order',
            'order_item',
        ];

        foreach ($byComponentModeBackupTables as $tableName) {
            $select = $this->getConnection()->select()
                ->from($this->getFullTableName($tableName))
                ->where('component_mode = ?', 'buy');

            if ($needBackup) {
                $resultTableName = $this->getBackupTableName($tableName);

                $backupTable = $this->getConnection()->createTableByDdl(
                    $this->getFullTableName($tableName), $resultTableName
                );
                $this->getConnection()->createTable($backupTable);

                $this->getConnection()->query($this->getConnection()->insertFromSelect($select, $resultTableName));
            }

            $this->getConnection()->query(
                $this->getConnection()->deleteFromSelect($select, $this->getFullTableName($tableName))
            );
        }

        $this->getConnection()->delete(
            $this->getFullTableName('module_config'),
            [
                '`group` like ?' => '/component/buy/%'
            ]
        );

        $this->getConnection()->delete(
            $this->getFullTableName('module_config'),
            [
                '`group` like ?' => '/buy/%'
            ]
        );

        $this->getConnection()->delete(
            $this->getFullTableName('synchronization_config'),
            [
                '`group` like ?' => '/buy/%'
            ]
        );

        $select = $this->getConnection()->select()
            ->from($this->getFullTableName('processing'), 'id')
            ->where('model like ?', 'Buy%');

        $processingIdsForRemove = $this->getConnection()->fetchCol($select);

        if (!empty($processingIdsForRemove)) {
            $this->getConnection()->delete(
                $this->getFullTableName('processing'),
                ['id IN (?)' => $processingIdsForRemove]
            );

            $this->getConnection()->delete(
                $this->getFullTableName('processing_lock'),
                ['processing_id IN (?)' => $processingIdsForRemove]
            );
        }
    }

    private function migrateOther()
    {
        $this->getConnection()->delete(
            $this->getFullTableName('module_config'),
            array('`group` REGEXP \'^\/component\/(ebay|amazon|buy){1}\/$\' AND `key` = \'allowed\'')
        );

        $this->getConnection()->delete(
            $this->getFullTableName('module_config'),
            array('`group` = \'/view/common/component/\'')
        );

        $this->getConnection()->delete(
            $this->getFullTableName('module_config'),
            array('`group` = \'/view/common/autocomplete/\'')
        );

        $this->getConfigModifier('module')->getEntity(NULL, 'is_disabled')->updateValue('0');

        $this->getConfigModifier('primary')->getEntity('/server/', 'messages')->delete();
    }

    //########################################

    private function modifyTableRows($tableName, \Closure $callback)
    {
        $rows = $this->getTableRows($tableName);
        if (empty($rows)) {
            return;
        }

        $newRows = [];

        foreach ($rows as $row) {
            $newRows[] = $callback($row);
        }

        $this->getConnection()->delete($tableName);
        $this->getConnection()->insertMultiple($tableName, $newRows);
    }

    private function getTableRows($tableName)
    {
        $select = $this->getConnection()->select()->from($tableName);
        return $this->getConnection()->fetchAll($select);
    }

    private function modifyModelName($modelName)
    {
        return str_replace(['M2ePro/', '_'], ['', '\\'], $modelName);
    }

    private function getBackupTableName($tableName)
    {
        return $this->getFullTableName(self::BACKUP_TABLE_SUFFIX.$tableName);
    }

    //########################################

    private function processLogsDeleteTask($tableName)
    {
        $table = $this->getFullTableName($tableName);

        $select = $this->getConnection()->select()->from(
            $table,
            [new \Zend_Db_Expr('COUNT(*)')]
        );

        $logsCount = $this->getConnection()->fetchOne($select);

        $logsCountLimit = 100000;

        if ($logsCount <= $logsCountLimit) {
            return;
        }

        $this->getConnection()->exec("CREATE TABLE `{$table}_temp` LIKE `{$table}`");
        $this->getConnection()->exec("INSERT INTO `{$table}_temp` (
                                        SELECT * FROM `{$table}` ORDER BY `id` DESC LIMIT {$logsCountLimit}
                                     )");
        $this->getConnection()->exec("DROP TABLE `{$table}`");
        $this->getConnection()->exec("RENAME TABLE `{$table}_temp` TO `{$table}`");
    }

    private function processLogsModifyActionIdTask($tableName)
    {
        $this->getTableModifier($tableName)->changeColumn('action_id', 'INT(10) UNSIGNED NOT NULL');
    }

    private function processLogsModifyEntityIdTask($tableName, $entityIdField)
    {
        $this->getTableModifier($tableName)->changeColumn($entityIdField, 'INT(10) UNSIGNED NOT NULL');
    }

    private function processLogsIndexTask($tableName)
    {
        $this->getTableModifier($tableName)->addIndex('create_date');
    }

    private function processLogsColumnsTask($tableName, $entityTableName, $entityIdField)
    {
        $this->getTableModifier($tableName)
            ->addColumn('account_id', 'INT(10) UNSIGNED NOT NULL', NULL, 'id', true, false)
            ->addColumn('marketplace_id', 'INT(10) UNSIGNED NOT NULL', NULL, 'account_id', true, false)
            ->commit();

        $table = $this->getFullTableName($tableName);
        $entityTable = $this->getFullTableName($entityTableName);

        $this->getConnection()->exec(<<<SQL
UPDATE `{$table}` `log_table`
  INNER JOIN `{$entityTable}` `entity_table` ON `log_table`.`{$entityIdField}` = `entity_table`.`id`
SET
  `log_table`.`account_id` = `entity_table`.`account_id`,
  `log_table`.`marketplace_id` = `entity_table`.`marketplace_id`;
SQL
        );

        $this->getConnection()->delete($table, [
            'account_id = ?' => 0,
            'marketplace_id = ?' => 0
        ]);
    }

    private function processLogsActionIdTask($tableName, $configName)
    {
        $noActionIdCondition = new \Zend_Db_Expr('(`action_id` IS NULL) OR (`action_id` = 0)');

        $select = $this->getConnection()->select()
            ->from(
                $this->getFullTableName($tableName),
                [new \Zend_Db_Expr('MIN(`id`)')]
            )
            ->where($noActionIdCondition);

        $minLogIdWithNoActionId = $this->getConnection()->fetchOne($select);

        if (is_null($minLogIdWithNoActionId)) {
            return;
        }

        $config = $this->getConfigModifier('module')->getEntity(
            $configName, 'last_action_id'
        );

        $nextActionId = $config->getValue() + 100;

        $this->getConnection()->update(
            $this->getFullTableName($tableName),
            [
                'action_id' => new \Zend_Db_Expr("`id` - {$minLogIdWithNoActionId} + {$nextActionId}")
            ],
            $noActionIdCondition
        );

        $select = $this->getConnection()->select()->from(
            $this->getFullTableName($tableName),
            [new \Zend_Db_Expr('MAX(`action_id`)')]
        );

        $maxActionId = (int)$this->getConnection()->fetchOne($select);

        $config = $this->getConfigModifier('module')->getEntity(
            $configName, 'last_action_id'
        );

        $config->updateValue($maxActionId + 100);
    }

    //########################################

    private function getConnection()
    {
        return $this->installer->getConnection();
    }

    private function getFullTableName($tableName)
    {
        return $this->helperFactory->getObject('Module\Database\Tables')->getFullName($tableName);
    }

    //########################################

    /**
     * @param $tableName
     * @return \Ess\M2ePro\Model\Setup\Database\Modifier\Table
     */
    protected function getTableModifier($tableName)
    {
        return $this->modelFactory->getObject('Setup\Database\Modifier\Table',
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
        $tableName = $configName.'_config';

        return $this->modelFactory->getObject('Setup\Database\Modifier\Config',
            [
                'installer' => $this->installer,
                'tableName' => $tableName,
            ]
        );
    }

    //########################################
}