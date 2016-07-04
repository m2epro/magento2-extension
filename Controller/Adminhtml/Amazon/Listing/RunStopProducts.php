<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing;

class RunStopProducts extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\ActionAbstract
{
    public function execute()
    {
        $this->setJsonContent($this->processConnector(\Ess\M2ePro\Model\Listing\Product::ACTION_STOP));

        return $this->getResult();
    }
}