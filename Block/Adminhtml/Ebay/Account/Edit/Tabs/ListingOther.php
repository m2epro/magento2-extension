<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Ebay\Account;

class ListingOther extends AbstractForm
{
    protected $ebayFactory;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->ebayFactory = $ebayFactory;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        /** @var \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper */
        $magentoAttributeHelper = $this->getHelper('Magento\Attribute');

        $generalAttributes = $magentoAttributeHelper->getGeneralFromAllAttributeSets();

        $attributes = $magentoAttributeHelper->filterByInputTypes(
            $generalAttributes, array(
                'text', 'textarea', 'select'
            )
        );

        // ---------------------------------------

        // ---------------------------------------
//        $back = $this->getHelper('Module')->makeBackUrlParam('*/adminhtml_ebay_account/edit', array(
//            'id' => $this->getRequest()->getParam('id'),
//            'tab' => 'listingOther'
//        ));
//        $url = $this->getUrl('*/adminhtml_ebay_listing_other_synchronization/edit', array('back' => $back));
//        $data = array(
//            'label'   => $this->__('Synchronization Settings'),
//            'onclick' => 'window.open(\'' . $url . '\', \'_blank\')',
//            'class'   => 'button_link'
//        );
//        $buttonBlock = $this->getLayout()->createBlock('adminhtml/widget_button')->setData($data);
//        $this->setChild('ebay_other_listings_synchronization_settings', $buttonBlock);
        // ---------------------------------------

        // ---------------------------------------
        $account = $this->getHelper('Data\GlobalData')->getValue('edit_account')
            ? $this->getHelper('Data\GlobalData')->getValue('edit_account') : array();
        $formData = !is_null($account) ? array_merge($account->getData(), $account->getChildObject()->getData()) : [];

        $marketplacesData = $formData['marketplaces_data'];
        $marketplacesData = !empty($marketplacesData)
            ? $this->getHelper('Data')->jsonDecode($marketplacesData) : array();

        $marketplaces = $this->ebayFactory->getObject('Marketplace')
            ->getCollection()
            ->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)
            ->setOrder('sorder','ASC')
            ->setOrder('title','ASC')
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

        $defaults = array(
            'other_listings_synchronization' => Account::OTHER_LISTINGS_SYNCHRONIZATION_YES,
            'other_listings_mapping_mode' => Account::OTHER_LISTINGS_MAPPING_MODE_NO,
            'other_listings_mapping_settings' => array()
        );
        $formData = array_merge($defaults, $formData);

        $form = $this->_formFactory->create();

        $form->addField(
            'ebay_accounts_other_listings',
            self::HELP_BLOCK,
            [
                'content' => $this->__(<<<HTML
<p>This tab of the Account settings contains main configurations for the 3rd Party Listing management.
You can set preferences whether you would like to import 3rd Party Listings
(Items that were Listed on eBay either directly on the channel or with the help of other than M2E Pro tool),
automatically map them to Magento Product, etc.</p><br>
<p>More detailed information you can find <a href="%url%" target="_blank" class="external-link">here</a>.</p>
HTML
                ,
                $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/LAItAQ'))
        ]);

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
                'label' => $this->__('Custom Label (SKU)'),
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
                'label' => $attribute['label']
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