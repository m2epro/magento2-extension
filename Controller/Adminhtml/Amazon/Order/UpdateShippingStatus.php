<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

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
            ->addFieldToFilter('id', array('in' => $ids))
            ->getItems();

        $canUpdateShippingStatuses = array();
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
        if (in_array(true, $canUpdateShippingStatuses, true) && $wasPrimeOrder)
        {
            $this->messageManager->addWarning(
                $this->__('Some Amazon Order(s) can not be updated for Shipped Status.')
            );
        }
        if (!in_array(true, $canUpdateShippingStatuses, true))
        {
            $this->messageManager->addError(
                $this->__('Amazon Order(s) can not be updated for Shipped Status.')
            );
        }

        $this->_redirect($this->_redirect->getRefererUrl());
    }
}