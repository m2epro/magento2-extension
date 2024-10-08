<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\ProductType\AttributeSetting;

class Provider
{
    /**
     * @return Provider\Item[]
     */
    public function getAttributes(
        \Ess\M2ePro\Model\Walmart\ProductType $productType,
        \Ess\M2ePro\Model\Magento\Product $product
    ): array {
        $attributes = [];
        foreach ($productType->getAttributesSettings() as $setting) {
            $resultValues = $this->getResultValues($setting, $product);
            if (empty($resultValues)) {
                continue;
            }

            $attributes[] = new Provider\Item(
                $setting->getAttributeName(),
                $resultValues
            );
        }

        return $attributes;
    }

    /**
     * @return string[]
     */
    private function getResultValues(
        \Ess\M2ePro\Model\Walmart\ProductType\AttributeSetting $attributeSetting,
        \Ess\M2ePro\Model\Magento\Product $product
    ): array {
        $result = [];
        foreach ($attributeSetting->getValues() as $settingValue) {
            if ($settingValue->isCustom()) {
                $result[] = $settingValue->getValue();
                continue;
            }

            if ($settingValue->isProductAttributeCode()) {
                $attributeValue = $product->getAttributeValue(
                    $settingValue->getValue()
                );
                if (!empty($attributeValue)) {
                    $result[] = $attributeValue;
                }
            }
        }

        return $result;
    }
}
