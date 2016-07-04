<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

class MarkAsPickedUp extends Order
{
    public function execute()
    {
        if ($this->sendInStorePickupNotifications('picked_up')) {
            $this->messageManager->addSuccess(
                $this->__('Orders were successfully marked as Picked Up.')
            );
        } else {
            $this->messageManager->addError(
                $this->__('Orders were not marked as Picked Up.')
            );
        }

        return $this->_redirect($this->_redirect->getRefererUrl());
    }
}