<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database;

/**
 * Class AddTableRow
 * @package Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database
 */
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
