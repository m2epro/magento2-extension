<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Order\MerchantFulfillment;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Order\MerchantFulfillment\GetLabel
 */
class GetLabel extends Order
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
        $labelContent = $order->getChildObject()->getData('merchant_fulfillment_label');

        if (empty($orderFulfillmentData['label']) || $labelContent === null) {
            return $this->getResponse()->setBody('The shipment has no label');
        }

        $this->getResponse()->setHeader('Content-type', $orderFulfillmentData['label']['file']['type']);

        $this->setRawContent($labelContent);

        return $this->getResult();
    }
}
