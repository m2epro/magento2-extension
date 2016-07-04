<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Order\ShippingAddress;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

class Edit extends Order
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        /** @var $order \Ess\M2ePro\Model\Order */
        $order = $this->amazonFactory->getObjectLoaded('Order', (int)$id);

        $this->getHelper('Data\GlobalData')->setValue('order', $order);

        $form = $this->createBlock('Amazon\Order\Edit\ShippingAddress\Form');

        $this->setAjaxContent($form->toHtml());
        return $this->getResult();
    }
}