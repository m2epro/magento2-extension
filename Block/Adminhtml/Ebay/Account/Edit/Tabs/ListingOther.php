<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Ebay\Account;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs\ListingOther
 */
class ListingOther extends AbstractForm
{
    protected $ebayFactory;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->ebayFactory = $ebayFactory;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        /** @var \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper */
        $magentoAttributeHelper = $this->getHelper('Magento\Attribute');

        $generalAttributes = $magentoAttributeHelper->getGeneralFromAllAttributeSets();

        $attributes = $magentoAttributeHelper->filterByInputTypes(
            $generalAttributes,
            [
                'text', 'textarea', 'select'
            ]
        );

        // ---------------------------------------

        // ---------------------------------------
        $account = $this->getHelper('Data\GlobalData')->getValue('edit_account')
            ? $this->getHelper('Data\GlobalData')->getValue('edit_account') : [];
        $formData = $account !== null ? array_merge($account->getData(), $account->getChildObject()->getData()) : [];

        $marketplacesData = $formData['marketplaces_data'];
        $marketplacesData = !empty($marketplacesData)
            ? $this->getHelper('Data')->jsonDecode($marketplacesData) : [];

        $marketplaces = $this->ebayFactory->getObject('Marketplace')
            ->getCollection()
            ->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)
            ->setOrder('sorder', 'ASC')
            ->setOrder('title', 'ASC')
            ->toArray();
        $marketplaces = $marketplaces['items'];

        foreach ($marketplaces as &$marketplace) {
            $marketplaceId = $marketplace['id'];
            $marketplace['related_store_id'] = isset($marketplacesData[$marketplaceId]['related_store_id'])
                ? $marketplacesData[$marketplaceId]['related_store_id']
                : \Magento\Store\Model\Store::DEFAULT_STORE_ID;
        }

        $key = 'other_listings_mapping_settings';
        if (isset($formData[$key])) {
            $formData[$key] = (array)$this->getHelper('Data')->jsonDecode($formData[$key]);
        }

        $defaults = [
            'other_listings_synchronization' => Account::OTHER_LISTINGS_SYNCHRONIZATION_YES,
            'other_listings_mapping_mode' => Account::OTHER_LISTINGS_MAPPING_MODE_NO,
            'other_listings_mapping_settings' => []
        ];
        $formData = array_merge($defaults, $formData);

        $form = $this->_formFactory->create();

        $form->addField(
            'ebay_accounts_other_listings',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    <<<HTML
<p>This tab of the Account settings contains main configurations for the 3rd Party Listing management.
You can set preferences whether you would like to import 3rd Party Listings
(Items that were Listed on eBay either directly on the channel or with the help of other than M2E Pro tool),
automatically map them to Magento Product, etc.</p><br>
<p>More detailed information you can find <a href="%url%" target="_blank" class="external-link">here</a>.</p>
HTML
                    ,
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/LAItAQ')
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
                'tooltip' => $this->__(
                    'Choose whether to Import eBay Listings that have been Listed on eBay either directly
                     or using a tool other than M2E Pro.
                     Only active eBay Listings created within the last 2 years will be imported.'
                )
            ]
        );

        $fieldset->addField(
            'other_listings_mapping_mode',
            'select',
            [
                'container_id' => 'other_listings_mapping_mode_tr',
                'name' => 'other_listings_mapping_mode',
                'label' => $this->__('Product Mapping'),
                'class' => 'M2ePro-require-select-attribute',
                'values' => [
                    Account::OTHER_LISTINGS_MAPPING_MODE_YES => $this->__('Yes'),
                    Account::OTHER_LISTINGS_MAPPING_MODE_NO => $this->__('No'),
                ],
                'value' => $formData['other_listings_mapping_mode'],
                'tooltip' => $this->__(
                    'Choose whether imported eBay Listings should automatically map to a
                    Product in your Magento Inventory.'
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_accounts_other_listings_product_mapping',
            [
                'legend' => $this->__('Magento Product Mapping Settings'),
                'collapsable' => true,
                'tooltip' => $this->__(
                    '<p>In this section you can provide settings for automatic Mapping of the newly
                    imported 3rd Party Listings to the appropriate Magento Products.</p><br>
                    <p>The imported Items are mapped based on the correspondence between eBay Item
                    values and Magento Product Attribute values. </p>'
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
                'label' => $this->__('Custom Label (SKU)'),
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
<div id="mapping_sku_priority">
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
                'label' => $attribute['label']
            ];
        }

        // ---------------------------------------

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
                    && $mappingSettings['title']['mode'] != Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_CUSTOM_ATTRIBUTE
                    ? $mappingSettings['title']['mode'] : '',
                'create_magento_attribute' => true,
            ]
        )->setAfterElementHtml(<<<HTML
<div id="mapping_title_priority">
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

        // ---------------------------------------

        $preparedAttributes = [];
        foreach ($attributes as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (isset($mappingSettings['item_id']['mode'])
                && $mappingSettings['item_id']['mode'] == Account::OTHER_LISTINGS_MAPPING_ITEM_ID_MODE_CUSTOM_ATTRIBUTE
                && $mappingSettings['item_id']['attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => Account::OTHER_LISTINGS_MAPPING_ITEM_ID_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label']
            ];
        }

        $mappingItemPriority = isset($mappingSettings['item_id']['priority'])
            ? (int)$mappingSettings['item_id']['priority'] : Account::OTHER_LISTINGS_MAPPING_ITEM_ID_DEFAULT_PRIORITY;
        $fieldset->addField(
            'mapping_item_id_mode',
            self::SELECT,
            [
                'name' => 'mapping_item_id_mode',
                'label' => $this->__('eBay Item ID'),
                'class' => 'attribute-mode-select',
                'style' => 'float:left; margin-right: 15px;',
                'values' => [
                    Account::OTHER_LISTINGS_MAPPING_ITEM_ID_MODE_NONE => $this->__('None'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => isset($mappingSettings['item_id']['mode'])
                && $mappingSettings['item_id']['mode'] != Account::OTHER_LISTINGS_MAPPING_ITEM_ID_MODE_CUSTOM_ATTRIBUTE
                    ? $mappingSettings['item_id']['mode'] : '',
                'create_magento_attribute' => true,
            ]
        )->setAfterElementHtml(<<<HTML
<div id="mapping_item_id_priority">
    {$this->__('Priority')}: <input style="width: 50px;"
                                    name="mapping_item_id_priority"
                                    value="$mappingItemPriority"
                                    type="text"
                                    class="input-text admin__control-text required-entry _required">
</div>
HTML
        )->addCustomAttribute('allowed_attribute_types', 'text,textarea,select');

        $fieldset->addField(
            'mapping_item_id_attribute',
            'hidden',
            [
                'name' => 'mapping_item_id_attribute',
                'value' => isset($mappingSettings['item_id']['attribute'])
                    ? $mappingSettings['item_id']['attribute'] : '',
            ]
        );

        if (!empty($marketplaces)) {
            $fieldset = $form->addFieldset(
                'magento_block_ebay_accounts_other_listings_related_store_views',
                [
                    'legend' => $this->__('Related Store Views'),
                    'collapsable' => true,
                    'tooltip' => $this->__(
                        'Establish Connection between Marketplaces and Magento Store
                        Views for correct data Synchronization.'
                    )
                ]
            );

            foreach ($marketplaces as &$marketplace) {
                $fieldset->addField(
                    'related_store_id_' . $marketplace['id'],
                    self::STORE_SWITCHER,
                    [
                        'label' => $this->__($marketplace['title']),
                        'value' => $marketplace['related_store_id']
                    ]
                );
            }
        }

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
