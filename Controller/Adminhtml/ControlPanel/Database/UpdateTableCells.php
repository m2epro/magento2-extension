<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database;

/**
 * Class UpdateTableCells
 * @package Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database
 */
class UpdateTableCells extends Table
{
    public function execute()
    {
        $ids           = $this->prepareIds();
        $cellsValues   = $this->prepareCellsValuesArray();
        $modelInstance = $this->getTableModel();

        if (empty($ids) || empty($cellsValues)) {
            return;
        }

        $modelInstance->updateEntries($ids, $cellsValues);
        $this->afterTableAction($modelInstance->getTableName());
    }
}
