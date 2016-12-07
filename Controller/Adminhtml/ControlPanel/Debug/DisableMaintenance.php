<?php

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Debug;

use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Main;

class DisableMaintenance extends Main
{
    public function execute()
    {
        $this->helperFactory->getObject('Module\Maintenance\Debug')->disable();
        $this->getMessageManager()->addSuccess('Maintenance was successfully disabled.');
        return $this->_redirect($this->getHelper('View\ControlPanel')->getPageDebugTabUrl());
    }
}