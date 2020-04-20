<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Order;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Order\PageActions
 */
class PageActions extends AbstractBlock
{
    protected function _toHtml()
    {
        // ---------------------------------------
        $marketplaceSwitcherBlock = $this->createBlock('Amazon_Marketplace_Switcher')->setData([
            'component_mode' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'controller_name' => 'amazon_order'
        ]);

        $accountSwitcherBlock = $this->createBlock('Amazon_Account_Switcher')->setData([
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
