<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing;

class AllItems extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    /** @var \Ess\M2ePro\Block\Adminhtml\Tag\SwitcherFactory */
    private $tagSwitcherFactory;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Tag\SwitcherFactory $tagSwitcherFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->tagSwitcherFactory = $tagSwitcherFactory;
    }

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
        return $this->getFilterBlockHtml()
            . $this->getTabsBlockHtml()
            . $this->getHtmlForActions(parent::_toHtml());
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getFilterBlockHtml(): string
    {
        $marketplaceSwitcherBlock = $this->createSwitcher(\Ess\M2ePro\Block\Adminhtml\Marketplace\Switcher::class);
        $accountSwitcherBlock = $this->createSwitcher(\Ess\M2ePro\Block\Adminhtml\Account\Switcher::class);
        $tagSwitcherBlock = $this->tagSwitcherFactory->create(
            $this->getLayout(),
            __('eBay Error'),
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            $this->getRequest()->getControllerName()
        );

        return <<<HTML
<div class="page-main-actions">
    <div class="filter_block">
        {$accountSwitcherBlock->toHtml()}
        {$marketplaceSwitcherBlock->toHtml()}
        {$tagSwitcherBlock->toHtml()}
    </div>
</div>
HTML;
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

    /**
     * @param string $switcherClass
     *
     * @return \Ess\M2ePro\Block\Adminhtml\Component\Switcher
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function createSwitcher(string $switcherClass): \Ess\M2ePro\Block\Adminhtml\Switcher
    {
        return $this->getLayout()
                    ->createBlock($switcherClass)
                    ->setData([
                        'component_mode' => \Ess\M2ePro\Helper\View\Ebay::NICK,
                        'controller_name' => $this->getRequest()->getControllerName(),
                    ]);
    }
}
