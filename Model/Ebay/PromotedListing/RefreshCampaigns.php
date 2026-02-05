<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\PromotedListing;

use Ess\M2ePro\Model\Ebay\PromotedListing\Channel\RetrieveCampaignItemsFromChannel;
use Ess\M2ePro\Model\Ebay\PromotedListing\Channel\RetrieveCampaignsFromChannel;

class RefreshCampaigns
{
    private array $existedCampaigns = [];

    private RetrieveCampaignsFromChannel $retrieveCampaignsFromChannel;
    private RetrieveCampaignItemsFromChannel $retrieveCampaignItemsFromChannel;
    private \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\Repository $promotedListingCampaignRepository;
    private CampaignFactory $campaignFactory;
    private \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\ItemsService $itemsService;
    private \Ess\M2ePro\Model\Ebay\PromotedListing\DeleteCampaign $deleteCampaign;
    private \Ess\M2ePro\Model\Cron\Task\Ebay\SynchronizePromotedListingCampaigns\KeepAliveEventDispatcher $keepAliveEventDispatcher;

    public function __construct(
        RetrieveCampaignsFromChannel $retrieveCampaignsFromChannel,
        RetrieveCampaignItemsFromChannel $retrieveCampaignItemsFromChannel,
        \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\Repository $campaignRepository,
        CampaignFactory $campaignFactory,
        \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\ItemsService $itemsService,
        \Ess\M2ePro\Model\Ebay\PromotedListing\DeleteCampaign $deleteCampaign,
        \Ess\M2ePro\Model\Cron\Task\Ebay\SynchronizePromotedListingCampaigns\KeepAliveEventDispatcher $keepAliveEventDispatcher
    ) {
        $this->retrieveCampaignsFromChannel = $retrieveCampaignsFromChannel;
        $this->retrieveCampaignItemsFromChannel = $retrieveCampaignItemsFromChannel;
        $this->promotedListingCampaignRepository = $campaignRepository;
        $this->campaignFactory = $campaignFactory;
        $this->itemsService = $itemsService;
        $this->deleteCampaign = $deleteCampaign;
        $this->keepAliveEventDispatcher = $keepAliveEventDispatcher;
    }

    public function execute(
        \Ess\M2ePro\Model\Ebay\Account $ebayAccount,
        ?\Ess\M2ePro\Model\Ebay\Marketplace $ebayMarketplace
    ) {
        $nextPage = 1;
        $processedCampaignIds = [];
        while ($nextPage > 0) {
            $response = $this->retrieveCampaignsFromChannel->process($ebayAccount, $nextPage);

            $receivedCampaigns = $response->getCampaigns();
            $receivedCampaigns = $this->filterByMarketplace($receivedCampaigns, $ebayMarketplace);

            $processedCampaignIds = array_merge(
                $processedCampaignIds,
                $this->createOrUpdateCampaigns($receivedCampaigns, $ebayAccount)
            );

            $nextPage = $response->getNextPage();
        }

        $this->deleteCampaignsExceptForIds($ebayAccount, $ebayMarketplace, $processedCampaignIds);
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\PromotedListing\Channel\Dto\Campaign[] $receivedCampaigns
     */
    private function createOrUpdateCampaigns(
        array $receivedCampaigns,
        \Ess\M2ePro\Model\Ebay\Account $ebayAccount
    ): array {
        $existedCampaigns = $this->getExistedCampaigns($ebayAccount);
        $processedCampaignIds = [];
        foreach ($receivedCampaigns as $receivedCampaign) {
            $processedCampaignIds[] = $receivedCampaign->getId();

            $existedCampaign = $existedCampaigns[$receivedCampaign->getId()] ?? null;
            if (empty($existedCampaign)) {
                $createdCampaign = $this->createCampaign($ebayAccount, $receivedCampaign);
                $this->processCampaignItems($ebayAccount, $createdCampaign);

                continue;
            }

            $this->updateCampaign($existedCampaign, $receivedCampaign);
            $this->processCampaignItems($ebayAccount, $existedCampaign);
        }

        return $processedCampaignIds;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign[]
     */
    private function getExistedCampaigns(\Ess\M2ePro\Model\Ebay\Account $ebayAccount): array
    {
        if (isset($this->existedCampaigns[$ebayAccount->getId()])) {
            return $this->existedCampaigns[$ebayAccount->getId()];
        }

        $existedCampaigns = [];
        $accountCampaigns = $this->promotedListingCampaignRepository
            ->getAllByAccountId((int)$ebayAccount->getId());
        foreach ($accountCampaigns as $campaign) {
            $existedCampaigns[$campaign->getEbayCampaignId()] = $campaign;
        }

        return $this->existedCampaigns[$ebayAccount->getId()] = $existedCampaigns;
    }

    private function createCampaign(
        \Ess\M2ePro\Model\Ebay\Account $ebayAccount,
        Channel\Dto\Campaign $receivedCampaign
    ): Campaign {
        $campaign = $this->campaignFactory
            ->create()
            ->initFromChannelCampaign($ebayAccount, $receivedCampaign);

        $this->promotedListingCampaignRepository->create($campaign);

        return $campaign;
    }

    private function updateCampaign(
        \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign $existedCampaign,
        Channel\Dto\Campaign $receivedCampaign
    ): void {
        $existedCampaign->updateFromChannelCampaign($receivedCampaign);

        $this->promotedListingCampaignRepository->save($existedCampaign);
    }

    private function deleteCampaignsExceptForIds(
        \Ess\M2ePro\Model\Ebay\Account $ebayAccount,
        ?\Ess\M2ePro\Model\Ebay\Marketplace $ebayMarketplace,
        array $processedCampaignIds
    ): void {
        $accountId = (int)$ebayAccount->getId();
        $marketplaceId = null;
        if (!empty($ebayMarketplace)) {
            $marketplaceId = (int)$ebayMarketplace->getId();
        }

        $campaignsToDelete = $this->promotedListingCampaignRepository
            ->getExceptForIds($accountId, $marketplaceId, $processedCampaignIds);

        foreach ($campaignsToDelete as $campaign) {
            $this->deleteCampaign->execute($campaign, false);
        }
    }

    private function processCampaignItems(
        \Ess\M2ePro\Model\Ebay\Account $ebayAccount,
        Campaign $campaign
    ): void {
        $this->itemsService->unassignAll($campaign);

        $nextPage = 1;
        while ($nextPage > 0) {
            $result = $this->retrieveCampaignItemsFromChannel
                ->process($ebayAccount, $campaign->getEbayCampaignId(), $nextPage);

            $this->itemsService->assignChannelItems($campaign, $result->getItems());
            $this->keepAliveEventDispatcher->process($nextPage);

            $nextPage = $result->getNextPage();
        }
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\PromotedListing\Channel\Dto\Campaign[] $receivedCampaigns
     * @return \Ess\M2ePro\Model\Ebay\PromotedListing\Channel\Dto\Campaign[]
     */
    private function filterByMarketplace(array $receivedCampaigns, ?\Ess\M2ePro\Model\Ebay\Marketplace $ebayMarketplace): array
    {
        if ($ebayMarketplace === null) {
            return $receivedCampaigns;
        }

        $result = [];
        foreach ($receivedCampaigns as $receivedCampaign) {
            if ($receivedCampaign->getMarketplaceId() !== (int)$ebayMarketplace->getId()) {
                continue;
            }

            $result[] = $receivedCampaign;
        }

        return $result;
    }
}
