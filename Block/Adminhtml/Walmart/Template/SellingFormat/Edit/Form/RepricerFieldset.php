<?php

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\SellingFormat\Edit\Form;

use Ess\M2ePro\Model\Walmart\Template\SellingFormat as WalmartSellingFormat;

class RepricerFieldset
{
    private \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper;

    public function __construct(\Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper)
    {
        $this->magentoAttributeHelper = $magentoAttributeHelper;
    }

    public function add(
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Framework\Data\Form $form,
        array $formData
    ) {
        $fieldset = $this->createFieldset($form);
        $this->addStrategyField($layout, $fieldset, $formData);
        $this->addMinPriceFields($fieldset, $formData);
        $this->addMaxPriceFields($fieldset, $formData);
    }

    private function createFieldset(\Magento\Framework\Data\Form $form): \Magento\Framework\Data\Form\Element\Fieldset
    {
        return $form->addFieldset(
            'magento_block_walmart_template_selling_format_repricer',
            [
                'legend' => __('Repricer'),
                'class' => 'm2epro-marketplace-depended-block us-only'
            ]
        );
    }

    private function addStrategyField(
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset,
        array $formData
    ) {
        $repricerStrategyBlock = $layout->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Walmart\Template\SellingFormat\Edit\Form\RepricerStrategy::class,
            '',
            [
                'form' => $fieldset->getForm(),
                'formData' => $formData,
            ]
        );

        $fieldset->addField(
            'repricer_strategy',
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm::CUSTOM_CONTAINER,
            [
                'text' => $repricerStrategyBlock->toHtml(),
                'css_class' => 'repricer-strategy-field'
            ]
        );
    }

    private function addMinPriceFields(
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset,
        array $formData
    ) {
        $minPriceMode = $formData['repricer_min_price_mode'] ?? WalmartSellingFormat::REPRICER_MIN_MAX_PRICE_MODE_NONE;
        $minPriceAttribute = $formData['repricer_min_price_attribute'] ?? '';

        $fieldset->addField(
            'repricer_min_price_attribute',
            'hidden',
            [
                'name' => 'repricer_min_price_attribute',
                'value' => $minPriceAttribute,
            ]
        );

        $preparedAttributes = [];
        foreach ($this->getAttributeOptions() as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $minPriceMode == WalmartSellingFormat::REPRICER_MIN_MAX_PRICE_MODE_ATTRIBUTE
                && $minPriceAttribute == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => WalmartSellingFormat::REPRICER_MIN_MAX_PRICE_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'repricer_min_price_mode',
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm::SELECT,
            [
                'name' => 'repricer_min_price_mode',
                'label' => __('Min Price'),
                'title' => __('Min Price'),
                'values' => [
                    WalmartSellingFormat::REPRICER_MIN_MAX_PRICE_MODE_NONE => __('None'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'class' => 'select',
                'create_magento_attribute' => true,
                'tooltip' => __('Select Magento attribute with the lowest price your product can be repriced to.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,price');
    }

    private function addMaxPriceFields(
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset,
        array $formData
    ) {
        $maxPriceMode = $formData['repricer_max_price_mode'] ?? WalmartSellingFormat::REPRICER_MIN_MAX_PRICE_MODE_NONE;
        $maxPriceAttribute = $formData['repricer_max_price_attribute'] ?? '';

        $fieldset->addField(
            'repricer_max_price_attribute',
            'hidden',
            [
                'name' => 'repricer_max_price_attribute',
                'value' => $maxPriceAttribute,
            ]
        );

        $preparedAttributes = [];
        foreach ($this->getAttributeOptions() as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $maxPriceMode == WalmartSellingFormat::REPRICER_MIN_MAX_PRICE_MODE_ATTRIBUTE
                && $maxPriceAttribute == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => WalmartSellingFormat::REPRICER_MIN_MAX_PRICE_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'repricer_max_price_mode',
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm::SELECT,
            [
                'name' => 'repricer_max_price_mode',
                'label' => __('Max Price'),
                'title' => __('Max Price'),
                'values' => [
                    WalmartSellingFormat::REPRICER_MIN_MAX_PRICE_MODE_NONE => __('None'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'class' => 'select',
                'create_magento_attribute' => true,
                'tooltip' => __('Select Magento attribute with the highest price your product can be repriced to.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,price');
    }

    private function getAttributeOptions(): array
    {
        return $this->magentoAttributeHelper
            ->filterAllAttrByInputTypes(['text', 'price']);
    }
}
