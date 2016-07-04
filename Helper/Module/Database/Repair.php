<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module\Database;

class Repair extends \Ess\M2ePro\Helper\AbstractHelper
{
    protected $resourceConnection;
    
    //########################################
    
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->resourceConnection = $resourceConnection;
        parent::__construct($helperFactory, $context);
    }
    
    //########################################

    public function getBrokenTablesInfo()
    {
        $horizontalTables = $this->getHelper('Module\Database\Structure')->getHorizontalTables();

        $brokenParentTables   = array();
        $brokenChildrenTables = array();
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

        return array(
            'parent'      => $brokenParentTables,
            'children'    => $brokenChildrenTables,
            'total_count' => $totalBrokenTables
        );
    }

    public function getBrokenRecordsInfo($table, $returnOnlyCount = false)
    {
        $connection = $this->resourceConnection->getConnection();
        $allTables = $this->getHelper('Module\Database\Structure')->getHorizontalTables();

        $result = $returnOnlyCount ? 0 : array();

        foreach ($allTables as $parentTable => $childTables) {
            foreach ($childTables as $component => $childTable) {

                if (!in_array($table, array($parentTable, $childTable))) {
                    continue;
                }

                $parentTablePrefix = $this->resourceConnection->getTableName($parentTable);
                $childTablePrefix = $this->resourceConnection->getTableName($childTable);

                $parentIdColumn = $this->getHelper('Module\Database\Structure')->getIdColumn($parentTable);
                $childIdColumn  = $this->getHelper('Module\Database\Structure')->getIdColumn($childTable);

                if ($table == $parentTable) {

                    $stmtQuery = $connection->select()
                        ->from(array('parent' => $parentTablePrefix),
                               $returnOnlyCount ? new \Zend_Db_Expr('count(*) as `count_total`')
                                                : array('id' => $parentIdColumn))
                        ->joinLeft(array('child' => $childTablePrefix),
                                   '`parent`.`'.$parentIdColumn.'` = `child`.`'.$childIdColumn.'`',
                                   array())
                        ->where('`parent`.`component_mode` = ?', $component)
                        ->where('`child`.`'.$childIdColumn.'` IS NULL')
                        ->query();

                } else if ($table == $childTable) {

                    $stmtQuery = $connection->select()
                        ->from(array('child' => $childTablePrefix),
                               $returnOnlyCount ? new \Zend_Db_Expr('count(*) as `count_total`')
                                                : array('id' => $childIdColumn))
                        ->joinLeft(array('parent' => $parentTablePrefix),
                                   '`child`.`'.$childIdColumn.'` = `parent`.`'.$parentIdColumn.'`',
                                   array())
                        ->where('`parent`.`'.$parentIdColumn.'` IS NULL')
                        ->query();
                }

                if ($returnOnlyCount) {
                    $row = $stmtQuery->fetch();
                    $result += (int)$row['count_total'];
                } else {
                    while ($row = $stmtQuery->fetch()) {
                        $result[] = (int)$row['id'];
                    }
                }
            }
        }

        return $result;
    }

    public function repairBrokenTables(array $tables)
    {
        $connection = $this->resourceConnection->getConnection();

        foreach ($tables as $table) {

            $brokenIds = $this->getBrokenRecordsInfo($table);
            if (count($brokenIds) <= 0) {
                continue;
            }
            $brokenIds = array_slice($brokenIds,0,50000);

            $tableWithPrefix = $this->resourceConnection->getTableName($table);
            $idColumnName = $this->getHelper('Module\Database\Structure')->getIdColumn($table);

            foreach (array_chunk($brokenIds,1000) as $brokenIdsPart) {

                if (count($brokenIdsPart) <= 0) {
                    continue;
                }

                $connection->delete(
                    $tableWithPrefix,
                    '`'.$idColumnName.'` IN ('.implode (',',$brokenIdsPart).')'
                );
            }

            $logTemp = "Table: {$table} ## Amount: ".count($brokenIds);
            // TODO magento 2
            Mage::log($logTemp, null, 'm2epro_repair_tables.log',true);
        }
    }

    // ---------------------------------------

    /**
     * @param $tableName
     * @return string <p> OK if repair was successfully or Error Message if not. </p>
     */
    public function repairCrashedTable($tableName)
    {
        $connWrite = $this->resourceConnection->getConnection('core_write');

        $tableName = $this->resourceConnection->getTableName($tableName);

        $result = $connWrite->query("REPAIR TABLE `{$tableName}`")->fetch();
        return $result['Msg_text'];
    }

    // ---------------------------------------

    public function fixColumnIndex($tableName, array $columnInfo)
    {
        if (!isset($columnInfo['name'], $columnInfo['key'])) {
            return;
        }

        $writeConnection = $this->resourceConnection->getConnection('core_write');
        $tableName = $this->resourceConnection->getTableName($tableName);

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
        !empty($columnInfo['after']) && $definition .= "AFTER `{$columnInfo['after']}`";

        $writeConnection = $this->resourceConnection->getConnection('core_write');
        $tableName = $this->resourceConnection->getTableName($tableName);

        if ($writeConnection->tableColumnExists($tableName, $columnInfo['name']) === false) {
            $writeConnection->addColumn($tableName, $columnInfo['name'], $definition);
            return;
        }

        $writeConnection->changeColumn($tableName, $columnInfo['name'], $columnInfo['name'], $definition);
    }

    public function dropColumn($tableName, array $columnInfo)
    {
        if (!isset($columnInfo['name'])) {
            return;
        }

        $writeConnection = $this->resourceConnection->getConnection('core_write');
        $tableName = $this->resourceConnection->getTableName($tableName);

        $writeConnection->dropColumn($tableName, $columnInfo['name']);
    }

    //########################################
}