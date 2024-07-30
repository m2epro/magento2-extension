<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\ObserverHandler;

class BundleOptionsCollector
{
    private \Ess\M2ePro\Helper\Magento\Product $productHelper;

    public function __construct(\Ess\M2ePro\Helper\Magento\Product $productHelper)
    {
        $this->productHelper = $productHelper;
    }

    public function collectOptionNames(\Magento\Catalog\Model\Product $product): array
    {
        if (!$this->productHelper->isBundleType($product->getTypeId())) {
            return [];
        }

        /** @var \Magento\Bundle\Model\Product\Type $typeInstance */
        $typeInstance = $product->getTypeInstance();

        $bundleOptionNames = [];
        /** @var \Magento\Bundle\Model\Option $option */
        foreach ($typeInstance->getOptionsCollection($product) as $option) {
            $bundleOptionNames[$option->getId()] = $option->getDefaultTitle();
        }

        return $bundleOptionNames;
    }
}
