<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Dictionary;

class ProductTypeFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(
        \Ess\M2ePro\Model\Marketplace $marketplace,
        string $nick,
        string $title,
        array $schema,
        array $variationThemes,
        array $attributesGroups,
        \DateTime $serverUpdateDate,
        \DateTime $clientUpdateDate
    ): ProductType {
        $model = $this->createEmpty();
        $model->create(
            $marketplace,
            $nick,
            $title,
            $schema,
            $variationThemes,
            $attributesGroups,
            $serverUpdateDate,
            $clientUpdateDate
        );

        return $model;
    }

    public function createEmpty(): ProductType
    {
        return $this->objectManager->create(ProductType::class);
    }
}
