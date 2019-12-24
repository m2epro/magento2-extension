<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Inspection;

use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Main;
use Ess\M2ePro\Helper\Module;
use Magento\Backend\App\Action;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Inspection\RepairCrashedTable
 */
class RepairCrashedTable extends Main
{
    public function execute()
    {
        if (!$tableName = $this->getRequest()->getParam('table_name')) {
            $this->getMessageManager()->addError('Table Name is not presented.');
            return $this->_redirect($this->getHelper('View\ControlPanel')->getPageInspectionTabUrl());
        }

        $this->getHelper('Module_Database_Repair')->repairCrashedTable($tableName)
            ? $this->getMessageManager()->addSuccess('Successfully repaired.')
            : $this->getMessageManager()->addError('Error.');

        return $this->_redirect($this->getHelper('View\ControlPanel')->getPageInspectionTabUrl());
    }
}
