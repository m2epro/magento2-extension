<?php

namespace Ess\M2ePro\Controller\Adminhtml\Order;

use Ess\M2ePro\Controller\Adminhtml\Order;

class ViewLogGrid extends Order
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $order = $this->activeRecordFactory->getObjectLoaded('Order', $id);

        $this->getHelper('Data\GlobalData')->setValue('order', $order);

        $grid = $this->createBlock('Order\View\Log\Grid');

        $this->setAjaxContent($grid->toHtml());
        return $this->getResult();
    }
}