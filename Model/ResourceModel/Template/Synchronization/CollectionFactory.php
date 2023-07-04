<?php

namespace Ess\M2ePro\Model\ResourceModel\Template\Synchronization;

use Ess\M2ePro\Model\ResourceModel\Template\Synchronization\Collection as SynchronizationCollection;

class CollectionFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = []): SynchronizationCollection
    {
        return $this->objectManager->create(SynchronizationCollection::class, $data);
    }
    public function createWithAmazonChildMode(): SynchronizationCollection
    {
        return $this->createWithChildMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
    }

    public function createWithEbayChildMode(): SynchronizationCollection
    {
        return $this->createWithChildMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);
    }

    public function createWithWalmartChildMode(): SynchronizationCollection
    {
        return $this->createWithChildMode(\Ess\M2ePro\Helper\Component\Walmart::NICK);
    }

    private function createWithChildMode(string $componentMode): SynchronizationCollection
    {
        return $this->objectManager->create(SynchronizationCollection::class, [
            'childMode' => $componentMode,
        ]);
    }
}
