<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing;

class AllItems extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    /**
     * @ingeritdoc
     */
    public function _construct()
    {
        parent::_construct();

        $this->_controller = 'adminhtml_ebay_listing_allItems';

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingAllItems');
        // ---------------------------------------

        $this->buttonList->remove('back');
        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');
        $this->buttonList->remove('add');
        $this->buttonList->remove('save');
        $this->buttonList->remove('edit');
    }

    /**
     * @ingeritdoc
     */
    protected function _prepareLayout()
    {
        $this->css->addFile('listing/search/grid.css');
        $this->css->addFile('switcher.css');

        return parent::_prepareLayout();
    }

    protected function _toHtml()
    {
        return $this->getTabsBlockHtml()
            . $this->getHtmlForActions(parent::_toHtml());
    }

    private function getTabsBlockHtml(): string
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Tabs $tabsBlock */
        $tabsBlock = $this->getLayout()->createBlock(Tabs::class);
        $tabsBlock->activateAllItemsTab();

        return $tabsBlock->toHtml();
    }

    private function getHtmlForActions(string $content): string
    {
        return '<div id="all_items_progress_bar"></div>'
            . '<div id="listing_container_errors_summary" class="errors_summary" style="display: none;"></div>'
            . '<div id="all_items_content_container">' . $content . '</div>';
    }
}
