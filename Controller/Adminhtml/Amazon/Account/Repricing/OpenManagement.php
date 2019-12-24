<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account\Repricing;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Account\Repricing\OpenManagement
 */
class OpenManagement extends Account
{
    public function execute()
    {
        $accountId = $this->getRequest()->getParam('id');

        /** @var $account \Ess\M2ePro\Model\Account */
        $account = $this->amazonFactory->getObjectLoaded('Account', $accountId, null, false);

        if ($accountId && $account === null) {
            $this->getMessageManager()->addError($this->__('Account does not exist.'));
            return $this->_redirect('*/amazon_account/index');
        }

        return $this->_redirect($this->getHelper('Component_Amazon_Repricing')->getManagementUrl($account));
    }
}
