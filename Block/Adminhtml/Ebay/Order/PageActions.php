<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Order;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Order\PageActions
 */
class PageActions extends AbstractBlock
{
    protected function _toHtml()
    {
        // ---------------------------------------
        $marketplaceSwitcherBlock = $this->createBlock('Marketplace\Switcher')->setData([
            'component_mode' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'controller_name' => 'ebay_order'
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $accountSwitcherBlock = $this->createBlock('Account\Switcher')->setData([
            'component_mode' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'controller_name' => 'ebay_order'
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $orderStateSwitcherBlock = $this->createBlock('Order\NotCreatedFilter')->setData([
            'component_mode' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'controller' => 'ebay_order'
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
