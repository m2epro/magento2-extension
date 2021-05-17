<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Order\MerchantFulfillment;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Order\MerchantFulfillment\CancelShippingOffer
 */
class CancelShippingOffer extends Order
{
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');

        if ($orderId === null) {
            return $this->getResponse()->setBody('You should specify order ID');
        }

        /** @var \Ess\M2ePro\Model\Order $order */
        $order = $this->amazonFactory->getObjectLoaded(
            'Order',
            $orderId
        );

        $orderFulfillmentData = $order->getChildObject()->getMerchantFulfillmentData();

        if (empty($orderFulfillmentData)) {
            return $this->getResponse()->setBody('You should create shipment first');
        }

        $statusRefundPurchased = \Ess\M2ePro\Helper\Component\Amazon\MerchantFulfillment::STATUS_PURCHASED;

        if ($orderFulfillmentData['status'] != $statusRefundPurchased) {
            return $this->getResponse()->setBody('Shipment status should be Purchased');
        }

        $requestData = [
            'shipment_id' => $orderFulfillmentData['shipment_id']
        ];

        $responseData = [
            'success' => false
        ];

        try {
            $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'shipment',
                'cancel',
                'entity',
                $requestData,
                null,
                $order->getAccount()
            );

            $dispatcherObject->process($connectorObj);
            $response = $connectorObj->getResponseData();

            if (empty($response['label']) && !empty($orderFulfillmentData['label'])) {
                $order->setData('merchant_fulfillment_label', null);
            }

            $order->setSettings('merchant_fulfillment_data', $response)->save();
            $responseData['success'] = true;
        } catch (\Exception $exception) {
            $responseData['error_message'] = $exception->getMessage();
        }

        $this->setJsonContent($responseData);

        return $this->getResult();
    }
}
