<?php

namespace Ess\M2ePro\Controller\Adminhtml\Order;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Order;

class ResubmitShippingInfo extends Order
{
    protected $orderShipmentCollection;

    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection $orderShipmentCollection,
        Context $context
    )
    {
        $this->orderShipmentCollection = $orderShipmentCollection;

        parent::__construct($context);
    }

    public function execute()
    {
        $ids = $this->getRequestIds();

        $isFail = false;

        foreach ($ids as $id) {
            /** @var \Ess\M2ePro\Model\Order $order */
            $order = $this->activeRecordFactory->getObjectLoaded('Order', $id);

            $shipmentsCollection = $this->orderShipmentCollection
                ->setOrderFilter($order->getMagentoOrderId());

            foreach ($shipmentsCollection->getItems() as $shipment) {
                /** @var \Magento\Sales\Model\Order\Shipment $shipment */
                if (!$shipment->getId()) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Order\Shipment\Handler $handler */
                $handler = $this->modelFactory->getObject('Order\Shipment\Handler')->factory(
                    $order->getComponentMode()
                );
                $result  = $handler->handle($order, $shipment);

                if ($result == \Ess\M2ePro\Model\Order\Shipment\Handler::HANDLE_RESULT_FAILED) {
                    $isFail = true;
                }
            }
        }

        if ($isFail) {
            $errorMessage = $this->__('Shipping Information was not resend.');
            if (count($ids) > 1) {
                $errorMessage = $this->__('Shipping Information was not resend for some Orders.');
            }

            $this->messageManager->addError($errorMessage);
        } else {
            $this->messageManager->addSuccess(
                $this->__('Shipping Information has been successfully resend.')
            );
        }

        return $this->_redirect($this->_redirect->getRefererUrl());
    }
}