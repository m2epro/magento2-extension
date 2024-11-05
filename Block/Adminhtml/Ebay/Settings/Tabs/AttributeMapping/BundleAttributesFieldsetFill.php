<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs\AttributeMapping;

class BundleAttributesFieldsetFill
{
    private \Ess\M2ePro\Helper\Magento\Attribute $attributeHelper;
    private \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\FormDataLoader $formDataLoader;

    public function __construct(
        \Ess\M2ePro\Helper\Magento\Attribute $attributeHelper,
        \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\FormDataLoader $formDataLoader
    ) {
        $this->attributeHelper = $attributeHelper;
        $this->formDataLoader = $formDataLoader;
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

        foreach ($this->formDataLoader->getBundleOptions() as $option) {
            $config = [
                'label' => $option->getTitle(),
                'title' => $option->getTitle(),
                'name' => $option->getFieldName(),
                'values' => [
                    '' => __('None'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                    ],
                ],
                'value' => $option->getMappingAttributeCode() ?? '',
            ];

            $fieldset->addField($option->getFieldElementId(), 'select', $config);
        }
    }
}
