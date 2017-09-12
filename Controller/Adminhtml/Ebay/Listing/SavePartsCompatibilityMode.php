<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

class SavePartsCompatibilityMode extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    public function execute()
    {
        $listingId = $this->getRequest()->getParam('listing_id');
        $mode = $this->getRequest()->getParam('mode');

        if (is_null($listingId)) {

            $this->setAjaxContent('0', false);
            return $this->getResult();
        }

        $model = $this->ebayFactory->getCachedObjectLoaded('Listing', $listingId);
        $model->getChildObject()->setData('parts_compatibility_mode', $mode)->save();

        $this->setAjaxContent('1', false);
        return $this->getResult();
    }
}