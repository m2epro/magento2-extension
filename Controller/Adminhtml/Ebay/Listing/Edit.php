<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

/**
 * Class Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Edit
 */
class Edit extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay_listings_m2epro');
    }

    //########################################

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $id, null, false);

        if ($listing === null) {
            $this->getMessageManager()->addError($this->__('Listing does not exist.'));
            return $this->_redirect('*/ebay_listing/index');
        }

        $this->addContent($this->createBlock('Ebay_Listing_Edit'));
        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('Edit M2E Pro Listing "%listing_title%" Settings', $listing->getTitle())
        );

        return $this->getResult();
    }

    //########################################
}
