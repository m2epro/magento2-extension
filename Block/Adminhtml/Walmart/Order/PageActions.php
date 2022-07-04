<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Order;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Order\PageActions
 */
class PageActions extends AbstractBlock
{
    protected function _toHtml()
    {
        // ---------------------------------------
        $marketplaceSwitcherBlock = $this->getLayout()
                                         ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Marketplace\Switcher::class)
                                         ->setData([
            'component_mode' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'controller_name' => 'walmart_order'
        ]);

        $accountSwitcherBlock = $this->getLayout()
                                     ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Account\Switcher::class)
                                     ->setData([
            'component_mode' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'controller_name' => 'walmart_order'
        ]);

        $orderStateSwitcherBlock = $this->getLayout()
                                        ->createBlock(\Ess\M2ePro\Block\Adminhtml\Order\NotCreatedFilter::class)
                                        ->setData([
            'component_mode' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'controller' => 'walmart_order'
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
