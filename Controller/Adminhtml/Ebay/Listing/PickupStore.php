<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

abstract class PickupStore extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    protected function initListing()
    {
        $id = $this->getRequest()->getParam('id');

        try {
            $listing = $this->ebayFactory->getCachedObjectLoaded('Listing',$id);
        } catch (\LogicException $e) {
            $this->getMessageManager()->addErrorMessage($this->__('Listing does not exist.'));
            return $this->_redirect('*/ebay_listing/index');
        }

        $this->getHelper('Data\GlobalData')->setValue('temp_data', $listing);
        return $listing;
    }

    //########################################
}