<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Settings\Tabs;

use Ess\M2ePro\Helper\Component\Amazon\Configuration;

class General extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    private Configuration $config;
    private \Ess\M2ePro\Helper\Module\Support $support;
    private \Ess\M2ePro\Helper\Magento\Attribute $attributeHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\Configuration $config,
        \Ess\M2ePro\Helper\Module\Support $support,
        \Ess\M2ePro\Helper\Magento\Attribute $attributeHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->config = $config;
        $this->support = $support;
        $this->attributeHelper = $attributeHelper;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Amazon\Settings\Tabs\General
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        $settingsHelp = __(
            'In this section, you can configure the general settings for the interaction between M2E Pro ' .
            'and Amazon Marketplaces.<br/><br/>Specify Product Identifier values for your Amazon items at a ' .
            'global level.<br/>If you prefer to set product IDs per listing, please navigate to ' .
            'Listings > Items > Edit Settings > Selling > Product Identifiers.<br/><br/>' .
            'Enable <a href="%url" target="_blank" class="external-link">Amazon Business (B2B)</a> to apply the ' .
            'Business Price and QTY Discounts to your offers on the selected marketplaces.',
            [
                'url' => $this->support->getDocumentationArticleUrl('help/m2/amazon-integration/amazon-business')
            ]
        );

        $form->addField(
            'amazon_settings_main_help',
            self::HELP_BLOCK,
            [
                'content' => $settingsHelp,
            ]
        );

        $fieldset = $form->addFieldset(
            'product_identifiers',
            [
                'legend' => __('Product Identifiers'),
                'collapsable' => false,
            ]
        );

        $attributesTextType = $this->attributeHelper->filterAllAttrByInputTypes(['text']);
        $preparedAttributes = [];
        $warningToolTip = '';

        if (
            $this->config->isGeneralIdModeCustomAttribute() &&
            !$this->attributeHelper->isExistInAttributesArray(
                $this->config->getGeneralIdCustomAttribute(),
                $attributesTextType
            )
        ) {
            $warningToolTip = $this->getAttributeWarningTooltip();
        }

        foreach ($attributesTextType as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $this->config->isGeneralIdModeCustomAttribute() &&
                $this->config->getGeneralIdCustomAttribute() == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => Configuration::GENERAL_ID_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'general_id',
            self::SELECT,
            [
                'name' => 'general_id_mode',
                'label' => __('ASIN / ISBN'),
                'title' => __('ASIN / ISBN'),
                'values' => [
                    Configuration::GENERAL_ID_MODE_NONE => __('None'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => ['is_magento_attribute' => true],
                    ],
                ],
                'value' => !$this->config->isGeneralIdModeCustomAttribute() ? $this->config->getGeneralIdMode() : '',
                'create_magento_attribute' => true,
                'tooltip' => __('This setting is a source for ASIN/ISBN value which will be used at the ' .
                    'time of Automatic Search of Amazon Products.'),
                'after_element_html' => $warningToolTip,
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField(
            'general_id_custom_attribute',
            'hidden',
            [
                'name' => 'general_id_custom_attribute',
                'value' => $this->config->getGeneralIdCustomAttribute(),
            ]
        );

        $attributesTextType = $this->attributeHelper->filterAllAttrByInputTypes(['text']);
        $preparedAttributes = [];
        $warningToolTip = '';

        if (
            $this->config->isWorldwideIdModeCustomAttribute()
            && !$this->attributeHelper->isExistInAttributesArray(
                $this->config->getWorldwideCustomAttribute(),
                $attributesTextType
            )
        ) {
            $warningToolTip = $this->getAttributeWarningTooltip();
        }

        foreach ($attributesTextType as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $this->config->isWorldwideIdModeCustomAttribute()
                && $this->config->getWorldwideCustomAttribute() == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => Configuration::WORLDWIDE_ID_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'worldwide_id',
            self::SELECT,
            [
                'name' => 'worldwide_id_mode',
                'label' => __('UPC / EAN'),
                'title' => __('UPC / EAN'),
                'values' => [
                    Configuration::WORLDWIDE_ID_MODE_NONE => __('None'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => ['is_magento_attribute' => true],
                    ],
                ],
                'value' => !$this->config->isWorldwideIdModeCustomAttribute() ?
                    $this->config->getWorldwideIdMode() : '',
                'create_magento_attribute' => true,
                'tooltip' => __('Amazon uses these Product IDs to associate your Item with its catalog ' .
                    'or to create a new <b>ASIN/ISBN</b>.<br>Select the attribute where the <b>UPC/EAN</b> ' .
                    'values are stored.'),
                'after_element_html' => $warningToolTip,
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField(
            'worldwide_id_custom_attribute',
            'hidden',
            [
                'name' => 'worldwide_id_custom_attribute',
                'value' => $this->config->getWorldwideCustomAttribute(),
            ]
        );

        $fieldset = $form->addFieldset(
            'amazon_main',
            [
                'legend' => __('Amazon Business (B2B)'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'business_mode',
            self::SELECT,
            [
                'name' => 'business_mode',
                'label' => __('Price, QTY Discounts'),
                'values' => [
                    0 => __('Disabled'),
                    1 => __('Enabled'),
                ],
                'value' => $this->config->isEnabledBusinessMode(),
                'tooltip' => __('After you <strong>Enable</strong> this option, you can provide the settings ' .
                    'for <strong>Business Price</strong> and <strong>Quantity Discounts</strong> within M2E Pro ' .
                    'Selling Policy.<br/><strong>Note:</strong> your Business Account must be approved ' .
                    'by Amazon.<br/>Countries where customers shop on Amazon Business - <strong>US</strong>, ' .
                    '<strong>Canada</strong>, <strong>Mexico</strong>, <strong>UK</strong>, <strong>France</strong>, ' .
                    '<strong>Germany</strong>, <strong>Italy</strong>, <strong>Spain</strong>, ' .
                    '<strong>Japan</strong>, <strong>India</strong> and <strong>Australia</strong>'),
            ]
        );

        $this->appendCustomizationDetailsFieldset($form);

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * @return mixed
     */
    private function getAttributeWarningTooltip()
    {
        $warningText = __('Selected Magento Attribute is invalid. Please ensure that the Attribute exists ' .
            'in your Magento, has a relevant Input Type and it is included in all Attribute Sets. ' .
            'Otherwise, select a different Attribute from the drop-down.');

        return sprintf(
            '<span class="fix-magento-tooltip m2e-tooltip-grid-warning">%s</span>',
            $this->getTooltipHtml($warningText)
        );
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Amazon\Settings\Tabs\General
     */
    protected function _beforeToHtml()
    {
        $this->jsUrl->add(
            $this->getUrl('*/amazon_settings/save'),
            \Ess\M2ePro\Block\Adminhtml\Amazon\Settings\Tabs::TAB_ID_GENERAL
        );

        $this->js->add(
            <<<JS
            require([
                'M2ePro/Amazon/Settings/Main'
            ], function(){
                window.AmazonSettingsMainObj = new AmazonSettingsMain();
                window.AmazonSettingsMainObj.initObservers();
            });
JS
        );

        return parent::_beforeToHtml();
    }

    private function appendCustomizationDetailsFieldset(\Magento\Framework\Data\Form $form): void
    {
        $fieldset = $form->addFieldset(
            'amazon_customization_details_fieldset',
            [
                'legend' => __('Amazon Custom'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'is_need_parse_buyer_customized_data',
            self::SELECT,
            [
                'name' => 'is_need_parse_buyer_customized_data',
                'label' => __('Import Customization Details to Magento Order'),
                'values' => [
                    0 => __('No'),
                    1 => __('Yes'),
                ],
                'value' => $this->config->getIsNeedParseBuyerCustomizedData(),
            ]
        );
    }
}
