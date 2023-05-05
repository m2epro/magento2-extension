<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing;

use Ess\M2ePro\Block\Adminhtml\Walmart\Marketplace\Switcher;

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
        $marketplaceSwitcherBlock = $this->getLayout()
                                         ->createBlock(Switcher::class)
                                         ->setData([
                                             'component_mode' => \Ess\M2ePro\Helper\View\Walmart::NICK,
                                             'controller_name' => $this->getRequest()->getControllerName(),
                                         ]);

        $accountSwitcherBlock = $this->getLayout()
                                     ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Account\Switcher::class)
                                     ->setData([
                                         'component_mode' => \Ess\M2ePro\Helper\View\Walmart::NICK,
                                         'controller_name' => $this->getRequest()->getControllerName(),
                                     ]);

        $filterBlockHtml = <<<HTML
<div class="page-main-actions">
    <div class="filter_block">
        {$accountSwitcherBlock->toHtml()}
        {$marketplaceSwitcherBlock->toHtml()}
    </div>
</div>
HTML;

        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Tabs $tabsBlock */
        $tabsBlock = $this->getLayout()->createBlock(Tabs::class);
        $tabsBlock->activateAllItemsTab();
        $tabsBlockHtml = $tabsBlock->toHtml();

        return $filterBlockHtml . $tabsBlockHtml . parent::_toHtml();
    }
}
