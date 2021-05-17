<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Order\MerchantFulfillment;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Order\MerchantFulfillment\ResetData
 */
class ResetData extends Order
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

        if ($orderFulfillmentData['status']
            == \Ess\M2ePro\Helper\Component\Amazon\MerchantFulfillment::STATUS_PURCHASED) {
            return $this->getResponse()->setBody('Shipment status should not be Purchased');
        }

        $order->addData(
            [
                'merchant_fulfillment_data'  => null,
                'merchant_fulfillment_label' => null
            ]
        )->save();

        $responseData = [
            'success' => true
        ];

        $this->setJsonContent($responseData);

        return $this->getResult();
    }
}
