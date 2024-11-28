<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Specific\Form\Element;

use Ess\M2ePro\Model\Ebay\Template\Category\Specific;
use Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Specific\Form\Element\Dictionary\Multiselect as Multi;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;

class Dictionary extends \Magento\Framework\Data\Form\Element\AbstractElement
{
    public static bool $isSetMappedAttribute = false;

    /** @var \Ess\M2ePro\Model\Ebay\AttributeMapping\Pair[] */
    private array $gpsrMappedAttributesByCode;
    private \Magento\Framework\View\LayoutInterface $layout;
    private \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper;
    private \Ess\M2ePro\Model\Ebay\AttributeMapping\GpsrService $mappingAttributesGpsrService;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\AttributeMapping\GpsrService $mappingAttributesGpsrService,
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        $data = []
    ) {
        $this->magentoAttributeHelper = $magentoAttributeHelper;
        $this->layout = $context->getLayout();
        $this->setType('specifics');
        $this->mappingAttributesGpsrService = $mappingAttributesGpsrService;
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
    }

    // ----------------------------------------

    public function getElementHtml()
    {
        return '';
    }

    public function getSpecifics()
    {
        return $this->getData('specifics');
    }

    public function getAttributeTitleHiddenHtml($index, $specific)
    {
        $element = $this->_factoryElement->create('hidden', [
            'data' => [
                'name' => 'specific[dictionary_' . $index . '][attribute_title]',
                'class' => 'M2ePro-dictionary-specific-attribute-title',
                'value' => $specific['title'],
            ],
        ]);
        $element->setForm($this->getForm());
        $element->setId('specific_dictionary_attribute_title_' . $index);

        return $element->getElementHtml();
    }

    public function getModeHtml($index)
    {
        $element = $this->_factoryElement->create('hidden', [
            'data' => [
                'name' => 'specific[dictionary_' . $index . '][mode]',
                'class' => 'specific_mode',
                'value' => Specific::MODE_ITEM_SPECIFICS,
            ],
        ]);

        $element->setForm($this->getForm());
        $element->setId('specific_dictionary_mode_' . $index);

        return $element->getElementHtml();
    }

    public function getAttributeTitleLabelHtml($index, $specific): string
    {
        $required = '';
        if ($specific['required']) {
            $required = '&nbsp;<span class="required">*</span>';
        }

        return <<<HTML
    <span id="specific_dictionary_attribute_title_label_{$index}">{$specific['title']}{$required}</span>
HTML;
    }

    public function getValueModeSelectHtml($index, $specific)
    {
        $values = [
            Specific::VALUE_MODE_NONE => [
                'value' => Specific::VALUE_MODE_NONE,
                'label' => __('None'),
            ],
            Specific::VALUE_MODE_EBAY_RECOMMENDED => [
                'value' => Specific::VALUE_MODE_EBAY_RECOMMENDED,
                'label' => __('eBay Recommended'),
            ],
            Specific::VALUE_MODE_CUSTOM_VALUE => [
                'value' => Specific::VALUE_MODE_CUSTOM_VALUE,
                'label' => __('Custom Value'),
            ],
            Specific::VALUE_MODE_CUSTOM_ATTRIBUTE => [
                'value' => Specific::VALUE_MODE_CUSTOM_ATTRIBUTE,
                'label' => __('Custom Attribute'),
            ],
        ];

        if ($specific['required']) {
            $values[Specific::VALUE_MODE_NONE] = [
                'label' => '',
                'value' => '',
                'style' => 'display: none',
            ];
        }

        if ($specific['type'] === Specific::RENDER_TYPE_TEXT) {
            unset($values[Specific::VALUE_MODE_EBAY_RECOMMENDED]);
        }

        if (
            $specific['type'] === Specific::RENDER_TYPE_SELECT_ONE
            || $specific['type'] === Specific::RENDER_TYPE_SELECT_MULTIPLE
        ) {
            unset($values[Specific::VALUE_MODE_CUSTOM_VALUE]);
        }

        if (empty($specific['values'])) {
            if (
                $specific['type'] === Specific::RENDER_TYPE_SELECT_ONE_OR_TEXT
                || $specific['type'] === Specific::RENDER_TYPE_SELECT_MULTIPLE_OR_TEXT
            ) {
                unset($values[Specific::VALUE_MODE_EBAY_RECOMMENDED]);
            }
        }

        $value = null;
        if ($this->isConfiguredSpecific($specific)) {
            $value = $specific['template_specific']['value_mode'];
        } elseif ($this->isExistMappedAttribute($specific['id'])) {
            $mode = $this->getGpsrAttributeMode($specific['id']);
            $value = $mode === \Ess\M2ePro\Model\AttributeMapping\Pair::VALUE_MODE_ATTRIBUTE ?
                Specific::VALUE_MODE_CUSTOM_ATTRIBUTE : Specific::VALUE_MODE_CUSTOM_VALUE;
        }

        /** @var \Magento\Framework\Data\Form\Element\Select $element */
        $element = $this->_factoryElement->create('select', [
            'data' => [
                'name' => 'specific[dictionary_' . $index . '][value_mode]',
                'class' => 'specific-value-mode',
                'style' => 'width: 100%',
                'onchange' => "EbayTemplateCategorySpecificsObj.dictionarySpecificModeChange('{$index}', this);",
                'value' => $value,
                'values' => $values,
            ],
        ]);

        $element->setNoSpan(true);
        $element->setClass('M2ePro-required-when-visible');
        $element->setForm($this->getForm());
        $element->setId('specific_dictionary_value_mode_' . $index);

        return $element->getHtml();
    }

    public function getValueEbayRecomendedHtml($index, $specific)
    {
        $values = [];
        foreach ($specific['values'] as $value) {
            $values[] = [
                'label' => $value['value'],
                'value' => $value['value'],
            ];
        }

        $display = 'display: none;';
        $disabled = true;
        if (
            isset($specific['template_specific']['value_mode']) &&
            $specific['template_specific']['value_mode'] == Specific::VALUE_MODE_EBAY_RECOMMENDED
        ) {
            $display = '';
            $disabled = false;
        }

        if (
            $specific['type'] == Specific::RENDER_TYPE_SELECT_MULTIPLE ||
            $specific['type'] == Specific::RENDER_TYPE_SELECT_MULTIPLE_OR_TEXT
        ) {
            /** @var \Magento\Framework\Data\Form\Element\Select $element */
            $element = $this->_factoryElement->create(
                Multi::class,
                [
                    'data' => [
                        'name' => 'specific[dictionary_' . $index . '][value_ebay_recommended][]',
                        'style' => 'width: 100%;' . $display,
                        'value' => empty($specific['template_specific']['value_ebay_recommended'])
                            ? []
                            : \Ess\M2ePro\Helper\Json::decode(
                                $specific['template_specific']['value_ebay_recommended']
                            ),
                        'values' => $values,
                        'data-min_values' => $specific['min_values'],
                        'data-max_values' => $specific['max_values'],
                        'disabled' => $disabled,
                    ],
                ]
            );
        } else {
            array_unshift(
                $values,
                [
                    'label' => '',
                    'value' => '',
                    'style' => 'display: none',
                ]
            );

            /** @var \Magento\Framework\Data\Form\Element\Select $element */
            $element = $this->_factoryElement->create('select', [
                'data' => [
                    'name' => 'specific[dictionary_' . $index . '][value_ebay_recommended]',
                    'style' => 'width: 100%;' . $display,
                    'value' => empty($specific['template_specific']['value_ebay_recommended'])
                        ? ''
                        : \Ess\M2ePro\Helper\Json::decode($specific['template_specific']['value_ebay_recommended']),
                    'values' => $values,
                    'disabled' => $disabled,
                ],
            ]);
        }

        $element->addClass('M2ePro-required-when-visible');
        $element->setNoSpan(true);
        $element->setForm($this->getForm());
        $element->setId('specific_dictionary_value_ebay_recommended_' . $index);

        return $element->getHtml();
    }

    public function getValueCustomValueHtml($index, $specific): string
    {
        $addMoreTxt = __('Add more');

        $customValueRows = '';

        if (empty($specific['template_specific']['value_custom_value'])) {
            $customValues = [''];
        } else {
            $customValues = \Ess\M2ePro\Helper\Json::decode($specific['template_specific']['value_custom_value']);
        }

        if (!is_array($customValues)) {
            $customValues = [''];
        }

        $display = 'display: none;';
        $disabled = true;
        if (
            isset($specific['template_specific']['value_mode']) &&
            $specific['template_specific']['value_mode'] == Specific::VALUE_MODE_CUSTOM_VALUE
        ) {
            $display = '';
            $disabled = false;
        }

        if (
            !$this->isConfiguredSpecific($specific)
            && $this->isExistMappedAttribute($specific['id'])
            && $this->getMappedAttributeMode($specific['id']) === \Ess\M2ePro\Model\AttributeMapping\Pair::VALUE_MODE_CUSTOM
        ) {
            $display = '';
            $disabled = false;
            $customValues = [$this->getMappedAttributeValue($specific['id'])];
            self::$isSetMappedAttribute = true;
        }

        $displayRemoveBtn = 'display: none;';
        if ($specific['max_values'] > 1 && count($customValues) > 1 && count($customValues) < $specific['max_values']) {
            $displayRemoveBtn = '';
        }

        $customIndex = 0;
        foreach ($customValues as $value) {
            /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Button $removeCustomValueBtn */
            $removeCustomValueBtn = $this->layout->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
                                                 ->setData(
                                                     [
                                                         'label' => __('Remove'),
                                                         'onclick' => 'EbayTemplateCategorySpecificsObj.removeItemSpecificsCustomValue(this);',
                                                         'class' => 'action remove_item_specifics_custom_value_button',
                                                         'style' => 'margin-left: 3px;',
                                                     ]
                                                 );

            /** @var \Magento\Framework\Data\Form\Element\Text $element */
            $element = $this->_factoryElement->create('text', [
                'data' => [
                    'name' => 'specific[dictionary_' . $index . '][value_custom_value][]',
                    'style' => 'width: 100%;',
                    'class' => 'M2ePro-required-when-visible item-specific',
                    'value' => $value,
                    'disabled' => $disabled,
                ],
            ]);
            $element->setNoSpan(true);
            $element->setForm($this->getForm());
            $element->setId('specific_dictionary_value_custom_value_' . $index . '_' . $customIndex);

            $customValueRows .= <<<HTML
    <tr>
        <td style="border: none; width: 100%; vertical-align:top; text-align: left; padding: 0; ">
            {$element->getHtml()}
        </td>
        <td class="btn_value_remove" style="padding:0; border: none; {$displayRemoveBtn}">
            {$removeCustomValueBtn->toHtml()}
        </td>
    </tr>
HTML;

            $customIndex++;
        }

        $displayAddBtn = 'display: none;';
        if ($specific['max_values'] > 1 && count($customValues) < $specific['max_values']) {
            $displayAddBtn = '';
        }

        return <<<HTML
    <div id="specific_dictionary_custom_value_table_{$index}" style="{$display}">
        <table style="border:none; border-collapse: separate; border-spacing: 0 2px; width: 100%">
            <tbody id="specific_dictionary_custom_value_table_body_{$index}"
            data-min_values="{$specific['min_values']}"
            data-max_values="{$specific['max_values']}">
                {$customValueRows}
            </tbody>
        </table>
        <a href="javascript: void(0);"
           style="float:right; font-size:11px; {$displayAddBtn}"
           onclick="EbayTemplateCategorySpecificsObj.addItemSpecificsCustomValueRow({$index},this);">
            {$addMoreTxt}
        </a>
    </div>
HTML;
    }

    public function getValueCustomAttributeHtml($index, $specific)
    {
        $attributes = $this->magentoAttributeHelper->getAll();

        foreach ($attributes as &$attribute) {
            $attribute['value'] = $attribute['code'];
            unset($attribute['code']);
        }

        $display = 'display: none;';
        $disabled = true;
        $value = '';
        if (
            isset($specific['template_specific']['value_mode']) &&
            $specific['template_specific']['value_mode'] == Specific::VALUE_MODE_CUSTOM_ATTRIBUTE
        ) {
            $display = '';
            $disabled = false;
            $value = $specific['template_specific']['value_custom_attribute'];
        }

        if (
            !$this->isConfiguredSpecific($specific)
            && $this->isExistMappedAttribute($specific['id'])
            && $this->getMappedAttributeMode($specific['id']) === \Ess\M2ePro\Model\AttributeMapping\Pair::VALUE_MODE_ATTRIBUTE
        ) {
            $display = '';
            $disabled = false;
            $value = $this->getMappedAttributeValue($specific['id']);
            self::$isSetMappedAttribute = true;
        }

        /** @var \Magento\Framework\Data\Form\Element\Select $element */
        $element = $this->_factoryElement->create('select', [
            'data' => [
                'name' => 'specific[dictionary_' . $index . '][value_custom_attribute]',
                'style' => 'width: 100%;' . $display,
                'class' => 'M2ePro-custom-attribute-can-be-created',
                'value' => $value,
                'values' => $attributes,
                'apply_to_all_attribute_sets' => 0,
                'disabled' => $disabled,
            ],
        ]);

        $element->setNoSpan(true);
        $element->setForm($this->getForm());
        $element->setId('specific_dictionary_value_custom_attribute_' . $index);

        return $element->getHtml();
    }

    private function isConfiguredSpecific(array $specific): bool
    {
        return isset($specific['template_specific']['value_mode'])
            && $specific['template_specific']['value_mode'] != Specific::VALUE_MODE_NONE;
    }

    private function isExistMappedAttribute(string $code): bool
    {
        return $this->isGpsrAttribute($code);
    }

    private function getMappedAttributeValue(string $code): string
    {
        if ($this->isGpsrAttribute($code)) {
            return $this->getGpsrAttributeValue($code);
        }

        return '';
    }

    private function getMappedAttributeMode(string $code): ?int
    {
        if ($this->isGpsrAttribute($code)) {
            return $this->getGpsrAttributeMode($code);
        }

        return null;
    }

    private function isGpsrAttribute(string $code): bool
    {
        return $this->getGpsrAttribute($code) !== null;
    }

    private function getGpsrAttribute(string $code): ?\Ess\M2ePro\Model\Ebay\AttributeMapping\Pair
    {
        if (!isset($this->gpsrMappedAttributesByCode)) {
            $attributes = [];
            foreach ($this->mappingAttributesGpsrService->getConfigured() as $pair) {
                $attributes[$pair->channelAttributeCode] = $pair;
            }

            $this->gpsrMappedAttributesByCode = $attributes;
        }

        return $this->gpsrMappedAttributesByCode[$code] ?? null;
    }

    private function getGpsrAttributeValue(string $code): string
    {
        $pair = $this->getGpsrAttribute($code);

        if ($pair === null) {
            return '';
        }

        return $pair->value;
    }

    private function getGpsrAttributeMode(string $code): ?int
    {
        $pair = $this->getGpsrAttribute($code);

        if ($pair === null) {
            return null;
        }

        return $pair->mode;
    }
}
