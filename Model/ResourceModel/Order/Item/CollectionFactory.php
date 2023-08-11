<?php

namespace Ess\M2ePro\Model\ResourceModel\Order\Item;

class CollectionFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = []): Collection
    {
        return $this->objectManager->create(Collection::class, $data);
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
