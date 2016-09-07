<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account\Repricing;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

class OpenManagement extends Account
{
    public function execute()
    {
        $accountId = $this->getRequest()->getParam('id');

        /** @var $account \Ess\M2ePro\Model\Account */
        $account = $this->amazonFactory->getObjectLoaded('Account', $accountId, NULL, false);

        if ($accountId && is_null($account)) {
            $this->getMessageManager()->addError($this->__('Account does not exist.'));
            return $this->_redirect('*/amazon_account/index');
        }

        return $this->_redirect($this->getHelper('Component\Amazon\Repricing')->getManagementUrl($account));
    }
}