<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing;

use Ess\M2ePro\Block\Adminhtml\Marketplace\Switcher;

class AllItems extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    protected function _prepareLayout()
    {
        $this->css->addFile('listing/search/grid.css');
        $this->css->addFile('switcher.css');

        return parent::_prepareLayout();
    }

    /**
     * @inheritDoc
     */
    protected function _toHtml()
    {
        $filterBlockHtml = $this->getFilterBlockHtml();

        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Tabs $tabsBlock */
        $tabsBlock = $this->getLayout()->createBlock(Tabs::class);
        $tabsBlock->activateAllItemsTab();
        $tabsBlockHtml = $tabsBlock->toHtml();

        return $filterBlockHtml . $tabsBlockHtml . parent::_toHtml();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getFilterBlockHtml(): string
    {
        $marketplaceSwitcherBlock = $this->createSwitcher(Switcher::class);
        $accountSwitcherBlock = $this->createSwitcher(\Ess\M2ePro\Block\Adminhtml\Account\Switcher::class);

        return <<<HTML
<div class="page-main-actions">
    <div class="filter_block">
        {$accountSwitcherBlock->toHtml()}
        {$marketplaceSwitcherBlock->toHtml()}
    </div>
</div>
HTML;
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
                        'component_mode' => \Ess\M2ePro\Helper\View\Amazon::NICK,
                        'controller_name' => $this->getRequest()->getControllerName(),
                    ]);
    }
}
