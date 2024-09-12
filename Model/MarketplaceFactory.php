<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model;

class MarketplaceFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): Marketplace
    {
        return $this->objectManager->create(Marketplace::class);
    }
}
