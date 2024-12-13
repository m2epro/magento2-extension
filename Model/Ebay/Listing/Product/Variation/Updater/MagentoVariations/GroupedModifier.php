<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Variation\Updater\MagentoVariations;

use Ess\M2ePro\Model\Ebay\Listing\Product\Variation\Updater\MagentoVariations;

class GroupedModifier
{
    private \Ess\M2ePro\Model\Ebay\AttributeMapping\GroupedService $groupedAttributeMapping;
    private \Ess\M2ePro\Model\Magento\ProductFactory $magentoProductFactory;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\AttributeMapping\GroupedService $groupedAttributeMapping,
        \Ess\M2ePro\Model\Magento\ProductFactory $magentoProductFactory
    ) {
        $this->groupedAttributeMapping = $groupedAttributeMapping;
        $this->magentoProductFactory = $magentoProductFactory;
    }

    public function canModify(
        array $rawMagentoVariations,
        \Ess\M2ePro\Model\Magento\Product $magentoProduct
    ): bool {
        return MagentoVariations::canBeCreated($rawMagentoVariations)
            && $magentoProduct->isGroupedType();
    }

    public function modify(
        array $rawMagentoVariations,
        \Ess\M2ePro\Model\Magento\Product $magentoProduct
    ): array {
        if (!$this->canModify($rawMagentoVariations, $magentoProduct)) {
            return $rawMagentoVariations;
        }

        $magentoVariations = new MagentoVariations($rawMagentoVariations);

        $valueOfVariationAttribute = $this->findValueOfVariationAttribute($magentoProduct);
        if ($valueOfVariationAttribute !== null) {
            $magentoVariations->setVariationAttribute($valueOfVariationAttribute);
        }

        $attributeCodeOfOptionTitle = $this->findProductAttributeCodeOfOptionTitle();
        if ($attributeCodeOfOptionTitle !== null) {
            $this->addOptions(
                $attributeCodeOfOptionTitle,
                $magentoVariations,
                $magentoProduct
            );
        }

        return $magentoVariations->getChangedMagentoVariations();
    }

    private function findValueOfVariationAttribute(
        \Ess\M2ePro\Model\Magento\Product $magentoProduct
    ): ?string {
        $mapping = $this->groupedAttributeMapping->findConfiguredVariationAttribute();
        if ($mapping === null) {
            return null;
        }

        $value = $magentoProduct->getAttributeValue(
            $mapping->value
        );

        return !empty($value) ? $value : null;
    }

    private function findProductAttributeCodeOfOptionTitle(): ?string
    {
        $mapping = $this->groupedAttributeMapping->findConfiguredVariationOptionTitle();
        if ($mapping === null) {
            return null;
        }

        return $mapping->value;
    }

    private function addOptions(
        string $attributeCodeOfOptionTitle,
        MagentoVariations $magentoVariations,
        \Ess\M2ePro\Model\Magento\Product $magentoProduct
    ): void {
        $typeInstance = $magentoProduct->getTypeInstance();
        /** @var \Magento\Catalog\Model\Product[] $associatedProducts */
        $associatedProducts = $typeInstance->getAssociatedProducts(
            $magentoProduct->getProduct()
        );

        foreach ($associatedProducts as $associatedProduct) {
            $newOptionTitle = $this->getProductAttributeValue(
                $attributeCodeOfOptionTitle,
                $associatedProduct
            );
            if ($newOptionTitle === null) {
                continue;
            }

            $magentoVariations->addOption(
                (int)$associatedProduct->getId(),
                $associatedProduct->getName(),
                $newOptionTitle
            );
        }
    }

    private function getProductAttributeValue(string $attributeCode, \Magento\Catalog\Model\Product $product): ?string
    {
        $magentoProduct = $this->magentoProductFactory->create();
        $magentoProduct->loadProduct(
            $product->getId(),
            $product->getStoreId()
        );

        $value = $magentoProduct->getAttributeValue($attributeCode);

        return !empty($value) ? $value : null;
    }
}
