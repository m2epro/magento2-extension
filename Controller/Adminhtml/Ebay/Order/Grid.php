<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

class Grid extends Order
{
    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Order\Grid $grid */
        $grid = $this->createBlock('Ebay\Order\Grid');

        $this->setAjaxContent($grid->toHtml());
        return $this->getResult();
    }
}