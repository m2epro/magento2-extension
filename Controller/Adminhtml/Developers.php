<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml;

abstract class Developers extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    //########################################

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

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay_help_center_health_status') ||
               $this->_authorization->isAllowed('Ess_M2ePro::amazon_help_center_health_status');
    }

    protected function _validateSecretKey()
    {
        return true;
    }

    //########################################
}