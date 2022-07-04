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
        $marketplaceSwitcherBlock = $this->getLayout()
                                         ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Marketplace\Switcher::class)
                                         ->setData([
            'component_mode' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'controller_name' => 'amazon_order'
        ]);

        $accountSwitcherBlock = $this->getLayout()
                                     ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Account\Switcher::class)
                                     ->setData([
            'component_mode' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'controller_name' => 'amazon_order'
        ]);

        $orderStateSwitcherBlock = $this->getLayout()
                                        ->createBlock(\Ess\M2ePro\Block\Adminhtml\Order\NotCreatedFilter::class)
                                        ->setData([
            'component_mode' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'controller' => 'amazon_order'
        ]);

        $invoiceCreditmemoFilterBlock = $this->getLayout()
                         ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Order\Grid\InvoiceCreditmemoFilter::class)
                         ->setData([
            'controller' => 'amazon_order'
        ]);
        // ---------------------------------------

        return
            '<div class="filter_block">'
            . $accountSwitcherBlock->toHtml()
            . $marketplaceSwitcherBlock->toHtml()
            . $orderStateSwitcherBlock->toHtml()
            . $invoiceCreditmemoFilterBlock->toHtml()
            . '</div>'
            . parent::_toHtml();
    }
}
