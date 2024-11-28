<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Magento\Product\Rule\Custom;

class AmazonSku extends \Ess\M2ePro\Model\Magento\Product\Rule\Custom\AbstractModel
{
    public function getAttributeCode(): string
    {
        return 'amazon_sku';
    }

    public function getLabel(): string
    {
        return (string)__('SKU');
    }

    public function getValueByProductInstance(\Magento\Catalog\Model\Product $product)
    {
        return $product->getData('amazon_sku')
            ?? $product->getData('online_sku');
    }
}
