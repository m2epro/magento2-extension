<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Promotion;

class DashboardUrlGenerator
{
    private \Ess\M2ePro\Model\MarketplaceFactory $marketplaceFactory;

    public function __construct(
        \Ess\M2ePro\Model\MarketplaceFactory $marketplaceFactory
    ) {
        $this->marketplaceFactory = $marketplaceFactory;
    }

    public function generate(int $marketplaceId): string
    {
        $marketplace = $this->marketplaceFactory->create();
        $marketplace->load($marketplaceId);

        return 'https://www.' . $marketplace->getUrl() . '/sh/mkt/promotionmanager/dashboard';
    }
}
