<?php

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Settings\Tabs;

class AttributeMapping extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    private \Ess\M2ePro\Block\Adminhtml\Walmart\Settings\Tabs\AttributeMapping\VariationAttributesFieldsetFill $variationAttributesFieldsetFill;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Walmart\Settings\Tabs\AttributeMapping\VariationAttributesFieldsetFill $variationAttributesFieldsetFill,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->variationAttributesFieldsetFill = $variationAttributesFieldsetFill;
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create([
            'data' => [
                'method' => 'post',
                'action' => $this->getUrl('*/*/save'),
            ],
        ]);

        // ----------------------------------------

        $this->addVariationAttributesFieldset($form);

        // ----------------------------------------

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _beforeToHtml()
    {
        $this->jsUrl->add(
            $this->getUrl('*/walmart_settings_attributeMapping/save'),
            \Ess\M2ePro\Block\Adminhtml\Walmart\Settings\Tabs::TAB_ID_ATTRIBUTE_MAPPING
        );

        return parent::_beforeToHtml();
    }

    private function addVariationAttributesFieldset(\Magento\Framework\Data\Form $form): void
    {
        $tooltipMessage = __(
            'In the Variation Attributes section of the Attribute Mapping settings, ' .
            'you can set default mappings between your Magento variation attribute options and the valid ' .
            'variation options on Walmart. For example, if your product has a variation attribute like "Color," ' .
            'you can ensure that the specific variation options (e.g., "black," "white," "yellow," "blue") ' .
            'in Magento are accurately matched to the corresponding options on Walmart. This helps to ' .
            'streamline the listing and management of product variations across both platforms.'
        );

        $fieldset = $form->addFieldset('variation_attributes', [
            'legend' => __('Variation Attributes'),
            'tooltip' => $tooltipMessage,
            'collapsable' => true,
        ]);

        $this->variationAttributesFieldsetFill->fill($fieldset);
    }
}
