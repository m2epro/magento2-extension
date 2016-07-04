<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Other;

class Grid extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Other
{
    public function execute()
    {
        $this->setAjaxContent($this->createBlock('Amazon\Listing\Other\View\Grid'));
        return $this->getResult();
    }
}