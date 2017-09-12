<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module\Database;

use Ess\M2ePro\Helper\Module;
use Magento\Framework\Component\ComponentRegistrar;

class Structure extends \Ess\M2ePro\Helper\AbstractHelper
{
    const TABLE_GROUP_CONFIGS           = 'configs';
    const TABLE_GROUP_ACCOUNTS          = 'accounts';
    const TABLE_GROUP_MARKETPLACES      = 'marketplaces';
    const TABLE_GROUP_LISTINGS          = 'listings';
    const TABLE_GROUP_LISTINGS_PRODUCTS = 'listings_products';
    const TABLE_GROUP_LISTINGS_OTHER    = 'listings_other';
    const TABLE_GROUP_LOGS              = 'logs';
    const TABLE_GROUP_ITEMS             = 'items';
    const TABLE_GROUP_PROCESSING        = 'processing';
    const TABLE_GROUP_CONNECTORS        = 'connectors';
    const TABLE_GROUP_DICTIONARY        = 'dictionary';
    const TABLE_GROUP_ORDERS            = 'orders';
    const TABLE_GROUP_TEMPLATES         = 'templates';
    const TABLE_GROUP_OTHER             = 'other';

    protected $resourceConnection;

    protected $directoryReaderFactory;

    protected $componentRegistrar;

    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Filesystem\Directory\ReadFactory $directoryReaderFactory,
        ComponentRegistrar $componentRegistrar,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->resourceConnection     = $resourceConnection;
        $this->directoryReaderFactory = $directoryReaderFactory;
        $this->componentRegistrar     = $componentRegistrar;
        $this->activeRecordFactory    = $activeRecordFactory;

        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getMySqlTables()
    {
        return array(
            'm2epro_primary_config',
            'm2epro_module_config',
            'm2epro_cache_config',
            'm2epro_synchronization_config',

            'm2epro_system_log',

            'm2epro_registry',
            'm2epro_archived_entity',

            'm2epro_lock_item',
            'm2epro_lock_transactional',

            'm2epro_product_change',
            'm2epro_operation_history',
            'm2epro_processing',
            'm2epro_processing_lock',
            'm2epro_connector_pending_requester_single',
            'm2epro_connector_pending_requester_partial',
            'm2epro_synchronization_log',

            'm2epro_request_pending_single',
            'm2epro_request_pending_partial',
            'm2epro_request_pending_partial_data',

            'm2epro_stop_queue',
            'm2epro_wizard',

            'm2epro_setup',
            'm2epro_versions_history',

            'm2epro_account',
            'm2epro_marketplace',

            'm2epro_template_description',
            'm2epro_template_selling_format',
            'm2epro_template_synchronization',

            'm2epro_listing',
            'm2epro_listing_auto_category',
            'm2epro_listing_auto_category_group',
            'm2epro_listing_log',
            'm2epro_listing_other',
            'm2epro_listing_other_log',
            'm2epro_listing_product',
            'm2epro_listing_product_variation',
            'm2epro_listing_product_variation_option',

            'm2epro_order',
            'm2epro_order_change',
            'm2epro_order_item',
            'm2epro_order_log',
            'm2epro_order_matching',

            'm2epro_ebay_account',
            'm2epro_ebay_account_store_category',
            'm2epro_ebay_account_pickup_store',
            'm2epro_ebay_account_pickup_store_state',
            'm2epro_ebay_account_pickup_store_log',
            'm2epro_ebay_dictionary_category',
            'm2epro_ebay_dictionary_marketplace',
            'm2epro_ebay_dictionary_motor_epid',
            'm2epro_ebay_dictionary_motor_ktype',
            'm2epro_ebay_dictionary_shipping',
            'm2epro_ebay_feedback',
            'm2epro_ebay_feedback_template',
            'm2epro_ebay_item',
            'm2epro_ebay_listing',
            'm2epro_ebay_listing_auto_category_group',
            'm2epro_ebay_listing_other',
            'm2epro_ebay_listing_product',
            'm2epro_ebay_listing_product_pickup_store',
            'm2epro_ebay_listing_product_variation',
            'm2epro_ebay_listing_product_variation_option',
            'm2epro_ebay_indexer_listing_product_variation_parent',
            'm2epro_ebay_marketplace',
            'm2epro_ebay_motor_filter',
            'm2epro_ebay_motor_group',
            'm2epro_ebay_motor_filter_to_group',
            'm2epro_ebay_order',
            'm2epro_ebay_order_item',
            'm2epro_ebay_order_external_transaction',
            'm2epro_ebay_processing_action',
            'm2epro_ebay_template_category',
            'm2epro_ebay_template_category_specific',
            'm2epro_ebay_template_description',
            'm2epro_ebay_template_other_category',
            'm2epro_ebay_template_payment',
            'm2epro_ebay_template_payment_service',
            'm2epro_ebay_template_return_policy',
            'm2epro_ebay_template_shipping',
            'm2epro_ebay_template_shipping_calculated',
            'm2epro_ebay_template_shipping_service',
            'm2epro_ebay_template_selling_format',
            'm2epro_ebay_template_synchronization',

            'm2epro_amazon_account',
            'm2epro_amazon_account_repricing',
            'm2epro_amazon_dictionary_category',
            'm2epro_amazon_dictionary_category_product_data',
            'm2epro_amazon_dictionary_marketplace',
            'm2epro_amazon_dictionary_specific',
            'm2epro_amazon_item',
            'm2epro_amazon_listing',
            'm2epro_amazon_listing_auto_category_group',
            'm2epro_amazon_listing_other',
            'm2epro_amazon_listing_product',
            'm2epro_amazon_listing_product_repricing',
            'm2epro_amazon_listing_product_variation',
            'm2epro_amazon_listing_product_variation_option',
            'm2epro_amazon_indexer_listing_product_variation_parent',
            'm2epro_amazon_marketplace',
            'm2epro_amazon_order',
            'm2epro_amazon_order_item',
            'm2epro_amazon_processing_action',
            'm2epro_amazon_processing_action_list_sku',
            'm2epro_amazon_template_description',
            'm2epro_amazon_template_description_definition',
            'm2epro_amazon_template_description_specific',
            'm2epro_amazon_template_selling_format',
            'm2epro_amazon_template_selling_format_business_discount',
            'm2epro_amazon_template_synchronization',
            'm2epro_amazon_dictionary_shipping_override',
            'm2epro_amazon_template_shipping_template',
            'm2epro_amazon_template_shipping_override',
            'm2epro_amazon_template_shipping_override_service',
            'm2epro_amazon_template_product_tax_code'
        );
    }

    public function getHorizontalTables()
    {
        $components = $this->getHelper('Component')->getComponents();
        $mySqlTables = $this->getMySqlTables();

        // minimal amount of child tables to be a horizontal table
        $minimalAmount = 2;

        $result = array();
        foreach ($mySqlTables as $mySqlTable) {

            $tempComponentTables = array();
            $mySqlTableCropped = str_replace('m2epro_','',$mySqlTable);

            foreach ($components as $component) {

                $needComponentTable = "m2epro_{$component}_{$mySqlTableCropped}";

                if (in_array($needComponentTable, $mySqlTables)) {
                    $tempComponentTables[$component] = $needComponentTable;
                } else {
                    break;
                }
            }

            if (count($tempComponentTables) >= $minimalAmount) {
                $result[$mySqlTable] = $tempComponentTables;
            }
        }

        return $result;
    }

    // ---------------------------------------

    public function getTableComponent($tableName)
    {
        foreach ($this->getHelper('Component')->getComponents() as $component) {

            if (strpos(strtolower($tableName),strtolower($component)) !== false) {
                return $component;
            }
        }

        return 'general';
    }

    public function getTableGroup($tableName)
    {
        $mySqlGroups = array(
            self::TABLE_GROUP_CONFIGS           => '/_config$/',
            self::TABLE_GROUP_ACCOUNTS          => '/_account/',
            self::TABLE_GROUP_MARKETPLACES      => '/(?<!dictionary)_marketplace$/',
            self::TABLE_GROUP_LISTINGS          => '/_listing$/',
            self::TABLE_GROUP_LISTINGS_PRODUCTS => '/_listing_product$/',
            self::TABLE_GROUP_LISTINGS_OTHER    => '/_listing_other$/',
            self::TABLE_GROUP_LOGS              => '/_log$/',
            self::TABLE_GROUP_ITEMS             => '/(?<!lock)(?<!order)(?<!action)_item$/',
            self::TABLE_GROUP_PROCESSING        => '/_processing/',
            self::TABLE_GROUP_CONNECTORS        => '/_connector/',
            self::TABLE_GROUP_DICTIONARY        => '/_dictionary_/',
            self::TABLE_GROUP_ORDERS            => '/_order/',
            self::TABLE_GROUP_TEMPLATES         => '/_template_/',
        );

        foreach ($mySqlGroups as $group => $expression) {

            if (preg_match($expression, $tableName)) {
                return $group;
            }
        }

        return self::TABLE_GROUP_OTHER;
    }

    // ---------------------------------------

    public function isModuleTable($tableName)
    {
        return strpos($tableName, 'm2epro_') !== false;
    }

    public function isTableHorizontal($tableName)
    {
        return $this->isTableHorizontalChild($tableName) || $this->isTableHorizontalParent($tableName);
    }

    public function isTableHorizontalChild($tableName)
    {
        $horizontalTables = $this->getHorizontalTables();

        $modifiedTableName = str_replace($this->getHelper('Component')->getComponents(), '', $tableName);
        $modifiedTableName = str_replace('__', '_', $modifiedTableName);

        return !array_key_exists($tableName, $horizontalTables) &&
                array_key_exists($modifiedTableName, $horizontalTables);
    }

    public function isTableHorizontalParent($tableName)
    {
        return array_key_exists($tableName, $this->getHorizontalTables());
    }

    // ---------------------------------------

    public function isTableExists($tableName)
    {
        $connection = $this->resourceConnection->getConnection();

        $databaseName = $this->getHelper('Magento')->getDatabaseName();
        $tableName = $this->resourceConnection->getTableName($tableName);

        $result = $connection->query("SHOW TABLE STATUS FROM `{$databaseName}` WHERE `name` = '{$tableName}'")
                           ->fetch() ;

        return $result !== false;
    }

    public function isTableStatusOk($tableName)
    {
        $connection = $this->resourceConnection->getConnection();

        if (!$this->isTableExists($tableName)) {
            throw new \Ess\M2ePro\Model\Exception("Table '{$tableName}' is not exists.");
        }

        $tableStatus = true;

        try {

            $tableName = $this->resourceConnection->getTableName($tableName);
            $connection->select()->from($tableName, new \Zend_Db_Expr('1'))
                     ->limit(1)
                     ->query();

        } catch (\Exception $e) {
            $tableStatus = false;
        }

        return $tableStatus;
    }

    public function isTableReady($tableName)
    {
        return $this->isTableExists($tableName) && $this->isTableStatusOk($tableName);
    }

    // ---------------------------------------

    public function getCountOfRecords($tableName)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName($tableName);

        $count = $connection->select()->from($tableName, new \Zend_Db_Expr('COUNT(*)'))
                          ->query()
                          ->fetchColumn();

        return (int)$count;
    }

    public function getDataLength($tableName)
    {
        $connection = $this->resourceConnection->getConnection();

        $databaseName = $this->getHelper('Magento')->getDatabaseName();
        $tableName = $this->resourceConnection->getTableName($tableName);

        $dataLength = $connection->select()
                     ->from('information_schema.tables', array(new \Zend_Db_Expr('data_length + index_length')))
                     ->where('`table_name` = ?', $tableName)
                     ->where('`table_schema` = ?', $databaseName)
                     ->query()
                     ->fetchColumn();

        return round($dataLength / 1024 / 1024, 2);
    }

    // ---------------------------------------

    public function getTablesInfo()
    {
        $tablesInfo = array();
        foreach ($this->getMySqlTables() as $currentTable) {
            $currentTableInfo = $this->getTableInfo($currentTable);
            $currentTableInfo && $tablesInfo[$currentTable] = $currentTableInfo;
        }

        return $tablesInfo;
    }

    public function getTableInfo($tableName)
    {
        $tableName = str_replace($this->getHelper('Magento')->getDatabaseTablesPrefix(), '', $tableName);

        if (!$this->isTableExists($tableName)) {
            return false;
        }

        $moduleTableName = $this->resourceConnection->getTableName($tableName);

        $stmtQuery = $this->resourceConnection->getConnection()->query(
            "SHOW COLUMNS FROM {$moduleTableName}"
        );

        $result = array();
        $afterPosition = '';

        while ($row = $stmtQuery->fetch()) {

            $result[strtolower($row['Field'])] = array(
                'name'     => strtolower($row['Field']),
                'type'     => strtolower($row['Type']),
                'null'     => strtolower($row['Null']),
                'key'      => strtolower($row['Key']),
                'default'  => strtolower($row['Default']),
                'extra'    => strtolower($row['Extra']),
                'after'    => $afterPosition
            );

            $afterPosition = strtolower($row['Field']);
        }

        return $result;
    }

    public function getColumnInfo($table, $columnName)
    {
        $info = $this->getTableInfo($table);
        return isset($info[$columnName]) ? $info[$columnName] : null;
    }

    public function getTableModel($tableName)
    {
        $tablesModels = $this->getTablesModels();
        if (!isset($tablesModels[$tableName])) {
            return NULL;
        }

        return $tablesModels[$tableName];
    }

    private function getTablesModels()
    {
        $path = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, Module::IDENTIFIER)
            .DIRECTORY_SEPARATOR.'Model'.DIRECTORY_SEPARATOR.'ResourceModel';
        $directoryReader = $this->directoryReaderFactory->create($path);

        $tablesModels = [];

        foreach ($directoryReader->readRecursively() as $directoryItem) {
            if (!$directoryReader->isFile($directoryItem)) {
                continue;
            }

            $modelName = preg_replace('/\.php$/', '', str_replace('/', '\\', $directoryItem));
            $className = '\Ess\M2ePro\Model\\'.$modelName;

            if (!\class_exists($className)) {
                continue;
            }

            $reflectionClass = new \ReflectionClass($className);
            if ($reflectionClass->isAbstract()) {
                continue;
            }

            $object = $this->activeRecordFactory->getObject($modelName);

            $tableName = $object->getResource()->getMainTable();
            $tableName = str_replace($this->getHelper('Magento')->getDatabaseTablesPrefix(), '', $tableName);

            $tablesModels[$tableName] = $modelName;
        }

        return $tablesModels;
    }

    // ---------------------------------------

    public function getIdColumn($table)
    {
        $tableModel = $this->getTableModel($table);
        $tableModel = $this->activeRecordFactory->getObject($tableModel);

        return $tableModel->getIdFieldName();
    }

    public function isIdColumnAutoIncrement($table)
    {
        $idColumn = $this->getIdColumn($table);
        $columnInfo = $this->getColumnInfo($table, $idColumn);

        return isset($columnInfo['extra']) && strpos($columnInfo['extra'], 'increment') !== false;
    }

    // ---------------------------------------

    public function getConfigSnapshot($table)
    {
        $tableModel = $this->getTableModel($table);
        $tableModel = $this->activeRecordFactory->getObject($tableModel);

        $collection = $tableModel->getCollection()->toArray();

        $result = array();
        foreach ($collection['items'] as $item) {

            $codeHash = strtolower($item['group']).'#'.strtolower($item['key']);
            $result[$codeHash] = array(
                'id'     => (int)$item['id'],
                'group'  => $item['group'],
                'key'    => $item['key'],
                'value'  => $item['value'],
            );
        }

        return $result;
    }

    // ---------------------------------------

    public function getStoreRelatedColumns()
    {
        $result = array();

        $simpleColumns = array('store_id', 'related_store_id');
        $jsonColumns   = array('magento_orders_settings', 'marketplaces_data');

        foreach ($this->getTablesInfo() as $tableName => $tableInfo) {
            foreach ($tableInfo as $columnName => $columnInfo) {

                if (in_array($columnName, $simpleColumns)) {
                    $result[$tableName][] = array('name' => $columnName, 'type' => 'int');
                }

                if (in_array($columnName, $jsonColumns)) {
                    $result[$tableName][] = array('name' => $columnName, 'type' => 'json');
                }
            }
        }

        return $result;
    }

    //########################################
}