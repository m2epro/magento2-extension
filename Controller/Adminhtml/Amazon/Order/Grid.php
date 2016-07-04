<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

class Grid extends Order
{
    public function execute()
    {
        $this->setAjaxContent($this->createBlock('Amazon\Order\Grid'));

        return $this->getResult();
    }
}