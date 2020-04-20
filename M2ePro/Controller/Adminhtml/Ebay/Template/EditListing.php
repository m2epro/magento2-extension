<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Template\EditListing
 */
class EditListing extends Template
{
    //########################################

    public function execute()
    {
        $this->addCss('ebay/listing/templates.css');

        $id = $this->getRequest()->getParam('id');
        $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $id);

        // ---------------------------------------
        $this->getHelper('Component_Ebay_Template_Switcher_DataLoader')->load($listing);
        // ---------------------------------------

        // ---------------------------------------
        $this->getHelper('Data\GlobalData')->setValue('ebay_listing', $listing);
        // ---------------------------------------

        $this->addContent($this->createBlock('Ebay_Listing_Edit'));
        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('M2E Pro Listing "%listing_title%" Settings', $listing->getTitle())
        );

        return $this->getResult();
    }

    //########################################
}
