<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

use Ess\M2ePro\Model\Amazon\Account;

class ListingOther extends AbstractForm
{
    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        /** @var \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper */
        $magentoAttributeHelper = $this->getHelper('Magento\Attribute');

        $generalAttributes = $magentoAttributeHelper->getGeneralFromAllAttributeSets();

        $attributes = $magentoAttributeHelper->filterByInputTypes(
            $generalAttributes, array(
                'text', 'textarea', 'select'
            )
        );

        /** @var $account \Ess\M2ePro\Model\Account */
        $account = $this->getHelper('Data\GlobalData')->getValue('edit_account');
        $formData = !is_null($account) ? array_merge($account->getData(), $account->getChildObject()->getData()) : [];

        if (isset($formData['other_listings_mapping_settings'])) {
            $formData['other_listings_mapping_settings'] = (array)$this->getHelper('Data')->jsonDecode(
                $formData['other_listings_mapping_settings']
            );
        }

        if (isset($formData['other_listings_move_settings'])) {
            $formData['other_listings_move_settings'] = (array)$this->getHelper('Data')->jsonDecode(
                $formData['other_listings_move_settings']
            );
            if (isset($formData['other_listings_move_settings']['synch'])) {
                $formData['other_listings_move_synch'] = $formData['other_listings_move_settings']['synch'];
            }
        }

        $defaults = array(
            'related_store_id' => 0,

            'other_listings_synchronization' => Account::OTHER_LISTINGS_SYNCHRONIZATION_YES,
            'other_listings_mapping_mode' => Account::OTHER_LISTINGS_MAPPING_MODE_YES,
            'other_listings_mapping_settings' => array(),
            'other_listings_move_mode' => Account::OTHER_LISTINGS_MOVE_TO_LISTINGS_DISABLED,
            'other_listings_move_synch' => Account::OTHER_LISTINGS_MOVE_TO_LISTINGS_SYNCH_MODE_NONE
        );

        $formData = array_merge($defaults, $formData);
        $isEdit = !!$this->getRequest()->getParam('id');

        $form->addField(
            'amazon_accounts_other_listings',
            self::HELP_BLOCK,
            [
                'content' => $this->__(<<<HTML
<p>This tab of the Account settings contains main configurations for the 3rd Party Listing management.
You can set preferences whether you would like to import 3rd Party Listings
(Items that were Listed on eBay either directly on the channel or with the help of other than M2E Pro tool),
automatically map them to Magento Product, etc..</p><br>
<p>More detailed information you can find <a href="%url%" target="_blank" class="external-link">here</a>.</p>
HTML
                    ,
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/NAItAQ')
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
                'label' => $this->__('Import 3rd Party Listings'),
                'values' => [
                    Account::OTHER_LISTINGS_SYNCHRONIZATION_YES => $this->__('Yes'),
                    Account::OTHER_LISTINGS_SYNCHRONIZATION_NO => $this->__('No'),
                ],
                'value' => $formData['other_listings_synchronization'],
                'tooltip' => $this->__('Allows importing 3rd Party Listings.')
            ]
        );

        $fieldset->addField(
            'related_store_id',
            self::STORE_SWITCHER,
            [
                'container_id' => 'marketplaces_related_store_id_container',
                'name' => 'related_store_id',
                'label' => $this->__('Related Store View'),
                'value' => $formData['related_store_id'],
                'tooltip' => $this->__(
                    'Store View, which will be associated with chosen Marketplace of the current Account.'
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
                'label' => $this->__('Product Mapping'),
                'values' => [
                    Account::OTHER_LISTINGS_MAPPING_MODE_YES => $this->__('Yes'),
                    Account::OTHER_LISTINGS_MAPPING_MODE_NO => $this->__('No'),
                ],
                'value' => $formData['other_listings_mapping_mode'],
                'tooltip' => $this->__(
                    'Choose whether imported Amazon Listings should automatically map to a
                    Product in your Magento Inventory.'
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_accounts_other_listings_product_mapping',
            [
                'legend' => $this->__('Magento Product Mapping Settings'),
                'collapsable' => false,
                'tooltip' => $this->__(
                    '<p>In this section you can provide settings for automatic Mapping of the newly imported
                    3rd Party Listings to the appropriate Magento Products. </p><br>
                    <p>The imported Items are mapped based on the correspondence between Amazon Item values and
                    Magento Product Attribute values. </p>')
            ]
        );

        $mappingSettings = $formData['other_listings_mapping_settings'];

        $preparedAttributes = [];
        foreach ($attributes as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                isset($mappingSettings['sku']['mode'])
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

        $modeCustomAttribute = Account::OTHER_LISTINGS_MAPPING_GENERAL_ID_MODE_CUSTOM_ATTRIBUTE;

        $preparedAttributes = [];
        foreach ($attributes as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                isset($mappingSettings['general_id']['mode'])
                && $mappingSettings['general_id']['mode'] == $modeCustomAttribute
                && $mappingSettings['general_id']['attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => Account::OTHER_LISTINGS_MAPPING_GENERAL_ID_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $mappingGeneralIdPriority = isset($mappingSettings['general_id']['priority'])
            ? (int)$mappingSettings['general_id']['priority']
            : Account::OTHER_LISTINGS_MAPPING_GENERAL_ID_DEFAULT_PRIORITY;
        $fieldset->addField(
            'mapping_general_id_mode',
            self::SELECT,
            [
                'name' => 'mapping_general_id_mode',
                'label' => $this->__('ASIN / ISBN'),
                'class' => 'attribute-mode-select',
                'values' => [
                    Account::OTHER_LISTINGS_MAPPING_GENERAL_ID_MODE_NONE => $this->__('None'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => isset($mappingSettings['general_id']['mode'])
                    && $mappingSettings['general_id']['mode'] != $modeCustomAttribute
                    ? $mappingSettings['general_id']['mode'] : '',
                'create_magento_attribute' => true,
            ]
        )->setAfterElementHtml(<<<HTML
<div id="mapping_general_id_priority_td">
    {$this->__('Priority')}: <input style="width: 50px;"
                                    name="mapping_general_id_priority"
                                    value="$mappingGeneralIdPriority"
                                    type="text"
                                    class="input-text admin__control-text required-entry _required">
</div>
HTML
    )->addCustomAttribute('allowed_attribute_types', 'text,textarea,select');

        $fieldset->addField(
            'mapping_general_id_attribute',
            'hidden',
            [
                'name' => 'mapping_general_id_attribute',
                'value' => isset($mappingSettings['general_id']['attribute'])
                    ? $mappingSettings['general_id']['attribute'] : ''
            ]
        );

        $preparedAttributes = [];
        foreach ($attributes as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                isset($mappingSettings['title']['mode'])
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

        $mappingTitlePriority = isset($mappingSettings['title']['priority'])
            ? (int)$mappingSettings['title']['priority'] : Account::OTHER_LISTINGS_MAPPING_TITLE_DEFAULT_PRIORITY;
        $fieldset->addField(
            'mapping_title_mode',
            self::SELECT,
            [
                'name' => 'mapping_title_mode',
                'label' => $this->__('Listing Title'),
                'class' => 'attribute-mode-select',
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
                    && $mappingSettings['title']['mode'] != Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_CUSTOM_ATTRIBUTE
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

        $fieldset = $form->addFieldset(
            'magento_block_amazon_accounts_other_listings_move_mode',
            [
                'legend' => $this->__('Auto Moving Mapped Amazon Items To M2E Pro Listings'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'other_listings_move_mode',
            'select',
            [
                'name' => 'other_listings_move_mode',
                'label' => $this->__('Move Mapped Amazon Items'),
                'values' => [
                    Account::OTHER_LISTINGS_MOVE_TO_LISTINGS_ENABLED => $this->__('Yes'),
                    Account::OTHER_LISTINGS_MOVE_TO_LISTINGS_DISABLED => $this->__('No'),
                ],
                'value' => $formData['other_listings_move_mode'],
                'tooltip' => $this->__(
                    '<p>Enable this option if you would like Amazon Items which have already been Mapped to
                    Magento Products to be automatically Moved from the 3rd Party Listings to M2E Pro
                    Listings for further management.</p><br>
                    <p><strong>Note:</strong> Auto Map and Move Actions are performed only during the
                    first 3rd Party Listing Synchronization. Afterwards, it can be performed manually on
                    the 3rd Party Listings Page.</p>'
                )
            ]
        );

        $fieldset->addField(
            'other_listings_move_synch',
            'select',
            [
                'container_id' => 'other_listings_move_synch_tr',
                'name' => 'other_listings_move_synch',
                'label' => $this->__('Immediate Synchronization'),
                'values' => [
                    Account::OTHER_LISTINGS_MOVE_TO_LISTINGS_SYNCH_MODE_NONE => $this->__('None'),
                    Account::OTHER_LISTINGS_MOVE_TO_LISTINGS_SYNCH_MODE_ALL => $this->__('Price and QTY'),
                    Account::OTHER_LISTINGS_MOVE_TO_LISTINGS_SYNCH_MODE_PRICE => $this->__('Price Only'),
                    Account::OTHER_LISTINGS_MOVE_TO_LISTINGS_SYNCH_MODE_QTY => $this->__('QTY Only'),
                ],
                'value' => $formData['other_listings_move_synch'],
                'tooltip' => $this->__('Updates Price and / or Quantity of Amazon Listing with Magento values.')
            ]
        );

        $this->setForm($form);

        $this->jsTranslator->add(
            'If Yes is chosen, you must select at least one Attribute for Product Mapping.',
            $this->__('If Yes is chosen, you must select at least one Attribute for Product Mapping.')
        );

        return parent::_prepareForm();
    }
}