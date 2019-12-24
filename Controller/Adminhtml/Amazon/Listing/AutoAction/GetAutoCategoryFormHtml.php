<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction\GetAutoCategoryFormHtml
 */
class GetAutoCategoryFormHtml extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction
{
    //########################################

    public function execute()
    {
        // ---------------------------------------
        $listingId = $this->getRequest()->getParam('id');
        $listing = $this->amazonFactory->getCachedObjectLoaded('Listing', $listingId);
        $this->getHelper('Data\GlobalData')->setValue('amazon_listing', $listing);
        // ---------------------------------------

        $block = $this->createBlock('Amazon_Listing_AutoAction_Mode_Category_Form');

        $this->setAjaxContent($block);
        return $this->getResult();
    }

    //########################################
}
