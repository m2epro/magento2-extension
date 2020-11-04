<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Inspection;

use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Inspection\ChangeMaintenanceMode
 */
class ChangeMaintenanceMode extends Main
{
    //########################################

    public function execute()
    {
        if ($this->getHelper('Module_Maintenance')->isEnabled()) {
            $this->getHelper('Module_Maintenance')->disable();
        } else {
            $this->getHelper('Module_Maintenance')->enable();
        }

        $this->messageManager->addSuccess($this->__('Changed.'));
        return $this->_redirect($this->getHelper('View_ControlPanel')->getPageUrl());
    }

    //########################################
}
