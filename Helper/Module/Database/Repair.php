<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module\Database;

use Magento\Framework\DB\Ddl\Table as DdlTable;

/**
 * Class \Ess\M2ePro\Helper\Module\Database\Repair
 */
class Repair extends \Ess\M2ePro\Helper\AbstractHelper
{
    protected $resourceConnection;
    protected $cacheConfig;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->cacheConfig = $cacheConfig;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    private function convertColumnDefinitionToArray($definition)
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
                list($precision, $scale) = array_map('trim', explode(',', $size, 2));
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
            list(,$defaultData) = explode(' ', trim($matches['default']), 2);
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
            list(,$afterColumn) = explode(' ', trim($matches['after']), 2);
            $definitionData['after'] = trim($afterColumn, " \t\n\r\0\x0B`");
        }

        $definitionData['comment'] = 'field';

        return $definitionData;
    }

    //########################################

    public function getBrokenTablesInfo()
    {
        $horizontalTables = $this->getHelper('Module_Database_Structure')->getHorizontalTables();

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

    public function getBrokenRecordsInfo($table, $returnOnlyCount = false)
    {
        $connection = $this->resourceConnection->getConnection();
        $allTables = $this->getHelper('Module_Database_Structure')->getHorizontalTables();

        $result = $returnOnlyCount ? 0 : [];

        foreach ($allTables as $parentTable => $childTables) {
            foreach ($childTables as $component => $childTable) {
                if (!in_array($table, [$parentTable, $childTable])) {
                    continue;
                }

                $parentTablePrefix = $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix($parentTable);
                $childTablePrefix  = $this->getHelper('Module_Database_Structure')
                                        ->getTableNameWithPrefix($childTable);

                $parentIdColumn = $this->getHelper('Module_Database_Structure')->getIdColumn($parentTable);
                $childIdColumn  = $this->getHelper('Module_Database_Structure')->getIdColumn($childTable);

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
                            $this->getHelper('Component')->getComponents()
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

    public function repairBrokenTables(array $tables)
    {
        $connection = $this->resourceConnection->getConnection();

        $logData = [];

        foreach ($tables as $table) {
            $brokenIds = $this->getBrokenRecordsInfo($table);
            if (count($brokenIds) <= 0) {
                continue;
            }
            $brokenIds = array_slice($brokenIds, 0, 50000);

            $tableWithPrefix = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix($table);
            $idColumnName = $this->getHelper('Module_Database_Structure')->getIdColumn($table);

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

        $this->cacheConfig->setGroupValue('/database/repair/', 'log_data', implode('', $logData));
    }

    // ---------------------------------------

    /**
     * @param $tableName
     * @return string <p> OK if repair was successfully or Error Message if not. </p>
     */
    public function repairCrashedTable($tableName)
    {
        $connWrite = $this->resourceConnection->getConnection();

        $tableName = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix($tableName);

        $result = $connWrite->query("REPAIR TABLE `{$tableName}`")->fetch();
        return $result['Msg_text'];
    }

    // ---------------------------------------

    public function fixColumnIndex($tableName, array $columnInfo)
    {
        if (!isset($columnInfo['name'], $columnInfo['key'])) {
            return;
        }

        $writeConnection = $this->resourceConnection->getConnection();
        $tableName = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix($tableName);

        if (empty($columnInfo['key'])) {
            $writeConnection->dropIndex($tableName, $columnInfo['name']);
            return;
        }

        $indexType = \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_PRIMARY;
        $columnInfo['key'] == 'mul' && $indexType = \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX;
        $columnInfo['key'] == 'uni' && $indexType = \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE;

        $writeConnection->addIndex($tableName, $columnInfo['name'], $columnInfo['name'], $indexType);
    }

    public function fixColumnProperties($tableName, array $columnInfo)
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
        $tableName = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix($tableName);

        $magentoVersion = $this->helperFactory->getObject('Magento')->getVersion();
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

    public function dropColumn($tableName, array $columnInfo)
    {
        if (!isset($columnInfo['name'])) {
            return;
        }

        $writeConnection = $this->resourceConnection->getConnection();
        $tableName = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix($tableName);

        $writeConnection->dropColumn($tableName, $columnInfo['name']);
    }

    //########################################
}
