<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Order\MerchantFulfillment;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Order;
use Ess\M2ePro\Helper\Component\Amazon\MerchantFulfillment;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Order\MerchantFulfillment\СreateShippingOffer
 */
class CreateShippingOffer extends Order
{
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');

        if ($orderId === null) {
            return $this->getResponse()->setBody('You should specify order ID');
        }

        $post = $this->getRequest()->getPost();

        if (empty($post)) {
            return $this->getResponse()->setBody('You should specify POST data');
        }

        if (!$post['shipping_service_id']) {
            return $this->getResponse()->setBody('You should choose shipping service');
        }

        $requestData = $this->getHelper('Data_Session')->getValue('fulfillment_request_data');

        if ($requestData === null) {
            return $this->getResponse()->setBody('You should get eligible shipping services on previous step');
        }

        $requestData['shipping_service_id'] = $post['shipping_service_id'];

        /** @var \Ess\M2ePro\Model\Order $order */
        $order = $this->amazonFactory->getObjectLoaded(
            'Order',
            $orderId
        );

        $popup = $this->createBlock('Amazon_Order_MerchantFulfillment_Information');
        $showTryAgainBtn = false;

        try {
            $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'shipment',
                'add',
                'entity',
                $requestData,
                null,
                $order->getAccount()
            );

            $dispatcherObject->process($connectorObj);
            $response = $connectorObj->getResponseData();

            $labelContent = $response['label']['file']['contents'];
            $labelContent = base64_decode($labelContent);
            $labelContent = gzdecode($labelContent);

            unset($response['label']['file']['contents']);

            $order->addData(
                [
                    'merchant_fulfillment_data'  => $this->getHelper('Data')->jsonEncode($response),
                    'merchant_fulfillment_label' => $labelContent,
                ]
            )->save();

            $popup->setData('fulfillment_details', $response);
            $showTryAgainBtn = $response['status'] != MerchantFulfillment::STATUS_PURCHASED;

            $orderItems = $order->getItemsCollection()->getItems();
            $popup->setData('order_items', $orderItems);
        } catch (\Exception $exception) {
            $popup->setData('error_message', $exception->getMessage());
        }

        $this->setAjaxContent($popup->toHtml());
        $this->setJsonContent([
            'show_try_again_btn' => $showTryAgainBtn,
            'html' => $popup->toHtml()
        ]);

        return $this->getResult();
    }
}
