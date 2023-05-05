<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Unmanaged;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class PageActions extends AbstractBlock
{
    public const BLOCK_PATH = 'Walmart_Listing_Unmanaged_PageActions';
    private const CONTROLLER_NAME = 'walmart_listing_unmanaged/index';

    /**
     * @inheritDoc
     */
    protected function _toHtml()
    {
        $accountSwitcherBlock = $this->createSwitcher(
            \Ess\M2ePro\Block\Adminhtml\Account\Switcher::class
        );

        $marketplaceSwitcherBlock = $this->createSwitcher(
            \Ess\M2ePro\Block\Adminhtml\Marketplace\Switcher::class
        );

        return
            '<div class="filter_block">'
            . $accountSwitcherBlock->toHtml()
            . $marketplaceSwitcherBlock->toHtml()
            . '</div>'
            . parent::_toHtml();
    }

    /**
     * @param string $blockClassName
     *
     * @return \Ess\M2ePro\Block\Adminhtml\Component\Switcher
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function createSwitcher(string $blockClassName): \Ess\M2ePro\Block\Adminhtml\Component\Switcher
    {
        return $this->getLayout()
                    ->createBlock($blockClassName)
                    ->setData([
                        'component_mode' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
                        'controller_name' => self::CONTROLLER_NAME,
                    ]);
    }
}
