<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

class GoToPaypal extends Order
{
    public function execute()
    {
        $transactionId = $this->getRequest()->getParam('transaction_id');

        if (!$transactionId) {
            $this->messageManager->addError($this->__('Transaction ID should be defined.'));
            return $this->_redirect('*/ebay_order/index');
        }

        /** @var $transaction \Ess\M2ePro\Model\Ebay\Order\ExternalTransaction */
        $transaction = $this->activeRecordFactory->getObject('Ebay\Order\ExternalTransaction')->load(
            $transactionId, 'transaction_id'
        );

        if (is_null($transaction->getId())) {
            $this->messageManager->addError($this->__('eBay Order Transaction does not exist.'));
            return $this->_redirect('*/ebay_order/index');
        }

        if (!$transaction->isPaypal()) {
            $this->messageManager->addError($this->__('This is not a PayPal Transaction.'));
            return $this->_redirect('*/ebay_order/index');
        }

        return $this->_redirect($transaction->getPaypalUrl());
    }
}