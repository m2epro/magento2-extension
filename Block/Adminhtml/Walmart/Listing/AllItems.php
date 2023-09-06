<?php

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing;

class AllItems extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    public function _construct()
    {
        parent::_construct();

        $this->setId('walmartListingAllItems');

        $this->_controller = 'adminhtml_walmart_listing_allItems';

        $this->removeButton('add');
    }

    protected function _prepareLayout()
    {
        $this->css->addFile('listing/search/grid.css');
        $this->css->addFile('switcher.css');

        return parent::_prepareLayout();
    }

    protected function _toHtml()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Tabs $tabsBlock */
        $tabsBlock = $this->getLayout()->createBlock(Tabs::class);
        $tabsBlock->activateAllItemsTab();
        $tabsBlockHtml = $tabsBlock->toHtml();

        return $tabsBlockHtml . parent::_toHtml();
    }
}
