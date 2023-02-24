<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing;

class Tabs extends \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractHorizontalStaticTabs
{
    private const ALL_ITEMS_TAB_ID = 'all_items';
    private const ITEMS_BY_LISTING_TAB_ID = 'items_by_listing';
    private const UNMANAGED_ITEMS_TAB_ID = 'unmanaged_items';

    /**
     * @inheritDoc
     */
    protected function init(): void
    {
        $cssMb20 = 'margin-bottom: 20px;';
        $cssMb10 = 'margin-bottom: 10px;';

        // ---------------------------------------

        $this->addTab(
            self::ITEMS_BY_LISTING_TAB_ID,
            __('Items By Listing'),
            $this->getUrl('*/ebay_listing/index')
        );
        $this->registerCssForTab(self::ITEMS_BY_LISTING_TAB_ID, $cssMb20);

        // ---------------------------------------

        $this->addTab(
            self::UNMANAGED_ITEMS_TAB_ID,
            __('Unmanaged items'),
            $this->getUrl('*/ebay_listing_unmanaged/index')
        );
        $this->registerCssForTab(self::UNMANAGED_ITEMS_TAB_ID, $cssMb20);

        // ---------------------------------------

        $this->addTab(
            self::ALL_ITEMS_TAB_ID,
            __('All Items'),
            $this->getUrl('*/ebay_listing/allItems')
        );
        $this->registerCssForTab(self::ALL_ITEMS_TAB_ID, $cssMb10);

        // ---------------------------------------
    }

    /**
     * @return void
     */
    public function activateItemsByListingTab(): void
    {
        $this->setActiveTabId(self::ITEMS_BY_LISTING_TAB_ID);
    }

    /**
     * @return void
     */
    public function activateUnmanagedItemsTab(): void
    {
        $this->setActiveTabId(self::UNMANAGED_ITEMS_TAB_ID);
    }

    /**
     * @return void
     */
    public function activateAllItemsTab(): void
    {
        $this->setActiveTabId(self::ALL_ITEMS_TAB_ID);
    }
}
