<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account\Repricing;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

class LinkOrRegister extends Account
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

        $backUrl = $this->getUrl(
            '*/amazon_account_repricing/link',
            array('id' => $account->getId())
        );

        /** @var $repricingAction \Ess\M2ePro\Model\Amazon\Repricing\Action\Account */
        $repricingAction = $this->modelFactory->getObject('Amazon\Repricing\Action\Account');
        $repricingAction->setAccount($account);
        $serverRequestToken = $repricingAction->sendLinkActionData($backUrl);

        if ($serverRequestToken === false) {
            $this->getMessageManager()->addError($this->__(
                'M2E Pro cannot to connect to the Amazon Repricing Service. Please try again later.'
            ));
            return $this->_redirect($this->getUrl('*/amazon_account/edit/', ['id' => $accountId]));
        }

        return $this->_redirect(
            $this->getHelper('Component\Amazon\Repricing')->prepareActionUrl(
                \Ess\M2ePro\Helper\Component\Amazon\Repricing::COMMAND_ACCOUNT_LINK, $serverRequestToken
            )
        );
    }
}