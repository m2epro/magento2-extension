<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing;

class Tabs extends \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractHorizontalStaticTabs
{
    private const ALL_ITEMS_TAB_ID = 'all_items';
    private const ITEMS_BY_LISTING_TAB_ID = 'items_by_listing';
    private const ITEMS_BY_ISSUE_TAB_ID = 'items_by_issue';
    private const UNMANAGED_ITEMS_TAB_ID = 'unmanaged_items';

    protected function init(): void
    {
        $cssMb20 = 'margin-bottom: 20px;';
        $cssMb10 = 'margin-bottom: 10px;';

        // ---------------------------------------

        $this->addTab(
            self::ITEMS_BY_LISTING_TAB_ID,
            __('Items By Listing'),
            $this->getUrl('*/amazon_listing/index')
        );
        $this->registerCssForTab(self::ITEMS_BY_LISTING_TAB_ID, $cssMb20);

        // --------------------------------------

        $this->addTab(
            self::ITEMS_BY_ISSUE_TAB_ID,
            __('Items By Issue'),
            $this->getUrl('*/amazon_listing/itemsByIssue')
        );
        $this->registerCssForTab(self::ITEMS_BY_ISSUE_TAB_ID, $cssMb20);

        // ---------------------------------------

        $this->addTab(
            self::UNMANAGED_ITEMS_TAB_ID,
            __('Unmanaged Items'),
            $this->getUrl('*/amazon_listing_unmanaged/index')
        );
        $this->registerCssForTab(self::UNMANAGED_ITEMS_TAB_ID, $cssMb20);

        // ---------------------------------------

        $this->addTab(
            self::ALL_ITEMS_TAB_ID,
            __('All Items'),
            $this->getUrl('*/amazon_listing/allItems')
        );
        $this->registerCssForTab(self::ALL_ITEMS_TAB_ID, $cssMb10);

        // ---------------------------------------
    }

    public function activateItemsByListingTab(): void
    {
        $this->setActiveTabId(self::ITEMS_BY_LISTING_TAB_ID);
    }

    public function activateItemsByIssueTab(): void
    {
        $this->setActiveTabId(self::ITEMS_BY_ISSUE_TAB_ID);
    }

    public function activateUnmanagedItemsTab(): void
    {
        $this->setActiveTabId(self::UNMANAGED_ITEMS_TAB_ID);
    }

    public function activateAllItemsTab(): void
    {
        $this->setActiveTabId(self::ALL_ITEMS_TAB_ID);
    }
}
