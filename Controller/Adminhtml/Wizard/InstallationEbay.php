<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Wizard;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay
 */
abstract class InstallationEbay extends Wizard
{
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay');
    }

    protected function getNick()
    {
        return \Ess\M2ePro\Helper\View\Ebay::WIZARD_INSTALLATION_NICK;
    }

    protected function init()
    {
        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('Configuration of %channel% Integration', $this->getHelper('Component\Ebay')->getChannelTitle())
        );
    }
}
