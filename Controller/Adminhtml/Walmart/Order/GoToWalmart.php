<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Order;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Order;

class GoToWalmart extends Order
{
    /** @var \Ess\M2ePro\Helper\Component\Walmart */
    private $walmartHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Walmart $walmartHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->walmartHelper = $walmartHelper;
    }

    public function execute()
    {
        $magentoOrderId = $this->getRequest()->getParam('magento_order_id');

        /** @var \Ess\M2ePro\Model\Order $order */
        $order = $this->walmartFactory->getObjectLoaded('Order', $magentoOrderId, 'magento_order_id');

        if ($order->getId() === null) {
            $this->messageManager->addError($this->__('Order does not exist.'));
            return $this->_redirect('*/walmart_order/index');
        }

        $url = $this->walmartHelper->getOrderUrl(
            $order->getChildObject()->getWalmartOrderId(),
            $order->getMarketplaceId()
        );

        return $this->_redirect($url);
    }
}
