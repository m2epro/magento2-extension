<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module\Database;

class Structure
{
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;

    /** @var \Magento\Framework\Filesystem\Directory\ReadFactory */
    private $directoryReaderFactory;

    /** @var \Magento\Framework\Component\ComponentRegistrar */
    private $componentRegistrar;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    private $activeRecordFactory;

    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;

    /** @var \Ess\M2ePro\Helper\Component */
    private $componentHelper;

    /** @var \Ess\M2ePro\Helper\Data\Cache\Runtime */
    private $runtimeCacheHelper;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\Filesystem\Directory\ReadFactory $directoryReaderFactory
     * @param \Magento\Framework\Component\ComponentRegistrar $componentRegistrar
     * @param \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Ess\M2ePro\Helper\Magento $magentoHelper
     * @param \Ess\M2ePro\Helper\Component $componentHelper
     * @param \Ess\M2ePro\Helper\Data\Cache\Runtime $runtimeCacheHelper
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Filesystem\Directory\ReadFactory $directoryReaderFactory,
        \Magento\Framework\Component\ComponentRegistrar $componentRegistrar,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Ess\M2ePro\Helper\Component $componentHelper,
        \Ess\M2ePro\Helper\Data\Cache\Runtime $runtimeCacheHelper
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->directoryReaderFactory = $directoryReaderFactory;
        $this->componentRegistrar = $componentRegistrar;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->objectManager = $objectManager;
        $this->magentoHelper = $magentoHelper;
        $this->componentHelper = $componentHelper;
        $this->runtimeCacheHelper = $runtimeCacheHelper;
    }

    /**
     * @return array|mixed
     */
    public function getMysqlTables()
    {
        $cacheData = $this->runtimeCacheHelper->getValue(__METHOD__);
        if (null !== $cacheData) {
            return $cacheData;
        }

        $result = [];

        $queryStmt = $this->resourceConnection->getConnection()
                                              ->select()
                                              ->from('information_schema.tables', ['table_name'])
                                              ->where('table_schema = ?', $this->magentoHelper->getDatabaseName())
                                              ->where('table_name LIKE ?', "%m2epro\_%")
                                              ->query();

        while ($tableName = $queryStmt->fetchColumn()) {
            $result[] = $tableName;
        }

        $this->runtimeCacheHelper->setValue(__METHOD__, $result);

        return $result;
    }

    /**
     * @return int[]|string[]
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \ReflectionException
     */
    public function getModuleTables(): array
    {
        return array_keys($this->getTablesModels());
    }

    /**
     * @return array|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \ReflectionException
     */
    public function getHorizontalTables()
    {
        $cacheData = $this->runtimeCacheHelper->getValue(__METHOD__);
        if (null !== $cacheData) {
            return $cacheData;
        }

        $components = $this->componentHelper->getComponents();
        $mySqlTables = $this->getModuleTables();

        // minimal amount of child tables to be a horizontal table
        $minimalAmount = 2;

        $result = [];
        foreach ($mySqlTables as $mySqlTable) {
            $tempComponentTables = [];
            $mySqlTableCropped = str_replace('m2epro_', '', $mySqlTable);

            foreach ($components as $component) {
                $needComponentTable = "m2epro_{$component}_$mySqlTableCropped";

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

        $this->runtimeCacheHelper->setValue(__METHOD__, $result);

        return $result;
    }

    /**
     * @param string $tableName
     *
     * @return mixed|string
     */
    public function getTableComponent(string $tableName)
    {
        foreach ($this->componentHelper->getComponents() as $component) {
            if (strpos(strtolower($tableName), strtolower($component)) !== false) {
                return $component;
            }
        }

        return 'general';
    }

    /**
     * @param string $tableName
     *
     * @return bool
     */
    public function isModuleTable(string $tableName): bool
    {
        return strpos($tableName, 'm2epro_') !== false;
    }

    /**
     * @param string $tableName
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \ReflectionException
     */
    public function isTableHorizontal(string $tableName): bool
    {
        return $this->isTableHorizontalChild($tableName)
            || $this->isTableHorizontalParent($tableName);
    }

    /**
     * @param string $tableName
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \ReflectionException
     */
    public function isTableHorizontalChild(string $tableName): bool
    {
        $horizontalTables = $this->getHorizontalTables();

        $modifiedTableName = str_replace($this->componentHelper->getComponents(), '', $tableName);
        $modifiedTableName = str_replace('__', '_', $modifiedTableName);

        return !array_key_exists($tableName, $horizontalTables)
            && array_key_exists($modifiedTableName, $horizontalTables);
    }

    /**
     * @param string $tableName
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \ReflectionException
     */
    public function isTableHorizontalParent(string $tableName): bool
    {
        return array_key_exists($tableName, $this->getHorizontalTables());
    }

    /**
     * @param string $tableName
     *
     * @return bool|mixed
     * @throws \Zend_Db_Statement_Exception
     */
    public function isTableExists(string $tableName)
    {
        $cacheKey = __METHOD__ . $tableName;
        $cacheData = $this->runtimeCacheHelper->getValue($cacheKey);

        if (null !== $cacheData) {
            return $cacheData;
        }

        $connection = $this->resourceConnection->getConnection();

        $databaseName = $this->magentoHelper->getDatabaseName();
        $tableName = $this->getTableNameWithPrefix($tableName);

        $result = $connection->query("SHOW TABLE STATUS FROM `$databaseName` WHERE `name` = '$tableName'")
                             ->fetch();

        $this->runtimeCacheHelper->setValue($cacheKey, $result);

        return $result !== false;
    }

    /**
     * @param string $tableName
     *
     * @return bool|mixed
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function isTableStatusOk(string $tableName)
    {
        $cacheKey = __METHOD__ . $tableName;
        $cacheData = $this->runtimeCacheHelper->getValue($cacheKey);

        if (null !== $cacheData) {
            return $cacheData;
        }

        $connection = $this->resourceConnection->getConnection();

        if (!$this->isTableExists($tableName)) {
            throw new \Ess\M2ePro\Model\Exception("Table '$tableName' is not exists.");
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

        $this->runtimeCacheHelper->setValue($cacheKey, $result);

        return $result;
    }

    /**
     * @param string $tableName
     *
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function isTableReady(string $tableName): bool
    {
        return $this->isTableExists($tableName)
            && $this->isTableStatusOk($tableName);
    }

    /**
     * @param string $tableName
     *
     * @return int|mixed
     */
    public function getCountOfRecords(string $tableName)
    {
        $cacheKey = __METHOD__ . $tableName;
        $cacheData = $this->runtimeCacheHelper->getValue($cacheKey);

        if (null !== $cacheData) {
            return $cacheData;
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->getTableNameWithPrefix($tableName);

        $result = $connection->select()->from($tableName, new \Zend_Db_Expr('COUNT(*)'))
                             ->query()
                             ->fetchColumn();

        $this->runtimeCacheHelper->setValue($cacheKey, $result);

        return (int)$result;
    }

    /**
     * @param string $tableName
     *
     * @return float|mixed
     */
    public function getDataLength(string $tableName)
    {
        $cacheKey = __METHOD__ . $tableName;
        $cacheData = $this->runtimeCacheHelper->getValue($cacheKey);

        if (null !== $cacheData) {
            return $cacheData;
        }

        $connection = $this->resourceConnection->getConnection();

        $databaseName = $this->magentoHelper->getDatabaseName();
        $tableName = $this->getTableNameWithPrefix($tableName);

        $dataLength = $connection->select()
                                 ->from('information_schema.tables', [new \Zend_Db_Expr('data_length + index_length')])
                                 ->where('`table_name` = ?', $tableName)
                                 ->where('`table_schema` = ?', $databaseName)
                                 ->query()
                                 ->fetchColumn();

        $result = round($dataLength / 1024 / 1024, 2);

        $this->runtimeCacheHelper->setValue($cacheKey, $result);

        return $result;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \ReflectionException
     * @throws \Zend_Db_Statement_Exception
     */
    public function getModuleTablesInfo(): array
    {
        $tablesInfo = [];
        foreach ($this->getModuleTables() as $currentTable) {
            $currentTableInfo = $this->getTableInfo($currentTable);
            $currentTableInfo && $tablesInfo[$currentTable] = $currentTableInfo;
        }

        return $tablesInfo;
    }

    /**
     * @param string $tableName
     *
     * @return array|false|mixed
     * @throws \Zend_Db_Statement_Exception
     */
    public function getTableInfo(string $tableName)
    {
        $cacheKey = __METHOD__ . $tableName;
        $cacheData = $this->runtimeCacheHelper->getValue($cacheKey);

        if (null !== $cacheData) {
            return $cacheData;
        }

        if (!$this->isTableExists($this->getTableNameWithoutPrefix($tableName))) {
            return false;
        }

        $moduleTableName = $this->getTableNameWithPrefix($tableName);

        $stmtQuery = $this->resourceConnection->getConnection()->query(
            "SHOW COLUMNS FROM $moduleTableName"
        );

        $result = [];
        $afterPosition = '';

        while ($row = $stmtQuery->fetch()) {
            $result[strtolower($row['Field'])] = [
                'name'    => strtolower($row['Field']),
                'type'    => strtolower($row['Type']),
                'null'    => strtolower($row['Null']),
                'key'     => strtolower($row['Key']),
                'default' => strtolower($row['Default'] ?? ''),
                'extra'   => strtolower($row['Extra']),
                'after'   => $afterPosition,
            ];

            $afterPosition = strtolower($row['Field']);
        }

        $this->runtimeCacheHelper->setValue($cacheKey, $result);

        return $result;
    }

    /**
     * @param string $table
     * @param string $columnName
     *
     * @return mixed|null
     * @throws \Zend_Db_Statement_Exception
     */
    public function getColumnInfo(string $table, string $columnName)
    {
        $info = $this->getTableInfo($table);

        return $info[$columnName] ?? null;
    }

    /**
     * @param string $tableName
     *
     * @return mixed|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \ReflectionException
     */
    public function getTableModel(string $tableName)
    {
        $tablesModels = $this->getTablesModels();

        return $tablesModels[$tableName] ?? null;
    }

    /**
     * @return array|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \ReflectionException
     */
    protected function getTablesModels()
    {
        $cacheData = $this->runtimeCacheHelper->getValue(__METHOD__);
        if (null !== $cacheData) {
            return $cacheData;
        }

        $path = $this->componentRegistrar->getPath(
            \Magento\Framework\Component\ComponentRegistrar::MODULE,
            \Ess\M2ePro\Helper\Module::IDENTIFIER
        );
        $path .= '/Model/ResourceModel';

        /** @var \Magento\Framework\Filesystem\Directory\Read $directoryReader */
        $directoryReader = $this->directoryReaderFactory->create($path);

        $tablesModels = [];
        foreach ($directoryReader->readRecursively() as $directoryItem) {
            if (!$directoryReader->isFile($directoryItem)) {
                continue;
            }

            $modelName = preg_replace('/\.php$/', '', str_replace('/', '\\', $directoryItem));
            $className = '\Ess\M2ePro\Model\ResourceModel\\' . $modelName;

            $reflectionClass = new \ReflectionClass($className);
            if (
                $reflectionClass->isAbstract() ||
                !$reflectionClass->isSubclassOf(\Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel::class)
            ) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel $object */
            $object = $this->objectManager->get($className);

            $tableName = $object->getMainTable();
            $tableName = str_replace($this->magentoHelper->getDatabaseTablesPrefix(), '', $tableName);

            $tablesModels[$tableName] = $modelName;
        }

        $this->runtimeCacheHelper->setValue(__METHOD__, $tablesModels);

        return $tablesModels;
    }

    /**
     * @param string $table
     *
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \ReflectionException
     */
    public function getIdColumn(string $table): string
    {
        $tableModel = $this->getTableModel($table);
        $tableModel = $this->activeRecordFactory->getObject($tableModel);

        return $tableModel->getIdFieldName();
    }

    /**
     * @param string $table
     *
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \ReflectionException
     * @throws \Zend_Db_Statement_Exception
     */
    public function isIdColumnAutoIncrement(string $table): bool
    {
        $idColumn = $this->getIdColumn($table);
        $columnInfo = $this->getColumnInfo($table, $idColumn);

        return isset($columnInfo['extra']) && strpos($columnInfo['extra'], 'increment') !== false;
    }

    /**
     * @param string $table
     *
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \ReflectionException
     */
    public function getConfigSnapshot(string $table): array
    {
        $tableModel = $this->getTableModel($table);
        $tableModel = $this->activeRecordFactory->getObject($tableModel);

        $collection = $tableModel->getCollection()->toArray();

        $result = [];
        foreach ($collection['items'] as $item) {
            $codeHash = strtolower($item['group']) . '#' . strtolower($item['key']);
            $result[$codeHash] = [
                'id'    => (int)$item['id'],
                'group' => $item['group'],
                'key'   => $item['key'],
                'value' => $item['value'],
            ];
        }

        return $result;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \ReflectionException
     * @throws \Zend_Db_Statement_Exception
     */
    public function getStoreRelatedColumns(): array
    {
        $result = [];

        $simpleColumns = ['store_id', 'related_store_id'];
        $jsonColumns = ['magento_orders_settings', 'marketplaces_data'];

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

    /**
     * @param string $tableName
     *
     * @return string
     */
    public function getTableNameWithPrefix(string $tableName): string
    {
        return $this->resourceConnection->getTableName($tableName);
    }

    /**
     * @param string $tableName
     *
     * @return array|string|string[]
     */
    public function getTableNameWithoutPrefix(string $tableName)
    {
        return str_replace(
            $this->magentoHelper->getDatabaseTablesPrefix(),
            '',
            $this->getTableNameWithPrefix($tableName)
        );
    }
}
