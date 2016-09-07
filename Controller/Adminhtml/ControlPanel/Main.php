<?php

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel;

use Ess\M2ePro\Helper\Module;
use Magento\Backend\App\Action;

abstract class Main extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    //########################################

    public function _isAllowed()
    {
        return true;
    }

    //########################################

    protected function init()
    {
        $this->addCss('control_panel.css');

        $title = $this->__('Control Panel')
                 .' (M2E Pro '.$this->getHelper('Module')->getVersion()
                 .'#'.$this->getHelper('Module')->getRevision().')';

        $this->getResultPage()->getConfig()->getTitle()->prepend($title);
    }

    //########################################

    protected function preDispatch(\Magento\Framework\App\RequestInterface $request)
    {
        $result = parent::preDispatch($request);

        if ($request->isGet() &&
            !$request->isPost() &&
            !$request->isXmlHttpRequest()) {

            $this->addDevelopmentNotification();
            $this->addMaintenanceNotification();
        }

        return $result;
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
        if (!$this->getHelper('Module\Maintenance\Debug')->isEnabled()) {
            return false;
        }

        $this->getMessageManager()->addWarning('Maintenance is Active now.');

        return true;
    }

    //########################################
}