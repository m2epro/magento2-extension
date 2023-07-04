<?php

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Template\SellingFormat\ShippingOverride;

use Ess\M2ePro\Model\ResourceModel\Walmart\Template\SellingFormat\ShippingOverride\Collection as ShipOverrideCollection;

class CollectionFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = []): ShipOverrideCollection
    {
        return $this->objectManager->create(ShipOverrideCollection::class, $data);
    }
}
