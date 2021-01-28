<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Settings\Tabs;

use Ess\M2ePro\Helper\Component\Walmart\Configuration as ConfigurationHelper;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Settings\Tabs\Main
 */
class Main extends \Ess\M2ePro\Block\Adminhtml\Settings\Tabs\AbstractTab
{
    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        if (!$this->getHelper('Module\Wizard')->isActive(\Ess\M2ePro\Helper\View\Walmart::WIZARD_INSTALLATION_NICK)) {
            $form->addField(
                'walmart_settings_main_help',
                self::HELP_BLOCK,
                [
                    'content' => $this->__(
                        <<<HTML
In this section, you can configure the general settings for interaction between
M2E Pro and Walmart Marketplaces including SKU, Product Identifiers, image URL settings.
Click <strong>Save</strong> after the changes are made.
HTML
                    )
                ]
            );
        }

        /** @var \Ess\M2ePro\Helper\Component\Walmart\Configuration $configurationHelper */
        $configurationHelper = $this->getHelper('Component_Walmart_Configuration');

        /** @var \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper */
        $magentoAttributeHelper = $this->getHelper('Magento_Attribute');

        $textAttributes = $magentoAttributeHelper->filterByInputTypes(
            $magentoAttributeHelper->getAll(),
            ['text']
        );

        // SKU Settings
        $fieldset = $form->addFieldset(
            'sku_settings_fieldset',
            [
                'legend'      => $this->__('SKU Settings'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'sku_custom_attribute',
            'hidden',
            [
                'name'  => 'sku_custom_attribute',
                'value' => $configurationHelper->getSkuCustomAttribute()
            ]
        );

        $preparedAttributes = [];
        foreach ($textAttributes as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if ($configurationHelper->isSkuModeCustomAttribute() &&
                $attribute['code'] == $configurationHelper->getSkuCustomAttribute()) {
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
                'name'                     => 'sku_mode',
                'label'                    => $this->__('Source'),
                'values'                   => [
                    ConfigurationHelper::SKU_MODE_PRODUCT_ID => $this->__('Product ID'),
                    ConfigurationHelper::SKU_MODE_DEFAULT    => $this->__('Product SKU'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value'                    => !$configurationHelper->isSkuModeCustomAttribute(
                ) ? $configurationHelper->getSkuMode() : '',
                'create_magento_attribute' => true,
                'tooltip'                  => $this->__(
                    'SKU is a unique identifier for each Item in your catalog. Select Attribute where the SKU values
                     are stored.<br/>
                     <b>Note:</b> SKU is required when you create a new offer on Walmart.
                     Must be less than 50 characters.'
                )
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField(
            'sku_modification_mode',
            self::SELECT,
            [
                'label'   => $this->__('Modification'),
                'name'    => 'sku_modification_mode',
                'values'  => [
                    ConfigurationHelper::SKU_MODIFICATION_MODE_NONE     => $this->__('None'),
                    ConfigurationHelper::SKU_MODIFICATION_MODE_PREFIX   => $this->__('Prefix'),
                    ConfigurationHelper::SKU_MODIFICATION_MODE_POSTFIX  => $this->__('Postfix'),
                    ConfigurationHelper::SKU_MODIFICATION_MODE_TEMPLATE => $this->__('Template'),
                ],
                'value'   => $configurationHelper->getSkuModificationMode(),
                'tooltip' => $this->__(<<<HTML
    Select one of the available options to modify the SKU value taken from the Source Attribute.
HTML
                )
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
                'container_id'           => 'sku_modification_custom_value_tr',
                'label'                  => $this->__('Modification Value'),
                'field_extra_attributes' => $fieldStyle,
                'name'                   => 'sku_modification_custom_value',
                'value'                  => $configurationHelper->getSkuModificationCustomValue(),
                'class'                  => 'M2ePro-validate-sku-modification-custom-value
                            M2ePro-validate-sku-modification-custom-value-max-length',
                'required'               => true
            ]
        );

        $fieldset->addField(
            'generate_sku_mode',
            self::SELECT,
            [
                'label'   => $this->__('Generate'),
                'name'    => 'generate_sku_mode',
                'values'  => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes')
                ],
                'value'   => $configurationHelper->getGenerateSkuMode(),
                'tooltip' => $this->__(
                    'Enable to automatically generate another SKU value if Item SKU that you submit to the Channel
                    already exists in your Walmart Inventory.'
                )
            ]
        );

        // Identifiers
        // UPC
        $fieldset = $form->addFieldset(
            'identifiers_settings_fieldset',
            [
                'legend'      => $this->__('Product Identifiers'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'upc_custom_attribute',
            'hidden',
            [
                'name'  => 'upc_custom_attribute',
                'value' => $configurationHelper->getUpcCustomAttribute()
            ]
        );

        $preparedAttributes = [];

        $warningToolTip = '';

        if ($configurationHelper->isUpcModeCustomAttribute() &&
            !$magentoAttributeHelper->isExistInAttributesArray(
                $configurationHelper->getUpcCustomAttribute(),
                $textAttributes
            ) && $this->getData('upc_custom_attribute') != '') {
            $warningText = $this->__(
                <<<HTML
    Selected Magento Attribute is invalid.
    Please ensure that the Attribute exists in your Magento, has a relevant Input Type and it
    is included in all Attribute Sets.
    Otherwise, select a different Attribute from the drop-down.
HTML
            );

            $warningToolTip = $this->__(
                <<<HTML
<span class="fix-magento-tooltip m2e-tooltip-grid-warning">
    {$this->getTooltipHtml($warningText)}
</span>
HTML
            );

            $attrs = ['attribute_code' => $configurationHelper->getUpcCustomAttribute()];
            $attrs['selected'] = 'selected';
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => ConfigurationHelper::UPC_MODE_CUSTOM_ATTRIBUTE,
                'label' => $magentoAttributeHelper->getAttributeLabel($configurationHelper->getUpcCustomAttribute())
            ];
        }

        foreach ($textAttributes as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if ($configurationHelper->isUpcModeCustomAttribute() &&
                $attribute['code'] == $configurationHelper->getUpcCustomAttribute()) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => ConfigurationHelper::UPC_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'upc_mode',
            self::SELECT,
            [
                'name'                     => 'upc_mode',
                'label'                    => $this->__('UPC'),
                'class'                    => 'M2ePro-walmart-required-identifier-setting',
                'values'                   => [
                    ConfigurationHelper::UPC_MODE_NOT_SET => $this->__('Not Set'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value'                    => !$configurationHelper->isUpcModeCustomAttribute(
                ) ? $configurationHelper->getUpcMode() : '',
                'create_magento_attribute' => true,
                'tooltip'                  => $this->__(
                    'Walmart uses Product IDs to associate your Item with its catalog. Select Attribute where the UPC
                     values are stored.<br>
                     <strong>Note:</strong> At least one Product ID has to be specified when you create
                     a new offer on Walmart.'
                ),
                'after_element_html'       => $warningToolTip
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        // EAN
        $fieldset->addField(
            'ean_custom_attribute',
            'hidden',
            [
                'name'  => 'ean_custom_attribute',
                'value' => $configurationHelper->getEanCustomAttribute()
            ]
        );

        $preparedAttributes = [];

        $warningToolTip = '';

        if ($configurationHelper->isEanModeCustomAttribute() &&
            !$magentoAttributeHelper->isExistInAttributesArray(
                $configurationHelper->getEanCustomAttribute(),
                $textAttributes
            ) && $this->getData('ean_custom_attribute') != '') {
            $warningText = $this->__(
                <<<HTML
    Selected Magento Attribute is invalid.
    Please ensure that the Attribute exists in your Magento, has a relevant Input Type and it
    is included in all Attribute Sets.
    Otherwise, select a different Attribute from the drop-down.
HTML
            );

            $warningToolTip = $this->__(
                <<<HTML
<span class="fix-magento-tooltip m2e-tooltip-grid-warning">
    {$this->getTooltipHtml($warningText)}
</span>
HTML
            );

            $attrs = ['attribute_code' => $configurationHelper->getEanCustomAttribute()];
            $attrs['selected'] = 'selected';
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => ConfigurationHelper::EAN_MODE_CUSTOM_ATTRIBUTE,
                'label' => $magentoAttributeHelper->getAttributeLabel($configurationHelper->getEanCustomAttribute())
            ];
        }

        foreach ($textAttributes as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if ($configurationHelper->isEanModeCustomAttribute() &&
                $attribute['code'] == $configurationHelper->getEanCustomAttribute()) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => ConfigurationHelper::EAN_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'ean_mode',
            self::SELECT,
            [
                'name'                     => 'ean_mode',
                'label'                    => $this->__('EAN'),
                'class'                    => 'M2ePro-walmart-required-identifier-setting',
                'values'                   => [
                    ConfigurationHelper::EAN_MODE_NOT_SET => $this->__('Not Set'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value'                    => !$configurationHelper->isEanModeCustomAttribute(
                ) ? $configurationHelper->getEanMode() : '',
                'create_magento_attribute' => true,
                'tooltip'                  => $this->__(
                    'Walmart uses Product IDs to associate your Item with its catalog. Select Attribute where the EAN
                     values are stored.<br>
                    <strong>Note:</strong> At least one Product ID has to be specified when you create a new
                    offer on Walmart.'
                ),
                'after_element_html'       => $warningToolTip
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        // GTIN
        $fieldset->addField(
            'gtin_custom_attribute',
            'hidden',
            [
                'name'  => 'gtin_custom_attribute',
                'value' => $configurationHelper->getGtinCustomAttribute()
            ]
        );

        $preparedAttributes = [];

        $warningToolTip = '';

        if ($configurationHelper->isGtinModeCustomAttribute() &&
            !$magentoAttributeHelper->isExistInAttributesArray(
                $configurationHelper->getGtinCustomAttribute(),
                $textAttributes
            ) && $this->getData('gtin_custom_attribute') != '') {
            $warningText = $this->__(
                <<<HTML
    Selected Magento Attribute is invalid.
    Please ensure that the Attribute exists in your Magento, has a relevant Input Type and it
    is included in all Attribute Sets.
    Otherwise, select a different Attribute from the drop-down.
HTML
            );

            $warningToolTip = $this->__(
                <<<HTML
<span class="fix-magento-tooltip m2e-tooltip-grid-warning">
    {$this->getTooltipHtml($warningText)}
</span>
HTML
            );

            $attrs = ['attribute_code' => $configurationHelper->getGtinCustomAttribute()];
            $attrs['selected'] = 'selected';
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => ConfigurationHelper::GTIN_MODE_CUSTOM_ATTRIBUTE,
                'label' => $magentoAttributeHelper->getAttributeLabel($configurationHelper->getGtinCustomAttribute())
            ];
        }

        foreach ($textAttributes as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if ($configurationHelper->isGtinModeCustomAttribute() &&
                $attribute['code'] == $configurationHelper->getGtinCustomAttribute()) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => ConfigurationHelper::GTIN_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'gtin_mode',
            self::SELECT,
            [
                'name'                     => 'gtin_mode',
                'label'                    => $this->__('GTIN'),
                'class'                    => 'M2ePro-walmart-required-identifier-setting',
                'values'                   => [
                    ConfigurationHelper::GTIN_MODE_NOT_SET => $this->__('Not Set'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value'                    => !$configurationHelper->isGtinModeCustomAttribute() ?
                    $configurationHelper->getGtinMode() : '',
                'create_magento_attribute' => true,
                'tooltip'                  => $this->__(
                    'Walmart uses Product IDs to associate your Item with its catalog. Select Attribute where the GTIN
                     values are stored.<br>
                    <strong>Note:</strong> At least one Product ID has to be specified when you create a new
                    offer on Walmart.'
                ),
                'after_element_html'       => $warningToolTip
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        // ISBN
        $fieldset->addField(
            'isbn_custom_attribute',
            'hidden',
            [
                'name'  => 'isbn_custom_attribute',
                'value' => $configurationHelper->getIsbnCustomAttribute()
            ]
        );

        $preparedAttributes = [];

        $warningToolTip = '';

        if ($configurationHelper->isIsbnModeCustomAttribute() &&
            !$magentoAttributeHelper->isExistInAttributesArray(
                $configurationHelper->getIsbnCustomAttribute(),
                $textAttributes
            ) && $this->getData('isbn_custom_attribute') != '') {
            $warningText = $this->__(
                <<<HTML
    Selected Magento Attribute is invalid.
    Please ensure that the Attribute exists in your Magento, has a relevant Input Type and it
    is included in all Attribute Sets.
    Otherwise, select a different Attribute from the drop-down.
HTML
            );

            $warningToolTip = $this->__(
                <<<HTML
<span class="fix-magento-tooltip m2e-tooltip-grid-warning">
    {$this->getTooltipHtml($warningText)}
</span>
HTML
            );

            $attrs = ['attribute_code' => $configurationHelper->getIsbnCustomAttribute()];
            $attrs['selected'] = 'selected';
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => ConfigurationHelper::ISBN_MODE_CUSTOM_ATTRIBUTE,
                'label' => $magentoAttributeHelper->getAttributeLabel($configurationHelper->getIsbnCustomAttribute())
            ];
        }

        foreach ($textAttributes as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if ($configurationHelper->isIsbnModeCustomAttribute() &&
                $attribute['code'] == $configurationHelper->getIsbnCustomAttribute()) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => ConfigurationHelper::ISBN_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'isbn_mode',
            self::SELECT,
            [
                'name'                     => 'isbn_mode',
                'label'                    => $this->__('ISBN'),
                'class'                    => 'M2ePro-walmart-required-identifier-setting',
                'values'                   => [
                    ConfigurationHelper::ISBN_MODE_NOT_SET => $this->__('Not Set'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value'                    => !$configurationHelper->isIsbnModeCustomAttribute() ?
                    $configurationHelper->getIsbnMode() : '',
                'create_magento_attribute' => true,
                'tooltip'                  => $this->__(
                    'Walmart uses Product IDs to associate your Item with its catalog. Select Attribute where the ISBN
                     values are stored.<br>
                     <strong>Note:</strong> At least one Product ID has to be specified when you create a new
                     offer on Walmart.'
                ),
                'after_element_html'       => $warningToolTip
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField(
            'product_id_override_mode',
            self::SELECT,
            [
                'name'    => 'product_id_override_mode',
                'label'   => $this->__('Product ID Override'),
                'values'  => [
                    ConfigurationHelper::PRODUCT_ID_OVERRIDE_MODE_NONE              => $this->__('Not Set'),
                    ConfigurationHelper::PRODUCT_ID_OVERRIDE_MODE_ALL               => $this->__('All products'),
                    ConfigurationHelper::PRODUCT_ID_OVERRIDE_MODE_SPECIFIC_PRODUCTS => $this->__('Specific products'),
                ],
                'value'   => $configurationHelper->getProductIdOverrideMode(),
                'tooltip' => $this->__(
                    <<<HTML
    <b>None</b> - all products will be listed with the standard Product IDs.<br/>
    <b>All products</b> - Product ID exemption will be applied to all products.<br/>
    <b>Specific products</b> - Product ID exemption will be applied to products that have a value
    “CUSTOM” in Product ID attribute.<br/><br/>
    
    <b>Note:</b> You must apply for Product ID exemption on Walmart first.
HTML
                )
            ]
        );

        // Advanced
        $fieldset = $form->addFieldset(
            'advanced',
            [
                'legend' => $this->__('Advanced')
            ]
        );

        $fieldset->addField(
            'option_images_url_mode',
            'select',
            [
                'name'    => 'option_images_url_mode',
                'label'   => $this->__('Image(s) URL'),
                'values'  => [
                    ConfigurationHelper::OPTION_IMAGES_URL_MODE_ORIGINAL => $this->__('Original'),
                    ConfigurationHelper::OPTION_IMAGES_URL_MODE_HTTPS    => $this->__(' Replace with HTTPS'),
                    ConfigurationHelper::OPTION_IMAGES_URL_MODE_HTTP     => $this->__('Replace with HTTP')
                ],
                'value'   => $configurationHelper->getOptionImagesURLMode(),
                'tooltip' => $this->__(
                    <<<HTML
    <p>Select how to upload images to Walmart:</p><br>
    <p><strong>Original</strong> - images will be uploaded based on the Magento settings.</p>
    <p><strong>Replace with HTTP</strong> - images will be uploaded using HTTP protocol.</p>
    <p><strong>Replace with HTTPS</strong> - images will be uploaded using HTTPS protocol.</p>
HTML
                )
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->jsTranslator->add(
            'Required at least one identifier',
            $this->__('Required at least one identifier')
        );

        $this->jsUrl->add(
            $this->getUrl('*/walmart_settings/save'),
            \Ess\M2ePro\Block\Adminhtml\Walmart\Settings\Tabs::TAB_ID_MAIN
        );

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Helper\Component\Walmart\Configuration::class)
        );

        $this->js->add(
            <<<JS
require([
    'M2ePro/Walmart/Settings/Main'
], function(){

    window.WalmartSettingsMainObj = new WalmartSettingsMain();

    $('sku_mode').observe('change', WalmartSettingsMainObj.sku_mode_change);
    $('sku_modification_mode').observe('change', WalmartSettingsMainObj.sku_modification_mode_change);

    $('upc_mode').observe('change', WalmartSettingsMainObj.upc_mode_change);
    $('ean_mode').observe('change', WalmartSettingsMainObj.ean_mode_change);
    $('gtin_mode').observe('change', WalmartSettingsMainObj.gtin_mode_change);
    $('isbn_mode').observe('change', WalmartSettingsMainObj.isbn_mode_change);
});
JS
        );

        return parent::_beforeToHtml();
    }

    //########################################

    protected function getGlobalNotice()
    {
        return '';
    }

    //########################################
}
