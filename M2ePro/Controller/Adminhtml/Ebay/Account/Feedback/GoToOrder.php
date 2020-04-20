<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback\GoToOrder
 */
class GoToOrder extends Account
{
    public function execute()
    {
        $feedbackId = $this->getRequest()->getParam('feedback_id');

        if ($feedbackId === null) {
            $this->getMessageManager()->addError($this->__('Feedback is not defined.'));
            return $this->_redirect('*/ebay_order/index');
        }

        /** @var $feedback \Ess\M2ePro\Model\Ebay\Feedback */
        $feedback = $this->activeRecordFactory->getObjectLoaded('Ebay\Feedback', $feedbackId);
        $order = $feedback->getOrder();

        if ($order === null) {
            $this->getMessageManager()->addError($this->__('Requested Order was not found.'));
            return $this->_redirect('*/ebay_order/index');
        }

        return $this->_redirect('*/ebay_order/view', ['id' => $order->getId()]);
    }
}
