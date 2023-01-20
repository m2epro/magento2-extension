<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Repricer;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

class Link extends Account
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function execute()
    {
        $accountId = $this->getRequest()->getParam('id');
        $token = $this->getRequest()->getParam('account_token');
        $email = $this->getRequest()->getParam('email');

        $status = $this->getRequest()->getParam('status');
        $messages = $this->getRequest()->getParam('message', []);

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->amazonFactory->getObjectLoaded('Account', $accountId, null, false);

        if ($accountId && $account === null) {
            $this->getMessageManager()->addError($this->__('Account does not exist.'));

            return $this->_redirect('*/amazon_account/index');
        }

        foreach ($messages as $message) {
            if ($message['type'] === 'notice') {
                $this->getMessageManager()->addNotice($message['text']);
            }

            if ($message['type'] === 'warning') {
                $this->getMessageManager()->addWarning($message['text']);
            }

            if ($message['type'] === 'error') {
                $this->getMessageManager()->addError($message['text']);
            }
        }

        if ($status == '1') {
            $accountRepricingModel = $this->activeRecordFactory->getObject('Amazon_Account_Repricing');

            $this->modelFactory->getObject('Amazon_Account_Repricing_Builder')->build(
                $accountRepricingModel,
                [
                    'account_id' => $accountId,
                    'email' => $email,
                    'token' => $token,
                ]
            );

            /** @var \Ess\M2ePro\Model\Amazon\Repricing\Synchronization\General $repricing */
            $repricing = $this->modelFactory->getObject('Amazon_Repricing_Synchronization_General');
            $repricing->setAccount($account);
            $repricing->run();

            return $this->_redirect(
                $this->getUrl('*/amazon_repricer_settings/edit', [
                    'id' => $accountId,
                ])
            );
        }

        return $this->_redirect($this->getUrl('*/amazon_repricer_settings/index/'));
    }
}
