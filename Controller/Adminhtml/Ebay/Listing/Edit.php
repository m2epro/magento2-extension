<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit\Tab\Policies;

class Edit extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay_listings_m2epro');
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $id, null, false);

        if ($listing === null) {
            $this->getMessageManager()->addError($this->__('Listing does not exist.'));

            return $this->_redirect('*/ebay_listing/index');
        }

        $this->addContent($this->getLayout()->createBlock(Policies::class));
        $this->getResultPage()->getConfig()->getTitle()->prepend(
            __('Edit Listing "%listing_title" Settings', ['listing_title' => $listing->getTitle()])
        );

        return $this->getResult();
    }
}
