<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Wizard;

abstract class InstallationAmazon extends Wizard
{
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::amazon');
    }

    protected function init()
    {
        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('Configuration of %channel% Integration', $this->getHelper('Component\Amazon')->getChannelTitle())
        );
    }

    protected function getNick()
    {
        return \Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK;
    }
}