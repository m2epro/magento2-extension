<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database\ManageTables
 */
class ManageTables extends Table
{
    public function execute()
    {
        $tables = $this->getRequest()->getParam('tables', []);

        $response = '';
        foreach ($tables as $table) {
            if ($this->getHelper('Module_Database_Structure')->getTableModel($table) === null) {
                continue;
            }

            $url = $this->getUrl('*/*/manageTable', ['table' => $table]);
            $response .= "window.open('{$url}');";
        }

        $backUrl = $this->getHelper('View\ControlPanel')->getPageDatabaseTabUrl();
        $response = "<script>
                        {$response}
                        window.location = '{$backUrl}';
                     </script>";

        return $this->getResponse()->setBody($response);
    }
}
