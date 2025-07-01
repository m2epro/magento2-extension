<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\PromotedListing;

class CreateCampaign
{
    private Channel\CreateCampaignOnChannel $createCampaignOnChannel;
    private \Ess\M2ePro\Model\Ebay\PromotedListing\CampaignFactory $campaignFactory;
    private \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\Repository $campaignRepository;

    public function __construct(
        Channel\CreateCampaignOnChannel $createCampaignOnChannel,
        \Ess\M2ePro\Model\Ebay\PromotedListing\CampaignFactory $campaignFactory,
        \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\Repository $campaignRepository
    ) {
        $this->createCampaignOnChannel = $createCampaignOnChannel;
        $this->campaignFactory = $campaignFactory;
        $this->campaignRepository = $campaignRepository;
    }

    public function execute(
        \Ess\M2ePro\Model\Ebay\Account $ebayAccount,
        \Ess\M2ePro\Model\Ebay\Marketplace $ebayMarketplace,
        Channel\Dto\CreateCampaign $campaign
    ): void {
        $createResult = $this->createCampaignOnChannel
            ->process($ebayAccount, $ebayMarketplace, $campaign);

        if ($createResult->isFail()) {
            throw new CampaignException($createResult->getFailMessages());
        }

        $campaign = $this->campaignFactory
            ->create()
            ->initFromChannelCampaign($ebayAccount, $createResult->getChannelCampaign());

        $this->campaignRepository->create($campaign);
    }
}
