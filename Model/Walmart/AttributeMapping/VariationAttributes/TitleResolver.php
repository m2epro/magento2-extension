<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes;

use Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes\Provider\MagentoAttributeOptionLoader;

class TitleResolver
{
    private \Ess\M2ePro\Model\Walmart\Dictionary\ProductType\Repository $productTypeRepository;
    /** @var \Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes\Provider\MagentoAttributeOptionLoader */
    private Provider\MagentoAttributeOptionLoader $attributeOptionLoader;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Dictionary\ProductType\Repository $productTypeRepository,
        MagentoAttributeOptionLoader $attributeOptionLoader
    ) {
        $this->productTypeRepository = $productTypeRepository;
        $this->attributeOptionLoader = $attributeOptionLoader;
    }

    public function getAttributeTitle(int $productTypeId, string $attributeCode): string
    {
        $productType = $this->productTypeRepository->get($productTypeId);

        foreach ($productType->getAttributes() as $attribute) {
            $attributeName = str_replace('#array', '', $attribute['name']);
            if ($attributeName === $attributeCode) {
                return (string)$attribute['title'];
            }
        }

        return '';
    }

    public function getOptionTitle(int $productTypeId, string $attributeCode, string $optionCode): string
    {
        $productType = $this->productTypeRepository->get($productTypeId);

        foreach ($productType->getAttributes() as $attribute) {
            $attributeName = str_replace('#array', '', $attribute['name']);
            if ($attributeName !== $attributeCode) {
                continue;
            }

            foreach ($attribute['options'] as $optionKey => $optionValue) {
                if ($optionKey === $optionCode) {
                    return (string)$optionValue;
                }
            }
        }

        return '';
    }

    public function getMagentoOptionName(string $magentoAttributeCode, int $magentoAttributeOptionId): string
    {
        $options = $this->attributeOptionLoader->getOptionsMagento($magentoAttributeCode);
        foreach ($options as $option) {
            if ((int)$option['value'] === $magentoAttributeOptionId) {
                return (string)$option['label'];
            }
        }

        return '';
    }
}
