<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Magento\Product\Rule\Custom;

class EbayStatus extends \Ess\M2ePro\Model\Magento\Product\Rule\Custom\AbstractModel
{
    public function getAttributeCode(): string
    {
        return 'ebay_status';
    }

    public function getLabel(): string
    {
        return (string)__('Status');
    }

    public function getValueByProductInstance(\Magento\Catalog\Model\Product $product)
    {
        return $product->getData('ebay_status')
            ?? $product->getData('status');
    }

    public function getInputType(): string
    {
        return 'select';
    }

    public function getValueElementType(): string
    {
        return 'select';
    }

    public function getOptions(): array
    {
        return [
            [
                'value' => \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED,
                'label' => __('Not Listed'),
            ],
            [
                'value' => \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED,
                'label' => __('Listed'),
            ],
            [
                'value' => \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN,
                'label' => __('Listed (Hidden)'),
            ],
            [
                'value' => \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED,
                'label' => __('Pending'),
            ],
            [
                'value' => \Ess\M2ePro\Model\Listing\Product::STATUS_INACTIVE,
                'label' => __('Inactive'),
            ],
        ];
    }
}
