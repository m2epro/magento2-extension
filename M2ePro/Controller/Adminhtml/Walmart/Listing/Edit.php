<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Edit
 */
class Edit extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::walmart_listings_m2epro');
    }

    //########################################

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $listing = $this->walmartFactory->getCachedObjectLoaded('Listing', $id, null, false);

        if ($listing === null) {
            $this->getMessageManager()->addError($this->__('Listing does not exist.'));
            return $this->_redirect('*/walmart_listing/index');
        }

        $this->getHelper('Data\GlobalData')->setValue('edit_listing', $listing);

        $this->addContent($this->createBlock('Walmart_Listing_Edit'));

        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('Edit M2E Pro Listing "%listing_title%" Settings', $listing->getTitle())
        );

        $this->setPageHelpLink('x/QwBhAQ');

        return $this->getResult();
    }

    //########################################
}
