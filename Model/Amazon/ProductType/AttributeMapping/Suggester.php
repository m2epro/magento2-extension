<?php

namespace Ess\M2ePro\Model\Amazon\ProductType\AttributeMapping;

use Ess\M2ePro\Model\ResourceModel\Amazon\ProductType\AttributeMapping;

class Suggester
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\ProductType\AttributeMapping\CollectionFactory */
    private $productTypeAttributeMappingCollectionFactory;

    public function __construct(
        AttributeMapping\CollectionFactory $attributeMappingCollectionFactory
    ) {
        $this->productTypeAttributeMappingCollectionFactory = $attributeMappingCollectionFactory;
    }

    public function getSuggestedAttributes(): array
    {
        $attributes = [];
        $attributes = $this->appendAttributeMapSuggestedAttributes($attributes);
        $attributes = $this->appendDefaultSuggestedAttributes($attributes);

        return $attributes;
    }

    private function appendDefaultSuggestedAttributes(array $attributes): array
    {
        $map = [
            \Ess\M2ePro\Helper\Component\Amazon\ProductType::SPECIFIC_KEY_NAME => 'name',
            \Ess\M2ePro\Helper\Component\Amazon\ProductType::SPECIFIC_KEY_BRAND => 'manufacturer',
            \Ess\M2ePro\Helper\Component\Amazon\ProductType::SPECIFIC_KEY_MANUFACTURER => 'manufacturer',
            \Ess\M2ePro\Helper\Component\Amazon\ProductType::SPECIFIC_KEY_DESCRIPTION => 'description',
            \Ess\M2ePro\Helper\Component\Amazon\ProductType::SPECIFIC_KEY_COUNTRY_OF_ORIGIN => 'country_of_manufacture',
            \Ess\M2ePro\Helper\Component\Amazon\ProductType::SPECIFIC_KEY_ITEM_PACKAGE_WEIGHT => 'weight',
            \Ess\M2ePro\Helper\Component\Amazon\ProductType::SPECIFIC_KEY_MAIN_PRODUCT_IMAGE_LOCATOR => 'image',
        ];

        foreach ($map as $productTypeAttributeCode => $magentoAttributeCode) {
            if (array_key_exists($productTypeAttributeCode, $attributes)) {
                continue;
            }

            $attributes[$productTypeAttributeCode] = [
                'mode' => \Ess\M2ePro\Model\Amazon\Template\ProductType::FIELD_CUSTOM_ATTRIBUTE,
                'attribute_code' => $magentoAttributeCode,
            ];
        }

        return $attributes;
    }

    private function appendAttributeMapSuggestedAttributes(array $attributes): array
    {
        $collection = $this->productTypeAttributeMappingCollectionFactory->create();

        /** @var \Ess\M2ePro\Model\Amazon\ProductType\AttributeMapping $item */
        foreach ($collection->getItems() as $item) {
            $productTypeAttribute = $item->getProductTypeAttributeCode();
            $magentoAttributeCode = $item->getMagentoAttributeCode();

            if (
                array_key_exists($productTypeAttribute, $attributes)
                || $magentoAttributeCode === ''
            ) {
                continue;
            }

            $attributes[$productTypeAttribute] = [
                'mode' => \Ess\M2ePro\Model\Amazon\Template\ProductType::FIELD_CUSTOM_ATTRIBUTE,
                'attribute_code' => $magentoAttributeCode,
            ];
        }

        return $attributes;
    }
}
