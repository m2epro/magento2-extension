<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Search
 */
class Search extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartListingSearch');
        // ---------------------------------------

        $listingType = $this->getRequest()->getParam('listing_type', false);

        if ($listingType == \Ess\M2ePro\Block\Adminhtml\Listing\Search\TypeSwitcher::LISTING_TYPE_LISTING_OTHER) {
            $this->_controller = 'adminhtml_walmart_listing_search_other';
        } else {
            $this->_controller = 'adminhtml_walmart_listing_search_product';
        }
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('add');
        // ---------------------------------------
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->css->addFile('listing/search/grid.css');
        $this->css->addFile('switcher.css');

        $content = $this->__(
            <<<HTML
        On this page, you can review the Items from both M2E Pro and 3rd Party Listings.<br/>
        Filter the records by the Listing Type, Account or Marketplace. Click the Arrow Icon next to the Item to go
        to the related Listing.
HTML
        );

        $this->appendHelpBlock([
            'content' => $content
        ]);

        return parent::_prepareLayout();
    }

    //########################################

    protected function _toHtml()
    {
        $marketplaceSwitcherBlock = $this->createBlock('Walmart_Marketplace_Switcher')->setData([
            'component_mode' => \Ess\M2ePro\Helper\View\Walmart::NICK,
            'controller_name' => $this->getRequest()->getControllerName()
        ]);

        $accountSwitcherBlock = $this->createBlock('Walmart_Account_Switcher')->setData([
            'component_mode' => \Ess\M2ePro\Helper\View\Walmart::NICK,
            'controller_name' => $this->getRequest()->getControllerName()
        ]);

        $listingTypeSwitcherBlock = $this->createBlock('Listing_Search_TypeSwitcher')->setData([
            'controller_name' => $this->getRequest()->getControllerName()
        ]);

        $filterBlockHtml = <<<HTML
<div class="page-main-actions">
    <div class="filter_block">
        {$listingTypeSwitcherBlock->toHtml()}
        {$accountSwitcherBlock->toHtml()}
        {$marketplaceSwitcherBlock->toHtml()}
    </div>
</div>
HTML;

        return $filterBlockHtml . parent::_toHtml();
    }

    //########################################
}
