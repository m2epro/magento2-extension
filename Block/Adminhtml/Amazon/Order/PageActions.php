<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Order;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class PageActions extends AbstractBlock
{
    protected function _toHtml()
    {
        // ---------------------------------------
        $marketplaceSwitcherBlock = $this->createBlock('Amazon\Marketplace\Switcher')->setData([
            'component_mode' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'controller_name' => 'amazon_order'
        ]);

        $accountSwitcherBlock = $this->createBlock('Amazon\Account\Switcher')->setData([
            'component_mode' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'controller_name' => 'amazon_order'
        ]);

        $orderStateSwitcherBlock = $this->createBlock('Order\NotCreatedFilter')->setData([
            'component_mode' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'controller' => 'amazon_order'
        ]);
        // ---------------------------------------

        return
            '<div class="filter_block">'
            . $accountSwitcherBlock->toHtml()
            . $marketplaceSwitcherBlock->toHtml()
            . $orderStateSwitcherBlock->toHtml()
            . '</div>'
            . parent::_toHtml();
    }
}