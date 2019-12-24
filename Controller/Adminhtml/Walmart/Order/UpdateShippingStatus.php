<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Order;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Order\UpdateShippingStatus
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
        $orders = $this->walmartFactory->getObject('Order')
            ->getCollection()
            ->addFieldToFilter('id', ['in' => $ids])
            ->getItems();

        $hasFailed = false;
        $hasSucceeded = false;

        foreach ($orders as $order) {
            /** @var \Ess\M2ePro\Model\Order $order */

            $order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);

            $shipmentsCollection = $order->getMagentoOrder()->getShipmentsCollection()
                ->setOrderFilter($order->getMagentoOrderId());

            if ($shipmentsCollection->getSize() === 0) {
                $order->getChildObject()->updateShippingStatus([]) ? $hasSucceeded = true
                    : $hasFailed = true;
                continue;
            }

            foreach ($shipmentsCollection->getItems() as $shipment) {
                /** @var \Magento\Sales\Model\Order\Shipment $shipment */
                if (!$shipment->getId()) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Walmart\Order\Shipment\Handler $handler */
                $handler = $this->modelFactory->getObject('Order_Shipment_Handler')->factory(
                    $order->getComponentMode()
                );

                $result = $handler->handle($order, $shipment);

                $result == \Ess\M2ePro\Model\Order\Shipment\Handler::HANDLE_RESULT_SUCCEEDED ?
                    $hasSucceeded = true :
                    $hasFailed = true;
            }
        }
        if (!$hasFailed && $hasSucceeded) {
            $this->messageManager->addSuccess(
                $this->__('Updating Walmart Order(s) Status to Shipped in Progress...')
            );
        } elseif ($hasFailed && !$hasSucceeded) {
            $this->messageManager->addWarning(
                $this->__('Walmart Order(s) can not be updated for Shipped Status.')
            );
        } elseif ($hasFailed && $hasSucceeded) {
            $this->messageManager->addError(
                $this->__('Some of Walmart Order(s) can not be updated for Shipped Status.')
            );
        }

        $this->_redirect($this->_redirect->getRefererUrl());
    }
}
