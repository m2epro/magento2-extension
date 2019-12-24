<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Main
 */
abstract class Main extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    //########################################

    public function _isAllowed()
    {
        return true;
    }

    protected function _validateSecretKey()
    {
        return true;
    }

    //########################################

    protected function init()
    {
        $this->addCss('control_panel.css');

        $title = $this->__('Control Panel')
                 .' (M2E Pro '.$this->getHelper('Module')->getPublicVersion().')';

        $this->getResultPage()->getConfig()->getTitle()->prepend($title);
    }

    //########################################

    /**
     * It will allow to use control panel features even if extension is disabled, etc.
     * @param \Magento\Framework\App\RequestInterface $request
     * @return bool
     */
    protected function preDispatch(\Magento\Framework\App\RequestInterface $request)
    {
        if ($request->isGet() &&
            !$request->isPost() &&
            !$request->isXmlHttpRequest()) {
            $this->addDevelopmentNotification();
        }

        return true;
    }

    //########################################

    private function addDevelopmentNotification()
    {
        if (!$this->getHelper('Magento')->isDeveloper() &&
            !$this->getHelper('Module')->isDevelopmentMode()) {
            return false;
        }

        $enabledMods = [];
        $this->getHelper('Magento')->isDeveloper() && $enabledMods[] = 'Magento';
        $this->getHelper('Module')->isDevelopmentMode() && $enabledMods[] = 'M2ePro';

        $msg = $this->getMessageManager()->createMessage(\Magento\Framework\Message\MessageInterface::TYPE_WARNING);
        $msg->setText(implode(', ', $enabledMods).' Development Mode is Enabled.');

        $this->getMessageManager()->addUniqueMessages([$msg]);

        return true;
    }

    //########################################
}
