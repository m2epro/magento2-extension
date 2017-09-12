<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard;

use Ess\M2ePro\Controller\Adminhtml\Wizard;

abstract class MigrationFromMagento1 extends Wizard
{
    protected function init()
    {
        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('M2E Pro Module Migration from Magento v1.x')
        );
    }

    protected function initResultPage()
    {
        if (!is_null($this->resultPage)) {
            return;
        }

        parent::initResultPage();

        if (!is_null($this->getMenuRootNodeNick())) {
            $this->getResultPage()->setActiveMenu($this->getMenuRootNodeNick());
        }
    }

    protected function getNick()
    {
        return 'migrationFromMagento1';
    }

    protected function getCustomViewNick()
    {
        return $this->getRequest()->getParam('referrer', NULL);
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

        return NULL;
    }

    protected function getMenuRootNodeLabel()
    {
        $referrer = $this->getRequest()->getParam('referrer');

        if ($referrer == \Ess\M2ePro\Helper\View\Ebay::NICK) {
            return $this->getHelper('View\Ebay')->getMenuRootNodeLabel();
        }
        if ($referrer == \Ess\M2ePro\Helper\View\Amazon::NICK) {
            return $this->getHelper('View\Amazon')->getMenuRootNodeLabel();
        }

        return NULL;
    }
}