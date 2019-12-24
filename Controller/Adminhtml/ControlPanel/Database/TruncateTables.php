<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database\TruncateTables
 */
class TruncateTables extends Table
{
    public function execute()
    {
        $tables = $this->getRequest()->getParam('tables', []);
        !is_array($tables) && $tables = [$tables];

        foreach ($tables as $table) {
            $this->resourceConnection->getConnection()->truncateTable(
                $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix($table)
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
