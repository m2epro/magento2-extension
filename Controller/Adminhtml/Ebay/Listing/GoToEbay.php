<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

class GoToEbay extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay_listings_m2epro') ||
               $this->_authorization->isAllowed('Ess_M2ePro::ebay_listings_other');
    }

    //########################################

    public function execute()
    {
        $itemId = $this->getRequest()->getParam('item_id');
        $accountId = $this->getRequest()->getParam('account_id');
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');

        if (is_null($itemId) || is_null($accountId) || is_null($marketplaceId)) {
            $this->messageManager->addError($this->__('Requested eBay Item ID is not found.'));
            $this->_redirect('*/*/index');
            return;
        }

        $accountMode = $this->ebayFactory->getObjectLoaded('Account', $accountId)
            ->getChildObject()
            ->getMode();

        $url = $this->getHelper('Component\Ebay')->getItemUrl(
            $itemId, $accountMode, $marketplaceId
        );

        return $this->_redirect($url);
    }

    //########################################
}