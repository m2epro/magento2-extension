<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

class EditListing extends Template
{
    //########################################

    public function execute()
    {
        $this->addCss('ebay/listing/templates.css');
        
        $id = $this->getRequest()->getParam('id');
        $listing = $this->ebayFactory->getCachedObjectLoaded('Listing',$id);

        // ---------------------------------------
        $this->getHelper('Component\Ebay\Template\Switcher\DataLoader')->load($listing);
        // ---------------------------------------

        // ---------------------------------------
        $this->getHelper('Data\GlobalData')->setValue('ebay_listing', $listing);
        // ---------------------------------------

        $this->addContent($this->createBlock('Ebay\Listing\Edit'));
        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('M2E Pro Listing "%listing_title%" Settings', $listing->getTitle())
        );

        return $this->getResult();
    }

    //########################################
}