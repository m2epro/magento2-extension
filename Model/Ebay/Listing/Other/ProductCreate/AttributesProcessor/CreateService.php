<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\AttributesProcessor;

use Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\ChannelAttributeItem;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

class CreateService
{
    private const ATTRIBUTE_GROUP = 'product details';

    private \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory $attributeFactory;
    private \Magento\Catalog\Model\ResourceModel\Attribute $attributeResource;
    private \Magento\Eav\Api\AttributeManagementInterface $attributeManagement;
    private \Magento\Catalog\Model\Config $catalogConfig;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory $attributeFactory,
        \Magento\Catalog\Model\ResourceModel\Attribute $attributeResource,
        \Magento\Eav\Api\AttributeManagementInterface $attributeManagement,
        \Magento\Catalog\Model\Config $catalogConfig
    ) {
        $this->attributeFactory = $attributeFactory;
        $this->attributeResource = $attributeResource;
        $this->catalogConfig = $catalogConfig;
        $this->attributeManagement = $attributeManagement;
    }

    public function createMagentoAttribute(ChannelAttributeItem $channelAttribute): AbstractAttribute
    {
        $attribute = $this->attributeFactory->create();
        $attribute->setEntityTypeId(4);
        $attribute->setAttributeCode($channelAttribute->getAttributeCode());
        $attribute->setFrontendInput('select');
        $attribute->setDefaultFrontendLabel($channelAttribute->getAttributeLabel());
        $attribute->setScope(ScopedAttributeInterface::SCOPE_GLOBAL);
        $attribute->setBackendType('int');
        $attribute->setIsUserDefined(1);
        $attribute->setOption(
            $this->createAttributeOptions(
                $channelAttribute->getAttributeValues()
            )
        );

        $this->attributeResource->save($attribute);
        $this->assignAttributeToAttributeSet($attribute);

        return $attribute;
    }

    private function createAttributeOptions(array $channelOptions): array
    {
        $options = [];
        $count = 0;
        foreach ($channelOptions as $channelOptionValue) {
            $options['value']['option_' . $count] = [$channelOptionValue];
            $options['order']['option_' . $count] = $count + 1;
            $count++;
        }

        return $options;
    }

    private function assignAttributeToAttributeSet(AbstractAttribute $attribute): void
    {
        $attributeSetId = $this->catalogConfig
            ->getEntityType(\Magento\Catalog\Model\Product::ENTITY)
            ->getDefaultAttributeSetId();
        $groupId = $this->catalogConfig->getAttributeGroupId($attributeSetId, self::ATTRIBUTE_GROUP);
        $this->attributeManagement->assign(
            \Magento\Catalog\Model\Product::ENTITY,
            $attributeSetId,
            $groupId,
            $attribute->getAttributeCode(),
            999
        );
    }
}
