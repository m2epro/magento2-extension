<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

class RunStopAndRemoveProducts extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\ActionAbstract
{
    public function execute()
    {
        $this->setJsonContent($this->processConnector(
            \Ess\M2ePro\Model\Listing\Product::ACTION_STOP, array('remove' => true))
        );

        return $this->getResult();
    }
}