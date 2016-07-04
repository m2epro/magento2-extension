<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Order;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class PageActions extends AbstractBlock
{
    protected function _toHtml()
    {
        // ---------------------------------------
        $marketplaceFilterBlock = $this->createBlock('Marketplace\Switcher')->setData(array(
            'component_mode' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'controller_name' => 'amazon_order'
        ));
        $marketplaceFilterBlock->setUseConfirm(false);

        $accountFilterBlock = $this->createBlock('Account\Switcher')->setData(array(
            'component_mode' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'controller_name' => 'amazon_order'
        ));
        $accountFilterBlock->setUseConfirm(false);

        $orderStateSwitcherBlock = $this->createBlock('Order\NotCreatedFilter')->setData(array(
                'component_mode' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
                'controller' => 'amazon_order'
            )
        );
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