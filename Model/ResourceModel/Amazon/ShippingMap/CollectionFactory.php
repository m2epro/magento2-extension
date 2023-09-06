<?php

namespace Ess\M2ePro\Model\ResourceModel\Amazon\ShippingMap;

use Magento\Framework\ObjectManagerInterface;

class CollectionFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = []): Collection
    {
        return $this->objectManager->create(Collection::class, $data);
    }
}
