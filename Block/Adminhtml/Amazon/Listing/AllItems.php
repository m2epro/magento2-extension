<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing;

class AllItems extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    public function _construct()
    {
        parent::_construct();

        $this->_controller = 'adminhtml_amazon_listing_allItems';

        $this->setId('amazonListingAllItems');

        $this->buttonList->remove('back');
        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');
        $this->buttonList->remove('add');
        $this->buttonList->remove('save');
        $this->buttonList->remove('edit');
    }

    protected function _prepareLayout()
    {
        $this->css->addFile('listing/search/grid.css');
        $this->css->addFile('switcher.css');

        return parent::_prepareLayout();
    }

    protected function _toHtml()
    {

        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Tabs $tabsBlock */
        $tabsBlock = $this->getLayout()->createBlock(Tabs::class);
        $tabsBlock->activateAllItemsTab();
        $tabsBlockHtml = $tabsBlock->toHtml();

        return $tabsBlockHtml . $this->getHtmlForActions(parent::_toHtml());
    }

    private function getHtmlForActions(string $content): string
    {
        return '<div id="all_items_progress_bar"></div>'
            . '<div id="listing_container_errors_summary" class="errors_summary" style="display: none;"></div>'
            . '<div id="all_items_content_container">' . $content . '</div>';
    }
}
