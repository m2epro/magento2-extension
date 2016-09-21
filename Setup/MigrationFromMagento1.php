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
        $allM2eProTables = $this->installer->getConnection()->getTables('%m2epro_%');

        $oldTablesPrefix = (string)preg_replace('/m2epro_[A-Za-z0-9_]+$/', '', reset($allM2eProTables));
        $currentTablesPrefix = (string)$this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX);

        if (trim($oldTablesPrefix) == trim($currentTablesPrefix)) {
            return;
        }

        foreach ($allM2eProTables as $oldTableName) {
            $clearTableName = str_replace($oldTablesPrefix.'m2epro_', '', $oldTableName);
            $this->getConnection()->renameTable($oldTableName, $this->getFullTableName($clearTableName));
        }
    }

    //########################################

    public function process()
    {
        $this->migrateModuleConfig();
        $this->migrateServerLocation();
        $this->migrateModuleName();
        $this->migrateInfrastructureUrls();

        $this->migrateProcessing();
        $this->migrateLockItem();

        $this->migrateWizards();

        $this->migrateEbayReturnTemplate();
        $this->migrateEbaySynchronizationTemplate();

        $this->migrateAmazonMarketplaces();

        $this->createSetupTable();
        $this->removeAndBackupBuyData();

        $this->migrateOther();
    }

    //########################################

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
        $moduleConfigModifier->getEntity('/support/', 'website_url')->updateValue('https://m2epro.com/');

        $moduleConfigModifier->getEntity('/support/', 'main_support_url')->updateKey('support_url');
        $moduleConfigModifier->getEntity('/support/', 'support_url')->updateValue('https://support.m2epro.com/');

        $moduleConfigModifier->getEntity('/support/', 'documentation_url')->updateValue('https://docs.m2epro.com/');
        $moduleConfigModifier->getEntity('/support/', 'knowledge_base_url')->delete();
        $moduleConfigModifier->getEntity('/support/', 'ideas')->delete();

        $moduleConfigModifier->getEntity('/support/', 'magento_connect_url')->updateKey('magento_marketplace_url');

        $marketplaceUrl = 'https://marketplace.magento.com/'
            . 'm2epro-ebay-amazon-rakuten-sears-magento-integration-order-import-and-stock-level-synchronization.html';

        $moduleConfigModifier->getEntity('/support/', 'magento_marketplace_url')->updateValue($marketplaceUrl);
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
    }

    private function migrateAmazonMarketplaces()
    {
		$this->getTableModifier('amazon_marketplace')->renameColumn(
            'is_new_asin_available', 'is_asin_available', true, true
        );
		
        $this->getConnection()->delete($this->getFullTableName('marketplace'), [
            'id IN (?)' => [27, 32]
        ]);
        $this->getConnection()->delete($this->getFullTableName('amazon_marketplace'), [
            'marketplace_id IN (?)' => [27, 32]
        ]);
    }

    private function createSetupTable()
    {
        $setupTable = $this->getConnection()->newTable($this->getFullTableName('setup'))
            ->addColumn(
                'id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'version_from', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 32,
                ['default' => NULL]
            )
            ->addColumn(
                'version_to', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 32,
                ['nullable' => false]
            )
            ->addColumn(
                'is_backuped', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, NULL,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_completed', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, NULL,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'profiler_data', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, NULL,
                ['default' => NULL]
            )
            ->addColumn(
                'update_date', \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME, NULL,
                ['default' => NULL]
            )
            ->addColumn(
                'create_date', \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME, NULL,
                ['default' => NULL]
            )
            ->addIndex('version_from', 'version_from')
            ->addIndex('version_to', 'version_to')
            ->addIndex('is_backuped', 'is_backuped')
            ->addIndex('is_completed', 'is_completed')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($setupTable);
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
            'buy_dictionary_category',
            'buy_template_new_product',
            'buy_template_new_product_attribute',
            'buy_template_new_product_core',
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

        $this->getConfigModifier('module')->getEntity(NULL, 'is_disabled')->delete();

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