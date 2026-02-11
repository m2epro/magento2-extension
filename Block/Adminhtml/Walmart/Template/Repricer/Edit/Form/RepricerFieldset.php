<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\Repricer\Edit\Form;

use Ess\M2ePro\Model\Walmart\Template\Repricer as RepricerPolicy;

class RepricerFieldset
{
    private \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper;

    public function __construct(\Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper)
    {
        $this->magentoAttributeHelper = $magentoAttributeHelper;
    }

    public function add(
        \Magento\Framework\Data\Form $form,
        array $formData
    ) {
        $fieldset = $this->createFieldset($form);
        $this->addStrategyField($fieldset, $formData);
        $this->addMinPriceFields($fieldset, $formData);
        $this->addMaxPriceFields($fieldset, $formData);
    }

    private function createFieldset(\Magento\Framework\Data\Form $form): \Magento\Framework\Data\Form\Element\Fieldset
    {
        return $form->addFieldset(
            'magento_block_walmart_template_repricer_repricer_fieldset',
            [
                'legend' => __('Repricer'),
                'class' => '',
            ]
        );
    }

    private function addStrategyField(
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset,
        array $formData
    ) {
        $refreshButtonHtml = sprintf(
            '<button type="button" class="primary refresh-strategies">%s</button>',
            __('Refresh')
        );

        $fieldset->addField(
            'strategy_name',
            'hidden',
            [
                'name' => 'strategy_name',
                'value' => $formData['strategy_name'],
            ]
        );

        $fieldset->addField(
            'strategy_name_select',
            'select',
            [
                'label' => __('Strategy'),
                'required' => 'true',
                'class' => 'M2ePro-required-when-visible',
                'after_element_html' => $refreshButtonHtml,
            ]
        );
    }

    private function addMinPriceFields(
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset,
        array $formData
    ) {
        $minPriceMode = $formData['min_price_mode'];
        $minPriceAttribute = $formData['min_price_attribute'];

        $fieldset->addField(
            'min_price_attribute',
            'hidden',
            [
                'name' => 'min_price_attribute',
                'value' => $minPriceAttribute,
            ]
        );

        $preparedAttributes = [];
        foreach ($this->getAttributeOptions() as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $minPriceMode == RepricerPolicy::REPRICER_MIN_MAX_PRICE_MODE_ATTRIBUTE
                && $minPriceAttribute == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => RepricerPolicy::REPRICER_MIN_MAX_PRICE_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'min_price_mode',
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm::SELECT,
            [
                'name' => 'min_price_mode',
                'label' => __('Min Price'),
                'title' => __('Min Price'),
                'values' => [
                    RepricerPolicy::REPRICER_MIN_MAX_PRICE_MODE_NONE => __('None'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'required' => true,
                'class' => 'select',
                'create_magento_attribute' => true,
                'tooltip' => __('Select Magento attribute with the lowest price your product can be repriced to.'),
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,price');
    }

    private function addMaxPriceFields(
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset,
        array $formData
    ) {
        $maxPriceMode = $formData['max_price_mode'];
        $maxPriceAttribute = $formData['max_price_attribute'];

        $fieldset->addField(
            'max_price_attribute',
            'hidden',
            [
                'name' => 'max_price_attribute',
                'value' => $maxPriceAttribute,
            ]
        );

        $preparedAttributes = [];
        foreach ($this->getAttributeOptions() as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $maxPriceMode == RepricerPolicy::REPRICER_MIN_MAX_PRICE_MODE_ATTRIBUTE
                && $maxPriceAttribute == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => RepricerPolicy::REPRICER_MIN_MAX_PRICE_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'max_price_mode',
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm::SELECT,
            [
                'name' => 'max_price_mode',
                'label' => __('Max Price'),
                'title' => __('Max Price'),
                'values' => [
                    RepricerPolicy::REPRICER_MIN_MAX_PRICE_MODE_NONE => __('None'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'required' => true,
                'class' => 'select',
                'create_magento_attribute' => true,
                'tooltip' => __('Select Magento attribute with the highest price your product can be repriced to.'),
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,price');
    }

    private function getAttributeOptions(): array
    {
        return $this->magentoAttributeHelper
            ->filterAllAttrByInputTypes(['text', 'price']);
    }
}
