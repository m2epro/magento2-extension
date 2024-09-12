<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace\Sync;

class MarketplaceLoader
{
    private \Ess\M2ePro\Model\Amazon\Marketplace\Repository $amazonMarketplaceRepository;

    public function __construct(\Ess\M2ePro\Model\Amazon\Marketplace\Repository $amazonMarketplaceRepository)
    {
        $this->amazonMarketplaceRepository = $amazonMarketplaceRepository;
    }

    public function load($marketplaceId): \Ess\M2ePro\Model\Marketplace
    {
        if ($marketplaceId === null) {
            throw new \RuntimeException('Missing marketplace ID');
        }

        $marketplace = $this->amazonMarketplaceRepository->get((int)$marketplaceId);
        if (!$marketplace->isComponentModeAmazon()) {
            throw new \LogicException('Marketplace is not valid.');
        }

        return $marketplace;
    }
}
