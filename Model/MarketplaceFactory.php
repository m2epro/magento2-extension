<?php

namespace Ess\M2ePro\Model;

class MarketplaceFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): Marketplace
    {
        return $this->objectManager->create(Marketplace::class);
    }
}
