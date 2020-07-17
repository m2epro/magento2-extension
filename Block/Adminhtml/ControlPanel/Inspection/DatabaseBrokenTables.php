<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection\DatabaseBrokenTables
 */
class DatabaseBrokenTables extends AbstractInspection
{
    public $emptyTables        = [];
    public $notInstalledTables = [];

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('controlPanelInspectionDatabaseBrokenTables');
        $this->setTemplate('control_panel/inspection/databaseBrokenTables.phtml');

        $this->prepareTablesInfo();
    }

    //########################################

    public function isShown()
    {
        return !empty($this->emptyTables) ||
               !empty($this->notInstalledTables);
    }

    //########################################

    private function prepareTablesInfo()
    {
        $this->emptyTables        = $this->getEmptyTables();
        $this->notInstalledTables = $this->getNotInstalledTables();
    }

    //########################################

    private function getEmptyTables()
    {
        $helper = $this->getHelper('Module_Database_Structure');

        $emptyTables = [];
        foreach ($this->getGeneralTables() as $table) {
            if (!$helper->isTableReady($table)) {
                continue;
            }

            !$helper->getCountOfRecords($table) && $emptyTables[] = $table;
        }

        return $emptyTables;
    }

    private function getNotInstalledTables()
    {
        $helper = $this->getHelper('Module_Database_Structure');

        $notInstalledTables = [];
        foreach ($helper->getModuleTables() as $tableName) {
            !$helper->isTableExists($tableName) && $notInstalledTables[] = $tableName;
        }

        return $notInstalledTables;
    }

    //########################################

    private function getGeneralTables()
    {
        return [
            'm2epro_config',
            'm2epro_wizard',
            'm2epro_marketplace',
            'm2epro_amazon_marketplace',
            'm2epro_ebay_marketplace',
        ];
    }

    //########################################
}
