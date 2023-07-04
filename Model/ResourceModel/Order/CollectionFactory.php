<?php

namespace Ess\M2ePro\Model\ResourceModel\Order;

class CollectionFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): Collection
    {
        return $this->objectManager->create(Collection::class);
    }

    public function createWithAmazonChildMode(): Collection
    {
        return $this->createWithChildMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
    }

    public function createWithEbayChildMode(): Collection
    {
        return $this->createWithChildMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);
    }

    public function createWithWalmartChildMode(): Collection
    {
        return $this->createWithChildMode(\Ess\M2ePro\Helper\Component\Walmart::NICK);
    }

    private function createWithChildMode(string $childMode): Collection
    {
        return $this->objectManager->create(Collection::class, [
            'childMode' => $childMode,
        ]);
    }
}
