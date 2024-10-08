<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart;

class ProductTypeFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): ProductType
    {
        return $this->objectManager->create(ProductType::class);
    }

    public function createEmpty(): ProductType
    {
        return $this->objectManager->create(ProductType::class);
    }
}
