<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs\AttributeMapping;

class GroupedAttributesFieldsetFill
{
    private \Ess\M2ePro\Helper\Magento\Attribute $attributeHelper;
    private \Ess\M2ePro\Model\Ebay\AttributeMapping\GroupedService $groupedProductService;

    public function __construct(
        \Ess\M2ePro\Helper\Magento\Attribute $attributeHelper,
        \Ess\M2ePro\Model\Ebay\AttributeMapping\GroupedService $groupedProductService
    ) {
        $this->attributeHelper = $attributeHelper;
        $this->groupedProductService = $groupedProductService;
    }

    public function fill(\Magento\Framework\Data\Form\Element\Fieldset $fieldset): void
    {
        $attributesTextType = $this->attributeHelper->filterAllAttrByInputTypes(['text', 'select']);

        $preparedAttributes = [];
        foreach ($attributesTextType as $attribute) {
            $preparedAttributes[] = [
                'value' => $attribute['code'],
                'label' => $attribute['label'],
            ];
        }

        foreach ($this->groupedProductService->getAll() as $pair) {
            $config = [
                'label' => $pair->channelAttributeTitle,
                'title' => $pair->channelAttributeTitle,
                'name' => sprintf('grouped_attributes[%s]', $pair->channelAttributeCode),
                'values' => [
                    '' => __('None'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                    ],
                ],
                'value' => $pair->value ?? '',
            ];

            $fieldset->addField($pair->channelAttributeCode, 'select', $config);
        }
    }
}
