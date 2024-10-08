<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Dictionary;

class ProductTypeFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(
        int $marketplaceId,
        string $productTypeNick,
        string $productTypeTitle,
        array $attributes,
        array $variationAttributes
    ): ProductType {
        $entity = $this->createEmpty();
        $entity->init(
            $marketplaceId,
            $productTypeNick,
            $productTypeTitle,
            $attributes,
            $variationAttributes
        );

        return $entity;
    }

    public function createEmpty(): ProductType
    {
        return $this->objectManager->create(ProductType::class);
    }
}
