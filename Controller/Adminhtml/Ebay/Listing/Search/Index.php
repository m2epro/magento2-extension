<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Search;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay_listings_search');
    }

    public function execute()
    {
        if ($this->isAjax()) {
            $listingType = $this->getRequest()->getParam('listing_type', false);

            if ($listingType == \Ess\M2ePro\Block\Adminhtml\Listing\Search\TypeSwitcher::LISTING_TYPE_LISTING_OTHER) {
                $gridBlock = 'Ebay\Listing\Search\Other\Grid';
            } else {
                $gridBlock = 'Ebay\Listing\Search\Product\Grid';
            }

            $this->setAjaxContent(
                $this->createBlock($gridBlock)
            );
            return $this->getResult();
        }

        $this->addContent($this->createBlock('Ebay\Listing\Search'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Search Products'));
        $this->setPageHelpLink('x/6gEtAQ');

        return $this->getResult();
    }
}