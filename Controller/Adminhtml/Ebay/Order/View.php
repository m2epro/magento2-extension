<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

class View extends Order
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $order = $this->ebayFactory->getObjectLoaded('Order', (int)$id);

        $this->getHelper('Data\GlobalData')->setValue('order', $order);

        $this->addContent($this->createBlock('Ebay\Order\View'));

        $this->init();
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('View Order Details'));

        return $this->getResult();
    }
}