<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Shipping\Edit\Form;

use Ess\M2ePro\Model\Ebay\Template\Shipping;

class Data extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    protected $regionFactory;
    protected $ebayFactory;

    protected $_template = 'ebay/template/shipping/form/data.phtml';

    public $formData = [];
    public $marketplaceData = [];
    public $attributes = [];
    public $attributesByInputTypes = [];
    public $missingAttributes = [];

    //########################################

    public function __construct(
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->regionFactory = $regionFactory;
        $this->ebayFactory = $ebayFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayTemplateShippingEditFormData');
        // ---------------------------------------

        $this->formData = $this->getFormData();
        $this->marketplaceData = $this->getMarketplaceData();
        $this->attributes = $this->getHelper('Data\GlobalData')->getValue('ebay_attributes');

        /** @var \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper */
        $magentoAttributeHelper = $this->getHelper('Magento\Attribute');
        $this->attributesByInputTypes = [
            'text' => $magentoAttributeHelper->filterByInputTypes($this->attributes, ['text']),
            'text_select' => $magentoAttributeHelper->filterByInputTypes($this->attributes, ['text', 'select']),
            'text_price' => $magentoAttributeHelper->filterByInputTypes($this->attributes, ['text', 'price']),
            'text_weight' => $magentoAttributeHelper->filterByInputTypes($this->attributes, ['text', 'weight']),
            'text_price_select' => $magentoAttributeHelper->filterByInputTypes(
                $this->attributes, ['text', 'price', 'select']
            ),
        ];

        $this->missingAttributes = $this->getMissingAttributes();
    }

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        // ---------------------------------------

        $form->addField('shipping_id', 'hidden',
            [
                'name' => 'shipping[id]',
                'value' => (!$this->isCustom() && isset($this->formData['id'])) ?
                            (int)$this->formData['id'] : ''
            ]
        );

        $form->addField('shipping_title', 'hidden',
            [
                'name' => 'shipping[title]',
                'value' => $this->getTitle()
            ]
        );

        $form->addField('hidden_marketplace_id_'.$this->marketplaceData['id'], 'hidden',
            [
                'name' => 'shipping[marketplace_id]',
                'value' => $this->marketplaceData['id']
            ]
        );

        $form->addField('is_custom_template', 'hidden',
            [
                'name' => 'shipping[is_custom_template]',
                'value' => $this->isCustom() ? 1 : 0
            ]
        );

        // ---------------------------------------

        // ---------------------------------------
        // Location Block
        // ---------------------------------------

        $fieldSet = $form->addFieldset('shipping_location_fieldset',
            ['legend' => __('Item Location'), 'collapsable' => true]
        );

        // ---------------------------------------

        $fieldSet->addField('country_custom_value', 'hidden',
            [
                'name' => 'shipping[country_custom_value]',
                'value' => $this->formData['country_custom_value']
            ]
        );

        $fieldSet->addField('country_custom_attribute', 'hidden',
            [
                'name' => 'shipping[country_custom_attribute]',
                'value' => $this->formData['country_custom_attribute']
            ]
        );

        $fieldSet->addField('country_mode', self::SELECT,
            [
                'name' => 'shipping[country_mode]',
                'label' => $this->__('Country'),
                'title' => $this->__('Country'),
                'values' => [
                    $this->getCountryOptions(),
                    $this->getAttributesOptions(Shipping::COUNTRY_MODE_CUSTOM_ATTRIBUTE,
                        function ($attribute) {
                            return $this->formData['country_mode'] == Shipping::COUNTRY_MODE_CUSTOM_ATTRIBUTE
                                   && $attribute['code'] == $this->formData['country_custom_attribute'];
                    })
                ],
                'class' => 'required-entry',
                'create_magento_attribute' => true
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        // ---------------------------------------

        $fieldSet->addField('postal_code_custom_attribute', 'hidden',
            [
                'name' => 'shipping[postal_code_custom_attribute]',
                'value' => $this->formData['postal_code_custom_attribute']
            ]
        );

        $defaultValue = '';
        if ($this->formData['postal_code_mode'] != Shipping::POSTAL_CODE_MODE_CUSTOM_ATTRIBUTE) {
            $defaultValue = $this->formData['postal_code_mode'];
        }

        $fieldSet->addField('postal_code_mode', self::SELECT,
            [
                'name' => 'shipping[postal_code_mode]',
                'label' => $this->__('Zip/Postal Code'),
                'title' => $this->__('Zip/Postal Code'),
                'values' => [
                    ['label' => $this->__('None'), 'value' => Shipping::POSTAL_CODE_MODE_NONE],
                    ['label' => $this->__('Custom Value'), 'value' => Shipping::POSTAL_CODE_MODE_CUSTOM_VALUE],
                    $this->getAttributesOptions(Shipping::POSTAL_CODE_MODE_CUSTOM_ATTRIBUTE,
                        function($attribute) {
                            return $this->formData['postal_code_mode'] == Shipping::POSTAL_CODE_MODE_CUSTOM_ATTRIBUTE
                                   && $attribute['code'] == $this->formData['postal_code_custom_attribute'];
                        }
                    )
                ],
                'value' => $defaultValue,
                'class' => 'M2ePro-location-or-postal-required M2ePro-required-if-calculated',
                'create_magento_attribute' => true
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldSet->addField('postal_code_custom_value', 'text',
            [
                'name' => 'shipping[postal_code_custom_value]',
                'label' => $this->__('Zip/Postal Code Value'),
                'title' => $this->__('Zip/Postal Code Value'),
                'value' => $this->formData['postal_code_custom_value'],
                'class' => 'M2ePro-required-when-visible input-text',
                'required' => true,
                'field_extra_attributes' => 'id="postal_code_custom_value_tr" style="display: none;"'
            ]
        );

        // ---------------------------------------

        $fieldSet->addField('address_custom_attribute', 'hidden',
            [
                'name' => 'shipping[address_custom_attribute]',
                'value' => $this->formData['address_custom_attribute']
            ]
        );

        $defaultValue = '';
        if ($this->formData['address_mode'] != Shipping::ADDRESS_MODE_CUSTOM_ATTRIBUTE) {
            $defaultValue = $this->formData['address_mode'];
        }

        $fieldSet->addField('address_mode', self::SELECT,
            [
                'name' => 'shipping[address_mode]',
                'label' => $this->__('City, State'),
                'title' => $this->__('City, State'),
                'values' => [
                    ['label' => $this->__('None'), 'value' => Shipping::ADDRESS_MODE_NONE],
                    ['label' => $this->__('Custom Value'), 'value' => Shipping::ADDRESS_MODE_CUSTOM_VALUE],
                    $this->getAttributesOptions(
                        Shipping::ADDRESS_MODE_CUSTOM_ATTRIBUTE,
                        function($attribute) {
                            return $this->formData['address_mode'] == Shipping::ADDRESS_MODE_CUSTOM_ATTRIBUTE
                                    && $attribute['code'] == $this->formData['address_custom_attribute'];
                        }
                    )
                ],
                'value' => $defaultValue,
                'class' => 'M2ePro-location-or-postal-required M2ePro-required-if-calculated',
                'create_magento_attribute' => true
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldSet->addField('address_custom_value', 'text',
            [
                'name' => 'shipping[address_custom_value]',
                'label' => $this->__('City, State Value'),
                'title' => $this->__('City, State Value'),
                'value' => $this->formData['address_custom_value'],
                'class' => 'M2ePro-required-when-visible input-text',
                'field_extra_attributes' => 'id="address_custom_value_tr" style="display: none;"',
                'required' => true
            ]
        );

        // ---------------------------------------

        // ---------------------------------------
        // Domestic Shipping
        // ---------------------------------------

        $fieldSet = $form->addFieldset('domestic_shipping_fieldset',
            ['legend' => __('Domestic Shipping'), 'collapsable' => true]
        );

        $fieldSet->addField('local_shipping_mode', self::SELECT,
            [
                'name' => 'shipping[local_shipping_mode]',
                'label' => $this->__('Type'),
                'title' => $this->__('Type'),
                'values' => $this->getDomesticShippingOptions(),
                'value' => $this->formData['local_shipping_mode']
            ]
        );

        // ---------------------------------------

        if ($this->canDisplayLocalShippingRateTable()) {
            $fieldSet->addField('local_shipping_rate_table_mode', self::SELECT,
                [
                    'name' => 'shipping[local_shipping_rate_table_mode]',
                    'label' => $this->__('Use eBay Shipping Rate Table'),
                    'title' => $this->__('Use eBay Shipping Rate Table'),
                    'values' => [
                        ['value' => 0, 'label' => __('No')],
                        ['value' => 1, 'label' => __('Yes')]
                    ],
                    'value' => $this->formData['local_shipping_rate_table_mode'],
                    'field_extra_attributes' => 'id="local_shipping_rate_table_mode_tr"',
                    'tooltip' => $this->__(
                        'You can set up Shipping Rate Tables in eBay to set the amount you charge for postage
                        by delivery method and destination and use them in your M2E Pro Listings.
                        Shipping Rate Tables are set up directly on eBay, not in M2E Pro.
                        To set up or edit Shipping Rate Tables, Log in to your Seller Account on eBay.'
                    )
                ]
            );
        }

        // ---------------------------------------

        $fieldSet->addField('shipping_local_table_messages',
            self::CUSTOM_CONTAINER,
            [
                'text' => '',
                'css_class' => 'm2epro-fieldset-table no-margin-bottom'
            ]
        );

        $fieldSet->addField('local_shipping_methods_tr_wrapper', self::CUSTOM_CONTAINER,
            [
                'text' => $this->getShippingLocalTable(),
                'css_class' => 'm2epro-fieldset-table',
                'field_extra_attributes' => 'id="local_shipping_methods_tr"'
            ]
        );

        // ---------------------------------------

        $fieldSet->addField('dispatch_time', self::SELECT,
            [
                'name' => 'shipping[dispatch_time]',
                'label' => $this->__('Dispatch Time'),
                'title' => $this->__('Dispatch Time'),
                'values' => $this->getDispatchTimeOptions(),
                'value' => $this->formData['dispatch_time'],
                'class' => 'M2ePro-required-when-visible',
                'css_class' => 'local-shipping-tr local-shipping-always-visible-tr',
                'tooltip' => $this->__(
                    'Dispatch (or handling) time is the time between when the Buyer\'s payment
                    clears and when you send the Item.
                    Buyer dissatisfaction increases significantly when dispatch times are more than 3 days.
                    We strongly encourage dispatch times of 3 days or less. If possible,
                    dispatch the same day you get the Buyer\'s payment.'
                )
            ]
        );

        // ---------------------------------------

        if ($this->canDisplayClickAndCollectOption()) {
            $fieldSet->addField('click_and_collect_mode', self::SELECT,
                [
                    'name' => 'shipping[click_and_collect_mode]',
                    'label' => $this->__('Click And Collect Opt-out'),
                    'title' => $this->__('Click And Collect Opt-out'),
                    'values' => [
                        ['value' => 0, 'label' => __('No')],
                        ['value' => 1, 'label' => __('Yes')]
                    ],
                    'value' => $this->formData['click_and_collect_mode'],
                    'field_extra_attributes' => 'id="click_and_collect_mode_tr"',
                    'css_class' => 'local-shipping-tr',
                    'tooltip' => $this->__(
                        'Click & Collect at Argos enables your Buyers to collect Items at a time that
                         suits them from over 650 Argos Stores across the UK.
                         <br/>For more details please read
                         <a href="http://sellercentre.ebay.co.uk/click-and-collect"
                            target="_blank" class="external-link">this documentation</a>.'
                    )
                ]
            );
        }

        // ---------------------------------------

        $fieldSet->addField('local_handling_cost', 'text',
            [
                'name' => 'shipping[local_handling_cost]',
                'label' => $this->__('Handling Cost'),
                'title' => $this->__('Handling Cost'),
                'value' => $this->formData['local_handling_cost'],
                'class' => 'input-text M2ePro-validation-float',
                'css_class' => 'local-shipping-tr',
                'field_extra_attributes' => 'id="local_handling_cost_cv_tr"',
                'tooltip' => $this->__('Addition of handling cost to the shipping costs.')
            ]
        );

        // ---------------------------------------

        if (!is_null($this->getAccountId())) {
            $fieldsetCombined = $fieldSet->addFieldset('combined_shipping_profile',
                [
                    'legend' => __('Combined Shipping Profile'),
                    'collapsable' => false,
                    'class' => 'local-shipping-tr'
                ]
            );

            $fieldsetCombined->addField('local_shipping_discount_profile_id_'.$this->getAccountId(),
                self::SELECT,
                [
                    'name' => 'shipping[local_shipping_discount_profile_id]['.$this->getAccountId().']',
                    'label' => $this->__('Combined Shipping Profile'),
                    'title' => $this->__('Combined Shipping Profile'),
                    'values' => [
                        ['label' => $this->__('None'), 'value' => '']
                    ],
                    'class' => 'local-discount-profile-account-tr',
                    'value' => '',
                    'style' => 'margin-right: 18px',
                    'tooltip' => $this->__(
                        'If you have Flat Shipping Rules or Calculated Shipping Rules set up in eBay,
                        you can choose to use them here.<br/><br/>
                        Click <b>Refresh Profiles</b> to get your latest shipping profiles from eBay.'
                    )
                ]
            )->addCustomAttribute('account_id', $this->getAccountId())
             ->setData('after_element_html', "<a href=\"javascript:void(0);\"
                    onclick=\"EbayTemplateShippingObj.updateDiscountProfiles(".$this->getAccountId().");\">"
                    .$this->__('Refresh Profiles')
                    ."</a>"
            );
        } else {
            $fieldSet->addField('account_combined_shipping_profile_local',
                self::CUSTOM_CONTAINER,
                [
                    'text' => $this->getAccountCombinedShippingProfile('local'),
                    'css_class' => 'local-shipping-tr'
                ]
            );
        }

        // ---------------------------------------

        $fieldSet->addField('local_shipping_discount_mode', self::SELECT,
            [
                'name' => 'shipping[local_shipping_discount_mode]',
                'label' => $this->__('Promotional Shipping Rule'),
                'title' => $this->__('Promotional Shipping Rule'),
                'values' => [
                    ['value' => 0, 'label' => __('No')],
                    ['value' => 1, 'label' => __('Yes')]
                ],
                'value' => $this->formData['local_shipping_discount_mode'],
                'css_class' => 'local-shipping-tr',
                'tooltip' => $this->__(
                    'Offers the Shipping Discounts according to the \'Promotional shipping Rule
                    (applies to all Listings)\' Settings in your eBay Account.
                    Shipping Discounts are set up directly on eBay, not in M2E Pro.
                    To set up or edit Shipping Discounts, Log in to your Seller Account on eBay.'
                )
            ]
        );

        // ---------------------------------------

        if ($this->canDisplayCashOnDeliveryCost()) {
            $fieldSet->addField('cash_on_delivery_cost', 'text',
                [
                    'name' => 'shipping[cash_on_delivery_cost]',
                    'label' => $this->__('"Cash On Delivery" Cost'),
                    'title' => $this->__('"Cash On Delivery" Cost'),
                    'value' => $this->formData['cash_on_delivery_cost'],
                    'class' => 'input-text M2ePro-validation-float',
                    'field_extra_attributes' => 'id="cash_on_delivery_cost_cv_tr"',
                    'tooltip' => $this->__('Required when using COD Payment Method.')
                ]
            );
        }

        // ---------------------------------------

        $fieldSet = $form->addFieldset('magento_block_ebay_template_shipping_form_data_international',
            [
                'legend' => __('International Shipping'),
                'collapsable' => true
            ]
        );

        if ($this->canDisplayNorthAmericaCrossBorderTradeOption()
            || $this->canDisplayUnitedKingdomCrossBorderTradeOption()) {

            $fieldSet->addField('cross_border_trade', self::SELECT,
                [
                    'name' => 'shipping[cross_border_trade]',
                    'label' => $this->__('Cross Border Trade'),
                    'title' => $this->__('Cross Border Trade'),
                    'values' => $this->getSiteVisibilityOptions(),
                    'value' => $this->formData['cross_border_trade'],
                    'field_extra_attributes' => 'id="cross_border_trade_container"',
                    'tooltip' => $this->__(
                        'The international Site visibility feature allows qualifying Listings to be posted on
                        international Marketplaces.
                        <br/>Buyers on these Marketplaces will see the Listings exactly as you originally post them.
                        <br/><br/><b>Note:</b> There may be additional eBay charges for this option.'
                    )
                ]
            );
        }

        if ($this->canDisplayGlobalShippingProgram()) {
            $fieldSet->addField('global_shipping_program', self::SELECT,
                [
                    'name' => 'shipping[global_shipping_program]',
                    'label' => $this->__('Offer Global Shipping Program'),
                    'title' => $this->__('Offer Global Shipping Program'),
                    'values' => [
                        ['value' => 0, 'label' => __('No')],
                        ['value' => 1, 'label' => __('Yes')]
                    ],
                    'value' => $this->formData['global_shipping_program'],
                    'tooltip' => $this->__(
                        'Simplifies selling an Item to an international Buyer. Click
                        <a href="http://pages.ebay.com/help/sell/shipping-globally.html"
                           target="_blank" class="external-link">here</a> to find out more.
                        <br/><br/><b>Note:</b> This option is available for eBay Motors only
                        under "Parts & Accessories" Category.'
                    )
                ]
            );
        }

        // ---------------------------------------

        $fieldSet->addField('international_shipping_mode', self::SELECT,
            [
                'name' => 'shipping[international_shipping_mode]',
                'label' => $this->__('Type'),
                'title' => $this->__('Type'),
                'values' => $this->getInternationalShippingOptions(),
                'value' => $this->formData['international_shipping_mode']
            ]
        );

        // ---------------------------------------

        if ($this->canDisplayInternationalShippingRateTable()) {
            $fieldSet->addField('international_shipping_rate_table_mode', self::SELECT,
                [
                    'name' => 'shipping[international_shipping_rate_table_mode]',
                    'label' => $this->__('Use eBay Shipping Rate Table'),
                    'title' => $this->__('Use eBay Shipping Rate Table'),
                    'values' => [
                        ['value' => 0, 'label' => __('No')],
                        ['value' => 1, 'label' => __('Yes')]
                    ],
                    'value' => $this->formData['international_shipping_rate_table_mode'],
                    'css_class' => 'international-shipping-tr',
                    'field_extra_attributes' => 'id="international_shipping_rate_table_mode_tr"',
                    'tooltip' => $this->__(
                        'You can set up Shipping Rate Tables in eBay to set the amount you charge for
                        postage by delivery method and destination and use them in your M2E Pro Listings.
                        Shipping Rate Tables are set up directly on eBay, not in M2E Pro.
                        To set up or edit Shipping Rate Tables, Log in to your Seller Account on eBay.'
                    )
                ]
            );
        }

        // ---------------------------------------

        $fieldSet->addField('shipping_international_table_messages',
            self::CUSTOM_CONTAINER,
            [
                'text' => '',
                'css_class' => 'm2epro-fieldset-table no-margin-bottom'
            ]
        );

        $fieldSet->addField('international_shipping_methods_tr_wrapper',
            self::CUSTOM_CONTAINER,
            [
                'text' => $this->getShippingInternationalTable(),
                'css_class' => 'm2epro-fieldset-table',
                'container_class' => 'international-shipping-tr international-shipping-always-visible-tr',
                'field_extra_attributes' => 'id="international_shipping_methods_tr"'
            ]
        );

        // ---------------------------------------

        $fieldSet->addField('international_handling_cost', 'text',
            [
                'name' => 'shipping[international_handling_cost]',
                'label' => $this->__('Handling Cost'),
                'title' => $this->__('Handling Cost'),
                'value' => $this->formData['international_handling_cost'],
                'css_class' => 'international-shipping-tr',
                'field_extra_attributes' => 'id="international_handling_cost_cv_tr"',
                'tooltip' => $this->__('Addition of handling cost to the shipping costs.')
            ]
        );

        // ---------------------------------------

        if (!is_null($this->getAccountId())) {
            $fieldsetCombined = $fieldSet->addFieldset('international_shipping_profile',
                [
                    'legend' => __('Combined Shipping Profile'),
                    'collapsable' => false,
                    'class' => 'international-shipping-tr'
                ]
            );

            $fieldsetCombined->addField('international_shipping_discount_profile_id_'.$this->getAccountId(),
                self::SELECT,
                [
                    'name' => 'shipping[international_shipping_discount_profile_id]['.$this->getAccountId().']',
                    'label' => $this->__('Combined Shipping Profile'),
                    'title' => $this->__('Combined Shipping Profile'),
                    'class' => 'international-discount-profile-account-tr',
                    'values' => [
                        ['label' => $this->__('None'), 'value' => '']
                    ],
                    'value' => '',
                    'style' => 'margin-right: 18px',
                    'tooltip' => $this->__(
                        'Use the Flat Shipping Rule and Calculated Shipping Rule Profiles, which were created on eBay.
                        <br/><br/><b>Note:</b> Press "Refresh Profiles" Button for upload new or refreshes
                        eBay Shipping Profiles.'
                    ),
                    'field_extra_attributes' => 'account_id="'.$this->getAccountId().'"'
                ]
            )->addCustomAttribute('account_id', $this->getAccountId())
             ->setData('after_element_html', "<a href=\"javascript:void(0);\"
                    onclick=\"EbayTemplateShippingObj.updateDiscountProfiles(".$this->getAccountId().");\">"
                    .$this->__('Refresh Profiles')
                    ."</a>"
            );
        } else {
            $fieldSet->addField('account_international_shipping_profile_international',
                self::CUSTOM_CONTAINER,
                [
                    'text' => $this->getAccountCombinedShippingProfile('international'),
                    'css_class' => 'international-shipping-tr'
                ]
            );
        }

        // ---------------------------------------

        $fieldSet->addField('international_shipping_discount_mode', self::SELECT,
            [
                'name' => 'shipping[international_shipping_discount_mode]',
                'label' => $this->__('Promotional Shipping Rule'),
                'title' => $this->__('Promotional Shipping Rule'),
                'values' => [
                    ['value' => 0, 'label' => __('No')],
                    ['value' => 1, 'label' => __('Yes')]
                ],
                'value' => $this->formData['international_shipping_discount_mode'],
                'class' => 'M2ePro-required-when-visible',
                'css_class' => 'international-shipping-tr',
                'tooltip' => $this->__('Add Shipping Discounts according to Rules that are set in your eBay Account.')
            ]
        );

        // ---------------------------------------

        // ---------------------------------------
        // Package details
        // ---------------------------------------

        $fieldSet = $form->addFieldset('magento_block_ebay_template_shipping_form_data_calculated',
            ['legend' => __('Package details'), 'collapsable' => true]
        );

        $fieldSet->addField('measurement_system', self::SELECT,
            [
                'name' => 'shipping[measurement_system]',
                'label' => $this->__('Measurement System'),
                'title' => $this->__('Measurement System'),
                'values' => $this->getMeasurementSystemOptions(),
                'value' => $this->formData['international_shipping_discount_mode'],
                'class' => 'select'
            ]
        );

        // ---------------------------------------

        $fieldSet->addField('package_size_mode', 'hidden',
            [
                'name' => 'shipping[package_size_mode]',
                'value' => $this->formData['package_size_mode']
            ]
        );

        $fieldSet->addField('package_size_value', 'hidden',
            [
                'name' => 'shipping[package_size_value]',
                'value' => $this->formData['package_size_value']
            ]
        );

        $fieldSet->addField('package_size_attribute', 'hidden',
            [
                'name' => 'shipping[package_size_attribute]',
                'value' => $this->formData['package_size_attribute']
            ]
        );

        // ---------------------------------------

        $fieldSet->addField('package_size', self::SELECT,
            [
                'label' => $this->__('Package Size Source'),
                'title' => $this->__('Package Size Source'),
                'values' => $this->getPackageSizeSourceOptions(),
                'field_extra_attributes' => 'id="package_size_tr"',
                'create_magento_attribute' => true
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        // ---------------------------------------

        $fieldSet->addField('dimension_mode', self::SELECT,
            [
                'name' => 'shipping[dimension_mode]',
                'label' => $this->__('Dimension Source'),
                'title' => $this->__('Dimension Source'),
                'values' => [
                    [
                        'value' => Shipping\Calculated::DIMENSION_NONE,
                        'label' => $this->__('None'),
                        'attrs' => ['id' => 'dimension_mode_none']
                    ],
                    [
                        'value' => Shipping\Calculated::DIMENSION_CUSTOM_VALUE,
                        'label' => $this->__('Custom Value')
                    ],
                    [
                        'value' => Shipping\Calculated::DIMENSION_CUSTOM_ATTRIBUTE,
                        'label' => $this->__('Custom Attribute'),
                        'attrs' => ['id' => 'dimension_mode_none']
                    ]
                ],
                'value' => $this->formData['dimension_mode'],
                'class' => 'select',
                'field_extra_attributes' => 'id="dimensions_tr"'
            ]
        );

        // ---------------------------------------

        // ---------------------------------------
        // Dimensions
        // ---------------------------------------

        $heightAttrBlock = $this->elementFactory->create(self::SELECT, ['data' => [
            'html_id' => 'shipping_dimension_length_attribute',
            'name' => 'shipping[dimension_length_attribute]',
            'values' => $this->getDimensionsOptions('dimension_length_attribute'),
            'value' => $this->formData['dimension_length_attribute'],
            'class' => 'M2ePro-required-when-visible dimension-custom-input',
            'create_magento_attribute' => true
        ]])->addCustomAttribute('allowed_attribute_types', 'text');
        $heightAttrBlock->setForm($form);

        $depthAttrBlock = $this->elementFactory->create(self::SELECT, ['data' => [
            'html_id' => 'shipping_dimension_depth_attribute',
            'name' => 'shipping[dimension_depth_attribute]',
            'values' => $this->getDimensionsOptions('dimension_depth_attribute'),
            'value' => $this->formData['dimension_depth_attribute'],
            'class' => 'M2ePro-required-when-visible dimension-custom-input',
            'create_magento_attribute' => true
        ]])->addCustomAttribute('allowed_attribute_types', 'text');
        $depthAttrBlock->setForm($form);

        $fieldSet->addField('dimension', self::SELECT,
            [
                'css_class' => 'dimensions_ca_tr',
                'name' => 'shipping[dimension_width_attribute]',
                'label' => $this->__('Dimensions (Width×Height×Depth)'),
                'values' => $this->getDimensionsOptions('dimension_width_attribute'),
                'value' => $this->formData['dimension_width_attribute'],
                'class' => 'M2ePro-required-when-visible dimension-custom-input',
                'required' => true,
                'note' => $this->__('inches'),
                'create_magento_attribute' => true,
                'after_element_html' => ' <span style="color: #303030">&times;</span> '
                    . $heightAttrBlock->toHtml()
                    . ' <span style="color: #303030">&times;</span> '
                    . $depthAttrBlock->toHtml()
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $heightValBlock = $this->elementFactory->create('text', ['data' => [
            'name' => 'shipping[dimension_length_value]',
            'value' => $this->formData['dimension_length_value'],
            'class' => 'input-text M2ePro-required-when-visible M2ePro-validation-float dimension-custom-input',
            'style' => 'width: 125px;'
        ]]);
        $heightValBlock->setForm($form);

        $depthValBlock = $this->elementFactory->create('text', ['data' => [
            'name' => 'shipping[dimension_depth_value]',
            'value' => $this->formData['dimension_depth_value'],
            'class' => 'input-text M2ePro-required-when-visible M2ePro-validation-float dimension-custom-input',
        ]]);
        $depthValBlock->setForm($form);

        $fieldSet->addField('dimension_width_attribute_text', 'text',
            [
                'css_class' => 'dimensions_cv_tr',
                'name' => 'shipping[dimension_width_value]',
                'label' => $this->__('Dimensions (Width×Height×Depth)'),
                'value' => $this->formData['dimension_width_value'],
                'class' => 'input-text M2ePro-required-when-visible M2ePro-validation-float dimension-custom-input',
                'required' => true,
                'note' => $this->__('inches'),
                'after_element_html' => ' <span style="color: #303030">&times;</span> '
                    . $heightValBlock->toHtml()
                    . ' <span style="color: #303030">&times;</span> '
                    . $depthValBlock->toHtml()
            ]
        );

        // ---------------------------------------

        $fieldSet->addField('weight_mode', 'hidden',
            [
                'name' => 'shipping[weight_mode]',
                'value' => $this->formData['weight_mode']
            ]
        );

        $fieldSet->addField('weight_attribute', 'hidden',
            [
                'name' => 'shipping[weight_attribute]',
                'value' => $this->formData['weight_attribute']
            ]
        );

        // ---------------------------------------

        $fieldSet->addField('weight', self::SELECT,
            [
                'name' => 'shipping[test]',
                'label' => $this->__('Weight Source'),
                'title' => $this->__('Weight Source'),
                'values' => $this->getWeightSourceOptions(),
                'value' => $this->formData['weight_mode'] != Shipping\Calculated::WEIGHT_CUSTOM_ATTRIBUTE
                           ? $this->formData['weight_mode'] : '',
                'class' => 'select',
                'field_extra_attributes' => 'id="weight_tr"',
                'tooltip' => $this->__('The Weight Attribute must have the weight of the Item.'),
                'note' => $this->__('lbs. oz.'),
                'create_magento_attribute' => true
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $weightMinorBlock = $this->elementFactory->create('text', ['data' => [
            'name' => 'shipping[weight_minor]',
            'value' => $this->formData['weight_minor'],
            'class' => 'M2ePro-required-when-visible M2ePro-validation-float input-text admin__control-text',
            'style' => 'width: 30%',
        ]]);
        $weightMinorBlock->setForm($form);

        $fieldSet->addField('weight_mode_container',
            'text',
            [
                'container_id' => 'weight_cv',
                'label' => $this->__('Weight'),
                'name' => 'shipping[weight_major]',
                'value' => $this->formData['weight_major'],
                'class' => 'M2ePro-required-when-visible M2ePro-validation-float input-text',
                'style' => 'width: 30%',
                'required' => true,
                'note' => $this->__('lbs. oz.'),
                'after_element_html' => '<span style="color: black;"> &times; </span>' . $weightMinorBlock->toHtml()
            ]
        );

        // ---------------------------------------

        // ---------------------------------------
        // Excluded Locations
        // ---------------------------------------

        $fieldSet = $form->addFieldset('magento_block_ebay_template_shipping_form_data_excluded_locations',
            [
                'legend' => $this->__('Excluded Locations'),
                'collapsable' => true,
                'tooltip' => $this->__(
                    'To exclude Buyers in certain Locations from purchasing your Item,
                    create a Shipping Exclusion List.'
                )
            ]
        );

        $fieldSet->addField('excluded_locations_hidden', 'hidden',
            [
                'name' => 'shipping[excluded_locations]',
                'value' => $this->getHelper('Data')->jsonEncode($this->formData['excluded_locations'])
            ]
        );

        $excludedLocationTitles = $this->__('No Locations are currently excluded.');
        if (!empty($this->formData['excluded_locations'])) {
            $excludedLocationTitles = array();
            foreach ($this->formData['excluded_locations'] as $location) {
                is_null($location['region']) && $location['title'] = '<b>'.$location['title'].'</b>';
                $excludedLocationTitles[] = $location['title'];
            }
            $excludedLocationTitles = implode(', ',$excludedLocationTitles) . '<br/>';
        }
        $fieldSet->addField(
            'excluded_locations',
            self::CUSTOM_CONTAINER,
            [
                'label' => __('Locations'),
                'text' => '<p><span id="excluded_locations_titles">'.$excludedLocationTitles.'</span></p>
                <a href="javascript:void(0)" onclick="EbayTemplateShippingObj.showExcludeListPopup();">'
                    .$this->__('Edit Exclusion List').
                '</a>'
            ]
        );

        // ---------------------------------------

        $this->setForm($form);
        return $this;
    }

    // ---------------------------------------

    public function getShippingLocalTable()
    {
        $localShippingMethodButton = $this
        ->createBlock('Magento\Button')
        ->setData(array(
            'onclick' => 'EbayTemplateShippingObj.addRow(\'local\');',
            'class' => 'add add_local_shipping_method_button primary'
        ));

        return <<<HTML

                <table id="shipping_local_table"
                       class="border data-grid data-grid-not-hovered"
                       cellpadding="0"
                       cellspacing="0">
                    <thead>
                        <tr class="headings">
                            <th class="data-grid-th">{$this->__('Service')} <span class="required">*</span></th>
                            <th class="data-grid-th" style="width: 190px;">{$this->__('Mode')}</th>
                            <th class="data-grid-th" style="width: 175px;">{$this->__('Cost')}
                                <span class="required">*</span>
                            </th>
                            <th class="data-grid-th" style="width: 175px;">{$this->__('Additional Cost')}
                                <span class="required">*</span>
                            </th>
                            <th class="data-grid-th" style="width: 80px;">{$this->__('Currency')}</th>
                            <th class="data-grid-th" style="width: 80px;">{$this->__('Priority')}</th>
                            <th class="type-butt last data-grid-th" style="width: 105px;">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody id="shipping_local_tbody">
                        <!-- #shipping_table_row_template inserts here -->
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="20" class="a-right">
                                {$localShippingMethodButton->setData('label', $this->__('Add Method'))->toHtml()}
                            </td>
                        </tr>
                    </tfoot>
                </table>

                <div id="add_local_shipping_method_button" style="display: none;">

                    <table style="border: none" cellpadding="0" cellspacing="0">
                        <tfoot>
                            <tr>
                                <td valign="middle" align="center" style="vertical-align: middle; height: 40px">
                                    {$localShippingMethodButton->setData(
                                       'label', $this->__('Add Shipping Method')
                                    )->toHtml()}
                                </td>
                            </tr>
                        </tfoot>
                    </table>

                </div>

                <input type="text"
                       name="local_shipping_methods_validator"
                       id="local_shipping_methods_validator"
                       class="M2ePro-validate-shipping-methods"
                       style="visibility: hidden; width: 100%; margin-top: -25px; display: block;" />
HTML;
    }

    public function getShippingInternationalTable()
    {

        $buttonBlock = $this
            ->createBlock('Magento\Button')
            ->setData(array(
                'onclick' => 'EbayTemplateShippingObj.addRow(\'international\');',
                'class' => 'add add_international_shipping_method_button primary'
            ));

        return <<<HTML
        <table id="shipping_international_table"
               class="border data-grid data-grid-not-hovered"
               cellpadding="0"
               cellspacing="0"
               style="display: none">
            <thead>
                <tr class="headings">
                    <th class="data-grid-th">{$this->__('Service')} <span class="required">*</span></th>
                    <th class="data-grid-th" style="width: 190px;">{$this->__('Mode')}</th>
                    <th class="data-grid-th" style="width: 175px;">
                        {$this->__('Cost')} <span class="required">*</span>
                    </th>
                    <th class="data-grid-th" style="width: 175px;">
                        {$this->__('Additional Cost')} <span class="required">*</span>
                    </th>
                    <th class="data-grid-th" style="width: 80px;">{$this->__('Currency')}</th>
                    <th class="data-grid-th" style="width: 80px;">{$this->__('Priority')}</th>
                    <th class="type-butt last data-grid-th" style="width: 105px;">&nbsp;</th>
                </tr>
            </thead>
            <tbody id="shipping_international_tbody">
                <!-- #shipping_table_row_template inserts here -->
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="20" class="a-right">
                        {$buttonBlock->setData('label', $this->__('Add Method'))->toHtml()}
                    </td>
                </tr>
            </tfoot>
        </table>

        <div id="add_international_shipping_method_button" style="display: none; width: 1109px">

            <table style="border: none" cellpadding="0" cellspacing="0">
                <tfoot>
                    <tr>
                        <td valign="middle" align="center" style="vertical-align: middle; height: 40px">
                            {$buttonBlock->setData('label', $this->__('Add Shipping Method'))->toHtml()}
                        </td>
                    </tr>
                </tfoot>
            </table>

        </div>

        <input type="text"
               name="international_shipping_methods_validator"
               id="international_shipping_methods_validator"
               class="M2ePro-validate-shipping-methods"
               style="visibility: hidden; width: 100%; margin-top: -25px; display: block;">
HTML;
    }

    public function getAccountCombinedShippingProfile($locationType)
    {
        $html = '';
        $discountProfiles = $this->getDiscountProfiles();

        if (count($discountProfiles) > 0) {
            foreach ($discountProfiles as $accountId => $value) {
                $html .= <<<HTML
                    <tr class="{$locationType}-discount-profile-account-tr" account_id="{$accountId}">
                        <td class="label v-middle">
                            <label for="{$locationType}_shipping_discount_profile_id_{$accountId}">
                                {$value['account_name']}
                            </label>
                        </td>

                        <td class="value" style="border-right: none;">
                            <select class="select admin__control-select"
                                    name="shipping[{$locationType}_shipping_discount_profile_id][{$accountId}]"
                                    id="{$locationType}_shipping_discount_profile_id_{$accountId}">

                            </select>
                            {$this->getTooltipHtml($this->__(
                                'If you have Flat Shipping Rules or Calculated Shipping Rules set up in eBay,
                                 you can choose to use them here.<br/><br/>
                                 Click <b>Refresh Profiles</b> to get your latest shipping profiles from eBay.'
                            ))}
                        </td>
                        <td class="value v-middle" style="width: 200px; border-left: none;">
                            <a href="javascript:void(0);"
                               onclick="EbayTemplateShippingObj.updateDiscountProfiles({$accountId});">
                                  {$this->__('Refresh Profiles')}
                            </a>
                        </td>
                    </tr>
HTML;
            }
        } else {
            $html .= "<tr><td colspan=\"4\" style=\"text-align: center\">
                        {$this->__('You do not have eBay Accounts added to M2E Pro.')}
                     </td></tr>";

        }

        return <<<HTML
        <table id="{$locationType}_shipping_discount_profile_table"
               class="shipping-discount-profile-table data-grid data-grid-not-hovered data-grid-striped">
            <thead>
                <tr class="headings">
                    <th class="data-grid-th v-middle" style="width: 30%">
                        {$this->__('Account')}
                    </th>
                    <th class="data-grid-th v-middle" colspan="3">
                        {$this->__('Combined Shipping Profile')}
                    </th>
                </tr>
            </thead>

            {$html}
        </table>
HTML;

    }

    // ---------------------------------------

    public function getAttributesOptions($attributeValue, $conditionCallback = false)
    {
        $options = [
            'value' => [],
            'label' => $this->__('Magento Attribute'),
            'attrs' => ['is_magento_attribute' => true]
        ];
        $helper = $this->getHelper('Data');

        foreach ($this->attributes as $attribute) {
            $tmpOption = [
                'value' => $attributeValue,
                'label' => $helper->escapeHtml($attribute['label']),
                'attrs' => ['attribute_code' => $attribute['code']]
            ];

            if (is_callable($conditionCallback) && $conditionCallback($attribute)) {
                $tmpOption['attrs']['selected'] = 'selected';
            }

            $options['value'][] = $tmpOption;
        }

        return $options;
    }

    public function getCountryOptions()
    {
        $countryOptions = ['value' => [], 'label' => $this->__('Custom Value')];
        $helper = $this->getHelper('Data');

        foreach ($this->getHelper('Magento')->getCountries() as $country) {
            if (empty($country['value'])) {
                continue;
            }

            $tmpOption = [
                'value' => Shipping::COUNTRY_MODE_CUSTOM_VALUE,
                'label' => $helper->escapeHtml($country['label']),
                'attrs' => ['attribute_code' => $country['value']]
            ];

            if ($this->formData['country_mode'] == Shipping::COUNTRY_MODE_CUSTOM_VALUE
                && $country['value'] == $this->formData['country_custom_value']) {
                $tmpOption['attrs']['selected'] = 'selected';
            }

            $countryOptions['value'][] = $tmpOption;
        }

        return $countryOptions;
    }

    public function getDomesticShippingOptions()
    {
        $options = [[
            'value' => Shipping::SHIPPING_TYPE_FLAT,
            'label' => $this->__('Flat: same cost to all Buyers'),
            'attrs' => ['id' => 'local_shipping_mode_flat']
        ]];

        if ($this->canDisplayLocalCalculatedShippingType()) {
            $options[] = [
                'value' => Shipping::SHIPPING_TYPE_CALCULATED,
                'label' => $this->__('Calculated: cost varies by Buyer Location'),
                'attrs' => ['id' => 'local_shipping_mode_calculated']
            ];
        }

        if ($this->canDisplayFreightShippingType()) {
            $options[] = [
                'value' => Shipping::SHIPPING_TYPE_FREIGHT,
                'label' => $this->__('Freight: large Items'),
                'attrs' => ['id' => 'local_shipping_mode_freight']
            ];
        }

        $options[] = [
            'value' => Shipping::SHIPPING_TYPE_LOCAL,
            'label' => $this->__('No Shipping: local pickup only'),
            'attrs' => ['id' => 'local_shipping_mode_local']
        ];

        return $options;
    }

    public function getDispatchTimeOptions()
    {
        $options = [
            ['value' => '', 'label' => '', 'attrs' => ['class' => 'empty']]
        ];

        $isExceptionHandlingTimes = false;
        foreach ($this->marketplaceData['dispatch'] as $index => $dispatchOption) {
            if($dispatchOption['ebay_id'] > 3 && !$isExceptionHandlingTimes) {
                $options['opt_group'] = ['value' => [], 'label' => $this->__('Exception Handling Times')];
                $isExceptionHandlingTimes = true;
            }

            if ($dispatchOption['ebay_id'] == 0) {
                $label = $this->__('Same Business Day');
            } else {
                $label = $this->__(str_replace('Day','Business Day',$dispatchOption['title']));
            }

            $tmpOption = [
                'value' => $dispatchOption['ebay_id'],
                'label' => $label
            ];

            if ($isExceptionHandlingTimes) {
                $options['opt_group']['value'][] = $tmpOption;
            } else {
                $options[] = $tmpOption;
            }
        }

        return $options;
    }

    public function getSiteVisibilityOptions()
    {
        $options = [[
            'value' => Shipping::CROSS_BORDER_TRADE_NONE,
            'label' => $this->__('None')
        ]];

        if ($this->canDisplayNorthAmericaCrossBorderTradeOption()) {
            $options[] = [
                'value' => Shipping::CROSS_BORDER_TRADE_NORTH_AMERICA,
                'label' => $this->__('USA / Canada')
            ];
        }

        if ($this->canDisplayUnitedKingdomCrossBorderTradeOption()) {
            $options[] = [
                'value' => Shipping::CROSS_BORDER_TRADE_UNITED_KINGDOM,
                'label' => $this->__('United Kingdom')
            ];
        }

        return $options;
    }

    public function getInternationalShippingOptions()
    {
        $options = [
            [
                'value' => Shipping::SHIPPING_TYPE_NO_INTERNATIONAL,
                'label' => $this->__('No International Shipping'),
                'attrs' => ['id' => 'international_shipping_none']
            ],
            [
                'value' => Shipping::SHIPPING_TYPE_FLAT,
                'label' => $this->__('Flat: same cost to all Buyers')
            ]
        ];

        if ($this->canDisplayInternationalCalculatedShippingType()) {
            $options[] = [
                'value' => Shipping::SHIPPING_TYPE_CALCULATED,
                'label' => $this->__('Calculated: cost varies by Buyer Location')
            ];
        }

        return $options;
    }

    public function getMeasurementSystemOptions()
    {
        $options = [];

        if ($this->canDisplayEnglishMeasurementSystemOption()) {
            $options[] = [
                'value' => Shipping\Calculated::MEASUREMENT_SYSTEM_ENGLISH,
                'label' => $this->__('English (lbs, oz, in)')
            ];
        }

        if ($this->canDisplayMetricMeasurementSystemOption()) {
            $options[] = [
                'value' => Shipping\Calculated::MEASUREMENT_SYSTEM_METRIC,
                'label' => $this->__('Metric (kg, g, cm)')
            ];
        }

        return $options;
    }

    public function getPackageSizeSourceOptions()
    {
        $helper = $this->getHelper('Data');

        $ebayValues = ['value' => [], 'label' => $this->__('eBay Values')];
        foreach ($this->marketplaceData['packages'] as $package) {

            $tmp = [
                'value' => Shipping\Calculated::PACKAGE_SIZE_CUSTOM_VALUE,
                'label' => $package['title'],
                'attrs' => [
                    'attribute_code' => $helper->escapeHtml($package['ebay_id']),
                    'dimensions_supported' => $package['dimensions_supported']
                ]
            ];

            if ($this->formData['package_size_value'] == $package['ebay_id']
                && $this->formData['package_size_mode'] == Shipping\Calculated::PACKAGE_SIZE_CUSTOM_VALUE) {
                $tmp['attrs']['selected'] = 'selected';
            }

            $ebayValues['value'][] = $tmp;
        }

        $attributesOptions = [
            'value' => [],
            'label' => $this->__('Magento Attributes'),
            'attrs' => ['is_magento_attribute' => true]
        ];
        if (isset($this->missingAttributes['package_size_attribute'])) {
            $attributesOptions['value'][] = [
                'value' => Shipping\Calculated::PACKAGE_SIZE_CUSTOM_ATTRIBUTE,
                'label' => $helper->escapeHtml($this->missingAttributes['package_size_attribute']),
                'attrs' => [
                    'attribute_code' => $this->formData['package_size_attribute']
                ]
            ];
        }

        foreach ($this->attributesByInputTypes['text_select'] as $attribute) {

            $tmp = [
                'value' => Shipping\Calculated::PACKAGE_SIZE_CUSTOM_ATTRIBUTE,
                'label' => $helper->escapeHtml($attribute['label']),
                'attrs' => [
                    'attribute_code' => $attribute['code']
                ]
            ];

            if ($this->formData['package_size_attribute'] == $attribute['code']
                && $this->formData['package_size_mode'] == Shipping\Calculated::PACKAGE_SIZE_CUSTOM_ATTRIBUTE) {
                $tmp['attrs']['selected'] = 'selected';
            }

            $attributesOptions['value'][] = $tmp;
        }

        return [$ebayValues, $attributesOptions];
    }

    public function getDimensionsOptions($attributeCode)
    {
        $options = [
            ['value' => '', 'label' => '', 'attrs' => ['class' => 'empty-option']]
        ];
        $helper = $this->getHelper('Data');

        if (isset($this->missingAttributes[$attributeCode])) {
            $options[] = [
                'value' => $this->formData[$attributeCode],
                'label' => $helper->escapeHtml($this->missingAttributes[$attributeCode])
            ];
        }

        foreach ($this->attributesByInputTypes['text'] as $attribute) {
            $options[] = [
                'value' => $attribute['code'],
                'label' => $helper->escapeHtml($attribute['label'])
            ];
        }

        return $options;
    }

    public function getWeightSourceOptions()
    {
        $options = [
            [
                'value' => Shipping\Calculated::WEIGHT_NONE,
                'label' => $this->__('None'),
                'attrs' => ['id' => 'weight_mode_none']
            ],
            [
                'value' => Shipping\Calculated::WEIGHT_CUSTOM_VALUE,
                'label' => $this->__('Custom Value')
            ],
            'option_group' => [
                'value' => [],
                'label' => $this->__('Magento Attributes'),
                'attrs' => ['is_magento_attribute' => true]
            ]
        ];

        $helper = $this->getHelper('Data');
        if (isset($this->missingAttributes['weight_attribute'])) {
            $tmpOption = [
                'value' => Shipping\Calculated::WEIGHT_CUSTOM_ATTRIBUTE,
                'label' => $helper->escapeHtml($this->missingAttributes['weight_attribute']),
                'attrs' => ['attribute_code' => $this->formData['weight_attribute']]
            ];

            if ($this->formData['weight_mode'] == Shipping\Calculated::WEIGHT_CUSTOM_ATTRIBUTE) {
                $tmpOption['attrs']['selected'] = 'selected';
            }

            $options['option_group']['value'][] = $tmpOption;
        }

        foreach ($this->attributesByInputTypes['text_weight'] as $attribute) {
            $tmpOption = [
                'value' => Shipping\Calculated::WEIGHT_CUSTOM_ATTRIBUTE,
                'label' => $helper->escapeHtml($attribute['label']),
                'attrs' => ['attribute_code' => $attribute['code']]
            ];

            if ($this->formData['weight_mode'] == Shipping\Calculated::WEIGHT_CUSTOM_ATTRIBUTE
                && $this->formData['weight_attribute'] == $attribute['code']) {
                $tmpOption['attrs']['selected'] = 'selected';
            }

            $options['option_group']['value'][] = $tmpOption;
        }

        return $options;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getMarketplace()
    {
        $marketplace = $this->getHelper('Data\GlobalData')->getValue('ebay_marketplace');

        if (!$marketplace instanceof \Ess\M2ePro\Model\Marketplace) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Marketplace is required for editing Shipping Policy.');
        }

        return $marketplace;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    public function getAccount()
    {
        $account = $this->getHelper('Data\GlobalData')->getValue('ebay_account');

        if (!$account instanceof \Ess\M2ePro\Model\Account) {
            return NULL;
        }

        return $account;
    }

    public function getAccountId()
    {
        return $this->getAccount() ? $this->getAccount()->getId() : NULL;
    }

    //########################################

    public function getDiscountProfiles()
    {
        $template = $this->getHelper('Data\GlobalData')->getValue('ebay_template_shipping');

        $localDiscount = $template->getData('local_shipping_discount_profile_id');
        $internationalDiscount = $template->getData('international_shipping_discount_profile_id');

        !is_null($localDiscount) && $localDiscount = $this->getHelper('Data')->jsonDecode($localDiscount);
        !is_null($internationalDiscount) && $internationalDiscount = $this->getHelper('Data')->jsonDecode(
            $internationalDiscount
        );

        $accountCollection = $this->ebayFactory->getObject('Account')->getCollection();

        $profiles = array();

        foreach ($accountCollection as $account) {
            $accountId = $account->getId();

            $temp = array();
            $temp['account_name'] = $account->getTitle();
            $temp['selected']['local'] = isset($localDiscount[$accountId]) ? $localDiscount[$accountId] : '';
            $temp['selected']['international'] = isset($internationalDiscount[$accountId]) ?
                $internationalDiscount[$accountId] : '';

            $accountProfiles = $account->getChildObject()->getData('ebay_shipping_discount_profiles');
            $temp['profiles'] = array();

            if (is_null($accountProfiles)) {
                $profiles[$accountId] = $temp;
                continue;
            }

            $accountProfiles = $this->getHelper('Data')->jsonDecode($accountProfiles);
            $marketplaceId = $this->getMarketplace()->getId();

            if (is_array($accountProfiles) && isset($accountProfiles[$marketplaceId]['profiles'])) {
                foreach ($accountProfiles[$marketplaceId]['profiles'] as $profile) {
                    $temp['profiles'][] = array(
                        'type' => $this->getHelper('Data')->escapeHtml($profile['type']),
                        'profile_id' => $this->getHelper('Data')->escapeHtml($profile['profile_id']),
                        'profile_name' => $this->getHelper('Data')->escapeHtml($profile['profile_name'])
                    );
                }
            }

            $profiles[$accountId] = $temp;
        }

        return $profiles;
    }

    //########################################

    public function isCustom()
    {
        if (isset($this->_data['is_custom'])) {
            return (bool)$this->_data['is_custom'];
        }

        return false;
    }

    public function getTitle()
    {
        if ($this->isCustom()) {
            return isset($this->_data['custom_title']) ? $this->_data['custom_title'] : '';
        }

        $template = $this->getHelper('Data\GlobalData')->getValue('ebay_template_shipping');

        if (is_null($template)) {
            return '';
        }

        return $template->getTitle();
    }

    public function getFormData()
    {
        if (!empty($this->formData)) {
            return $this->formData;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Template\Shipping $template */
        $template = $this->getHelper('Data\GlobalData')->getValue('ebay_template_shipping');

        $default = $this->getDefault();
        if (is_null($template) || is_null($template->getId())) {
            return $default;
        }

        $this->formData = $template->getData();
        $this->formData['services'] = $template->getServices();

        $calculated = $template->getCalculatedShipping();

        if (!is_null($calculated)) {
            $this->formData = array_merge($this->formData, $calculated->getData());
        }

        if (is_string($this->formData['excluded_locations'])) {
            $excludedLocations = $this->getHelper('Data')->jsonDecode($this->formData['excluded_locations']);
            $this->formData['excluded_locations'] = is_array($excludedLocations) ? $excludedLocations : array();
        } else {
            unset($this->formData['excluded_locations']);
        }

        return array_merge($default, $this->formData);
    }

    public function getDefault()
    {
        $default = $this->activeRecordFactory->getObject('Ebay\Template\Shipping')
                                             ->getDefaultSettingsAdvancedMode();

        $default['excluded_locations'] = $this->getHelper('Data')->jsonDecode($default['excluded_locations']);

        // populate address fields with the data from magento configuration
        // ---------------------------------------
        $store = $this->getHelper('Data\GlobalData')->getValue('ebay_store');

        $city = $store->getConfig('shipping/origin/city');
        $regionId = $store->getConfig('shipping/origin/region_id');
        $countryId = $store->getConfig('shipping/origin/country_id');
        $postalCode = $store->getConfig('shipping/origin/postcode');

        $address = array(trim($city));

        if ($regionId) {
            $region = $this->regionFactory->create()->load($regionId);

            if ($region->getId()) {
                $address[] = trim($region->getName());
            }
        }

        $address = implode(', ', array_filter($address));

        if ($countryId) {
            $default['country_mode'] = \Ess\M2ePro\Model\Ebay\Template\Shipping::ADDRESS_MODE_CUSTOM_VALUE;
            $default['country_custom_value'] = $countryId;
        }

        if ($postalCode) {
            $default['postal_code_mode'] = \Ess\M2ePro\Model\Ebay\Template\Shipping::POSTAL_CODE_MODE_CUSTOM_VALUE;
            $default['postal_code_custom_value'] = $postalCode;
        }

        if ($address) {
            $default['address_mode'] = \Ess\M2ePro\Model\Ebay\Template\Shipping::ADDRESS_MODE_CUSTOM_VALUE;
            $default['address_custom_value'] = $address;
        }

        // ---------------------------------------

        return $default;
    }

    public function getMarketplaceData()
    {
        $data = array(
            'id' => $this->getMarketplace()->getId(),
            'currency' => $this->getMarketplace()->getChildObject()->getCurrency(),
            'services' => $this->getMarketplace()->getChildObject()->getShippingInfo(),
            'packages' => $this->getMarketplace()->getChildObject()->getPackageInfo(),
            'dispatch' => $this->getSortedDispatchInfo(),
            'locations' => $this->getMarketplace()->getChildObject()->getShippingLocationInfo(),
            'locations_exclude' => $this->getSortedLocationExcludeInfo(),
            'origin_country' => $this->getMarketplace()->getChildObject()->getOriginCountry(),
        );

        $data['services'] = $this->modifyNonUniqueShippingServicesTitles($data['services']);

        $policyLocalization = $this->getData('policy_localization');

        if (!empty($policyLocalization)) {

            $translator = $this->getHelper('Module\Translation');

            foreach ($data['services'] as $serviceKey => $service) {
                $data['services'][$serviceKey]['title'] = $translator->__($service['title']);
                foreach ($service['methods'] as $methodKey => $method) {
                    $data['services'][$serviceKey]['methods'][$methodKey]['title'] = $translator->__($method['title']);
                }
            }

            foreach ($data['locations'] as $key => $item) {
                $data['locations'][$key]['title'] =  $translator->__($item['title']);
            }

            foreach ($data['locations_exclude'] as $regionKey => $region) {
                foreach ($region as $locationKey => $location) {
                    $data['locations_exclude'][$regionKey][$locationKey] = $translator->__($location);
                }
            }
        }

        return $data;
    }

    // ---------------------------------------

    private function getSortedDispatchInfo()
    {
        $dispatchInfo = $this->getMarketplace()->getChildObject()->getDispatchInfo();

        $ebayIds = array();
        foreach ($dispatchInfo as $dispatchRecord) {
            $ebayIds[] = $dispatchRecord['ebay_id'];
        }
        array_multisort($ebayIds, SORT_ASC, $dispatchInfo);

        return $dispatchInfo;
    }

    private function getSortedLocationExcludeInfo()
    {
        $sortedInfo = array(
            'international' => array(),
            'domestic' => array(),
            'additional' => array()
        );

        foreach ($this->getMarketplace()->getChildObject()->getShippingLocationExcludeInfo() as $item) {

            $region = $item['region'];

            strpos(strtolower($item['region']), 'worldwide') !== false && $region = 'international';
            strpos(strtolower($item['region']), 'domestic') !== false && $region = 'domestic';
            strpos(strtolower($item['region']), 'additional') !== false && $region = 'additional';

            $sortedInfo[$region][$item['ebay_id']] = $item['title'];
        }

        foreach ($sortedInfo as $code => $info) {

            if ($code == 'domestic' || $code == 'international' || $code == 'additional') {
                continue;
            }

            $isInternational = array_key_exists($code, $sortedInfo['international']);
            $isDomestic = array_key_exists($code, $sortedInfo['domestic']);
            $isAdditional = array_key_exists($code, $sortedInfo['additional']);

            if (!$isInternational && !$isDomestic && !$isAdditional) {

                $foundedItem = array();
                foreach ($this->getMarketplace()->getChildObject()->getShippingLocationExcludeInfo() as $item) {
                    $item['ebay_id'] == $code && $foundedItem = $item;
                }

                if (empty($foundedItem)) {
                    continue;
                }

                unset($sortedInfo[$foundedItem['region']][$code]);
                $sortedInfo['international'][$code] = $foundedItem['title'];
            }
        }

        return $sortedInfo;
    }

    //########################################

    private function modifyNonUniqueShippingServicesTitles($services)
    {
        foreach ($services as &$category) {

            $nonUniqueTitles = array();
            foreach ($category['methods'] as $key => $method) {
                $nonUniqueTitles[$method['title']][] = $key;
            }

            foreach ($nonUniqueTitles as $methodsKeys) {
                if (count($methodsKeys) > 1) {

                    foreach ($methodsKeys as $key) {
                        $ebayId = $category['methods'][$key]['ebay_id'];
                        $title = $category['methods'][$key]['title'];

                        $duplicatedPart = str_replace(' ', '', preg_quote($title, '/'));
                        $uniqPart = preg_replace('/\w*'.$duplicatedPart.'/i', '', $ebayId);
                        $uniqPart = preg_replace('/([A-Z]+[a-z]*)/', '${1} ', $uniqPart);

                        $category['methods'][$key]['title'] = trim($title) . ' ' . str_replace('_', '', $uniqPart);
                    }
                }
            }
        }

        return $services;
    }

    //########################################

    public function getAttributesJsHtml()
    {
        $html = '';

        $attributes = $this->getHelper('Magento\Attribute')->filterByInputTypes(
            $this->attributes, array('text', 'price', 'select')
        );

        foreach ($attributes as $attribute) {
            $code = $this->getHelper('Data')->escapeHtml($attribute['code']);
            $html .= sprintf('<option value="%s">%s</option>', $code, $attribute['label']);
        }

        return $this->getHelper('Data')->escapeJs($html);
    }

    public function getMissingAttributes()
    {
        $formData = $this->getFormData();

        if (empty($formData)) {
            return array();
        }

        $attributes = array();

        // m2epro_ebay_template_shipping_service
        // ---------------------------------------
        $attributes['services'] = array();
        $magentoAttributeHelper = $this->getHelper('Magento\Attribute');

        foreach ($formData['services'] as $i => $service) {
            $mode = 'cost_mode';
            $code = 'cost_value';

            if ($service[$mode] == \Ess\M2ePro\Model\Ebay\Template\Shipping\Service::COST_MODE_CUSTOM_ATTRIBUTE) {
                if (!$this->isExistInAttributesArray($service[$code])) {
                    $label = $magentoAttributeHelper->getAttributeLabel($service[$code]);
                    $attributes['services'][$i][$code] = $label;
                }
            }

            $mode = 'cost_mode';
            $code = 'cost_additional_value';

            if ($service[$mode] == \Ess\M2ePro\Model\Ebay\Template\Shipping\Service::COST_MODE_CUSTOM_ATTRIBUTE) {
                if (!$this->isExistInAttributesArray($service[$code])) {
                    $label = $magentoAttributeHelper->getAttributeLabel($service[$code]);
                    $attributes['services'][$i][$code] = $label;
                }
            }

            $mode = 'cost_mode';
            $code = 'cost_surcharge_value';

            if ($service[$mode] == \Ess\M2ePro\Model\Ebay\Template\Shipping\Service::COST_MODE_CUSTOM_ATTRIBUTE) {
                if (!$this->isExistInAttributesArray($service[$code])) {
                    $label = $magentoAttributeHelper->getAttributeLabel($service[$code]);
                    $attributes['services'][$i][$code] = $label;
                }
            }
        }
        // ---------------------------------------

        // m2epro_ebay_template_shipping_calculated
        // ---------------------------------------
        if (!empty($formData['calculated'])) {
            $code = 'package_size_attribute';
            if (!$this->isExistInAttributesArray($formData['calculated'][$code])) {
                $label = $magentoAttributeHelper->getAttributeLabel($formData['calculated'][$code]);
                $attributes['calculated'][$code] = $label;
            }

            $code = 'dimension_width_attribute';
            if (!$this->isExistInAttributesArray($formData['calculated'][$code])) {
                $label = $magentoAttributeHelper->getAttributeLabel($formData['calculated'][$code]);
                $attributes['calculated'][$code] = $label;
            }

            $code = 'dimension_length_attribute';
            if (!$this->isExistInAttributesArray($formData['calculated'][$code])) {
                $label = $magentoAttributeHelper->getAttributeLabel($formData['calculated'][$code]);
                $attributes['calculated'][$code] = $label;
            }

            $code = 'dimension_depth_attribute';
            if (!$this->isExistInAttributesArray($formData['calculated'][$code])) {
                $label = $magentoAttributeHelper->getAttributeLabel($formData['calculated'][$code]);
                $attributes['calculated'][$code] = $label;
            }

            $code = 'weight_attribute';
            if (!$this->isExistInAttributesArray($formData['calculated'][$code])) {
                $label = $magentoAttributeHelper->getAttributeLabel($formData['calculated'][$code]);
                $attributes['calculated'][$code] = $label;
            }
        }
        // ---------------------------------------

        return $attributes;
    }

    //########################################

    public function isExistInAttributesArray($code)
    {
        if (!$code) {
            return true;
        }

        return $this->getHelper('Magento\Attribute')->isExistInAttributesArray($code, $this->attributes);
    }

    //########################################

    public function canDisplayLocalShippingRateTable()
    {
        return $this->getMarketplace()->getChildObject()->isLocalShippingRateTableEnabled();
    }

    public function canDisplayClickAndCollectOption()
    {
        return $this->getMarketplace()->getChildObject()->isClickAndCollectEnabled();
    }

    public function canDisplayFreightShippingType()
    {
        return $this->getMarketplace()->getChildObject()->isFreightShippingEnabled();
    }

    public function canDisplayCalculatedShippingType()
    {
        return $this->getMarketplace()->getChildObject()->isCalculatedShippingEnabled();
    }

    public function canDisplayLocalCalculatedShippingType()
    {
        if (!$this->canDisplayCalculatedShippingType()) {
            return false;
        }

        return true;
    }

    public function canDisplayInternationalCalculatedShippingType()
    {
        if (!$this->canDisplayCalculatedShippingType()) {
            return false;
        }

        return true;
    }

    public function canDisplayInternationalShippingRateTable()
    {
        return $this->getMarketplace()->getChildObject()->isInternationalShippingRateTableEnabled();
    }

    public function canDisplayCashOnDeliveryCost()
    {
        return $this->getMarketplace()->getChildObject()->isCashOnDeliveryEnabled();
    }

    public function canDisplayNorthAmericaCrossBorderTradeOption()
    {
        $marketplace = $this->getMarketplace();

        return $marketplace->getId() == 3   // UK
        || $marketplace->getId() == 17; // Ireland
    }

    public function canDisplayUnitedKingdomCrossBorderTradeOption()
    {
        $marketplace = $this->getMarketplace();

        return $marketplace->getId() == 1   // US
        || $marketplace->getId() == 2;  // Canada
    }

    public function canDisplayEnglishMeasurementSystemOption()
    {
        return $this->getMarketplace()->getChildObject()->isEnglishMeasurementSystemEnabled();
    }

    public function canDisplayMetricMeasurementSystemOption()
    {
        return $this->getMarketplace()->getChildObject()->isMetricMeasurementSystemEnabled();
    }

    public function canDisplayGlobalShippingProgram()
    {
        return $this->getMarketplace()->getChildObject()->isGlobalShippingProgramEnabled();
    }

    //########################################

    public function isLocalShippingModeCalculated()
    {
        $formData = $this->getFormData();

        if (!isset($formData['local_shipping_mode'])) {
            return false;
        }

        $mode = $formData['local_shipping_mode'];

        return $mode == \Ess\M2ePro\Model\Ebay\Template\Shipping::SHIPPING_TYPE_CALCULATED;
    }

    public function isInternationalShippingModeCalculated()
    {
        $formData = $this->getFormData();

        if (!isset($formData['international_shipping_mode'])) {
            return false;
        }

        $mode = $formData['international_shipping_mode'];

        return $mode == \Ess\M2ePro\Model\Ebay\Template\Shipping::SHIPPING_TYPE_CALCULATED;
    }

    //########################################

    protected function _beforeToHtml()
    {
        // ---------------------------------------
        $buttonBlock = $this
            ->createBlock('Magento\Button')
            ->setData(array(
                'label'   => $this->__('Remove'),
                'onclick' => 'EbayTemplateShippingObj.removeRow.call(this, \'%type%\');',
                'class' => 'delete icon-btn remove_shipping_method_button'
            ));
        $this->setChild('remove_shipping_method_button', $buttonBlock);
        // ---------------------------------------

        return parent::_beforeToHtml();
    }

    protected function _toHtml()
    {
        $this->jsTranslator->addTranslations([
            'Location or Zip/Postal Code should be specified.' => $this->__(
                'Location or Zip/Postal Code should be specified.'
            ),
            'Select one or more international ship-to Locations.' => $this->__(
                'Select one or more international ship-to Locations.'
            ),
            'PayPal payment method should be specified for Cross Border trade.' => $this->__(
                'PayPal payment method should be specified for Cross Border trade.'
            ),
            'You should specify at least one Shipping Method.' => $this->__(
                'You should specify at least one Shipping Method.'
            ),
            'None' => $this->__('None'),
            'Select Shipping Service' => $this->__('Select Shipping Service'),

            'Excluded Shipping Locations' => $this->__('Excluded Shipping Locations'),
            'No Locations are currently excluded.' => $this->__('No Locations are currently excluded.'),
            'selected' => $this->__('selected')
        ]);

        $this->jsUrl->addUrls([
            'ebay_template_shipping/updateDiscountProfiles' => $this->getUrl(
                '*/ebay_template_shipping/updateDiscountProfiles',
                [
                    'marketplace_id' => $this->marketplaceData['id'],
                    'account_id' => $this->getAccountId()
                ]
            )
        ]);

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Ebay\Template\Shipping')
        );
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Ebay\Template\Shipping\Service')
        );
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Ebay\Template\Shipping\Calculated')
        );

        $missingAttributes = $this->getHelper('Data')->jsonEncode($this->missingAttributes);
        $services = $this->getHelper('Data')->jsonEncode($this->marketplaceData['services']);
        $locations = $this->getHelper('Data')->jsonEncode($this->marketplaceData['locations']);
        $discountProfiles = $this->getHelper('Data')->jsonEncode($this->getDiscountProfiles());
        $originCountry = $this->getHelper('Data')->jsonEncode($this->marketplaceData['origin_country']);

        $formDataServices = $this->getHelper('Data')->jsonEncode($this->formData['services']);

        $this->js->addRequireJs([
            'form' => 'M2ePro/Ebay/Template/Shipping',
            'attr' => 'M2ePro/Attribute',
        ], <<<JS

        if (typeof AttributeObj === 'undefined') {
            window.AttributeObj = new Attribute();
        }
        window.AttributeObj.attrData = '{$this->getAttributesJsHtml()}';

        window.EbayTemplateShippingObj = new EbayTemplateShipping();

        var shippingMethods = {$formDataServices};
        _.forEach(shippingMethods, function(shipping, i) {
            shippingMethods[i].locations = shipping.locations.evalJSON();
        });

        EbayTemplateShippingObj.shippingMethods = shippingMethods;

        EbayTemplateShippingObj.counter = {
            local: 0,
            international: 0,
            total: 0
        };

        EbayTemplateShippingObj.initExcludeListPopup();

        EbayTemplateShippingObj.missingAttributes = {$missingAttributes};
        EbayTemplateShippingObj.shippingServices = {$services};
        EbayTemplateShippingObj.shippingLocations = {$locations};
        EbayTemplateShippingObj.discountProfiles = {$discountProfiles};
        EbayTemplateShippingObj.originCountry = {$originCountry};

        EbayTemplateShippingObj.initObservers();
JS
        );
        return parent::_toHtml();
    }

    //########################################

    public function getCurrencyAvailabilityMessage()
    {
        $marketplace = $this->getHelper('Data\GlobalData')->getValue('ebay_marketplace');
        $store       = $this->getHelper('Data\GlobalData')->getValue('ebay_store');
        $template    = $this->getHelper('Data\GlobalData')->getValue('ebay_template_selling_format');

        if (is_null($template) || is_null($template->getId())) {
            $templateData = $this->getDefault();
            $templateData['component_mode'] = \Ess\M2ePro\Helper\Component\Ebay::NICK;
            $usedAttributes = array();
        } else {
            $templateData = $template->getData();
            $usedAttributes = $template->getUsedAttributes();
        }

        $messagesBlock = $this
            ->createBlock('Template\Messages')
            ->getResultBlock(
                \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING,
                \Ess\M2ePro\Helper\Component\Ebay::NICK
            );

        $messagesBlock->setData('template_data', $templateData);
        $messagesBlock->setData('used_attributes', $usedAttributes);
        $messagesBlock->setData('marketplace_id', $marketplace ? $marketplace->getId() : null);
        $messagesBlock->setData('store_id', $store ? $store->getId() : null);

        $messages = $messagesBlock->getMessages();

        if (empty($messages)) {
            return '';
        }

        return $messagesBlock->getMessagesHtml($messages);
    }

    //########################################
}