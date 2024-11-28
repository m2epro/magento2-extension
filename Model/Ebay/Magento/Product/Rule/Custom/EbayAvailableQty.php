<?php

namespace Ess\M2ePro\Model\Ebay\Magento\Product\Rule\Custom;

class EbayAvailableQty extends \Ess\M2ePro\Model\Magento\Product\Rule\Custom\AbstractModel
{
    public function getAttributeCode(): string
    {
        return 'ebay_available_qty';
    }

    public function getLabel(): string
    {
        return (string)__('Available QTY');
    }

    public function getValueByProductInstance(\Magento\Catalog\Model\Product $product)
    {
        return $product->getData('available_qty')
            ?? $product->getData('online_qty');
    }
}
