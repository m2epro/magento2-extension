<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction;

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

        $block = $this->createBlock('Amazon\Listing\AutoAction\Mode\Category\Form');

        $this->setAjaxContent($block);
        return $this->getResult();
    }

    //########################################
}