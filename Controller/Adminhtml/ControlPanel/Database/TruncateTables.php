<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database;

class TruncateTables extends Table
{
    /** @var \Ess\M2ePro\Helper\View\ControlPanel */
    protected $controlPanelHelper;

    public function __construct(
        \Ess\M2ePro\Helper\View\ControlPanel $controlPanelHelper,
        \Ess\M2ePro\Controller\Adminhtml\Context $context,
        \Ess\M2ePro\Model\ControlPanel\Database\TableModelFactory $databaseTableFactory
    ) {
        parent::__construct($context, $databaseTableFactory);
        $this->controlPanelHelper = $controlPanelHelper;
    }

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

        $this->getMessageManager()->addSuccess('Truncate Tables was completed.');

        if (count($tables) == 1) {
            return $this->redirectToTablePage($tables[0]);
        }

        return $this->_redirect($this->controlPanelHelper->getPageDatabaseTabUrl());
    }
}
