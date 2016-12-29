<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Log\Listing;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;

abstract class AbstractView extends AbstractContainer
{
    /** @var  \Ess\M2ePro\Block\Adminhtml\Log\Listing\View\Switcher */
    protected $viewModeSwitcherBlock;

    /** @var  \Ess\M2ePro\Block\Adminhtml\Log\Listing\TypeSwitcher  */
    protected $listingTypeSwitcherBlock;

    /** @var  \Ess\M2ePro\Block\Adminhtml\Account\Switcher  */
    protected $accountSwitcherBlock;

    /** @var  \Ess\M2ePro\Block\Adminhtml\Marketplace\Switcher  */
    protected $marketplaceSwitcherBlock;

    //#######################################

    abstract protected function getComponentMode();

    abstract protected function getFiltersHtml();

    //#######################################

    protected function _prepareLayout()
    {
        $this->viewModeSwitcherBlock    = $this->createViewModeSwitcherBlock();
        $this->listingTypeSwitcherBlock = $this->createListingTypeSwitcherBlock();
        $this->accountSwitcherBlock     = $this->createAccountSwitcherBlock();
        $this->marketplaceSwitcherBlock = $this->createMarketplaceSwitcherBlock();

        $gridClass = $this->nameBuilder->buildClassName([
            $this->getComponentMode(),
            'Log\Listing',
            $this->listingTypeSwitcherBlock->getSelectedParam(),
            'View',
            $this->viewModeSwitcherBlock->getSelectedParam(),
            'Grid'
        ]);

        $this->addChild('grid', $this->getBlockClass($gridClass));

        $this->removeButton('add');

        $this->js->add(<<<JS
require(['M2ePro/Log/View'], function () {

    window.LogViewObj = new LogView();

    {$this->getChildBlock('grid')->getJsObjectName()}.initCallback = LogViewObj.processColorMapping;
    LogViewObj.processColorMapping();
});
JS
        );

        return parent::_prepareLayout();
    }

    protected function createViewModeSwitcherBlock()
    {
        return $this->createBlock('Log\Listing\View\Switcher')->setData([
            'component_mode' => $this->getComponentMode()
        ]);
    }

    protected function createListingTypeSwitcherBlock()
    {
        return $this->createBlock('Log\Listing\TypeSwitcher')->setData([
            'component_mode' => $this->getComponentMode()
        ]);
    }

    protected function createAccountSwitcherBlock()
    {
        return $this->createBlock('Account\Switcher')->setData([
            'component_mode' => $this->getComponentMode(),
        ]);
    }

    protected function createMarketplaceSwitcherBlock()
    {
        return $this->createBlock('Marketplace\Switcher')->setData([
            'component_mode' => $this->getComponentMode(),
        ]);
    }

    protected function getStaticFilterHtml($label, $value)
    {
        return <<<HTML
<p class="static-switcher">
    <span>{$label}:</span>
    <span>{$value}</span>
</p>
HTML;
    }

    protected function _toHtml()
    {
        $pageActionsHtml = <<<HTML
<div class="page-main-actions">
    <div class="filter_block">
        {$this->viewModeSwitcherBlock->toHtml()}
        {$this->getFiltersHtml()}
    </div>
</div>
HTML;

        return $pageActionsHtml . parent::_toHtml();
    }

    //#######################################
}