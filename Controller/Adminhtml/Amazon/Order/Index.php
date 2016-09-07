<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

class Index extends Order
{
    public function execute()
    {
        $this->init();
        $this->addContent($this->createBlock('Amazon\Order'));
        $this->setPageHelpLink('x/rgEtAQ');

        return $this->getResult();
    }
}