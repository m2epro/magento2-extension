<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup;

use Ess\M2ePro\Model\Exception;
use Ess\M2ePro\Setup\Modifier\Config;
use Ess\M2ePro\Setup\Modifier\ConfigFactory;
use Ess\M2ePro\Setup\Modifier\Table;
use Ess\M2ePro\Setup\Modifier\TableFactory;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Module\Setup;
use Magento\Framework\App\ResourceConnection;
use Magento\Setup\Module\Setup as MagentoSetup;

class MigrationFromMagento1
{
    const BACKUP_TABLE_SUFFIX = '_backup_mv1_';

    protected $tablesObject;

    protected $configModifierFactory;

    protected $tableModifierFactory;

    protected $deploymentConfig;

    protected $installer;

    //########################################

    public function __construct(
        Tables $tablesObject,
        ConfigFactory $configModifierFactory,
        TableFactory $tableModifierFactory,
        ResourceConnection $resourceConnection,
        DeploymentConfig $deploymentConfig
    ) {
        $this->tablesObject = $tablesObject;

        $this->configModifierFactory = $configModifierFactory;
        $this->tableModifierFactory  = $tableModifierFactory;

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
            $this->getConnection()->renameTable($oldTableName, $this->tablesObject->getFullName($clearTableName));
        }
    }

    //########################################

    public function process()
    {
        $this->migrateProcessingModelNames();
        $this->migrateReturnTemplateSchema();
        $this->migrateConfigs();
        $this->migrateWizardsData();
        $this->createSetupTable();
        $this->removeAndBackupBuyData();
    }

    //########################################

    private function migrateProcessingModelNames()
    {
        $this->modifyTableRows($this->tablesObject->getFullName('processing'), function ($row) {
            $params = (array)@json_decode($row['params'], true);

            if (!empty($params['responser_model_name'])) {
                $params['responser_model_name'] = $this->modifyModelName($params['responser_model_name']);
            }

            $row['params'] = json_encode($params);
            $row['model']  = $this->modifyModelName($row['model']);

            return $row;
        });

        // ---------------------------------------

        $this->modifyTableRows($this->tablesObject->getFullName('processing_lock'), function ($row) {
            $row['model_name'] = $this->modifyModelName($row['model_name']);
            return $row;
        });
    }

    private function migrateReturnTemplateSchema()
    {
        $this->getConnection()->renameTable(
            $this->tablesObject->getFullName('ebay_template_return'),
            $this->tablesObject->getFullName('ebay_template_return_policy')
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

    private function migrateConfigs()
    {
        $this->getConnection()->renameTable(
            $this->tablesObject->getFullName('config'),
            $this->tablesObject->getFullName('module_config')
        );

        $moduleConfigModifier = $this->getConfigModifier('module');

        $moduleConfigModifier->insert('/support/', 'community', 'https://community.m2epro.com/', NULL);
        $moduleConfigModifier->insert('/support/', 'ideas', 'https://support.m2epro.com/ideas/', NULL);
        $moduleConfigModifier->insert('/setup/upgrade/', 'is_need_rollback_backup', 0, NULL);

        $this->getConnection()->delete(
            $this->tablesObject->getFullName('module_config'),
            array('`group` REGEXP \'^\/component\/(ebay|amazon|buy){1}\/$\' AND `key` = \'allowed\'')
        );

        $this->getConnection()->delete(
            $this->tablesObject->getFullName('module_config'),
            array('`group` = \'/view/common/component/\'')
        );

        $this->getConnection()->update(
            $this->tablesObject->getFullName('module_config'),
            array('group' => '\'/view/amazon/autocomplete/\''),
            array('`group` = \'/view/common/autocomplete/\'')
        );
    }

    private function migrateWizardsData()
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
                'status'   => empty($this->getTableRows($this->tablesObject->getFullName('ebay_account')))
                    ? 0 : 2,
                'step'     => NULL,
                'type'     => 1,
                'priority' => 2,
            ],
            [
                'nick'     => 'installationAmazon',
                'view'     => 'amazon',
                'status'   => empty($this->getTableRows($this->tablesObject->getFullName('amazon_account')))
                    ? 0 : 2,
                'step'     => NULL,
                'type'     => 1,
                'priority' => 3,
            ]
        ];

        $this->getConnection()->truncateTable($this->tablesObject->getFullName('wizard'));
        $this->getConnection()->insertMultiple($this->tablesObject->getFullName('wizard'), $wizardsData);
    }

    private function createSetupTable()
    {
        $setupTable = $this->getConnection()->newTable($this->tablesObject->getFullName('setup'))
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
        $needBackup = !empty($this->getTableRows($this->tablesObject->getFullName('buy_account')));

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
                    $this->tablesObject->getFullName($tableName), $resultTableName
                );
                $this->getConnection()->createTable($backupTable);

                $select = $this->getConnection()->select()->from($this->tablesObject->getFullName($tableName));
                $this->getConnection()->query($this->getConnection()->insertFromSelect($select, $resultTableName));
            }

            if (strpos($tableName, 'buy_') === 0) {
                $this->getConnection()->dropTable($this->tablesObject->getFullName($tableName));
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
                ->from($this->tablesObject->getFullName($tableName))
                ->where('component_mode = ?', 'buy');

            if ($needBackup) {
                $resultTableName = $this->getBackupTableName($tableName);

                $backupTable = $this->getConnection()->createTableByDdl(
                    $this->tablesObject->getFullName($tableName), $resultTableName
                );
                $this->getConnection()->createTable($backupTable);

                $this->getConnection()->query($this->getConnection()->insertFromSelect($select, $resultTableName));
            }

            $this->getConnection()->query(
                $this->getConnection()->deleteFromSelect($select, $this->tablesObject->getFullName($tableName))
            );
        }

        $this->getConnection()->delete(
            $this->tablesObject->getFullName('module_config'),
            [
                '`group` like ?' => '/component/buy/%'
            ]
        );

        $this->getConnection()->delete(
            $this->tablesObject->getFullName('module_config'),
            [
                '`group` like ?' => '/buy/%'
            ]
        );

        $this->getConnection()->delete(
            $this->tablesObject->getFullName('synchronization_config'),
            [
                '`group` like ?' => '/buy/%'
            ]
        );

        $select = $this->getConnection()->select()
            ->from($this->tablesObject->getFullName('processing'), 'id')
            ->where('model like ?', 'Buy%');

        $processingIdsForRemove = $this->getConnection()->fetchCol($select);

        if (!empty($processingIdsForRemove)) {
            $this->getConnection()->delete(
                $this->tablesObject->getFullName('processing'),
                ['id IN (?)' => $processingIdsForRemove]
            );

            $this->getConnection()->delete(
                $this->tablesObject->getFullName('processing_lock'),
                ['processing_id IN (?)' => $processingIdsForRemove]
            );
        }
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
        return $this->installer->getTable(Tables::M2E_PRO_TABLE_PREFIX.self::BACKUP_TABLE_SUFFIX.$tableName);
    }

    //########################################

    private function getConnection()
    {
        return $this->installer->getConnection();
    }

    //########################################

    /**
     * @param $tableName
     * @return Table
     */
    protected function getTableModifier($tableName)
    {
        return $this->tableModifierFactory->create(
            [
                'installer' => $this->installer,
                'tableName' => $tableName,
            ]
        );
    }

    /**
     * @param $configName
     * @return Config
     */
    protected function getConfigModifier($configName)
    {
        $tableName = $configName.'_config';

        return $this->configModifierFactory->create(
            [
                'installer' => $this->installer,
                'tableName' => $tableName,
            ]
        );
    }

    //########################################
}