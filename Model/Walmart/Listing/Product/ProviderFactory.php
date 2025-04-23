<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Listing\Product;

class ProviderFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(\Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct): Provider
    {
        return $this->objectManager->create(
            Provider::class,
            ['walmartListingProduct' => $walmartListingProduct]
        );
    }
}
