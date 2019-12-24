<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Order\View
 */
class View extends Order
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $order = $this->ebayFactory->getObjectLoaded('Order', (int)$id);

        $this->getHelper('Data\GlobalData')->setValue('order', $order);

        $this->addContent($this->createBlock('Ebay_Order_View'));

        $this->init();
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('View Order Details'));

        return $this->getResult();
    }
}
