<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\AttributeMapping;

class VariationAttributesService
{
    public const MAPPING_TYPE = 'product_type_variation_attributes';

    /** @var \Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes\Provider */
    private VariationAttributes\Provider $provider;
    /** @var \Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes\Update */
    private VariationAttributes\Update $update;
    /** @var \Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes\TitleResolver */
    private VariationAttributes\TitleResolver $titleResolver;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes\Provider $provider,
        \Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes\Update $update,
        \Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes\TitleResolver $titleResolver
    ) {
        $this->provider = $provider;
        $this->update = $update;
        $this->titleResolver = $titleResolver;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes\Provider\ProductType[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAll(): array
    {
        return $this->provider->getAll();
    }

    public function save(array $attributesMapping): int
    {
        return $this->update->process($attributesMapping);
    }

    public function getChannelAttributeTitle(int $productTypeId, string $attributeCode): string
    {
        return $this->titleResolver->getAttributeTitle($productTypeId, $attributeCode);
    }

    public function getChannelOptionTitle(
        int $productTypeId,
        string $attributeCode,
        string $optionCode
    ): string {
        return $this->titleResolver->getOptionTitle($productTypeId, $attributeCode, $optionCode);
    }

    public function getMagentoOptionTitle(string $magentoAttributeCode, int $magentoAttributeOptionId): string
    {
        return $this->titleResolver->getMagentoOptionName($magentoAttributeCode, $magentoAttributeOptionId);
    }
}
