<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\AttributesProcessor;

use Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\ChannelAttributeItem;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;

class UpdateService
{
    private \Magento\Catalog\Model\ResourceModel\Attribute $attributeResource;

    public function __construct(\Magento\Catalog\Model\ResourceModel\Attribute $attributeResource)
    {
        $this->attributeResource = $attributeResource;
    }

    public function updateAttribute(
        AbstractAttribute $attribute,
        ChannelAttributeItem $channelAttribute
    ) {
        $originalOptions = $attribute->getSource()->getAllOptions();
        $originalOptionValues = array_map(
            function ($option) {
                return $option['label'] ?? null;
            },
            $originalOptions
        );

        foreach ($channelAttribute->getAttributeValues() as $attributeValue) {
            if (!in_array($attributeValue, $originalOptionValues)) {
                $this->saveOption($attribute, $attributeValue, $originalOptions);
            }
        }
    }

    private function saveOption(AbstractAttribute $attribute, $value, array $originalOptions)
    {
        $attribute->setOption(
            [
                'value' => ['option_0' => [$value]],
                'order' => ['option_0' => count($originalOptions) + 1],
            ]
        );

        $this->attributeResource->save($attribute);
    }
}
