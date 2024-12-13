<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\AttributeMapping;

class GroupedService
{
    public const MAPPING_TYPE = 'grouped';

    private Grouped\Update $update;
    private Grouped\Provider $attributeProvider;

    public function __construct(
        Grouped\Update $update,
        Grouped\Provider $attributeProvider
    ) {
        $this->update = $update;
        $this->attributeProvider = $attributeProvider;
    }

    /**
     * @return Pair[]
     */
    public function getAll(): array
    {
        return $this->attributeProvider->getAll();
    }

    public function findConfiguredVariationAttribute(): ?\Ess\M2ePro\Model\Ebay\AttributeMapping\Pair
    {
        return $this->attributeProvider->findConfiguredVariationAttribute();
    }

    public function findConfiguredVariationOptionTitle(): ?\Ess\M2ePro\Model\Ebay\AttributeMapping\Pair
    {
        return $this->attributeProvider->findConfiguredVariationOptionTitle();
    }

    /**
     * @param Pair[] $attributesMapping
     */
    public function save(array $attributesMapping): void
    {
        $this->update->process($attributesMapping);
    }
}
