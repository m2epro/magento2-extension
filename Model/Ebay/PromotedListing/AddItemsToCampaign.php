<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\PromotedListing;

class AddItemsToCampaign
{
    private const MAX_ITEM_IDS_COUNT = 500;

    /** @var \Ess\M2ePro\Model\Ebay\PromotedListing\Channel\AddItemsToCampaignOnChannel */
    private Channel\AddItemsToCampaignOnChannel $addItemsToCampaignOnChannel;
    private \Ess\M2ePro\Model\Ebay\Listing\Product\Repository $ebayListingProductRepository;
    /** @var \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\Logger */
    private Campaign\Logger $logger;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\PromotedListing\Channel\AddItemsToCampaignOnChannel $addItemsToCampaignOnChannel,
        \Ess\M2ePro\Model\Ebay\Listing\Product\Repository $ebayListingProductRepository,
        \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\Logger $logger
    ) {
        $this->addItemsToCampaignOnChannel = $addItemsToCampaignOnChannel;
        $this->ebayListingProductRepository = $ebayListingProductRepository;
        $this->logger = $logger;
    }

    public function execute(Campaign $campaign, array $listingProductIds): void
    {
        $listingProductsGroupedByEbayItemId = $this->ebayListingProductRepository
            ->getForAddToCampaign($listingProductIds);

        $ebayItemIds = array_keys($listingProductsGroupedByEbayItemId);

        foreach (array_chunk($ebayItemIds, self::MAX_ITEM_IDS_COUNT) as $ebayItemIdsChunk) {
            $addItemsResult = $this->addItemsToCampaignOnChannel
                ->process($campaign, $ebayItemIdsChunk);

            foreach ($addItemsResult->getItems() as $resultItem) {
                $ebayListingProduct = $listingProductsGroupedByEbayItemId[$resultItem->getId()] ?? null;
                if ($ebayListingProduct === null) {
                    continue;
                }

                if ($resultItem->isSuccess()) {
                    $ebayListingProduct->assignCampaign($campaign->getId(), $campaign->getRate());
                    $ebayListingProduct->save();

                    $message = (string)__(
                        'Item successfully added to promotion campaign "%campaign_name"',
                        [
                            'campaign_name' => $campaign->getName(),
                        ]
                    );
                    $this->logger->addSuccessLog($ebayListingProduct->getParentObject(), $message);

                    continue;
                }

                $message = (string)__(
                    'Failed to add item to promotion campaign "%campaign_name"',
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
