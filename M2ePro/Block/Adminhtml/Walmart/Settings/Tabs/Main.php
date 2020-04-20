<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Settings\Tabs;

use \Ess\M2ePro\Helper\Component\Walmart\Configuration as HelperConfiguration;

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
                    'content' => $this->__(<<<HTML
In this section, you can configure the general settings for interaction between
M2E Pro and Walmart Marketplaces including SKU, Product Identifiers, image URL settings.
Click <strong>Save</strong> after the changes are made.
HTML
                    )
                ]
            );
        }

        /** @var \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper */
        $magentoAttributeHelper = $this->getHelper('Magento\Attribute');

        $allAttributes = $this->getHelper('Magento\Attribute')->getAll();
        $generalAttributes = $magentoAttributeHelper->getGeneralFromAllAttributeSets();

        $attributesByTypes = [
            'boolean' => $magentoAttributeHelper->filterByInputTypes(
                $allAttributes,
                ['boolean']
            ),
            'text' => $magentoAttributeHelper->filterByInputTypes(
                $generalAttributes,
                ['text']
            ),
            'text_textarea' => $magentoAttributeHelper->filterByInputTypes(
                $allAttributes,
                ['text', 'textarea']
            ),
            'text_date' => $magentoAttributeHelper->filterByInputTypes(
                $generalAttributes,
                ['text', 'date', 'datetime']
            ),
            'text_select' => $magentoAttributeHelper->filterByInputTypes(
                $generalAttributes,
                ['text', 'select']
            ),
            'text_images' => $magentoAttributeHelper->filterByInputTypes(
                $generalAttributes,
                ['text', 'image', 'media_image', 'gallery', 'multiline', 'textarea', 'select', 'multiselect']
            )
        ];
        $formData = $this->getHelper('Component_Walmart_Configuration')->getConfigValues();

        // SKU Settings
        $fieldset = $form->addFieldset(
            'sku_settings_fieldset',
            [
                'legend' => $this->__('SKU Settings'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'sku_custom_attribute',
            'hidden',
            [
                'name' => 'sku_custom_attribute',
                'value' => $formData['sku_custom_attribute']
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByTypes['text'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if ($formData['sku_mode'] == HelperConfiguration::SKU_MODE_CUSTOM_ATTRIBUTE
                && $attribute['code'] == $formData['sku_custom_attribute']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => HelperConfiguration::SKU_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'sku_mode',
            self::SELECT,
            [
                'name' => 'sku_mode',
                'label' => $this->__('Source'),
                'values' => [
                    HelperConfiguration::SKU_MODE_PRODUCT_ID => $this->__('Product ID'),
                    HelperConfiguration::SKU_MODE_DEFAULT => $this->__('Product SKU'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => $formData['sku_mode'] != HelperConfiguration::SKU_MODE_CUSTOM_ATTRIBUTE ?
                    $formData['sku_mode'] : '',
                'create_magento_attribute' => true,
                'tooltip' => $this->__(
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
                'label' => $this->__('Modification'),
                'name' => 'sku_modification_mode',
                'values' => [
                    HelperConfiguration::SKU_MODIFICATION_MODE_NONE => $this->__('None'),
                    HelperConfiguration::SKU_MODIFICATION_MODE_PREFIX => $this->__('Prefix'),
                    HelperConfiguration::SKU_MODIFICATION_MODE_POSTFIX => $this->__('Postfix'),
                    HelperConfiguration::SKU_MODIFICATION_MODE_TEMPLATE => $this->__('Template'),
                ],
                'value' => $formData['sku_modification_mode'],
                'tooltip' => $this->__(
                    'Select one of the available options to modify the SKU
                     value taken from the Source Attribute.'
                )
            ]
        );

        $fieldStyle = '';
        if ($formData['sku_modification_mode'] == HelperConfiguration::SKU_MODIFICATION_MODE_NONE) {
            $fieldStyle = 'style="display: none"';
        }

        $fieldset->addField(
            'sku_modification_custom_value',
            'text',
            [
                'container_id' => 'sku_modification_custom_value_tr',
                'label' => $this->__('Modification Value'),
                'field_extra_attributes' => $fieldStyle,
                'name' => 'sku_modification_custom_value',
                'value' => $formData['sku_modification_custom_value'],
                'class' => 'M2ePro-validate-sku-modification-custom-value
                            M2ePro-validate-sku-modification-custom-value-max-length',
                'required' => true
            ]
        );

        $fieldset->addField(
            'generate_sku_mode',
            self::SELECT,
            [
                'label' => $this->__('Generate'),
                'name' => 'generate_sku_mode',
                'values' => [
                    HelperConfiguration::GENERATE_SKU_MODE_NO => $this->__('No'),
                    HelperConfiguration::GENERATE_SKU_MODE_YES => $this->__('Yes')
                ],
                'value' => $formData['generate_sku_mode'],
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
                'legend' => $this->__('Product Identifiers'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'upc_custom_attribute',
            'hidden',
            [
                'name' => 'upc_custom_attribute',
                'value' => $formData['upc_custom_attribute']
            ]
        );

        $preparedAttributes = [];

        $warningToolTip = '';

        if ($formData['upc_mode'] == HelperConfiguration::UPC_MODE_CUSTOM_ATTRIBUTE &&
            !$magentoAttributeHelper->isExistInAttributesArray(
                $formData['upc_custom_attribute'],
                $attributesByTypes['text']
            ) &&
            $this->getData('upc_custom_attribute') != ''
        ) {
            $warningText = $this->__(<<<HTML
    Selected Magento Attribute is invalid.
    Please ensure that the Attribute exists in your Magento, has a relevant Input Type and it
    is included in all Attribute Sets.
    Otherwise, select a different Attribute from the drop-down.
HTML
            );

            $warningToolTip = $this->__(<<<HTML
<span class="fix-magento-tooltip m2e-tooltip-grid-warning">
    {$this->getTooltipHtml($warningText)}
</span>
HTML
            );

            $attrs = ['attribute_code' => $formData['upc_custom_attribute']];
            $attrs['selected'] = 'selected';
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => HelperConfiguration::UPC_MODE_CUSTOM_ATTRIBUTE,
                'label' => $magentoAttributeHelper->getAttributeLabel($formData['upc_custom_attribute'])
            ];
        }

        foreach ($attributesByTypes['text'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if ($formData['upc_mode'] == HelperConfiguration::UPC_MODE_CUSTOM_ATTRIBUTE
                && $attribute['code'] == $formData['upc_custom_attribute']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => HelperConfiguration::UPC_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'upc_mode',
            self::SELECT,
            [
                'name' => 'upc_mode',
                'label' => $this->__('UPC'),
                'class' => 'M2ePro-walmart-required-identifier-setting',
                'values' => [
                    HelperConfiguration::UPC_MODE_NOT_SET => $this->__('Not Set'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => $formData['upc_mode'] != HelperConfiguration::UPC_MODE_CUSTOM_ATTRIBUTE ?
                    $formData['upc_mode'] : '',
                'create_magento_attribute' => true,
                'tooltip' => $this->__(
                    'Walmart uses Product IDs to associate your Item with its catalog. Select Attribute where the UPC
                     values are stored.<br>
                     <strong>Note:</strong> At least one Product ID has to be specified when you create
                     a new offer on Walmart.'
                ),
                'after_element_html' => $warningToolTip
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        // EAN
        $fieldset->addField(
            'ean_custom_attribute',
            'hidden',
            [
                'name' => 'ean_custom_attribute',
                'value' => $formData['ean_custom_attribute']
            ]
        );

        $preparedAttributes = [];

        $warningToolTip = '';

        if ($formData['ean_mode'] == HelperConfiguration::EAN_MODE_CUSTOM_ATTRIBUTE &&
            !$magentoAttributeHelper->isExistInAttributesArray(
                $formData['ean_custom_attribute'],
                $attributesByTypes['text']
            ) &&
            $this->getData('ean_custom_attribute') != ''
        ) {
            $warningText = $this->__(<<<HTML
    Selected Magento Attribute is invalid.
    Please ensure that the Attribute exists in your Magento, has a relevant Input Type and it
    is included in all Attribute Sets.
    Otherwise, select a different Attribute from the drop-down.
HTML
            );

            $warningToolTip = $this->__(<<<HTML
<span class="fix-magento-tooltip m2e-tooltip-grid-warning">
    {$this->getTooltipHtml($warningText)}
</span>
HTML
            );

            $attrs = ['attribute_code' => $formData['ean_custom_attribute']];
            $attrs['selected'] = 'selected';
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => HelperConfiguration::EAN_MODE_CUSTOM_ATTRIBUTE,
                'label' => $magentoAttributeHelper->getAttributeLabel($formData['ean_custom_attribute'])
            ];
        }

        foreach ($attributesByTypes['text'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if ($formData['ean_mode'] == HelperConfiguration::EAN_MODE_CUSTOM_ATTRIBUTE
                && $attribute['code'] == $formData['ean_custom_attribute']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => HelperConfiguration::EAN_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'ean_mode',
            self::SELECT,
            [
                'name' => 'ean_mode',
                'label' => $this->__('EAN'),
                'class' => 'M2ePro-walmart-required-identifier-setting',
                'values' => [
                    HelperConfiguration::EAN_MODE_NOT_SET => $this->__('Not Set'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => $formData['ean_mode'] != HelperConfiguration::EAN_MODE_CUSTOM_ATTRIBUTE ?
                    $formData['ean_mode'] : '',
                'create_magento_attribute' => true,
                'tooltip' => $this->__(
                    'Walmart uses Product IDs to associate your Item with its catalog. Select Attribute where the EAN
                     values are stored.<br>
                    <strong>Note:</strong> At least one Product ID has to be specified when you create a new
                    offer on Walmart.'
                ),
                'after_element_html' => $warningToolTip
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        // GTIN
        $fieldset->addField(
            'gtin_custom_attribute',
            'hidden',
            [
                'name' => 'gtin_custom_attribute',
                'value' => $formData['gtin_custom_attribute']
            ]
        );

        $preparedAttributes = [];

        $warningToolTip = '';

        if ($formData['gtin_mode'] == HelperConfiguration::GTIN_MODE_CUSTOM_ATTRIBUTE &&
            !$magentoAttributeHelper->isExistInAttributesArray(
                $formData['gtin_custom_attribute'],
                $attributesByTypes['text']
            ) &&
            $this->getData('gtin_custom_attribute') != ''
        ) {
            $warningText = $this->__(<<<HTML
    Selected Magento Attribute is invalid.
    Please ensure that the Attribute exists in your Magento, has a relevant Input Type and it
    is included in all Attribute Sets.
    Otherwise, select a different Attribute from the drop-down.
HTML
            );

            $warningToolTip = $this->__(<<<HTML
<span class="fix-magento-tooltip m2e-tooltip-grid-warning">
    {$this->getTooltipHtml($warningText)}
</span>
HTML
            );

            $attrs = ['attribute_code' => $formData['gtin_custom_attribute']];
            $attrs['selected'] = 'selected';
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => HelperConfiguration::GTIN_MODE_CUSTOM_ATTRIBUTE,
                'label' => $magentoAttributeHelper->getAttributeLabel($formData['gtin_custom_attribute'])
            ];
        }

        foreach ($attributesByTypes['text'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if ($formData['gtin_mode'] == HelperConfiguration::GTIN_MODE_CUSTOM_ATTRIBUTE
                && $attribute['code'] == $formData['gtin_custom_attribute']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => HelperConfiguration::GTIN_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'gtin_mode',
            self::SELECT,
            [
                'name' => 'gtin_mode',
                'label' => $this->__('GTIN'),
                'class' => 'M2ePro-walmart-required-identifier-setting',
                'values' => [
                    HelperConfiguration::GTIN_MODE_NOT_SET => $this->__('Not Set'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => $formData['gtin_mode'] != HelperConfiguration::GTIN_MODE_CUSTOM_ATTRIBUTE ?
                    $formData['gtin_mode'] : '',
                'create_magento_attribute' => true,
                'tooltip' => $this->__(
                    'Walmart uses Product IDs to associate your Item with its catalog. Select Attribute where the GTIN
                     values are stored.<br>
                    <strong>Note:</strong> At least one Product ID has to be specified when you create a new
                    offer on Walmart.'
                ),
                'after_element_html' => $warningToolTip
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        // ISBN
        $fieldset->addField(
            'isbn_custom_attribute',
            'hidden',
            [
                'name' => 'isbn_custom_attribute',
                'value' => $formData['isbn_custom_attribute']
            ]
        );

        $preparedAttributes = [];

        $warningToolTip = '';

        if ($formData['isbn_mode'] == HelperConfiguration::ISBN_MODE_CUSTOM_ATTRIBUTE &&
            !$magentoAttributeHelper->isExistInAttributesArray(
                $formData['isbn_custom_attribute'],
                $attributesByTypes['text']
            ) &&
            $this->getData('isbn_custom_attribute') != ''
        ) {
            $warningText = $this->__(<<<HTML
    Selected Magento Attribute is invalid.
    Please ensure that the Attribute exists in your Magento, has a relevant Input Type and it
    is included in all Attribute Sets.
    Otherwise, select a different Attribute from the drop-down.
HTML
            );

            $warningToolTip = $this->__(<<<HTML
<span class="fix-magento-tooltip m2e-tooltip-grid-warning">
    {$this->getTooltipHtml($warningText)}
</span>
HTML
            );

            $attrs = ['attribute_code' => $formData['isbn_custom_attribute']];
            $attrs['selected'] = 'selected';
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => HelperConfiguration::ISBN_MODE_CUSTOM_ATTRIBUTE,
                'label' => $magentoAttributeHelper->getAttributeLabel($formData['isbn_custom_attribute'])
            ];
        }

        foreach ($attributesByTypes['text'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if ($formData['isbn_mode'] == HelperConfiguration::ISBN_MODE_CUSTOM_ATTRIBUTE
                && $attribute['code'] == $formData['isbn_custom_attribute']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => HelperConfiguration::ISBN_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'isbn_mode',
            self::SELECT,
            [
                'name' => 'isbn_mode',
                'label' => $this->__('ISBN'),
                'class' => 'M2ePro-walmart-required-identifier-setting',
                'values' => [
                    HelperConfiguration::ISBN_MODE_NOT_SET => $this->__('Not Set'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => $formData['isbn_mode'] != HelperConfiguration::ISBN_MODE_CUSTOM_ATTRIBUTE ?
                    $formData['isbn_mode'] : '',
                'create_magento_attribute' => true,
                'tooltip' => $this->__(
                    'Walmart uses Product IDs to associate your Item with its catalog. Select Attribute where the ISBN
                     values are stored.<br>
                     <strong>Note:</strong> At least one Product ID has to be specified when you create a new
                     offer on Walmart.'
                ),
                'after_element_html' => $warningToolTip
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField(
            'product_id_override_mode',
            self::SELECT,
            [
                'name' => 'product_id_override_mode',
                'label' => $this->__('Product ID Override'),
                'values' => [
                    HelperConfiguration::PRODUCT_ID_OVERRIDE_MODE_NONE => $this->__('Not Set'),
                    HelperConfiguration::PRODUCT_ID_OVERRIDE_MODE_ALL => $this->__('All products'),
                    HelperConfiguration::PRODUCT_ID_OVERRIDE_MODE_SPECIFIC_PRODUCTS => $this->__('Specific products'),
                ],
                'value' => $formData['product_id_override_mode'],
                'tooltip' => $this->__(
                    '<b>None</b> - all products will be listed with the standard Product IDs.<br/>
                     <b>All products</b> - Product ID exemption will be applied to all products.<br/>
                     <b>Specific products</b> - Product ID exemption will be applied to products that have a value
                     “CUSTOM” in Product ID attribute.<br/><br/>

                    <b>Note:</b> You must apply for Product ID exemption on Walmart first.'
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
                'name' => 'option_images_url_mode',
                'label' => $this->__('Image(s) URL'),
                'values' => [
                    HelperConfiguration::OPTION_IMAGES_URL_MODE_ORIGINAL => $this->__('Original'),
                    HelperConfiguration::OPTION_IMAGES_URL_MODE_HTTPS => $this->__(' Replace with HTTPS'),
                    HelperConfiguration::OPTION_IMAGES_URL_MODE_HTTP => $this->__('Replace with HTTP')
                ],
                'value' => $formData['option_images_url_mode'],
                'tooltip' => $this->__('
                <p>Select how to upload images to Walmart:</p><br>
                <p><strong>Original</strong> - images will be uploaded based on the Magento settings.</p>
                <p><strong>Replace with HTTP</strong> - images will be uploaded using HTTP protocol.</p>
                <p><strong>Replace with HTTPS</strong> - images will be uploaded using HTTPS protocol.</p>')
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

        $this->js->add(<<<JS
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
