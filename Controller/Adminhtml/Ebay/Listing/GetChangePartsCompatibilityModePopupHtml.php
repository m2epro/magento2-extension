<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

class GetChangePartsCompatibilityModePopupHtml extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Grid\Motor\EditMode $block */
        $block = $this->createBlock('Ebay\Listing\Grid\Motor\EditMode');
        $block->setListingId($this->getRequest()->getParam('listing_id'));

        $this->setAjaxContent($block);
        return $this->getResult();
    }
}