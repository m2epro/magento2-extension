<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database\DeleteTableRows
 */
class DeleteTableRows extends Table
{
    public function execute()
    {
        $ids           = $this->prepareIds();
        $modelInstance = $this->getTableModel();

        if (empty($ids)) {
            $this->getMessageManager()->addError("Failed to get model or any of Table Rows are not selected.");
            $this->redirectToTablePage($modelInstance->getTableName());
        }

        $modelInstance->deleteEntries($ids);
        $this->afterTableAction($modelInstance->getTableName());
    }
}
