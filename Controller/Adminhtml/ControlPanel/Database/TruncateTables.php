<?php

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database;

class TruncateTables extends Table
{
    public function execute()
    {
        $tables = $this->getRequest()->getParam('tables', array());
        !is_array($tables) && $tables = array($tables);

        foreach ($tables as $table) {

            $this->resourceConnection->getConnection()->truncateTable(
                $this->resourceConnection->getTableName($table)
            );
            $this->afterTableAction($table);
        }

        $this->getMessageManager()->addSuccess('Truncate Tables was successfully completed.');

        if (count($tables) == 1) {
            return $this->redirectToTablePage($tables[0]);
        }

        return $this->_redirect($this->getHelper('View\ControlPanel')->getPageDatabaseTabUrl());
    }
}