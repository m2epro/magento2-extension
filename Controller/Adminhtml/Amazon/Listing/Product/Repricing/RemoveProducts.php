<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Repricing;

class RemoveProducts extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    public function execute()
    {
        $listingId     = $this->getRequest()->getParam('id');
        $accountId     = $this->getRequest()->getParam('account_id');
        $responseToken = $this->getRequest()->getParam('response_token');

        if (empty($responseToken)) {
            return $this->_redirect($this->getUrl('*/amazon_listing/view', ['id' => $listingId]));
        }

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->amazonFactory->getObjectLoaded('Account', $accountId, NULL, false);

        if (!$account->getId()) {
            $this->getMessageManager()->addError($this->__('Account does not exist.'));
            return $this->_redirect($this->getUrl('*/amazon_listing/view', ['id' => $listingId]));
        }

        /** @var $repricingAction \Ess\M2ePro\Model\Amazon\Repricing\Action\Product */
        $repricingAction = $this->modelFactory->getObject('Amazon\Repricing\Action\Product');
        $repricingAction->setAccount($account);
        $response = $repricingAction->getActionResponseData($responseToken);

        if (!empty($response['messages'])) {
            foreach ($response['messages'] as $message) {

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
        }

        if ($response['status'] == '0') {
            return $this->_redirect($this->getUrl('*/amazon_listing/view', ['id' => $listingId]));
        }

        if (empty($response['offers'])) {
            return $this->_redirect($this->getUrl('*/amazon_listing/view', ['id' => $listingId]));
        }

        $skus = array();
        foreach ($response['offers'] as $offer) {
            $skus[] = $offer['sku'];
        }

        /** @var $repricingSynchronization \Ess\M2ePro\Model\Amazon\Repricing\Synchronization\General */
        $repricingSynchronization = $this->modelFactory->getObject('Amazon\Repricing\Synchronization\General');
        $repricingSynchronization->setAccount($account);
        $repricingSynchronization->run($skus);

        $this->getMessageManager()->addSuccess(
            $this->__('Amazon Products have been successfully removed from the Amazon Repricing Tool.')
        );
        return $this->_redirect($this->getUrl('*/amazon_listing/view', ['id' => $listingId]));
    }
}