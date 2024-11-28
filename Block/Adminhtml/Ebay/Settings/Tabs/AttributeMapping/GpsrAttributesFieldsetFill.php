<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs\AttributeMapping;

use Ess\M2ePro\Model\Ebay\AttributeMapping\Gpsr\Provider;

class GpsrAttributesFieldsetFill
{
    private const FIELDSET_ATTRIBUTE_MAPPING = [
        'manufacturer_details' => [
            'title' => 'Manufacturer Contact Details',
            'attributes' => [
                Provider::ATTR_MANUFACTURER_COMPANY_NAME,
                Provider::ATTR_MANUFACTURER_STREET_1,
                Provider::ATTR_MANUFACTURER_STREET_2,
                Provider::ATTR_MANUFACTURER_CITY_NAME,
                Provider::ATTR_MANUFACTURER_STATE_OR_PROVINCE,
                Provider::ATTR_MANUFACTURER_POSTAL_CODE,
                Provider::ATTR_MANUFACTURER_COUNTRY_CODE,
                Provider::ATTR_MANUFACTURER_EMAIL,
                Provider::ATTR_MANUFACTURER_PHONE,
            ],
        ],
        'responsible_person_details' => [
            'title' => 'EU Responsible Person Contact Details',
            'attributes' => [
                Provider::ATTR_RESPONSIBLE_PERSON_COMPANY_NAME,
                Provider::ATTR_RESPONSIBLE_PERSON_STREET_1,
                Provider::ATTR_RESPONSIBLE_PERSON_STREET_2,
                Provider::ATTR_RESPONSIBLE_PERSON_CITY_NAME,
                Provider::ATTR_RESPONSIBLE_PERSON_STATE_OR_PROVINCE,
                Provider::ATTR_RESPONSIBLE_PERSON_POSTAL_CODE,
                Provider::ATTR_RESPONSIBLE_PERSON_COUNTRY_CODE,
                Provider::ATTR_RESPONSIBLE_PERSON_EMAIL,
                Provider::ATTR_RESPONSIBLE_PERSON_PHONE,
                Provider::ATTR_RESPONSIBLE_PERSON_CODE_TYPES,
            ],
        ],
    ];

    private \Ess\M2ePro\Helper\Magento\Attribute $attributeHelper;
    private \Ess\M2ePro\Model\Ebay\AttributeMapping\GpsrService $gpsrService;

    public function __construct(
        \Ess\M2ePro\Helper\Magento\Attribute $attributeHelper,
        \Ess\M2ePro\Model\Ebay\AttributeMapping\GpsrService $gpsrService
    ) {
        $this->attributeHelper = $attributeHelper;
        $this->gpsrService = $gpsrService;
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

        $gpsrAttributes = $this->gpsrService->getAll();
        $groupedAttributes = [];
        foreach ($gpsrAttributes as $pair) {
            $groupedAttributes[$pair->channelAttributeCode] = $pair;
        }

        foreach (self::FIELDSET_ATTRIBUTE_MAPPING as $fieldsetId => $fieldsetData) {
            $subFieldset = $fieldset->addFieldset($fieldsetId, [
                'legend' => __($fieldsetData['title']),
                'collapsable' => false,
            ]);

            foreach ($groupedAttributes as $channelAttributeCode => $pair) {
                if (!in_array($channelAttributeCode, $fieldsetData['attributes'], true)) {
                    continue;
                }

                $subFieldset->addField($pair->channelAttributeCode . '_mode', 'select', [
                    'label' => $pair->channelAttributeTitle,
                    'name' => sprintf('gpsr_attributes[%s][mode]', $pair->channelAttributeCode),
                    'values' => [
                        \Ess\M2ePro\Model\AttributeMapping\Pair::VALUE_MODE_NONE => __('None'),
                        \Ess\M2ePro\Model\AttributeMapping\Pair::VALUE_MODE_CUSTOM => __('Custom Value'),
                        \Ess\M2ePro\Model\AttributeMapping\Pair::VALUE_MODE_ATTRIBUTE => __('Custom Attribute'),
                    ],
                    'value' => $pair->mode,
                    'onchange' => sprintf(
                        "EbaySettingsObj.onModeChange('%s', this.value)",
                        str_replace(' ', '_', $pair->channelAttributeCode)
                    ),
                    'after_element_html' => $this->getFieldsHtml($preparedAttributes, $pair)
                ]);
            }
        }
    }

    private function getFieldsHtml(array $preparedAttributes, \Ess\M2ePro\Model\Ebay\AttributeMapping\Pair $pair): string
    {
        $attrHtml = $this->getAttributesInputHtml($preparedAttributes, $pair);
        $customHtml = $this->getCustomValueInputHtml($pair);

        return $attrHtml . $customHtml;
    }
    private function getCustomValueInputHtml(\Ess\M2ePro\Model\Ebay\AttributeMapping\Pair $pair): string
    {
        $id = str_replace(' ', '_', $pair->channelAttributeCode) . '_custom_value';
        $attributes = [
            'type' => 'text',
            'style' => '',
            'id' => $id,
            'name' => sprintf('gpsr_attributes[%s][value]', $pair->channelAttributeCode),
            'value' => $pair->isValueModeCustom() ? $pair->value : '',
            'placeholder' => __('Enter value here'),
        ];

        if (
            !$pair->isValueModeCustom()
        ) {
            $attributes['style'] .= 'display:none';
            $attributes['disabled'] = 'disabled';
        }

        return '<input ' . $this->renderHtmlAttributes($attributes) . '>';
    }

    private function renderHtmlAttributes(array $attributes): string
    {
        $preparedAttributes = [];
        foreach ($attributes as $attributeName => $attributeValue) {
            $preparedAttributes[] = sprintf(
                '%s="%s"',
                $attributeName,
                $attributeValue
            );
        }

        return implode(' ', $preparedAttributes);
    }

    private function getAttributesInputHtml(array $preparedAttributes, \Ess\M2ePro\Model\Ebay\AttributeMapping\Pair $pair): string
    {
        $optionsHtml = '';

        $style = $pair->isValueModeAttribute() ? '' : 'display:none;';
        $disabled = $pair->isValueModeAttribute() ? '' : 'disabled';

        foreach ($preparedAttributes as $attribute) {
            $selected = $attribute['value'] === $pair->value ? ' selected' : '';
            $optionsHtml .= sprintf(
                '<option value="%s"%s>%s</option>',
                $attribute['value'],
                $selected,
                $attribute['label']
            );
        }

        return sprintf(
            '<select name="gpsr_attributes[%s][value]" id="%s" style="%s" %s>%s</select>',
            $pair->channelAttributeCode,
            str_replace(' ', '_', $pair->channelAttributeCode) . '_custom_attribute',
            $style,
            $disabled,
            $optionsHtml
        );
    }
}
