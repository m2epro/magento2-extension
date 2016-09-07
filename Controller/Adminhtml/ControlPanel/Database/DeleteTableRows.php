<?php

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database;

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