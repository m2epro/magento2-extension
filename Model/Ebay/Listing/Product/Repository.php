<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product;

class Repository
{
    private \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\CollectionFactory $ebayListingProductCollectionFactory;
    private \Ess\M2ePro\Model\ResourceModel\Ebay\Item $ebayItemResource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\CollectionFactory $ebayListingProductCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Item $ebayItemResource
    ) {
        $this->ebayListingProductCollectionFactory = $ebayListingProductCollectionFactory;
        $this->ebayItemResource = $ebayItemResource;
    }

    public function findByItemId(string $itemId): ?\Ess\M2ePro\Model\Ebay\Listing\Product
    {
        $collection = $this->ebayListingProductCollectionFactory->create();

        $collection->join(
            ['elp' => $this->ebayItemResource->getMainTable()],
            sprintf(
                'main_table.%s = elp.%s',
                \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_EBAY_ITEM_ID,
                \Ess\M2ePro\Model\ResourceModel\Ebay\Item::COLUMN_ID
            ),
            []
        );

        $collection->addFieldToFilter(\Ess\M2ePro\Model\ResourceModel\Ebay\Item::COLUMN_ITEM_ID, $itemId);

        $item = $collection->getFirstItem();

        if ($item->isObjectNew()) {
            return null;
        }

        return $item;
    }

    /**
     * Key is eBay item_id
     * @param string[] $itemIds
     *
     * @return array<string, \Ess\M2ePro\Model\Ebay\Listing\Product>
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getByItemIdsGroupedByEbayItemId(array $itemIds): array
    {
        $collection = $this->ebayListingProductCollectionFactory->create();

        $collection->join(
            ['elp' => $this->ebayItemResource->getMainTable()],
            sprintf(
                'main_table.%s = elp.%s',
                \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_EBAY_ITEM_ID,
                \Ess\M2ePro\Model\ResourceModel\Ebay\Item::COLUMN_ID
            ),
            [\Ess\M2ePro\Model\ResourceModel\Ebay\Item::COLUMN_ITEM_ID]
        );

        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Item::COLUMN_ITEM_ID,
            ['in' => $itemIds]
        );

        $result = [];
        foreach ($collection->getItems() as $item) {
            $result[$item->getData(\Ess\M2ePro\Model\ResourceModel\Ebay\Item::COLUMN_ITEM_ID)] = $item;
        }

        return $result;
    }

    /**
     * Key is eBay item_id
     * @param string[] $listingProductIds
     *
     * @return array<string, \Ess\M2ePro\Model\Ebay\Listing\Product>
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getForAddToCampaign(array $listingProductIds): array
    {
        $collection = $this->ebayListingProductCollectionFactory->create();

        $collection->join(
            ['elp' => $this->ebayItemResource->getMainTable()],
            sprintf(
                'main_table.%s = elp.%s',
                \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_EBAY_ITEM_ID,
                \Ess\M2ePro\Model\ResourceModel\Ebay\Item::COLUMN_ID
            ),
            [\Ess\M2ePro\Model\ResourceModel\Ebay\Item::COLUMN_ITEM_ID]
        );

        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_PROMOTED_LISTING_CAMPAIGN_ID,
            ['null' => true]
        );
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_LISTING_PRODUCT_ID,
            ['in' => $listingProductIds]
        );

        $result = [];
        foreach ($collection->getItems() as $item) {
            $result[$item->getData(\Ess\M2ePro\Model\ResourceModel\Ebay\Item::COLUMN_ITEM_ID)] = $item;
        }

        return $result;
    }

    /**
     * Key is eBay item_id
     * @param string[] $listingProductIds
     *
     * @return array<string, \Ess\M2ePro\Model\Ebay\Listing\Product>
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getForDeleteFromCampaign(array $listingProductIds, int $campaignId): array
    {
        $collection = $this->ebayListingProductCollectionFactory->create();

        $collection->join(
            ['elp' => $this->ebayItemResource->getMainTable()],
            sprintf(
                'main_table.%s = elp.%s',
                \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_EBAY_ITEM_ID,
                \Ess\M2ePro\Model\ResourceModel\Ebay\Item::COLUMN_ID
            ),
            [\Ess\M2ePro\Model\ResourceModel\Ebay\Item::COLUMN_ITEM_ID]
        );

        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_LISTING_PRODUCT_ID,
            ['in' => $listingProductIds]
        );

        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_PROMOTED_LISTING_CAMPAIGN_ID,
            ['eq' => $campaignId]
        );

        $result = [];
        foreach ($collection->getItems() as $item) {
            $result[$item->getData(\Ess\M2ePro\Model\ResourceModel\Ebay\Item::COLUMN_ITEM_ID)] = $item;
        }

        return $result;
    }
}
