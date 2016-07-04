<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

class OrderItemGrid extends Order
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $order = $this->amazonFactory->getObjectLoaded('Order', (int)$id);

        if (!$id || !$order->getId()) {
            return $this->_redirect($this->_redirect->getRefererUrl());
        }

        $this->getHelper('Data\GlobalData')->setValue('order', $order);

        $this->setAjaxContent($this->createBlock('Amazon\Order\View\Item'));

        return $this->getResult();
    }
}