<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account\Repricing;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

class Link extends Account
{
    public function execute()
    {
        $accountId = $this->getRequest()->getParam('id');
        $token = $this->getRequest()->getParam('account_token');
        $email = $this->getRequest()->getParam('email');

        $status = $this->getRequest()->getParam('status');
        $messages = $this->getRequest()->getParam('messages', array());

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->amazonFactory->getObjectLoaded('Account', $accountId, NULL, false);

        if ($accountId && is_null($account)) {
            $this->getMessageManager()->addError($this->__('Account does not exist.'));
            return $this->_redirect('*/amazon_account/index');
        }

        foreach ($messages as $message) {

            if ($message['type'] == 'notice') {
                $this->getMessageManager()->addNotice($message['text']);
            }

            if ($message['type'] == 'warning') {
                $this->getMessageManager()->addWarning($message['text']);
            }

            if ($message['type'] == 'error') {
                $this->getMessageManager()->addError($message['text']);
            }
        }

        if ($status == '1') {

            $accountRepricingModel = $this->activeRecordFactory->getObject('Amazon\Account\Repricing');

            $accountRepricingModel->setData(array(
                'account_id' => $accountId,
                'email' => $email,
                'token' => $token
            ));

            $accountRepricingModel->save();

            /** @var $repricing \Ess\M2ePro\Model\Amazon\Repricing\Synchronization\General */
            $repricing = $this->modelFactory->getObject('Amazon\Repricing\Synchronization\General');
            $repricing->setAccount($account);
            $repricing->run();
        }

        return $this->_redirect($this->getUrl('*/amazon_account/edit', array(
                'id' => $accountId
            )).'#repricing');
    }
}