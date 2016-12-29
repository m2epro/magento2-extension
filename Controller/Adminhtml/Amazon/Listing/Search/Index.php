<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Search;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class Index extends Main
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::amazon_listings_search');
    }

    //########################################

    public function execute()
    {
        if ($this->isAjax()) {
            $listingType = $this->getRequest()->getParam('listing_type', false);

            if ($listingType == \Ess\M2ePro\Block\Adminhtml\Listing\Search\TypeSwitcher::LISTING_TYPE_LISTING_OTHER) {
                $gridBlock = 'Amazon\Listing\Search\Other\Grid';
            } else {
                $gridBlock = 'Amazon\Listing\Search\Product\Grid';
            }

            $this->setAjaxContent(
                $this->createBlock($gridBlock)
            );
            return $this->getResult();
        }

        $this->addContent($this->createBlock('Amazon\Listing\Search'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Search Products'));
        $this->setPageHelpLink('x/-gEtAQ');

        return $this->getResult();
    }

    //########################################
}