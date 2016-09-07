<?php

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database;

class AddTableRow extends Table
{
    public function execute()
    {
        $modelInstance = $this->getTableModel();
        $cellsValues   = $this->prepareCellsValuesArray();

        if (empty($cellsValues)) {
            return;
        }

        $modelInstance->createEntry($cellsValues);
        $this->afterTableAction($modelInstance->getTableName());
    }
}