<?php

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Debug;

use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Main;

class EnableMaintenance extends Main
{
    public function execute()
    {
        $this->helperFactory->getObject('Module\Maintenance\Debug')->enable();
        $this->getMessageManager()->addSuccess('Maintenance was successfully enabled.');
        return $this->_redirect($this->getHelper('View\ControlPanel')->getPageDebugTabUrl());
    }
}