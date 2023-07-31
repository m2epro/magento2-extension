<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Settings\Tabs;

use Ess\M2ePro\Helper\Component\Walmart\Configuration as ConfigurationHelper;

class Main extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /** @var \Ess\M2ePro\Helper\Magento\Attribute */
    protected $magentoAttributeHelper;

    /** @var \Ess\M2ePro\Helper\Component\Walmart\Configuration */
    private $walmartConfigurationHelper;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Helper\Module\Wizard */
    private $wizardHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Component\Walmart\Configuration $walmartConfigurationHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Module\Wizard $wizardHelper,
        array $data = []
    ) {
        $this->magentoAttributeHelper = $magentoAttributeHelper;
        $this->walmartConfigurationHelper = $walmartConfigurationHelper;
        $this->dataHelper = $dataHelper;
        $this->wizardHelper = $wizardHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        if (!$this->wizardHelper->isActive(\Ess\M2ePro\Helper\View\Walmart::WIZARD_INSTALLATION_NICK)) {
            $form->addField(
                'walmart_settings_main_help',
                self::HELP_BLOCK,
                [
                    'content' => __(
                        <<<HTML
In this section, you can configure the general settings for interaction between
M2E Pro and Walmart Marketplaces including SKU, Product Identifier, image URL settings.
Click <strong>Save</strong> after the changes are made.
HTML
                    ),
                ]
            );
        }

        $configurationHelper = $this->walmartConfigurationHelper;

        $textAttributes = $this->magentoAttributeHelper->filterByInputTypes(
            $this->magentoAttributeHelper->getAll(),
            ['text']
        );

        // SKU Settings
        $fieldset = $form->addFieldset(
            'sku_settings_fieldset',
            [
                'legend' => __('SKU Settings'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'sku_custom_attribute',
            'hidden',
            [
                'name' => 'sku_custom_attribute',
                'value' => $configurationHelper->getSkuCustomAttribute(),
            ]
        );

        $preparedAttributes = [];
        foreach ($textAttributes as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $configurationHelper->isSkuModeCustomAttribute() &&
                $attribute['code'] == $configurationHelper->getSkuCustomAttribute()
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => ConfigurationHelper::SKU_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'sku_mode',
            self::SELECT,
            [
                'name' => 'sku_mode',
                'label' => __('Source'),
                'values' => [
                    ConfigurationHelper::SKU_MODE_PRODUCT_ID => __('Product ID'),
                    ConfigurationHelper::SKU_MODE_DEFAULT => __('Product SKU'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'value' => !$configurationHelper->isSkuModeCustomAttribute() ? $configurationHelper->getSkuMode() : '',
                'create_magento_attribute' => true,
                'tooltip' => __(
                    'SKU is a unique identifier for each Item in your catalog. Select Attribute where the SKU values
                     are stored.<br/>
                     <b>Note:</b> SKU is required when you create a new offer on Walmart.
                     Must be less than 50 characters.'
                ),
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField(
            'sku_modification_mode',
            self::SELECT,
            [
                'label' => __('Modification'),
                'name' => 'sku_modification_mode',
                'values' => [
                    ConfigurationHelper::SKU_MODIFICATION_MODE_NONE => __('None'),
                    ConfigurationHelper::SKU_MODIFICATION_MODE_PREFIX => __('Prefix'),
                    ConfigurationHelper::SKU_MODIFICATION_MODE_POSTFIX => __('Postfix'),
                    ConfigurationHelper::SKU_MODIFICATION_MODE_TEMPLATE => __('Template'),
                ],
                'value' => $configurationHelper->getSkuModificationMode(),
                'tooltip' => __(
                    <<<HTML
    Select one of the available options to modify the SKU value taken from the Source Attribute.
HTML
                ),
            ]
        );

        $fieldStyle = '';
        if ($configurationHelper->isSkuModificationModeNone()) {
            $fieldStyle = 'style="display: none"';
        }

        $fieldset->addField(
            'sku_modification_custom_value',
            'text',
            [
                'container_id' => 'sku_modification_custom_value_tr',
                'label' => __('Modification Value'),
                'field_extra_attributes' => $fieldStyle,
                'name' => 'sku_modification_custom_value',
                'value' => $configurationHelper->getSkuModificationCustomValue(),
                'class' => 'M2ePro-validate-sku-modification-custom-value
                            M2ePro-validate-sku-modification-custom-value-max-length',
                'required' => true,
            ]
        );

        $fieldset->addField(
            'generate_sku_mode',
            self::SELECT,
            [
                'label' => __('Generate'),
                'name' => 'generate_sku_mode',
                'values' => [
                    0 => __('No'),
                    1 => __('Yes'),
                ],
                'value' => $configurationHelper->getGenerateSkuMode(),
                'tooltip' => __(
                    'Enable to automatically generate another SKU value if Item SKU that you submit to the Channel
                    already exists in your Walmart Inventory.'
                ),
            ]
        );

        // Identifier
        $fieldset = $form->addFieldset(
            'identifiers_settings_fieldset',
            [
                'legend' => __('Product Identifier'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'product_id_custom_attribute',
            'hidden',
            [
                'name' => 'product_id_custom_attribute',
                'value' => $configurationHelper->getProductIdCustomAttribute(),
            ]
        );

        $preparedAttributes = [];

        $warningToolTip = '';

        if (
            $configurationHelper->isProductIdModeCustomAttribute() &&
            !$this->magentoAttributeHelper->isExistInAttributesArray(
                $configurationHelper->getProductIdCustomAttribute(),
                $textAttributes
            ) && $this->getData('product_id_custom_attribute') != ''
        ) {
            $warningText = __(
                <<<HTML
    Selected Magento Attribute is invalid.
    Please ensure that the Attribute exists in your Magento, has a relevant Input Type and it
    is included in all Attribute Sets.
    Otherwise, select a different Attribute from the drop-down.
HTML
            );

            $warningToolTip = __(
                <<<HTML
<span class="fix-magento-tooltip m2e-tooltip-grid-warning">
    {$this->getTooltipHtml($warningText)}
</span>
HTML
            );

            $attrs = ['attribute_code' => $configurationHelper->getProductIdCustomAttribute()];
            $attrs['selected'] = 'selected';
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => ConfigurationHelper::PRODUCT_ID_MODE_CUSTOM_ATTRIBUTE,
                'label' => $this->magentoAttributeHelper
                    ->getAttributeLabel($configurationHelper->getProductIdCustomAttribute()),
            ];
        }

        foreach ($textAttributes as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];

            if (
                $configurationHelper->isProductIdModeCustomAttribute() &&
                $attribute['code'] == $configurationHelper->getProductIdCustomAttribute()
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => ConfigurationHelper::PRODUCT_ID_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'product_id_mode',
            self::SELECT,
            [
                'name' => 'product_id_mode',
                'label' => __('Product ID'),
                'class' => 'M2ePro-walmart-required-identifier-setting',
                'values' => [
                    ConfigurationHelper::PRODUCT_ID_MODE_NOT_SET => __('Not Set'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'value' => !$configurationHelper->isProductIdModeCustomAttribute()
                    ? $configurationHelper->getProductIdMode()
                    : '',
                'create_magento_attribute' => true,
                'tooltip' => __(
                    'Walmart uses Product IDs to associate your Item with its catalog. Select Attribute where the
                     Product ID values are stored.<br>
                     <strong>Note:</strong> At least one Product ID has to be specified when you create
                     a new offer on Walmart.'
                ),
                'after_element_html' => $warningToolTip,
                'required' => true,
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField(
            'product_id_override_mode',
            self::SELECT,
            [
                'name' => 'product_id_override_mode',
                'label' => __('Product ID Override'),
                'values' => [
                    ConfigurationHelper::PRODUCT_ID_OVERRIDE_MODE_NONE => __('Not Set'),
                    ConfigurationHelper::PRODUCT_ID_OVERRIDE_MODE_ALL => __('All products'),
                    ConfigurationHelper::PRODUCT_ID_OVERRIDE_MODE_SPECIFIC_PRODUCTS => __('Specific products'),
                ],
                'value' => $configurationHelper->getProductIdOverrideMode(),
                'tooltip' => __(
                    <<<HTML
    <b>None</b> - all products will be listed with the standard Product IDs.<br/>
    <b>All products</b> - Product ID exemption will be applied to all products.<br/>
    <b>Specific products</b> - Product ID exemption will be applied to products that have a value
    “CUSTOM” in Product ID attribute.<br/><br/>

    <b>Note:</b> You must apply for Product ID exemption on Walmart first.
HTML
                ),
            ]
        );

        // Advanced
        $fieldset = $form->addFieldset(
            'advanced',
            [
                'legend' => __('Advanced'),
            ]
        );

        $fieldset->addField(
            'option_images_url_mode',
            'select',
            [
                'name' => 'option_images_url_mode',
                'label' => __('Image(s) URL'),
                'values' => [
                    ConfigurationHelper::OPTION_IMAGES_URL_MODE_ORIGINAL => __('Original'),
                    ConfigurationHelper::OPTION_IMAGES_URL_MODE_HTTPS => __(' Replace with HTTPS'),
                    ConfigurationHelper::OPTION_IMAGES_URL_MODE_HTTP => __('Replace with HTTP'),
                ],
                'value' => $configurationHelper->getOptionImagesURLMode(),
                'tooltip' => __(
                    <<<HTML
    <p>Select how to upload images to Walmart:</p><br>
    <p><strong>Original</strong> - images will be uploaded based on the Magento settings.</p>
    <p><strong>Replace with HTTP</strong> - images will be uploaded using HTTP protocol.</p>
    <p><strong>Replace with HTTPS</strong> - images will be uploaded using HTTPS protocol.</p>
HTML
                ),
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _beforeToHtml()
    {
        $this->jsTranslator->add(
            'Required identifier',
            __('Required identifier')
        );

        $this->jsUrl->add(
            $this->getUrl('*/walmart_settings/save'),
            \Ess\M2ePro\Block\Adminhtml\Walmart\Settings\Tabs::TAB_ID_MAIN
        );

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Helper\Component\Walmart\Configuration::class)
        );

        $this->js->add(
            <<<JS
require([
    'M2ePro/Walmart/Settings/Main'
], function(){

    window.WalmartSettingsMainObj = new WalmartSettingsMain();

    $('sku_mode').observe('change', WalmartSettingsMainObj.sku_mode_change);
    $('sku_modification_mode').observe('change', WalmartSettingsMainObj.sku_modification_mode_change);

    $('product_id_mode').observe('change', WalmartSettingsMainObj.product_id_mode_change);
});
JS
        );

        return parent::_beforeToHtml();
    }

    protected function getGlobalNotice()
    {
        return '';
    }
}
