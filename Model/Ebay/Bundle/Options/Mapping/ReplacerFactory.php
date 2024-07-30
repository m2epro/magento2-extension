<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping;

class ReplacerFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(
        \Magento\Bundle\Model\Option $bundleOption,
        \Magento\Catalog\Model\Product $optionProduct
    ): Replacer {
        return $this->objectManager->create(Replacer::class, [
            'bundleOption' => $bundleOption,
            'optionProduct' => $optionProduct,
        ]);
    }
}
