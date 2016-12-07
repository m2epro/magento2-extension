<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\PickupStore;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Account
{
    //########################################

    public function execute()
    {
        if (!$this->getRequest()->getParam('account_id')) {
            return $this->_redirect('*/ebay_account/index');
        }

        if ($this->isAjax()) {
            $this->setAjaxContent($this->createBlock('Ebay\Account\PickupStore\Grid'));
            return $this->getResult();
        }

        $account = $this->ebayFactory->getObjectLoaded(
            'Account', (int)$this->getRequest()->getParam('account_id')
        );
        $this->addContent($this->createBlock('Ebay\Account\PickupStore'));
        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('My Stores for "%s%"', $account->getTitle())
        );

        return $this->getResultPage();
    }

    //########################################
}