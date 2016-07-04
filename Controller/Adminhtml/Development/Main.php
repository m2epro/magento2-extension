<?php

namespace Ess\M2ePro\Controller\Adminhtml\Development;

use Ess\M2ePro\Helper\Module;
use Magento\Backend\App\Action;

abstract class Main extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    //########################################

    protected function preDispatch(\Magento\Framework\App\RequestInterface $request)
    {
        parent::preDispatch($request);

        if ($request->isGet() &&
            !$request->isPost() &&
            !$request->isXmlHttpRequest()) {

            $this->addDevelopmentNotification();
            $this->addMaintenanceNotification();
        }
    }

    //########################################

    private function addDevelopmentNotification()
    {
        if (!$this->getHelper('Magento')->isDeveloper() &&
            !$this->getHelper('Module')->isDevelopmentMode()) {
            return false;
        }

        $enabledMods = array();
        $this->getHelper('Magento')->isDeveloper() && $enabledMods[] = 'Magento';
        $this->getHelper('Module')->isDevelopmentMode() && $enabledMods[] = 'M2ePro';

        $this->getMessageManager()->addWarning(implode(', ', $enabledMods).' Development Mode is Enabled.');

        return true;
    }

    private function addMaintenanceNotification()
    {
        if (!$this->getHelper('Module\Maintenance')->isEnabled()) {
            return false;
        }

        $this->getMessageManager()->addWarning('Maintenance is Active now.');

        return true;
    }

    //########################################
}