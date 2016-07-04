<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Order;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class PageActions extends AbstractBlock
{
    protected function _toHtml()
    {
        // ---------------------------------------
        $marketplaceFilterBlock = $this->createBlock('Marketplace\Switcher')->setData(array(
            'component_mode' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'controller_name' => 'ebay_order'
        ));
        $marketplaceFilterBlock->setUseConfirm(false);
        // ---------------------------------------

        // ---------------------------------------
        $accountFilterBlock = $this->createBlock('Account\Switcher')->setData(array(
            'component_mode' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'controller_name' => 'ebay_order'
        ));
        $accountFilterBlock->setUseConfirm(false);
        // ---------------------------------------

        // ---------------------------------------
        $orderStateSwitcherBlock = $this->createBlock('Order\NotCreatedFilter')->setData(array(
            'component_mode' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'controller' => 'ebay_order'
        ));
        // ---------------------------------------

        return
          '<div class="filter_block">'
        . $marketplaceFilterBlock->toHtml()
        . $accountFilterBlock->toHtml()
        . $orderStateSwitcherBlock->toHtml()
        . '</div>'
        . parent::_toHtml();
    }
}