<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

use Ess\M2ePro\Model\Walmart\Account;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Account\Edit\Tabs\ListingOther
 */
class ListingOther extends AbstractForm
{
    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        /** @var \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper */
        $magentoAttributeHelper = $this->getHelper('Magento\Attribute');

        $allAttributes = $magentoAttributeHelper->getAll();

        $attributes = $magentoAttributeHelper->filterByInputTypes(
            $allAttributes,
            [
                'text', 'textarea', 'select'
            ]
        );

        /** @var $account \Ess\M2ePro\Model\Account */
        $account = $this->getHelper('Data\GlobalData')->getValue('edit_account');
        $formData = $account !== null ? array_merge($account->getData(), $account->getChildObject()->getData()) : [];

        if (isset($formData['other_listings_mapping_settings'])) {
            $formData['other_listings_mapping_settings'] = (array)$this->getHelper('Data')->jsonDecode(
                $formData['other_listings_mapping_settings']
            );
        }

        $defaults = $this->modelFactory->getObject('Walmart_Account_Builder')->getDefaultData();

        $formData = array_merge($defaults, $formData);

        $form->addField(
            'walmart_accounts_other_listings',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    <<<HTML
        The Unmanaged Listings include Items which were listed on Walmart without using M2E Pro Extension.<br/><br/>

        To allow the Unmanaged Listing importing for the current Walmart Account,
        enable the Import Unmanaged Listings option. The imported Unmanaged Items can be found
        under <i>Walmart Integration > Listings > Unmanaged</i>.<br/><br/>

        The Unmanaged Items can be automatically linked to the related Magento Product by SKU, UPC, GTIN,
        Walmart ID or Title values. To do this, enable the Product Linking option and
        select appropriate Attribute.<br/><br/>

        <strong>Note:</strong> Automatic linking of the Unmanaged Item is performed only during
        the initial Unmanaged Listing importing. Afterward, you can link and move the Unmanaged Items manually
        under <i>Walmart Integration > Listings > Unmanaged</i>.<br/><br/>

        The detailed information can be found <a href="%url%" target="_blank">here</a>.
HTML
                    ,
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/UgBhAQ')
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'general',
            [
                'legend' => $this->__('General'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'other_listings_synchronization',
            'select',
            [
                'name' => 'other_listings_synchronization',
                'label' => $this->__('Import Unmanaged Listings'),
                'values' => [
                    1 => $this->__('Yes'),
                    0 => $this->__('No'),
                ],
                'value' => $formData['other_listings_synchronization'],
                'tooltip' => $this->__('Enable to automatically import the Unmanaged Items.')
            ]
        );

        $fieldset->addField(
            'related_store_id',
            self::STORE_SWITCHER,
            [
                'container_id' => 'other_listings_store_view_tr',
                'name' => 'related_store_id',
                'label' => $this->__('Related Store View'),
                'value' => $formData['related_store_id'],
                'tooltip' => $this->__(
                    'Select Magento Store View that will be associated with Marketplace set for the current Account.'
                )
            ]
        );

        $fieldset->addField(
            'other_listings_mapping_mode',
            'select',
            [
                'container_id' => 'other_listings_mapping_mode_tr',
                'name' => 'other_listings_mapping_mode',
                'class' => 'M2ePro-require-select-attribute',
                'label' => $this->__('Product Linking'),
                'values' => [
                    1 => $this->__('Yes'),
                    0 => $this->__('No'),
                ],
                'value' => $formData['other_listings_mapping_mode'],
                'tooltip' => $this->__(
                    'Enable to automatically link your Unmanaged Items to Magento
                    Products based on the linking Attribute settings.'
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_walmart_accounts_other_listings_product_mapping',
            [
                'legend' => $this->__('Attributes Of Linking Walmart Items To Magento Products'),
                'collapsable' => false,
                'tooltip' => $this->__(
                    '<p>In this section you can provide settings for automatic Linking of the newly imported
                    Unmanaged Listings to the appropriate Magento Products. </p><br>
                    <p>The imported Items are linked based on the correspondence between Walmart Item values and
                    Magento Product Attribute values. </p>'
                )
            ]
        );

        $mappingSettings = $formData['other_listings_mapping_settings'];

        $preparedAttributes = [];
        foreach ($attributes as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (isset($mappingSettings['sku']['mode'])
                && $mappingSettings['sku']['mode'] == Account::OTHER_LISTINGS_MAPPING_SKU_MODE_CUSTOM_ATTRIBUTE
                && $mappingSettings['sku']['attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => Account::OTHER_LISTINGS_MAPPING_SKU_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $mappingSkuPriority = isset($mappingSettings['sku']['priority'])
            ? (int)$mappingSettings['sku']['priority'] : Account::OTHER_LISTINGS_MAPPING_SKU_DEFAULT_PRIORITY;
        $fieldset->addField(
            'mapping_sku_mode',
            self::SELECT,
            [
                'name' => 'mapping_sku_mode',
                'label' => $this->__('SKU'),
                'class' => 'attribute-mode-select',
                'style' => 'float:left; margin-right: 15px;',
                'values' => [
                    Account::OTHER_LISTINGS_MAPPING_SKU_MODE_NONE => $this->__('None'),
                    Account::OTHER_LISTINGS_MAPPING_SKU_MODE_DEFAULT => $this->__('Product SKU'),
                    Account::OTHER_LISTINGS_MAPPING_SKU_MODE_PRODUCT_ID => $this->__('Product ID'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => isset($mappingSettings['sku']['mode'])
                    && $mappingSettings['sku']['mode'] != Account::OTHER_LISTINGS_MAPPING_SKU_MODE_CUSTOM_ATTRIBUTE
                    ? $mappingSettings['sku']['mode'] : '',
                'create_magento_attribute' => true,
            ]
        )->setAfterElementHtml(<<<HTML
<div id="mapping_sku_priority_td">
    {$this->__('Priority')}: <input style="width: 50px;"
                                    name="mapping_sku_priority"
                                    value="$mappingSkuPriority"
                                    type="text"
                                    class="input-text admin__control-text required-entry _required">
</div>
HTML
        )->addCustomAttribute('allowed_attribute_types', 'text,textarea,select');

        $fieldset->addField(
            'mapping_sku_attribute',
            'hidden',
            [
                'name' => 'mapping_sku_attribute',
                'value' => isset($mappingSettings['sku']['attribute']) ? $mappingSettings['sku']['attribute'] : '',
            ]
        );

        $modeCustomAttribute = Account::OTHER_LISTINGS_MAPPING_UPC_MODE_CUSTOM_ATTRIBUTE;

        $preparedAttributes = [];
        foreach ($attributes as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (isset($mappingSettings['upc']['mode'])
                && $mappingSettings['upc']['mode'] == $modeCustomAttribute
                && $mappingSettings['upc']['attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => Account::OTHER_LISTINGS_MAPPING_UPC_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $mappingUpcPriority = isset($mappingSettings['upc']['priority'])
            ? (int)$mappingSettings['upc']['priority']
            : Account::OTHER_LISTINGS_MAPPING_UPC_DEFAULT_PRIORITY;
        $fieldset->addField(
            'mapping_upc_mode',
            self::SELECT,
            [
                'name' => 'mapping_upc_mode',
                'label' => $this->__('UPC'),
                'class' => 'attribute-mode-select',
                'style' => 'float:left; margin-right: 15px;',
                'values' => [
                    Account::OTHER_LISTINGS_MAPPING_UPC_MODE_NONE => $this->__('None'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => isset($mappingSettings['upc']['mode'])
                    && $mappingSettings['upc']['mode'] != $modeCustomAttribute
                    ? $mappingSettings['upc']['mode'] : '',
                'create_magento_attribute' => true,
            ]
        )->setAfterElementHtml(<<<HTML
<div id="mapping_upc_priority_td">
    {$this->__('Priority')}: <input style="width: 50px;"
                                    name="mapping_upc_priority"
                                    value="$mappingUpcPriority"
                                    type="text"
                                    class="input-text admin__control-text required-entry _required">
</div>
HTML
        )->addCustomAttribute('allowed_attribute_types', 'text,textarea,select');

        $fieldset->addField(
            'mapping_upc_attribute',
            'hidden',
            [
                'name' => 'mapping_upc_attribute',
                'value' => isset($mappingSettings['upc']['attribute'])
                    ? $mappingSettings['upc']['attribute'] : ''
            ]
        );

        $preparedAttributes = [];
        foreach ($attributes as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (isset($mappingSettings['gtin']['mode'])
                && $mappingSettings['gtin']['mode'] == Account::OTHER_LISTINGS_MAPPING_GTIN_MODE_CUSTOM_ATTRIBUTE
                && $mappingSettings['gtin']['attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => Account::OTHER_LISTINGS_MAPPING_GTIN_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $mappingTitlePriority = isset($mappingSettings['gtin']['priority'])
            ? (int)$mappingSettings['gtin']['priority'] : Account::OTHER_LISTINGS_MAPPING_GTIN_DEFAULT_PRIORITY;
        $fieldset->addField(
            'mapping_gtin_mode',
            self::SELECT,
            [
                'name' => 'mapping_gtin_mode',
                'label' => $this->__('GTIN'),
                'class' => 'attribute-mode-select',
                'style' => 'float:left; margin-right: 15px;',
                'values' => [
                    Account::OTHER_LISTINGS_MAPPING_GTIN_MODE_NONE => $this->__('None'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => isset($mappingSettings['gtin']['mode'])
                    && $mappingSettings['gtin']['mode'] != Account::OTHER_LISTINGS_MAPPING_GTIN_MODE_CUSTOM_ATTRIBUTE
                    ? $mappingSettings['gtin']['mode'] : '',
                'create_magento_attribute' => true,
            ]
        )->setAfterElementHtml(<<<HTML
<div id="mapping_gtin_priority_td">
    {$this->__('Priority')}: <input style="width: 50px;"
                                    name="mapping_gtin_priority"
                                    value="$mappingTitlePriority"
                                    type="text"
                                    class="input-text admin__control-text required-entry _required">
</div>
HTML
        )->addCustomAttribute('allowed_attribute_types', 'text,textarea,select');

        $fieldset->addField(
            'mapping_gtin_attribute',
            'hidden',
            [
                'name' => 'mapping_gtin_attribute',
                'value' => isset($mappingSettings['gtin']['attribute']) ? $mappingSettings['gtin']['attribute'] : '',
            ]
        );

        // -----------

        $preparedAttributes = [];
        foreach ($attributes as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (isset($mappingSettings['wpid']['mode'])
                && $mappingSettings['wpid']['mode'] == Account::OTHER_LISTINGS_MAPPING_WPID_MODE_CUSTOM_ATTRIBUTE
                && $mappingSettings['wpid']['attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => Account::OTHER_LISTINGS_MAPPING_WPID_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $accountMappingWPIDModeCustomAttribute = Account::OTHER_LISTINGS_MAPPING_WPID_MODE_CUSTOM_ATTRIBUTE;

        $mappingTitlePriority = isset($mappingSettings['wpid']['priority'])
            ? (int)$mappingSettings['wpid']['priority'] : Account::OTHER_LISTINGS_MAPPING_WPID_DEFAULT_PRIORITY;
        $fieldset->addField(
            'mapping_wpid_mode',
            self::SELECT,
            [
                'name' => 'mapping_wpid_mode',
                'label' => $this->__('Walmart ID'),
                'class' => 'attribute-mode-select',
                'style' => 'float:left; margin-right: 15px;',
                'values' => [
                    Account::OTHER_LISTINGS_MAPPING_WPID_MODE_NONE => $this->__('None'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => isset($mappingSettings['wpid']['mode'])
                           && $mappingSettings['wpid']['mode'] != $accountMappingWPIDModeCustomAttribute
                    ? $mappingSettings['wpid']['mode'] : '',
                'create_magento_attribute' => true,
            ]
        )->setAfterElementHtml(<<<HTML
<div id="mapping_wpid_priority_td">
    {$this->__('Priority')}: <input style="width: 50px;"
                                    name="mapping_wpid_priority"
                                    value="$mappingTitlePriority"
                                    type="text"
                                    class="input-text admin__control-text required-entry _required">
</div>
HTML
        )->addCustomAttribute('allowed_attribute_types', 'text,textarea,select');

        $fieldset->addField(
            'mapping_wpid_attribute',
            'hidden',
            [
                'name' => 'mapping_wpid_attribute',
                'value' => isset($mappingSettings['wpid']['attribute']) ? $mappingSettings['wpid']['attribute'] : '',
            ]
        );

        // -----------

        $preparedAttributes = [];
        foreach ($attributes as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (isset($mappingSettings['title']['mode'])
                && $mappingSettings['title']['mode'] == Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_CUSTOM_ATTRIBUTE
                && $mappingSettings['title']['attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $accountMappingTitleModeCustomAttribute = Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_CUSTOM_ATTRIBUTE;

        $mappingTitlePriority = isset($mappingSettings['title']['priority'])
            ? (int)$mappingSettings['title']['priority'] : Account::OTHER_LISTINGS_MAPPING_TITLE_DEFAULT_PRIORITY;
        $fieldset->addField(
            'mapping_title_mode',
            self::SELECT,
            [
                'name' => 'mapping_title_mode',
                'label' => $this->__('Listing Title'),
                'class' => 'attribute-mode-select',
                'style' => 'float:left; margin-right: 15px;',
                'values' => [
                    Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_NONE => $this->__('None'),
                    Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_DEFAULT => $this->__('Product Name'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => isset($mappingSettings['title']['mode'])
                           && $mappingSettings['title']['mode'] != $accountMappingTitleModeCustomAttribute
                    ? $mappingSettings['title']['mode'] : '',
                'create_magento_attribute' => true,
            ]
        )->setAfterElementHtml(<<<HTML
<div id="mapping_title_priority_td">
    {$this->__('Priority')}: <input style="width: 50px;"
                                    name="mapping_title_priority"
                                    value="$mappingTitlePriority"
                                    type="text"
                                    class="input-text admin__control-text required-entry _required">
</div>
HTML
        )->addCustomAttribute('allowed_attribute_types', 'text,textarea,select');

        $fieldset->addField(
            'mapping_title_attribute',
            'hidden',
            [
                'name' => 'mapping_title_attribute',
                'value' => isset($mappingSettings['title']['attribute']) ? $mappingSettings['title']['attribute'] : '',
            ]
        );

        // -----------

        $this->setForm($form);

        $this->jsTranslator->add(
            'If Yes is chosen, you must select at least one Attribute for Product Linking.',
            $this->__('If Yes is chosen, you must select at least one Attribute for Product Linking.')
        );

        return parent::_prepareForm();
    }
}
