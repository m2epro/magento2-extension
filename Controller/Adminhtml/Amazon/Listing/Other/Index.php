<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Other;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Other
{
    public function execute()
    {
        $this->addContent($this->createBlock('Amazon\Listing\Other'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('3rd Party Listings'));
        $this->setComponentPageHelpLink('3rd+Party+Listing');

        return $this->getResult();
    }
}