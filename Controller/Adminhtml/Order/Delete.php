<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Order;

use Ess\M2ePro\Controller\Adminhtml\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Order\Delete
 */
class Delete extends Order
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        if ($id === null) {
            $this->messageManager->addError($this->__('Order ID is not defined.'));
            return $this->_redirect('*/*/index');
        }

        /** @var \Ess\M2ePro\Model\Order $order */
        $order = $this->activeRecordFactory->getObjectLoaded('Order', $id);
        $order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);

        if ($order->getId() === null) {
            $this->messageManager->addError($this->__('Order with such ID does not exist.'));
            return $this->_redirect('*/*/index');
        }

        $order->delete();

        $this->messageManager->addSuccess($this->__('Order was successfully deleted.'));
        return $this->_redirect('*/*/index');
    }
}
