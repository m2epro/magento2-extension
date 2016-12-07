<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

class GoToOrder extends Account
{
    public function execute()
    {
        $feedbackId = $this->getRequest()->getParam('feedback_id');

        if (is_null($feedbackId)) {
            $this->getMessageManager()->addError($this->__('Feedback is not defined.'));
            return $this->_redirect('*/ebay_order/index');
        }

        /** @var $feedback \Ess\M2ePro\Model\Ebay\Feedback */
        $feedback = $this->activeRecordFactory->getObjectLoaded('Ebay\Feedback', $feedbackId);
        $order = $feedback->getOrder();

        if (is_null($order)) {
            $this->getMessageManager()->addError($this->__('Requested Order was not found.'));
            return $this->_redirect('*/ebay_order/index');
        }

        return $this->_redirect('*/ebay_order/view', array('id' => $order->getId()));
    }
}