<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing;

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
        $filterBlockHtml = $this->getFilterBlockHtml();

        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Tabs $tabsBlock */
        $tabsBlock = $this->getLayout()->createBlock(Tabs::class);
        $tabsBlock->activateAllItemsTab();
        $tabsBlockHtml = $tabsBlock->toHtml();

        return $filterBlockHtml . $tabsBlockHtml . parent::_toHtml();
    }

    private function getFilterBlockHtml(): string
    {
        $marketplaceSwitcherBlock = $this->createSwitcher(\Ess\M2ePro\Block\Adminhtml\Marketplace\Switcher::class);
        $accountSwitcherBlock = $this->createSwitcher(\Ess\M2ePro\Block\Adminhtml\Account\Switcher::class);
        $tagSwitcherBlock = $this->tagSwitcherFactory->create(
            $this->getLayout(),
            __('Amazon Error'),
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
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

    private function createSwitcher(string $switcherClass): \Ess\M2ePro\Block\Adminhtml\Switcher
    {
        return $this->getLayout()
                    ->createBlock($switcherClass)
                    ->setData([
                        'component_mode' => \Ess\M2ePro\Helper\View\Amazon::NICK,
                        'controller_name' => $this->getRequest()->getControllerName(),
                    ]);
    }
}
