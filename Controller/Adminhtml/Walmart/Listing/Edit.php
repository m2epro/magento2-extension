<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing;

class Edit extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing
{
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::walmart_listings_m2epro');
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $listing = $this->walmartFactory->getCachedObjectLoaded('Listing', $id, null, false);

        if ($listing === null) {
            $this->getMessageManager()->addError($this->__('Listing does not exist.'));
            return $this->_redirect('*/walmart_listing/index');
        }

        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Edit::class));

        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('Edit M2E Pro Listing "%listing_title%" Settings', $listing->getTitle())
        );

        $this->setPageHelpLink('x/a-1IB');

        return $this->getResult();
    }
}
