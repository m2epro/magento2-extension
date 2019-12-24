<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Search;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Search\Index
 */
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
                $gridBlock = 'Amazon_Listing_Search_Other_Grid';
            } else {
                $gridBlock = 'Amazon_Listing_Search_Product_Grid';
            }

            $this->setAjaxContent(
                $this->createBlock($gridBlock)
            );
            return $this->getResult();
        }

        $this->addContent($this->createBlock('Amazon_Listing_Search'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Search Products'));
        $this->setPageHelpLink('x/-gEtAQ');

        return $this->getResult();
    }

    //########################################
}
