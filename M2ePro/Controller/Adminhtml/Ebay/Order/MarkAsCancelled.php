<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Order\MarkAsCancelled
 */
class MarkAsCancelled extends Order
{
    public function execute()
    {
        if ($this->sendInStorePickupNotifications('cancelled')) {
            $this->messageManager->addSuccess(
                $this->__('Orders were successfully marked as Cancelled.')
            );
        } else {
            $this->messageManager->addError(
                $this->__('Orders were not marked as Cancelled.')
            );
        }

        return $this->_redirect($this->_redirect->getRefererUrl());
    }
}
