<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

class Review extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    public function execute()
    {
        $listingId = $this->getRequest()->getParam('id');
        $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $listingId);

        $this->getHelper('Data\GlobalData')->setValue('review_listing', $listing);

        $ids = $this->getHelper('Data\Session')->getValue('added_products_ids');

        if (empty($ids) && !$this->getRequest()->getParam('disable_list')) {
            return $this->_redirect('*/*/view', array('id' => $listingId));
        }

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Congratulations'));
        $this->addContent($this->createBlock('Ebay\Listing\Product\Review', '', [
            'data' => [
                'products_count' => count($ids)
            ]
        ]));

        return $this->getResult();
    }
}