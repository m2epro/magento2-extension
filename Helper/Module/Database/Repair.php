<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module\Database;

use Magento\Framework\DB\Ddl\Table as DdlTable;

class Repair
{
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;

    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseHelper;

    /** @var \Ess\M2ePro\Helper\Component */
    private $componentHelper;

    /** @var \Ess\M2ePro\Helper\Module */
    private $moduleHelper;

    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper
     * @param \Ess\M2ePro\Helper\Component $componentHelper
     * @param \Ess\M2ePro\Helper\Module $moduleHelper
     * @param \Ess\M2ePro\Helper\Magento $magentoHelper
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper,
        \Ess\M2ePro\Helper\Component $componentHelper,
        \Ess\M2ePro\Helper\Module $moduleHelper,
        \Ess\M2ePro\Helper\Magento $magentoHelper
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->databaseHelper = $databaseHelper;
        $this->componentHelper = $componentHelper;
        $this->moduleHelper = $moduleHelper;
        $this->magentoHelper = $magentoHelper;
    }

    /**
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function getBrokenTablesInfo(): array
    {
        $horizontalTables = $this->databaseHelper->getHorizontalTables();

        $brokenParentTables   = [];
        $brokenChildrenTables = [];
        $totalBrokenTables = 0;

        foreach ($horizontalTables as $parentTable => $childrenTables) {
            if ($brokenItemsCount = $this->getBrokenRecordsInfo($parentTable, true)) {
                $brokenParentTables[$parentTable] = $brokenItemsCount;
                $totalBrokenTables++;
            }

            foreach ($childrenTables as $childrenTable) {
                if ($brokenItemsCount = $this->getBrokenRecordsInfo($childrenTable, true)) {
                    $brokenChildrenTables[$childrenTable] = $brokenItemsCount;
                    $totalBrokenTables++;
                }
            }
        }

        return [
            'parent'      => $brokenParentTables,
            'children'    => $brokenChildrenTables,
            'total_count' => $totalBrokenTables
        ];
    }

    /**
     * @param string $table
     * @param bool $returnOnlyCount
     *
     * @return array|int
     * @throws \Zend_Db_Statement_Exception
     */
    public function getBrokenRecordsInfo(string $table, bool $returnOnlyCount = false)
    {
        $connection = $this->resourceConnection->getConnection();
        $allTables = $this->databaseHelper->getHorizontalTables();

        $result = $returnOnlyCount ? 0 : [];

        foreach ($allTables as $parentTable => $childTables) {
            foreach ($childTables as $component => $childTable) {
                if (!in_array($table, [$parentTable, $childTable])) {
                    continue;
                }

                $parentTablePrefix = $this->databaseHelper
                    ->getTableNameWithPrefix($parentTable);
                $childTablePrefix  = $this->databaseHelper
                    ->getTableNameWithPrefix($childTable);

                $parentIdColumn = $this->databaseHelper->getIdColumn($parentTable);
                $childIdColumn  = $this->databaseHelper->getIdColumn($childTable);

                if ($table == $parentTable) {
                    $stmtQuery = $connection->select()
                        ->from(
                            ['parent' => $parentTablePrefix],
                            $returnOnlyCount ? new \Zend_Db_Expr('count(*) as `count_total`')
                            : ['id' => $parentIdColumn]
                        )
                        ->joinLeft(
                            ['child' => $childTablePrefix],
                            '`parent`.`'.$parentIdColumn.'` = `child`.`'.$childIdColumn.'`',
                            []
                        )
                        ->where(
                            '`parent`.`component_mode` = \''.$component.'\' OR
                                (`parent`.`component_mode` NOT IN (?) OR `parent`.`component_mode` IS NULL)',
                            $this->componentHelper->getComponents()
                        )
                        ->where('`child`.`'.$childIdColumn.'` IS NULL')
                        ->query();
                } elseif ($table == $childTable) {
                    $stmtQuery = $connection->select()
                        ->from(
                            ['child' => $childTablePrefix],
                            $returnOnlyCount ? new \Zend_Db_Expr('count(*) as `count_total`')
                            : ['id' => $childIdColumn]
                        )
                        ->joinLeft(
                            ['parent' => $parentTablePrefix],
                            "`child`.`{$childIdColumn}` = `parent`.`{$parentIdColumn}` AND
                                   `parent`.`component_mode` = '{$component}'",
                            []
                        )
                        ->where('`parent`.`'.$parentIdColumn.'` IS NULL')
                        ->query();
                }

                if ($returnOnlyCount) {
                    $row = $stmtQuery->fetch();
                    $result += (int)$row['count_total'];
                } else {
                    while ($row = $stmtQuery->fetch()) {
                        $id = (int)$row['id'];
                        $result[$id] = $id;
                    }
                }
            }
        }

        if (!$returnOnlyCount) {
            $result = array_values($result);
        }

        return $result;
    }

    /**
     * @param array $tables
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function repairBrokenTables(array $tables): void
    {
        $connection = $this->resourceConnection->getConnection();

        $logData = [];

        foreach ($tables as $table) {
            $brokenIds = $this->getBrokenRecordsInfo($table);
            if (count($brokenIds) <= 0) {
                continue;
            }
            $brokenIds = array_slice($brokenIds, 0, 50000);

            $tableWithPrefix = $this->databaseHelper->getTableNameWithPrefix($table);
            $idColumnName = $this->databaseHelper->getIdColumn($table);

            foreach (array_chunk($brokenIds, 1000) as $brokenIdsPart) {
                if (count($brokenIdsPart) <= 0) {
                    continue;
                }

                $connection->delete(
                    $tableWithPrefix,
                    '`'.$idColumnName.'` IN ('.implode(',', $brokenIdsPart).')'
                );
            }

            $logData[] = "Table: {$table} ## Amount: ".count($brokenIds);
        }

        $this->moduleHelper->getRegistry()->setValue('/database/repair/log_data/', implode('', $logData));
    }

    /**
     * @param string $tableName
     * @param array $columnInfo
     *
     * @return void
     */
    public function fixColumnIndex(string $tableName, array $columnInfo): void
    {
        if (!isset($columnInfo['name'], $columnInfo['key'])) {
            return;
        }

        $writeConnection = $this->resourceConnection->getConnection();
        $tableName = $this->databaseHelper->getTableNameWithPrefix($tableName);

        if (empty($columnInfo['key'])) {
            $writeConnection->dropIndex($tableName, $columnInfo['name']);
            return;
        }

        $indexType = \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_PRIMARY;
        $columnInfo['key'] == 'mul' && $indexType = \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX;
        $columnInfo['key'] == 'uni' && $indexType = \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE;

        $writeConnection->addIndex($tableName, $columnInfo['name'], $columnInfo['name'], $indexType);
    }

    /**
     * @param string $tableName
     * @param array $columnInfo
     *
     * @return void
     */
    public function fixColumnProperties(string $tableName, array $columnInfo): void
    {
        if (!isset($columnInfo['name'])) {
            return;
        }

        $definition = "{$columnInfo['type']} ";
        $columnInfo['null'] == 'no' && $definition .= 'NOT NULL ';
        $columnInfo['default'] != '' && $definition .= "DEFAULT '{$columnInfo['default']}' ";
        ($columnInfo['null'] == 'yes' && $columnInfo['default'] == '') && $definition .= 'DEFAULT NULL ';
        $columnInfo['extra'] == 'auto_increment' && $definition .= 'AUTO_INCREMENT ';
        !empty($columnInfo['after']) && $definition .= "AFTER `{$columnInfo['after']}`";

        $writeConnection = $this->resourceConnection->getConnection();
        $tableName = $this->databaseHelper->getTableNameWithPrefix($tableName);

        $magentoVersion = $this->magentoHelper->getVersion();
        $isConvertColumnDefinitionToArray = version_compare($magentoVersion, '2.3.0', '>=');

        if ($writeConnection->tableColumnExists($tableName, $columnInfo['name']) === false) {
            if ($isConvertColumnDefinitionToArray) {
                $writeConnection->addColumn(
                    $tableName, $columnInfo['name'], $this->convertColumnDefinitionToArray($definition)
                );
            } else {
                $writeConnection->addColumn($tableName, $columnInfo['name'], $definition);
            }

            return;
        }

        if ($isConvertColumnDefinitionToArray) {
            $writeConnection->changeColumn(
                $tableName, $columnInfo['name'], $columnInfo['name'], $this->convertColumnDefinitionToArray($definition)
            );
        } else {
            $writeConnection->changeColumn($tableName, $columnInfo['name'], $columnInfo['name'], $definition);
        }
    }

    /**
     * @param string $tableName
     * @param array $columnInfo
     *
     * @return void
     */
    public function dropColumn(string $tableName, array $columnInfo): void
    {
        if (!isset($columnInfo['name'])) {
            return;
        }

        $writeConnection = $this->resourceConnection->getConnection();
        $tableName = $this->databaseHelper->getTableNameWithPrefix($tableName);

        $writeConnection->dropColumn($tableName, $columnInfo['name']);
    }

    /**
     * @param string $definition
     *
     * @return array|string
     */
    private function convertColumnDefinitionToArray(string $definition)
    {
        $pattern = "#^(?P<type>[a-z]+(?:\(\d+,?\d?\))?)";
        $pattern .= '(?:';
        $pattern .= "(?P<unsigned>\sUNSIGNED)?";
        $pattern .= "(?P<nullable>\s(?:NOT\s)?NULL)?";
        $pattern .= "(?P<default>\sDEFAULT\s[^\s]+)?";
        $pattern .= "(?P<auto_increment>\sAUTO_INCREMENT)?";
        $pattern .= "(?P<primary_key>\sPRIMARY\sKEY)?";
        $pattern .= "(?P<after>\sAFTER\s[^\s]+)?";
        $pattern .= ')?#i';

        $matches = [];
        if (preg_match($pattern, $definition, $matches) === false || !isset($matches['type'])) {
            return $definition;
        }

        $typeMap = [
            DdlTable::TYPE_SMALLINT => ['TINYINT', 'SMALLINT'],
            DdlTable::TYPE_INTEGER => ['INT'],
            DdlTable::TYPE_FLOAT => ['FLOAT'],
            DdlTable::TYPE_DECIMAL => ['DECIMAL'],
            DdlTable::TYPE_DATETIME => ['DATETIME'],
            DdlTable::TYPE_TEXT => ['VARCHAR', 'TEXT', 'LONGTEXT'],
            DdlTable::TYPE_BLOB => ['BLOB', 'LONGBLOB'],
        ];

        $size = null;
        $type = $matches['type'];
        if (strpos($type, '(') !== false) {
            $size = str_replace(['(', ')'], '', substr($type, strpos($type, '(')));
            $type = substr($type, 0, strpos($type, '('));
        }

        if (strtoupper('LONGTEXT') === strtoupper($type)) {
            $size = 16777217;
        }

        $definitionData = [];
        foreach ($typeMap as $ddlType => $types) {
            if (!in_array(strtoupper($type), $types)) {
                continue;
            }

            if ($ddlType == DdlTable::TYPE_TEXT || $ddlType == DdlTable::TYPE_BLOB) {
                $definitionData['length'] = $size;
            }

            if (($ddlType == DdlTable::TYPE_FLOAT || $ddlType == DdlTable::TYPE_DECIMAL) &&
                strpos($size, ',') !== false) {
                [$precision, $scale] = array_map('trim', explode(',', $size, 2));
                $definitionData['precision'] = (int)$precision;
                $definitionData['scale'] = (int)$scale;
            }

            $definitionData['type'] = $ddlType;
            break;
        }

        if (!empty($matches['unsigned'])) {
            $definitionData['unsigned'] = true;
        }

        if (!empty($matches['nullable'])) {
            $definitionData['nullable'] = strpos(
                strtolower($matches['nullable']), 'not null'
            ) ==! false  ? false : true;
        }

        if (!empty($matches['default'])) {
            [,$defaultData] = explode(' ', trim($matches['default']), 2);
            $defaultData = trim($defaultData);
            $definitionData['default'] = strtolower($defaultData) == 'null' ? null : $defaultData;
        }

        if (!empty($matches['auto_increment'])) {
            $definitionData['auto_increment'] = true;
        }

        if (!empty($matches['primary_key'])) {
            $definitionData['primary'] = true;
        }

        if (!empty($matches['after'])) {
            [,$afterColumn] = explode(' ', trim($matches['after']), 2);
            $definitionData['after'] = trim($afterColumn, " \t\n\r\0\x0B`");
        }

        $definitionData['comment'] = 'field';

        return $definitionData;
    }
}
