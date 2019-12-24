<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Order\UpdateShippingStatus
 */
class UpdateShippingStatus extends Order
{
    public function execute()
    {
        if ($this->processConnector(\Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher::ACTION_SHIP)) {
            $this->messageManager->addSuccess(
                $this->__('Shipping status for selected eBay Order(s) was updated to Shipped.')
            );
        } else {
            $this->messageManager->addError(
                $this->__('Shipping status for selected eBay Order(s) was not updated.')
            );
        }

        return $this->_redirect($this->_redirect->getRefererUrl());
    }
}
