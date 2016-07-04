<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction;

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

        $block = $this->createBlock('Ebay\Listing\AutoAction\Mode\Category\Form');

        $this->setAjaxContent($block);
        return $this->getResult();
    }

    //########################################
}