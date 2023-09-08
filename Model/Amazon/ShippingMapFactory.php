<?php

namespace Ess\M2ePro\Model\Amazon;

use Magento\Framework\ObjectManagerInterface;

class ShippingMapFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = [])
    {
        return $this->objectManager->create(ShippingMap::class, $data);
    }
}
