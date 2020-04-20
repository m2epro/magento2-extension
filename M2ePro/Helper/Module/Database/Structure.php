<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module\Database;

use Ess\M2ePro\Helper\Module;
use Magento\Framework\Component\ComponentRegistrar;

/**
 * Class \Ess\M2ePro\Helper\Module\Database\Structure
 */
class Structure extends \Ess\M2ePro\Helper\AbstractHelper
{
    protected $resourceConnection;

    protected $directoryReaderFactory;

    protected $componentRegistrar;

    protected $activeRecordFactory;

    protected $objectManager;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Filesystem\Directory\ReadFactory $directoryReaderFactory,
        ComponentRegistrar $componentRegistrar,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->resourceConnection     = $resourceConnection;
        $this->directoryReaderFactory = $directoryReaderFactory;
        $this->componentRegistrar     = $componentRegistrar;
        $this->activeRecordFactory    = $activeRecordFactory;
        $this->objectManager          = $objectManager;

        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getMysqlTables()
    {
        $cacheData = $this->helperFactory->getObject('Data_Cache_Runtime')->getValue(__METHOD__);
        if (null !== $cacheData) {
            return $cacheData;
        }

        $result = [];

        $queryStmt = $this->resourceConnection->getConnection()
            ->select()
            ->from('information_schema.tables', ['table_name'])
            ->where('table_schema = ?', $this->getHelper('Magento')->getDatabaseName())
            ->where('table_name LIKE ?', "%m2epro\_%")
            ->query();

        while ($tableName = $queryStmt->fetchColumn()) {
            $result[] = $tableName;
        }

        $this->helperFactory->getObject('Data_Cache_Runtime')->setValue(__METHOD__, $result);
        return $result;
    }

    public function getModuleTables()
    {
        return array_keys($this->getTablesModels());
    }

    //########################################

    public function getHorizontalTables()
    {
        $cacheData = $this->helperFactory->getObject('Data_Cache_Runtime')->getValue(__METHOD__);
        if (null !== $cacheData) {
            return $cacheData;
        }

        $components = $this->getHelper('Component')->getComponents();
        $mySqlTables = $this->getModuleTables();

        // minimal amount of child tables to be a horizontal table
        $minimalAmount = 2;

        $result = [];
        foreach ($mySqlTables as $mySqlTable) {
            $tempComponentTables = [];
            $mySqlTableCropped = str_replace('m2epro_', '', $mySqlTable);

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

        $this->helperFactory->getObject('Data_Cache_Runtime')->setValue(__METHOD__, $result);
        return $result;
    }

    // ---------------------------------------

    public function getTableComponent($tableName)
    {
        foreach ($this->getHelper('Component')->getComponents() as $component) {
            if (strpos(strtolower($tableName), strtolower($component)) !== false) {
                return $component;
            }
        }

        return 'general';
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
        $cacheKey  = __METHOD__ . $tableName;
        $cacheData = $this->helperFactory->getObject('Data_Cache_Runtime')->getValue($cacheKey);

        if (null !== $cacheData) {
            return $cacheData;
        }

        $connection = $this->resourceConnection->getConnection();

        $databaseName = $this->getHelper('Magento')->getDatabaseName();
        $tableName = $this->getTableNameWithPrefix($tableName);

        $result = $connection->query("SHOW TABLE STATUS FROM `{$databaseName}` WHERE `name` = '{$tableName}'")
                           ->fetch();

        $this->helperFactory->getObject('Data_Cache_Runtime')->setValue($cacheKey, $result);
        return $result !== false;
    }

    public function isTableStatusOk($tableName)
    {
        $cacheKey  = __METHOD__ . $tableName;
        $cacheData = $this->helperFactory->getObject('Data_Cache_Runtime')->getValue($cacheKey);

        if (null !== $cacheData) {
            return $cacheData;
        }

        $connection = $this->resourceConnection->getConnection();

        if (!$this->isTableExists($tableName)) {
            throw new \Ess\M2ePro\Model\Exception("Table '{$tableName}' is not exists.");
        }

        $result = true;

        try {
            $tableName = $this->getTableNameWithPrefix($tableName);
            $connection->select()->from($tableName, new \Zend_Db_Expr('1'))
                     ->limit(1)
                     ->query();
        } catch (\Exception $e) {
            $result = false;
        }

        $this->helperFactory->getObject('Data_Cache_Runtime')->setValue($cacheKey, $result);
        return $result;
    }

    public function isTableReady($tableName)
    {
        return $this->isTableExists($tableName) && $this->isTableStatusOk($tableName);
    }

    // ---------------------------------------

    public function getCountOfRecords($tableName)
    {
        $cacheKey  = __METHOD__ . $tableName;
        $cacheData = $this->helperFactory->getObject('Data_Cache_Runtime')->getValue($cacheKey);

        if (null !== $cacheData) {
            return $cacheData;
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->getTableNameWithPrefix($tableName);

        $result = $connection->select()->from($tableName, new \Zend_Db_Expr('COUNT(*)'))
                          ->query()
                          ->fetchColumn();

        $this->helperFactory->getObject('Data_Cache_Runtime')->setValue($cacheKey, $result);
        return (int)$result;
    }

    public function getDataLength($tableName)
    {
        $cacheKey  = __METHOD__ . $tableName;
        $cacheData = $this->helperFactory->getObject('Data_Cache_Runtime')->getValue($cacheKey);

        if (null !== $cacheData) {
            return $cacheData;
        }

        $connection = $this->resourceConnection->getConnection();

        $databaseName = $this->getHelper('Magento')->getDatabaseName();
        $tableName = $this->getTableNameWithPrefix($tableName);

        $dataLength = $connection->select()
                     ->from('information_schema.tables', [new \Zend_Db_Expr('data_length + index_length')])
                     ->where('`table_name` = ?', $tableName)
                     ->where('`table_schema` = ?', $databaseName)
                     ->query()
                     ->fetchColumn();

        $result = round($dataLength / 1024 / 1024, 2);

        $this->helperFactory->getObject('Data_Cache_Runtime')->setValue($cacheKey, $result);
        return $result;
    }

    // ---------------------------------------

    public function getModuleTablesInfo()
    {
        $tablesInfo = [];
        foreach ($this->getModuleTables() as $currentTable) {
            $currentTableInfo = $this->getTableInfo($currentTable);
            $currentTableInfo && $tablesInfo[$currentTable] = $currentTableInfo;
        }

        return $tablesInfo;
    }

    public function getTableInfo($tableName)
    {
        $cacheKey  = __METHOD__ . $tableName;
        $cacheData = $this->helperFactory->getObject('Data_Cache_Runtime')->getValue($cacheKey);

        if (null !== $cacheData) {
            return $cacheData;
        }

        if (!$this->isTableExists($this->getTableNameWithoutPrefix($tableName))) {
            return false;
        }

        $moduleTableName = $this->getTableNameWithPrefix($tableName);

        $stmtQuery = $this->resourceConnection->getConnection()->query(
            "SHOW COLUMNS FROM {$moduleTableName}"
        );

        $result = [];
        $afterPosition = '';

        while ($row = $stmtQuery->fetch()) {
            $result[strtolower($row['Field'])] = [
                'name'     => strtolower($row['Field']),
                'type'     => strtolower($row['Type']),
                'null'     => strtolower($row['Null']),
                'key'      => strtolower($row['Key']),
                'default'  => strtolower($row['Default']),
                'extra'    => strtolower($row['Extra']),
                'after'    => $afterPosition
            ];

            $afterPosition = strtolower($row['Field']);
        }

        $this->helperFactory->getObject('Data_Cache_Runtime')->setValue($cacheKey, $result);
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
            return null;
        }

        return $tablesModels[$tableName];
    }

    protected function getTablesModels()
    {
        $cacheData = $this->helperFactory->getObject('Data_Cache_Runtime')->getValue(__METHOD__);
        if (null !== $cacheData) {
            return $cacheData;
        }

        $path = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, Module::IDENTIFIER);
        $path .= '/Model/ResourceModel';

        /** @var \Magento\Framework\Filesystem\Directory\Read $directoryReader */
        $directoryReader = $this->directoryReaderFactory->create($path);

        $tablesModels = [];
        foreach ($directoryReader->readRecursively() as $directoryItem) {
            if (!$directoryReader->isFile($directoryItem)) {
                continue;
            }

            $modelName = preg_replace('/\.php$/', '', str_replace('/', '\\', $directoryItem));
            $className = '\Ess\M2ePro\Model\ResourceModel\\'.$modelName;

            $reflectionClass = new \ReflectionClass($className);
            if ($reflectionClass->isAbstract() ||
                !$reflectionClass->isSubclassOf(\Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel::class)
            ) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel $object */
            $object = $this->objectManager->get($className);

            $tableName = $object->getMainTable();
            $tableName = str_replace($this->getHelper('Magento')->getDatabaseTablesPrefix(), '', $tableName);

            $tablesModels[$tableName] = $modelName;
        }

        $this->helperFactory->getObject('Data_Cache_Runtime')->setValue(__METHOD__, $tablesModels);
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

        $result = [];
        foreach ($collection['items'] as $item) {
            $codeHash = strtolower($item['group']).'#'.strtolower($item['key']);
            $result[$codeHash] = [
                'id'     => (int)$item['id'],
                'group'  => $item['group'],
                'key'    => $item['key'],
                'value'  => $item['value'],
            ];
        }

        return $result;
    }

    // ---------------------------------------

    public function getStoreRelatedColumns()
    {
        $result = [];

        $simpleColumns = ['store_id', 'related_store_id'];
        $jsonColumns   = ['magento_orders_settings', 'marketplaces_data'];

        foreach ($this->getModuleTablesInfo() as $tableName => $tableInfo) {
            foreach ($tableInfo as $columnName => $columnInfo) {
                if (in_array($columnName, $simpleColumns)) {
                    $result[$tableName][] = ['name' => $columnName, 'type' => 'int'];
                }

                if (in_array($columnName, $jsonColumns)) {
                    $result[$tableName][] = ['name' => $columnName, 'type' => 'json'];
                }
            }
        }

        return $result;
    }

    public function getTableNameWithPrefix($tableName)
    {
        return $this->resourceConnection->getTableName($tableName);
    }

    public function getTableNameWithoutPrefix($tableName)
    {
        return str_replace(
            $this->getHelper('Magento')->getDatabaseTablesPrefix(),
            '',
            $this->getTableNameWithPrefix($tableName)
        );
    }

    //########################################
}
