<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Other;

class View extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Other
{
    public function execute()
    {
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('3rd Amazon Party Listings'));

        $this->addContent($this->createBlock('Amazon\Listing\Other\View'));

        return $this->getResult();
    }
}