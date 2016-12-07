<?php

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Main;

abstract class Table extends Main
{
    private $databaseTableFactory;

    //########################################

    public function __construct(
        Context $context,
        \Ess\M2ePro\Model\ControlPanel\Database\TableModelFactory $databaseTableFactory
    )
    {
        $this->databaseTableFactory = $databaseTableFactory;
        parent::__construct($context);
    }

    //########################################

    protected function getTableModel()
    {
        $tableName = $this->getRequest()->getParam('table');
        $component = $this->getRequest()->getParam('component');
        $mergeMode = (bool)$this->getRequest()->getParam('merge');

        /** @var \Ess\M2ePro\Model\ControlPanel\Database\TableModel $model */
        $model = $this->databaseTableFactory->create(['data' => [
            'table_name' => $tableName,
            'merge_mode' => $mergeMode,
            'merge_mode_component' => $component
        ]]);

        return $model;
    }

    protected function isMergeModeEnabled($table)
    {
        return (bool)$this->getRequest()->getParam('merge') &&
               $this->getHelper('Module\Database\Structure')->isTableHorizontal($table);
    }

    protected function prepareCellsValuesArray()
    {
        $cells = $this->getRequest()->getParam('cells', array());
        is_string($cells) && $cells = array($cells);

        $bindArray = array();
        foreach ($cells as $columnName) {

            if (is_null($columnValue = $this->getRequest()->getParam('value_'.$columnName))) {
                continue;
            }

            strtolower($columnValue) == 'null' && $columnValue = NULL;
            $bindArray[$columnName] = $columnValue;
        }

        return $bindArray;
    }

    protected function prepareIds()
    {
        $ids = explode(',', $this->getRequest()->getParam('ids'));
        return array_filter(array_map('intval', $ids));
    }

    //########################################

    protected function redirectToTablePage($tableName)
    {
        $this->_redirect('*/*/manageTable', ['table' => $tableName]);
    }

    protected function afterTableAction($tableName)
    {
        if (strpos($tableName, 'config') !== false || strpos($tableName, 'wizard') !== false) {
            $this->getHelper('Module')->clearCache();
        }
    }

    //########################################
}