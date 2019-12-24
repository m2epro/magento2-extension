<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Log\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Log\Order\Index
 */
class Index extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Log\Order
{
    //########################################

    public function execute()
    {
        $orderId = $this->getRequest()->getParam('id', false);

        if ($orderId) {
            $order = $this->amazonFactory->getObjectLoaded('Order', $orderId, 'id', false);

            if ($order === null) {
                $order = $this->amazonFactory->getObject('Order');
            }

            if (!$order->getId()) {
                $this->getMessageManager()->addError($this->__('Listing does not exist.'));
                return $this->_redirect('*/*/index');
            }

            $this->getResult()->getConfig()->getTitle()->prepend(
                $this->__('Order #%s% Log', $order->getChildObject()->getData('amazon_order_id'))
            );
        } else {
            $this->getResult()->getConfig()->getTitle()->prepend($this->__('Orders Logs & Events'));
        }

        $this->addContent($this->createBlock('Amazon_Log_Order'));
        return $this->getResult();
    }

    //########################################
}
