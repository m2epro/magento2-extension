<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\PromotedListing\Campaign;

class ItemsService
{
    private \Magento\Framework\App\ResourceConnection $resource;
    private \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product $ebayListingProductResource;
    private \Ess\M2ePro\Model\Ebay\Listing\Product\Repository $ebayListingProductRepository;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product $ebayListingProductResource,
        \Ess\M2ePro\Model\Ebay\Listing\Product\Repository $ebayListingProductRepository
    ) {
        $this->resource = $resource;
        $this->ebayListingProductResource = $ebayListingProductResource;
        $this->ebayListingProductRepository = $ebayListingProductRepository;
    }

    public function unassignAll(\Ess\M2ePro\Model\Ebay\PromotedListing\Campaign $campaign)
    {
        $updateData = [
            \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_PROMOTED_LISTING_CAMPAIGN_ID => null,
            \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_PROMOTED_LISTING_CAMPAIGN_RATE => null,
        ];

        $whereCondition = sprintf(
            '%s = %s',
            \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_PROMOTED_LISTING_CAMPAIGN_ID,
            $campaign->getId()
        );

        $this->resource
            ->getConnection()
            ->update($this->ebayListingProductResource->getMainTable(), $updateData, $whereCondition);
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\PromotedListing\Channel\Dto\CampaignItem[] $campaignItems
     */
    public function assignChannelItems(\Ess\M2ePro\Model\Ebay\PromotedListing\Campaign $campaign, array $campaignItems)
    {
        $campaignItemsGroupedByEbayItemId = [];
        foreach ($campaignItems as $campaignItem) {
            $campaignItemsGroupedByEbayItemId[$campaignItem->getId()] = $campaignItem;
        }

        $existedListingProducts = $this->ebayListingProductRepository
            ->getByItemIdsGroupedByEbayItemId(array_keys($campaignItemsGroupedByEbayItemId));

        foreach ($existedListingProducts as $ebayItemId => $ebayListingProduct) {
            if (!isset($campaignItemsGroupedByEbayItemId[$ebayItemId])) {
                continue;
            }

            $campaignItem = $campaignItemsGroupedByEbayItemId[$ebayItemId];

            $ebayListingProduct->assignCampaign($campaign->getId(), $campaignItem->getRate());
            $ebayListingProduct->save();
        }
    }
}
