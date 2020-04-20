<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Order\UpdateShippingStatus
 */
class UpdateShippingStatus extends Order
{
    protected $orderShipmentCollectionFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $orderShipmentCollectionFactory,
        Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->orderShipmentCollectionFactory = $orderShipmentCollectionFactory;
    }

    //########################################

    public function execute()
    {
        $ids = $this->getRequestIds();

        if (count($ids) == 0) {
            $this->messageManager->addError($this->__('Please select Order(s).'));
            return $this->_redirect('*/*/index');
        }

        /** @var \Ess\M2ePro\Model\Order[] $orders */
        $ordersCollection = $this->amazonFactory->getObject('Order')->getCollection();
        $ordersCollection->addFieldToFilter('id', ['in' => $ids]);

        $hasFailed = false;
        $hasSucceeded = false;
        /** @var \Ess\M2ePro\Model\Amazon\Order\Shipment\Handler $handler */
        $handler = $this->modelFactory->getObject('Amazon_Order_Shipment_Handler');

        foreach ($ordersCollection->getItems() as $order) {
            /** @var \Ess\M2ePro\Model\Order $order */

            if ($order->getChildObject()->isPrime()) {
                $hasFailed = true;
                continue;
            }

            $order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);

            /** @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection $shipmentsCollection */
            $shipmentsCollection = $this->orderShipmentCollectionFactory->create();
            $shipmentsCollection->setOrderFilter($order->getMagentoOrderId());

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

                $result  = $handler->handle($order, $shipment);

                $result == \Ess\M2ePro\Model\Order\Shipment\Handler::HANDLE_RESULT_SUCCEEDED ? $hasSucceeded = true
                                                                                             : $hasFailed = true;
            }
        }

        if (!$hasFailed && $hasSucceeded) {
            $this->messageManager->addSuccess(
                $this->__('Updating Amazon Order(s) Status to Shipped in Progress...')
            );
        } elseif ($hasFailed && !$hasSucceeded) {
            $this->messageManager->addError(
                $this->__('Amazon Order(s) can not be updated for Shipped Status.')
            );
        } elseif ($hasFailed && $hasSucceeded) {
            $this->messageManager->addError(
                $this->__('Some of Amazon Order(s) can not be updated for Shipped Status.')
            );
        }

        return $this->_redirect($this->_redirect->getRefererUrl());
    }

    //########################################
}
