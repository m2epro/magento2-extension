<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Order\MerchantFulfillment;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Order\MerchantFulfillment\MarkAsShipped
 */
class MarkAsShipped extends Order
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

        $responseData = [
            'success' => true,
        ];

        if ($order->getChildObject()->isPrime()) {
            $popUp = $this->createBlock('Amazon_order_merchantFulfillment_message');
            $popUp->setData('message', 'markAsShipped');
            $responseData['html'] = $popUp->toHtml();
            $responseData['success'] = false;
        }

        $this->setJsonContent($responseData);

        return $this->getResult();
    }
}
