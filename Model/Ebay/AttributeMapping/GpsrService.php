<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\AttributeMapping;

class GpsrService
{
    public const MAPPING_TYPE = 'gpsr';

    /** @var \Ess\M2ePro\Model\Ebay\AttributeMapping\Gpsr\Provider */
    private Gpsr\Provider $attributeProvider;
    /** @var \Ess\M2ePro\Model\Ebay\AttributeMapping\Gpsr\Update */
    private Gpsr\Update $update;
    /** @var \Ess\M2ePro\Model\Ebay\AttributeMapping\Gpsr\CategoryModifier */
    private Gpsr\CategoryModifier $categoryModifier;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\AttributeMapping\Gpsr\Provider $attributeProvider,
        \Ess\M2ePro\Model\Ebay\AttributeMapping\Gpsr\Update $update,
        \Ess\M2ePro\Model\Ebay\AttributeMapping\Gpsr\CategoryModifier $categoryModifier
    ) {
        $this->attributeProvider = $attributeProvider;
        $this->update = $update;
        $this->categoryModifier = $categoryModifier;
    }

    /**
     * @return Pair[]
     */
    public function getAll(): array
    {
        return $this->attributeProvider->getAll();
    }

    /**
     * @return Pair[]
     */
    public function getConfigured(): array
    {
        return $this->attributeProvider->getConfigured();
    }

    /**
     * @param Pair[] $attributesMapping
     *
     * @return int - processed (updated or created) count
     */
    public function save(array $attributesMapping): int
    {
        return $this->update->process($attributesMapping);
    }

    public function setToCategories(): void
    {
        $this->categoryModifier->process($this->getConfigured());
    }
}
