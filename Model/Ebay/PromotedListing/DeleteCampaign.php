<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\PromotedListing;

class DeleteCampaign
{
    private Channel\DeleteCampaignOnChannel $deleteCampaignOnChannel;
    private \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\Repository $campaignRepository;
    /** @var \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\ItemsService */
    private Campaign\ItemsService $itemsService;

    public function __construct(
        Channel\DeleteCampaignOnChannel $deleteCampaignOnChannel,
        \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\Repository $campaignRepository,
        \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\ItemsService $itemsService
    ) {
        $this->deleteCampaignOnChannel = $deleteCampaignOnChannel;
        $this->campaignRepository = $campaignRepository;
        $this->itemsService = $itemsService;
    }

    public function execute(Campaign $campaign, bool $deleteFromChannel): void
    {
        if ($deleteFromChannel) {
            $result = $this->deleteCampaignOnChannel->process(
                $campaign->getEbayAccount(),
                $campaign->getEbayCampaignId(),
            );

            if ($result->isFail()) {
                throw new CampaignException($result->getFailMessages());
            }
        }

        $this->itemsService->unassignAll($campaign);
        $this->campaignRepository->delete($campaign);
    }
}
