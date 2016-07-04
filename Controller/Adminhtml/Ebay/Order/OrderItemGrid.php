<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

class OrderItemGrid extends Order
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        /** @var $order \Ess\M2ePro\Model\Order */
        $order = $this->ebayFactory->getObjectLoaded('Order', (int)$id);

        $this->getHelper('Data\GlobalData')->setValue('order', $order);

        $this->setAjaxContent($this->createBlock('Ebay\Order\View\Item')->toHtml());

        return $this->getResult();
    }
}