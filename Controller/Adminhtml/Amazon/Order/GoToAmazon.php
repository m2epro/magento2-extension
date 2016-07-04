<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

class GoToAmazon extends Order
{
    public function execute()
    {
        $magentoOrderId = $this->getRequest()->getParam('magento_order_id');

        /** @var $order \Ess\M2ePro\Model\Order */
        $order = $this->amazonFactory->getObject('Order')->load($magentoOrderId, 'magento_order_id');

        if (is_null($order->getId())) {
            $this->messageManager->addError($this->__('Order does not exist.'));
            return $this->_redirect('*/amazon_order/index');
        }

        $url = $this->getHelper('Component\Amazon')->getOrderUrl(
            $order->getChildObject()->getAmazonOrderId(), $order->getMarketplaceId()
        );

        return $this->_redirect($url);
    }
}