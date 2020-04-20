<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Review
 */
class Review extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    public function execute()
    {
        $listingId = $this->getRequest()->getParam('id');
        $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $listingId);

        $this->getHelper('Data\GlobalData')->setValue('review_listing', $listing);

        $ids = $this->getHelper('Data\Session')->getValue('added_products_ids');

        if (empty($ids) && !$this->getRequest()->getParam('disable_list')) {
            return $this->_redirect('*/*/view', ['id' => $listingId]);
        }

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Congratulations'));
        $this->addContent($this->createBlock('Ebay_Listing_Product_Review', '', [
            'data' => [
                'products_count' => count($ids)
            ]
        ]));

        return $this->getResult();
    }
}
