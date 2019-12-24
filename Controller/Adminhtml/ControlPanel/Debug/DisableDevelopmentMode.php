<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Debug;

use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Debug\DisableDevelopmentMode
 */
class DisableDevelopmentMode extends Main
{
    public function execute()
    {
        $this->helperFactory->getObject('Module')->setDevelopmentMode(false);
        $this->getMessageManager()->addSuccess('Development Mode was successfully disabled.');
        return $this->_redirect($this->getHelper('View\ControlPanel')->getPageDebugTabUrl());
    }
}
