<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction\GetAutoCategoryFormHtml
 */
class GetAutoCategoryFormHtml extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction
{
    //########################################

    public function execute()
    {
        // ---------------------------------------
        $listingId = $this->getRequest()->getParam('id');
        $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $listingId);
        $this->getHelper('Data\GlobalData')->setValue('ebay_listing', $listing);
        // ---------------------------------------

        $block = $this->createBlock('Ebay_Listing_AutoAction_Mode_Category_Form');

        $this->setAjaxContent($block);
        return $this->getResult();
    }

    //########################################
}
