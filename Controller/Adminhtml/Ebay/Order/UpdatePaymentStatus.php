<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

class UpdatePaymentStatus extends Order
{
    public function execute()
    {
        if ($this->processConnector(\Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher::ACTION_PAY)) {
            $this->messageManager->addSuccess(
                $this->__('Payment status for selected eBay Order(s) was updated to Paid.')
            );
        } else {
            $this->messageManager->addError(
                $this->__('Payment status for selected eBay Order(s) was not updated.')
            );
        }

        return $this->_redirect($this->_redirect->getRefererUrl());
    }
}