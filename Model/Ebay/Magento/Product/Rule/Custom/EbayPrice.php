<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Magento\Product\Rule\Custom;

class EbayPrice extends \Ess\M2ePro\Model\Magento\Product\Rule\Custom\AbstractModel
{
    public function getAttributeCode(): string
    {
        return 'ebay_online_current_price';
    }

    public function getLabel(): string
    {
        return (string)__('Price');
    }

    public function getInputType(): string
    {
        return 'price';
    }

    public function getValueByProductInstance(\Magento\Catalog\Model\Product $product)
    {
        $minPrice = $product->getData('min_online_price')
            ?? $product->getData('online_current_price');

        $maxPrice = $product->getData('max_online_price')
            ?? $product->getData('online_current_price');

        if (!empty($minPrice) && !empty($maxPrice) && $minPrice != $maxPrice) {
            return [
                $minPrice,
                $maxPrice,
            ];
        }

        return $minPrice;
    }
}
