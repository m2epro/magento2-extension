<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Order\Log;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Order\Log
{
    //########################################

    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id', false);

        if ($orderId) {
            $order = $this->amazonFactory->getObjectLoaded('Order', $orderId, 'id', false);

            if (is_null($order)) {
                $order = $this->amazonFactory->getObject('Order');
            }

            if (!$order->getId()) {
                $this->getMessageManager()->addError($this->__('Listing does not exist.'));
                return $this->_redirect('*/*/index');
            }

            $this->getResult()->getConfig()->getTitle()->prepend(
                $this->__('Logs For Order #%s%', $order->getChildObject()->getData('amazon_order_id'))
            );
        } else {
            $this->getResult()->getConfig()->getTitle()->prepend($this->__('Orders Logs & Events'));
        }

        $this->getHelper('Data\GlobalData')->setValue('component_nick', $this->getCustomViewNick());
        $this->addContent($this->createBlock('Order\Log'));
        return $this->getResult();
    }

    //########################################
}