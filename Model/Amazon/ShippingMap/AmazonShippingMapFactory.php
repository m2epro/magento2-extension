<?php

namespace Ess\M2ePro\Model\Amazon\ShippingMap;

use Magento\Framework\ObjectManagerInterface;

class AmazonShippingMapFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = [])
    {
        return $this->objectManager->create(AmazonShippingMap::class, $data);
    }
}
