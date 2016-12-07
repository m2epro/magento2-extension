<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay;

abstract class Order extends Main
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay_sales_orders');
    }

    //########################################

    protected function init()
    {
        $this->addCss('order.css');
        $this->addCss('switcher.css');
        $this->addCss('ebay/order/grid.css');

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Sales'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Orders'));
    }

    protected function processConnector($action, array $params = array())
    {
        $ids = $this->getRequestIds();

        if (count($ids) == 0) {
            $this->messageManager->addError($this->__('Please select Order(s).'));
            return false;
        }

        return $this->modelFactory->getObject('Ebay\Connector\Order\Dispatcher')->process(
            $action, $ids, $params
        );
    }

    protected function sendInStorePickupNotifications($type)
    {
        $ids = $this->getRequestIds();

        $orderCollection = $this->ebayFactory->getObject('Order')->getCollection();
        $orderCollection->addFieldToFilter('id', $ids);

        /** @var \Ess\M2ePro\Model\Order[] $orders */
        $orders = $orderCollection->getItems();

        $successMessage = '';
        switch ($type) {
            case 'ready_for_pickup':
                $successMessage = $this->__('Order was successfully marked as Ready For Pickup');
                break;

            case 'picked_up':
                $successMessage = $this->__('Order was successfully marked as Picked Up');
                break;

            case 'cancelled':
                $successMessage = $this->__('Order was successfully marked as Cancelled');
                break;
        }

        foreach ($orders as $order) {
            /** @var \Ess\M2ePro\Model\Ebay\Order $ebayOrder */
            $ebayOrder = $order->getChildObject();

            $dispatcher = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');
            $connector = $dispatcher->getVirtualConnector(
                'store', 'update', 'order',
                array('order_id' => $ebayOrder->getEbayOrderId(), 'type' => $type),
                NULL, NULL, $order->getAccount()
            );

            try {
                $dispatcher->process($connector);
            } catch (\Exception $exception) {
                return false;
            }

            $order->addSuccessLog($successMessage);
        }

        return true;
    }

    //########################################
}