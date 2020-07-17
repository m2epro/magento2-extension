<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard;

use Ess\M2ePro\Controller\Adminhtml\Wizard;

/**
 * Class  \Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationToInnodb
 */
abstract class MigrationToInnodb extends Wizard
{
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed($this->getMenuRootNodeNick());
    }

    protected function getNick()
    {
        return 'migrationToInnodb';
    }

    protected function getCustomViewNick()
    {
        return null;
    }

    protected function initResultPage()
    {
        if ($this->resultPage !== null) {
            return;
        }

        parent::initResultPage();

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Marketplace Synchronization'));
        $this->getResultPage()->setActiveMenu($this->getMenuRootNodeNick());
    }

    protected function getMenuRootNodeNick()
    {
        $referrer = $this->getRequest()->getParam('referrer');

        if ($referrer == \Ess\M2ePro\Helper\View\Ebay::NICK) {
            return \Ess\M2ePro\Helper\View\Ebay::MENU_ROOT_NODE_NICK;
        }
        if ($referrer == \Ess\M2ePro\Helper\View\Amazon::NICK) {
            return \Ess\M2ePro\Helper\View\Amazon::MENU_ROOT_NODE_NICK;
        }
        if ($referrer == \Ess\M2ePro\Helper\View\Walmart::NICK) {
            return \Ess\M2ePro\Helper\View\Walmart::MENU_ROOT_NODE_NICK;
        }

        return null;
    }

    protected function getMenuRootNodeLabel()
    {
        return 'Marketplace Synchronization';
    }

    protected function congratulationAction()
    {
        if (!$this->isFinished()) {
            return $this->_redirect('*/*/index');
        }

        $this->getHelper('Magento')->clearMenuCache();

        /** @var \Ess\M2ePro\Model\Wizard\MigrationToInnodb $wizard */
        $wizard = $this->getWizardHelper()->getWizard($this->getNick());
        $redirectUrl = $wizard->getRefererUrl();
        empty($redirectUrl) && $redirectUrl = $this->getUrl('*/support/index');

        $wizard->clearRefererUrl();
        return $this->_redirect($redirectUrl);
    }
}
