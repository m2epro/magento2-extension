<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Edit
 */
class Edit extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::amazon_listings_m2epro');
    }

    //########################################

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $listing = $this->amazonFactory->getCachedObjectLoaded('Listing', $id, null, false);

        if ($listing === null) {
            $this->getMessageManager()->addError($this->__('Listing does not exist.'));
            return $this->_redirect('*/amazon_listing/index');
        }

        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Edit::class));

        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('Edit M2E Pro Listing "%listing_title%" Settings', $listing->getTitle())
        );

        $this->setPageHelpLink('x/g-8UB');

        return $this->getResult();
    }

    //########################################
}
