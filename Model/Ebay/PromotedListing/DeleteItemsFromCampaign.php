<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\PromotedListing;

class DeleteItemsFromCampaign
{
    private const MAX_ITEM_IDS_COUNT = 500;

    /** @var \Ess\M2ePro\Model\Ebay\PromotedListing\Channel\DeleteItemsFromCampaignOnChannel */
    private Channel\DeleteItemsFromCampaignOnChannel $deleteItemsFromCampaignOnChannel;
    private \Ess\M2ePro\Model\Ebay\Listing\Product\Repository $ebayListingProductRepository;
    /** @var \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\Logger */
    private Campaign\Logger $logger;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\PromotedListing\Channel\DeleteItemsFromCampaignOnChannel $deleteItemsFromCampaignOnChannel,
        \Ess\M2ePro\Model\Ebay\Listing\Product\Repository $ebayListingProductRepository,
        \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\Logger $logger
    ) {
        $this->deleteItemsFromCampaignOnChannel = $deleteItemsFromCampaignOnChannel;
        $this->ebayListingProductRepository = $ebayListingProductRepository;
        $this->logger = $logger;
    }

    public function execute(Campaign $campaign, array $listingProductIds): void
    {
        $listingProductsGroupedByEbayItemId = $this->ebayListingProductRepository
            ->getForDeleteFromCampaign($listingProductIds, $campaign->getId());

        $ebayItemIds = array_keys($listingProductsGroupedByEbayItemId);

        foreach (array_chunk($ebayItemIds, self::MAX_ITEM_IDS_COUNT) as $ebayItemIdsChunk) {
            $deleteItemsResult = $this->deleteItemsFromCampaignOnChannel
                ->process($campaign, $ebayItemIdsChunk);

            foreach ($deleteItemsResult->getItems() as $resultItem) {
                $ebayListingProduct = $listingProductsGroupedByEbayItemId[$resultItem->getId()] ?? null;
                if ($ebayListingProduct === null) {
                    continue;
                }

                if ($resultItem->isSuccess()) {
                    $ebayListingProduct->unassignCampaign();
                    $ebayListingProduct->save();

                    $message = (string)__(
                        'Item successfully removed from promotion campaign "%campaign_name"',
                        ['campaign_name' => $campaign->getName()]
                    );
                    $this->logger->addSuccessLog($ebayListingProduct->getParentObject(), $message);

                    continue;
                }

                $message = (string)__(
                    'Failed to remove item from promotion campaign "%campaign_name". Reasons: %fail_reasons',
                    [
                        'campaign_name' => $campaign->getName(),
                        'fail_reasons' => implode('; ', array_map(function ($message) {
                            return $message->getText();
                        }, $resultItem->getMessages())),
                    ]
                );
                $this->logger->addErrorLog($ebayListingProduct->getParentObject(), $message);
            }
        }
    }
}
