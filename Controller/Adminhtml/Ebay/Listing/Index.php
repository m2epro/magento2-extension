<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $this->setAjaxContent(
                $this->getLayout()->createBlock('Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Grid')
            );
            return $this->getResult();
        }

        $this->addContent($this->createBlock('Ebay\Listing'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('M2E Pro Listings'));
        $this->setPageHelpLink('x/7gEtAQ');

        return $this->getResult();
    }
}