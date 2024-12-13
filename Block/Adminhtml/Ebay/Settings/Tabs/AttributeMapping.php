<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs;

class AttributeMapping extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs\AttributeMapping\BundleAttributesFieldsetFill */
    private AttributeMapping\BundleAttributesFieldsetFill $variationOptionFieldsetFill;
    /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs\AttributeMapping\GpsrAttributesFieldsetFill */
    private AttributeMapping\GpsrAttributesFieldsetFill $gpsrAttributesFieldsetFill;
    /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs\AttributeMapping\GroupedAttributesFieldsetFill */
    private AttributeMapping\GroupedAttributesFieldsetFill $groupedAttributesFieldsetFill;

    public function __construct(
        AttributeMapping\BundleAttributesFieldsetFill $variationOptionFieldsetFill,
        AttributeMapping\GpsrAttributesFieldsetFill $gpsrAttributesFieldsetFill,
        AttributeMapping\GroupedAttributesFieldsetFill $groupedAttributesFieldsetFill,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->variationOptionFieldsetFill = $variationOptionFieldsetFill;
        $this->gpsrAttributesFieldsetFill = $gpsrAttributesFieldsetFill;
        $this->groupedAttributesFieldsetFill = $groupedAttributesFieldsetFill;

        parent::__construct($context, $registry, $formFactory, $data);
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
        $this->addGpsrAttributesFieldset($form);
        $this->addBundleAttributesFieldset($form);
        $this->addGroupedAttributesFieldset($form);
        // ----------------------------------------

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _beforeToHtml()
    {
        $this->jsUrl->add(
            $this->getUrl('*/ebay_settings_attributeMapping/save'),
            \Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs::TAB_ID_MAPPING_ATTRIBUTES
        );

        return parent::_beforeToHtml();
    }

    private function addGpsrAttributesFieldset(\Magento\Framework\Data\Form $form): void
    {
        $fieldset = $form->addFieldset(
            'gpsr_attributes',
            [
                'legend' => __('GPSR Attributes'),
                'tooltip' => __('The GPSR Attributes section allows you to define the default mapping between Magento attributes and eBay attributes for the GPSR (General Product Safety Regulation) fields. By setting these mappings, you can automatically apply the appropriate attributes to all eBay categories that require GPSR, simplifying the listing process and ensuring consistency across your products.'),
                'collapsable' => true,
            ]
        );
        $this->gpsrAttributesFieldsetFill->fill($fieldset);
    }

    private function addBundleAttributesFieldset(\Magento\Framework\Data\Form $form): void
    {
        $fieldset = $form->addFieldset(
            'bundle_attributes',
            [
                'legend' => __('Bundle Product Attributes'),
                'collapsable' => true,
            ]
        );
        $this->variationOptionFieldsetFill->fill($fieldset);
    }

    private function addGroupedAttributesFieldset(\Magento\Framework\Data\Form $form): void
    {
        $fieldset = $form->addFieldset(
            'grouped_attributes',
            [
                'legend' => __('Grouped Product Attributes'),
                'collapsable' => true,
            ]
        );
        $this->groupedAttributesFieldsetFill->fill($fieldset);
    }
}
