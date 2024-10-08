<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Dictionary;

class MarketplaceFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function createWithProductTypes(
        int $marketplaceId,
        array $productTypes,
        \DateTime $clientDetailsLastUpdateDate,
        \DateTime $serverDetailsLastUpdateDate
    ): Marketplace {
        $entity = $this->createEmpty();
        $entity->init(
            $marketplaceId,
            $clientDetailsLastUpdateDate,
            $serverDetailsLastUpdateDate
        );
        $entity->setProductTypes($productTypes);

        return $entity;
    }

    public function createWithoutProductTypes(
        int $marketplaceId,
        \DateTime $clientDetailsLastUpdateDate,
        \DateTime $serverDetailsLastUpdateDate
    ): Marketplace {
        $entity = $this->createEmpty();
        $entity->init(
            $marketplaceId,
            $clientDetailsLastUpdateDate,
            $serverDetailsLastUpdateDate
        );

        return $entity;
    }

    public function createEmpty(): Marketplace
    {
        return $this->objectManager->create(Marketplace::class);
    }
}
