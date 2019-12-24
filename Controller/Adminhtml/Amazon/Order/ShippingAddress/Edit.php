<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Order\ShippingAddress;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Order\ShippingAddress\Edit
 */
class Edit extends Order
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        /** @var $order \Ess\M2ePro\Model\Order */
        $order = $this->amazonFactory->getObjectLoaded('Order', (int)$id);

        $this->getHelper('Data\GlobalData')->setValue('order', $order);

        $form = $this->createBlock('Amazon_Order_Edit_ShippingAddress_Form');

        $this->setAjaxContent($form->toHtml());
        return $this->getResult();
    }
}
