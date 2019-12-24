<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Order\UpdateShippingStatus
 */
class UpdateShippingStatus extends Order
{
    public function execute()
    {
        $ids = $this->getRequestIds();

        if (count($ids) == 0) {
            $this->messageManager->addError($this->__('Please select Order(s).'));
            $this->_redirect('*/*/index');
            return;
        }

        /** @var \Ess\M2ePro\Model\Order[] $orders */
        $orders = $this->amazonFactory->getObject('Order')
            ->getCollection()
            ->addFieldToFilter('id', ['in' => $ids])
            ->getItems();

        $canUpdateShippingStatuses = [];
        $wasPrimeOrder = false;
        foreach ($orders as $order) {
            if ($order->getChildObject()->isPrime()) {
                $wasPrimeOrder = true;
            } else {
                $canUpdateShippingStatuses[] = $order->getChildObject()->updateShippingStatus();
            }
        }

        if (!in_array(false, $canUpdateShippingStatuses, true) && !$wasPrimeOrder) {
            $this->messageManager->addSuccess(
                $this->__('Updating Amazon Order(s) Status to Shipped in Progress...')
            );
        }
        if (in_array(true, $canUpdateShippingStatuses, true) && $wasPrimeOrder) {
            $this->messageManager->addWarning(
                $this->__('Some Amazon Order(s) can not be updated for Shipped Status.')
            );
        }
        if (!in_array(true, $canUpdateShippingStatuses, true)) {
            $this->messageManager->addError(
                $this->__('Amazon Order(s) can not be updated for Shipped Status.')
            );
        }

        $this->_redirect($this->_redirect->getRefererUrl());
    }
}
