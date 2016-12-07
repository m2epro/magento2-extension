<?php

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Debug;

use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Main;

class EnableDevelopmentMode extends Main
{
    public function execute()
    {
        $this->helperFactory->getObject('Module')->setDevelopmentMode(true);
        $this->getMessageManager()->addSuccess('Development Mode was successfully enabled.');
        return $this->_redirect($this->getHelper('View\ControlPanel')->getPageDebugTabUrl());
    }
}