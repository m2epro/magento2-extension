<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Order\View
 */
class View extends Order
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $order = $this->amazonFactory->getObjectLoaded('Order', (int)$id);

        $this->getHelper('Data\GlobalData')->setValue('order', $order);

        $this->init();

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('View Order Details'));

        $this->addContent($this->createBlock('Amazon_Order_View'));

        return $this->getResult();
    }
}
