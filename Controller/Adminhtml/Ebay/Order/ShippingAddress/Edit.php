<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Order\ShippingAddress;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

class Edit extends Order
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        /** @var $order \Ess\M2ePro\Model\Order */
        $order = $this->ebayFactory->getObjectLoaded('Order', (int)$id);

        $this->getHelper('Data\GlobalData')->setValue('order', $order);

        $form = $this->createBlock('Ebay\Order\Edit\ShippingAddress\Form');

        $this->setAjaxContent($form->toHtml());
        return $this->getResult();
    }
}