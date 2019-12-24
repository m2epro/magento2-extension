<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\SavePartsCompatibilityMode
 */
class SavePartsCompatibilityMode extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    public function execute()
    {
        $listingId = $this->getRequest()->getParam('listing_id');
        $mode = $this->getRequest()->getParam('mode');

        if ($listingId === null) {
            $this->setAjaxContent('0', false);
            return $this->getResult();
        }

        $model = $this->ebayFactory->getCachedObjectLoaded('Listing', $listingId);
        $model->getChildObject()->setData('parts_compatibility_mode', $mode)->save();

        $this->setAjaxContent('1', false);
        return $this->getResult();
    }
}
