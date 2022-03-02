<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Inspector;

use Ess\M2ePro\Model\ControlPanel\Inspection\FixerInterface;
use Ess\M2ePro\Model\ControlPanel\Inspection\InspectorInterface;
use Ess\M2ePro\Helper\Module\Database\Structure as DatabaseStructure;
use Ess\M2ePro\Helper\Component as HelperComponent;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\ResourceConnection;
use Ess\M2ePro\Model\ControlPanel\Inspection\Issue\Factory as IssueFactory;

class BrokenTables implements InspectorInterface, FixerInterface
{
    /** @var array */
    private $brokenTables = [];

    /** @var DatabaseStructure */
    private $databaseStructure;

    /** @var HelperComponent */
    private $helperComponent;

    /** @var UrlInterface */
    private $urlBuilder;

    /** @var ResourceConnection */
    private $resourceConnection;

    /** @var IssueFactory */
    private $issueFactory;

    //########################################

    public function __construct(
        DatabaseStructure $databaseStructure,
        HelperComponent $helperComponent,
        UrlInterface $urlBuilder,
        ResourceConnection $resourceConnection,
        IssueFactory $issueFactory
    ) {
        $this->databaseStructure = $databaseStructure;
        $this->helperComponent = $helperComponent;
        $this->urlBuilder = $urlBuilder;
        $this->resourceConnection = $resourceConnection;
        $this->issueFactory = $issueFactory;
    }

    //########################################

    public function process()
    {
        $issues = [];
        $this->getBrokenTables();

        if (!empty($this->brokenTables)) {
            $issues[] = $this->issueFactory->create(
                'Has broken data in table',
                $this->renderMetadata($this->brokenTables)
            );
        }

        return $issues;
    }

    //########################################

    private function getBrokenTables()
    {
        $horizontalTables = $this->databaseStructure->getHorizontalTables();

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
        $allTables = $this->databaseStructure->getHorizontalTables();

        $result = $returnOnlyCount ? 0 : [];

        foreach ($allTables as $parentTable => $childTables) {
            foreach ($childTables as $component => $childTable) {
                if (!in_array($table, [$parentTable, $childTable])) {
                    continue;
                }

                $parentTablePrefix = $this->databaseStructure->getTableNameWithPrefix($parentTable);
                $childTablePrefix  = $this->databaseStructure->getTableNameWithPrefix($childTable);

                $parentIdColumn = $this->databaseStructure->getIdColumn($parentTable);
                $childIdColumn  = $this->databaseStructure->getIdColumn($childTable);

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
                            $this->helperComponent->getComponents()
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

    private function renderMetadata($data)
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

            $tableWithPrefix = $this->databaseStructure->getTableNameWithPrefix($table);
            $idColumnName = $this->databaseStructure->getIdColumn($table);

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
