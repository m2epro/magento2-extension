<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Variation\Updater;

use Ess\M2ePro\Model\Magento\Product\Variation\StandardSuite;
use Ess\M2ePro\Model\Magento\Product\Variation;

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

    public function canModify(StandardSuite $suite): bool
    {
        return $suite->hasGroupedAttributeLabel();
    }

    public function modify(StandardSuite $suite, \Ess\M2ePro\Model\Magento\Product $magentoProduct): void
    {
        if (!$this->canModify($suite)) {
            return;
        }

        $options = $this->getOptions($magentoProduct);
        $attribute = $this->retrieveVariationAttribute($magentoProduct);

        $changedVariations = $this->getChangedVariations(
            $suite->getVariations(),
            $options,
            $attribute
        );

        $suite->setVariations($changedVariations);
    }

    private function getOptions(\Ess\M2ePro\Model\Magento\Product $magentoProduct): array
    {
        $options = [];

        $associatedProducts = $this->getAssociatedProducts($magentoProduct);

        foreach ($associatedProducts as $associatedProduct) {
            $newOptionTitle = $this->retrieveConfiguredTitle($associatedProduct);
            if ($newOptionTitle === null) {
                continue;
            }

            $options[] = [
                'product_id' => (int)$associatedProduct->getId(),
                'replaced_title' => $associatedProduct->getName(),
                'new_title' => $newOptionTitle,
            ];
        }

        return $options;
    }

    public function retrieveVariationAttribute(\Ess\M2ePro\Model\Magento\Product $magentoProduct): ?string
    {
        $mapping = $this->groupedAttributeMapping->findConfiguredVariationAttribute();
        if ($mapping === null) {
            return null;
        }

        $value = $magentoProduct->getAttributeValue(
            $mapping->value
        );

        return !empty($value) ? $value : null;
    }

    /**
     * @return \Magento\Catalog\Model\Product[]
     */
    private function getAssociatedProducts(\Ess\M2ePro\Model\Magento\Product $magentoProduct): array
    {
        $typeInstance = $magentoProduct->getTypeInstance();

        return $typeInstance->getAssociatedProducts(
            $magentoProduct->getProduct()
        );
    }

    public function retrieveConfiguredTitle(\Magento\Catalog\Model\Product $product): ?string
    {
        $mapping = $this->groupedAttributeMapping->findConfiguredVariationOptionTitle();
        if ($mapping === null) {
            return null;
        }

        $magentoProduct = $this->magentoProductFactory->create();
        $magentoProduct->loadProduct(
            $product->getId(),
            $product->getStoreId()
        );

        $value = $magentoProduct->getAttributeValue($mapping->value);

        return !empty($value) ? $value : null;
    }

    private function getChangedVariations(
        array $variations,
        array $options,
        ?string $newAttribute
    ): array {
        $attribute = Variation::GROUPED_PRODUCT_ATTRIBUTE_LABEL;

        // Replace Variation Attribute
        // ---------------------------------------
        if ($newAttribute !== null) {
            $attribute = $newAttribute;

            // In set
            $variations['set'][$attribute] = $variations['set'][Variation::GROUPED_PRODUCT_ATTRIBUTE_LABEL];
            unset($variations['set'][Variation::GROUPED_PRODUCT_ATTRIBUTE_LABEL]);

            // In variations
            foreach ($variations['variations'] as &$list) {
                foreach ($list as &$item) {
                    $item['attribute'] = $attribute;
                }
            }
        }

        // Replace Variation Options
        // ---------------------------------------
        foreach ($options as $option) {
            // In set
            foreach ($variations['set'][$attribute] as $key => $currentTitle) {
                if ($currentTitle === $option['replaced_title']) {
                    $variations['set'][$attribute][$key] = $option['new_title'];
                }
            };

            // In variations
            foreach ($variations['variations'] as &$list) {
                foreach ($list as &$item) {
                    if ((int)$item['product_id'] === $option['product_id']) {
                        $item['option'] = $option['new_title'];
                    }
                }
            }
        }

        // ---------------------------------------

        return $variations;
    }
}
