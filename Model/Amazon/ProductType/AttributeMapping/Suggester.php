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
            'item_name#array/value' => 'name',
            'brand#array/value' => 'manufacturer',
            'manufacturer#array/value' => 'manufacturer',
            'product_description#array/value' => 'description',
            'country_of_origin#array/value' => 'country_of_manufacture',
            'item_package_weight#array/value' => 'weight',
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
