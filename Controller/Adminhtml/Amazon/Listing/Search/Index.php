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
                $gridBlock = \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Search\Other\Grid::class;
            } else {
                $gridBlock = \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Search\Product\Grid::class;
            }

            $this->setAjaxContent(
                $this->getLayout()->createBlock($gridBlock)
            );
            return $this->getResult();
        }

        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Search::class));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Search Products'));
        $this->setPageHelpLink('x/Jv8UB');

        return $this->getResult();
    }

    //########################################
}
