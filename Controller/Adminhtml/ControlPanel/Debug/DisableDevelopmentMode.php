<?php

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Debug;

use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Main;

class DisableDevelopmentMode extends Main
{
    public function execute()
    {
        $this->helperFactory->getObject('Module')->setDevelopmentMode(false);
        $this->getMessageManager()->addSuccess('Development Mode was successfully disabled.');
        return $this->_redirect($this->getHelper('View\ControlPanel')->getPageDebugTabUrl());
    }
}