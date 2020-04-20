<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Order;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Order\GoToWalmart
 */
class GoToWalmart extends Order
{
    public function execute()
    {
        $magentoOrderId = $this->getRequest()->getParam('magento_order_id');

        /** @var $order \Ess\M2ePro\Model\Order */
        $order = $this->walmartFactory->getObjectLoaded('Order', $magentoOrderId, 'magento_order_id');

        if ($order->getId() === null) {
            $this->messageManager->addError($this->__('Order does not exist.'));
            return $this->_redirect('*/walmart_order/index');
        }

        $url = $this->getHelper('Component\Walmart')->getOrderUrl(
            $order->getChildObject()->getWalmartOrderId(),
            $order->getMarketplaceId()
        );

        return $this->_redirect($url);
    }
}
