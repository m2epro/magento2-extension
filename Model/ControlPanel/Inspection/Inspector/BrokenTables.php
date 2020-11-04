<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Inspector;

use Ess\M2ePro\Model\ControlPanel\Inspection\AbstractInspection;
use Ess\M2ePro\Model\ControlPanel\Inspection\FixerInterface;
use Ess\M2ePro\Model\ControlPanel\Inspection\InspectorInterface;
use Ess\M2ePro\Model\ControlPanel\Inspection\Manager;

class BrokenTables extends AbstractInspection implements InspectorInterface, FixerInterface
{
    /** @var array */
    protected $brokenTables = [];

    //########################################

    public function getTitle()
    {
        return 'Broken tables';
    }

    public function getGroup()
    {
        return Manager::GROUP_STRUCTURE;
    }

    public function getExecutionSpeed()
    {
        return Manager::EXECUTION_SPEED_FAST;
    }

    //########################################

    public function process()
    {
        $issues = [];
        $this->getBrokenTables();

        if (!empty($this->brokenTables)) {
            $issues[] = $this->resultFactory->createError(
                $this,
                'Has broken data in table',
                $this->renderMetadata($this->brokenTables)
            );
        }

        return $issues;
    }

    //########################################

    protected function getBrokenTables()
    {
        $horizontalTables = $this->helperFactory->getObject('Module_Database_Structure')->getHorizontalTables();

        foreach ($horizontalTables as $parentTable => $childrenTables) {
            if ($brokenItemsCount = $this->getBrokenRecordsInfo($parentTable, true)) {
                $this->brokenTables[$parentTable] = $brokenItemsCount;
            }

            foreach ($childrenTables as $childrenTable) {
                if ($brokenItemsCount = $this->getBrokenRecordsInfo($childrenTable, true)) {
                    $this->brokenTables[$childrenTable] = $brokenItemsCount;
                }
            }
        }
    }

    public function getBrokenRecordsInfo($table, $returnOnlyCount = false)
    {
        $connection = $this->resourceConnection->getConnection();
        $allTables = $this->helperFactory->getObject('Module_Database_Structure')->getHorizontalTables();

        $result = $returnOnlyCount ? 0 : [];

        foreach ($allTables as $parentTable => $childTables) {
            foreach ($childTables as $component => $childTable) {
                if (!in_array($table, [$parentTable, $childTable])) {
                    continue;
                }

                $parentTablePrefix = $this->helperFactory->getObject('Module_Database_Structure')
                    ->getTableNameWithPrefix($parentTable);
                $childTablePrefix  = $this->helperFactory->getObject('Module_Database_Structure')
                    ->getTableNameWithPrefix($childTable);

                $parentIdColumn = $this->helperFactory
                    ->getObject('Module_Database_Structure')
                    ->getIdColumn($parentTable);
                $childIdColumn  = $this->helperFactory
                    ->getObject('Module_Database_Structure')
                    ->getIdColumn($childTable);

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
                            $this->helperFactory->getObject('Component')->getComponents()
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

    //########################################

    protected function renderMetadata($data)
    {
        $currentUrl = $this->urlBuilder
            ->getUrl('m2epro/controlPanel_tools_m2ePro/general', ['action' => 'deleteBrokenData']);
        $infoUrl = $this->urlBuilder
            ->getUrl('m2epro/controlPanel_tools_m2ePro/general', ['action' => 'showBrokenTableIds']);

        $html = <<<HTML
        <form method="GET" action="{$currentUrl}">
            <input type="hidden" name="action" value="repair" />
            <table style="width: 100%">
<tr>
    <td><div style="height:10px;"></div></td>
</tr>
<tr>
    <th style="width: 400px">Table</th>
    <th style="width: 50px">Count</th>
    <th style="width: 50px"></th>
</tr>
HTML;
        foreach ($data as $tableName => $brokenItemsCount) {
            $html .= <<<HTML
<tr>
    <td>
        <a href="{$infoUrl}?table[]={$tableName}"
           target="_blank" title="Show Ids" style="text-decoration:none;">{$tableName}</a>
    </td>
    <td>
        {$brokenItemsCount}
    </td>
    <td>
        <input type="checkbox" name="table[]" value="{$tableName}" />
    </td>
HTML;
        }

        $html .= <<<HTML
            </table>
            <button type="button" onclick="ControlPanelInspectionObj.removeRow(this)">Delete checked</button>
        </form>
HTML;
        return $html;
    }

    public function fix($tables)
    {
        $connection = $this->resourceConnection->getConnection();

        foreach ($tables as $table) {
            $brokenIds = $this->getBrokenRecordsInfo($table);
            if (count($brokenIds) <= 0) {
                continue;
            }
            $brokenIds = array_slice($brokenIds, 0, 50000);

            $tableWithPrefix = $this->helperFactory
                ->getObject('Module_Database_Structure')->getTableNameWithPrefix($table);
            $idColumnName = $this->helperFactory
                ->getObject('Module_Database_Structure')->getIdColumn($table);

            foreach (array_chunk($brokenIds, 1000) as $brokenIdsPart) {
                if (count($brokenIdsPart) <= 0) {
                    continue;
                }

                $connection->delete(
                    $tableWithPrefix,
                    '`'.$idColumnName.'` IN ('.implode(',', $brokenIdsPart).')'
                );
            }
        }
    }

    //########################################
}
