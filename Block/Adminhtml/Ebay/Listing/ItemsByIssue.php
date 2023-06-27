<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;

class ItemsByIssue extends AbstractContainer
{
    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingItemsByIssue');
        $this->_controller = 'adminhtml_ebay_listing_itemsByIssue';
        // ---------------------------------------

        $this->buttonList->remove('back');
        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');
        $this->buttonList->remove('add');
        $this->buttonList->remove('save');
        $this->buttonList->remove('edit');
    }

    protected function _prepareLayout()
    {
        $this->css->addFile('switcher.css');

        return parent::_prepareLayout();
    }

    /**
     * @ingeritdoc
     */
    protected function _toHtml(): string
    {
        $filterBlockHtml = $this->getFilterBlockHtml();

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Tabs $tabsBlock */
        $tabsBlock = $this->getLayout()->createBlock(Tabs::class);
        $tabsBlock->activateItemsByIssueTab();
        $tabsBlockHtml = $tabsBlock->toHtml();

        return $filterBlockHtml . $tabsBlockHtml . parent::_toHtml();
    }

    private function getFilterBlockHtml(): string
    {
        $marketplaceSwitcherBlock = $this->getLayout()
             ->createBlock(\Ess\M2ePro\Block\Adminhtml\Marketplace\Switcher::class)
             ->setData([
                 'component_mode' => \Ess\M2ePro\Helper\View\Ebay::NICK,
                 'controller_name' => $this->getRequest()->getControllerName(),
             ]);

        $accountSwitcherBlock = $this->getLayout()
             ->createBlock(\Ess\M2ePro\Block\Adminhtml\Account\Switcher::class)
             ->setData([
                 'component_mode' => \Ess\M2ePro\Helper\View\Ebay::NICK,
                 'controller_name' => $this->getRequest()->getControllerName(),
             ]);

        return <<<HTML
<div class="page-main-actions">
    <div class="filter_block">
        {$accountSwitcherBlock->toHtml()}
        {$marketplaceSwitcherBlock->toHtml()}
    </div>
</div>
HTML;
    }
}
