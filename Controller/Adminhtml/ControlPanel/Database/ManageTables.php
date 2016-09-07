<?php

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database;

class ManageTables extends Table
{
    public function execute()
    {
        $tables = $this->getRequest()->getParam('tables', array());

        $response = '';
        foreach ($tables as $table) {

            if (is_null($this->getHelper('Module\Database\Structure')->getTableModel($table))) {
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