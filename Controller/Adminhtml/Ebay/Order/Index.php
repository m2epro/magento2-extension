<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

class Index extends Order
{
    public function execute()
    {
        $this->init();
        $this->addContent($this->createBlock('Ebay\Order'));
        $this->setPageHelpLink('x/ngEtAQ');

        return $this->getResultPage();
    }
}