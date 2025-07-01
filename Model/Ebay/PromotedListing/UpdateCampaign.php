<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\PromotedListing;

class UpdateCampaign
{
    private Channel\UpdateCampaignOnChannel $updateCampaignOnChannel;
    private \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\Repository $campaignRepository;

    public function __construct(
        Channel\UpdateCampaignOnChannel $updateCampaignOnChannel,
        \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\Repository $campaignRepository
    ) {
        $this->campaignRepository = $campaignRepository;
        $this->updateCampaignOnChannel = $updateCampaignOnChannel;
    }

    public function execute(
        \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign $campaign,
        string $name,
        \DateTime $startDate,
        ?\DateTime $endDate
    ): void {
        $isDataChanged = $campaign->updateNameStartDateAndEndDate($name, $startDate, $endDate);
        if (!$isDataChanged) {
            return;
        }

        $updateResult = $this->updateCampaignOnChannel
            ->process(
                $campaign->getEbayAccount(),
                new Channel\Dto\UpdateCampaign(
                    $campaign->getEbayCampaignId(),
                    $campaign->getName(),
                    $campaign->getStartDate(),
                    $campaign->getEndDate()
                )
            );

        if ($updateResult->isFail()) {
            throw new CampaignException($updateResult->getFailMessages());
        }

        $this->campaignRepository->save($campaign);
    }
}
