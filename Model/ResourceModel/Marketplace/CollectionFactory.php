<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Marketplace;

class CollectionFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): Collection
    {
        return $this->objectManager->create(Collection::class);
    }

    public function createWithEbayChildMode(): Collection
    {
        return $this->createWithChildMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);
    }

    public function createWithAmazonChildMode(): Collection
    {
        return $this->createWithChildMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
    }

    public function createWithWalmartChildMode(): Collection
    {
        return $this->createWithChildMode(\Ess\M2ePro\Helper\Component\Walmart::NICK);
    }

    private function createWithChildMode(string $componentMode): Collection
    {
        return $this->objectManager->create(Collection::class, [
            'childMode' => $componentMode,
        ]);
    }
}
