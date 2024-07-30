<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs;

class Mapping extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    private \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\FormDataLoader $formDataLoader;
    private \Ess\M2ePro\Helper\Magento\Attribute $attributeHelper;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\FormDataLoader $formDataLoader,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Magento\Attribute $attributeHelper,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->formDataLoader = $formDataLoader;
        $this->attributeHelper = $attributeHelper;
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create([
            'data' => [
                'method' => 'post',
                'action' => $this->getUrl('*/*/save'),
            ],
        ]);

        $fieldset = $form->addFieldset(
            'information',
            [
                'legend' => __('Variation Option Mapping for Bundle Products'),
                'collapsable' => false,
            ]
        );

        $this->addMappingFields(
            $fieldset,
            $this->formDataLoader->getBundleOptions()
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _beforeToHtml()
    {
        $this->jsUrl->add(
            $this->getUrl('*/ebay_settings_mapping/save'),
            \Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs::TAB_ID_MAPPING
        );

        return parent::_beforeToHtml();
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\Fieldset $fieldset
     * @param \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\FormDataLoader\FormOption[] $options
     *
     * @return void
     */
    private function addMappingFields(
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset,
        array $options
    ): void {
        $attributesTextType = $this->attributeHelper->filterAllAttrByInputTypes(['text', 'select']);

        $preparedAttributes = [];
        foreach ($attributesTextType as $attribute) {
            $preparedAttributes[] = [
                'value' => $attribute['code'],
                'label' => $attribute['label'],
            ];
        }

        foreach ($options as $option) {
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
