<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\SellingFormat\Edit\Form;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Ebay\Template\SellingFormat;

class Data extends AbstractForm
{
    protected $resourceConnection;
    protected $currency;
    protected $cacheConfig;
    protected $ebayFactory;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Locale\Currency $currency,
        \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->currency = $currency;
        $this->cacheConfig = $cacheConfig;
        $this->ebayFactory = $ebayFactory;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $attributes = $this->getHelper('Data\GlobalData')->getValue('ebay_attributes');

        /** @var \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper */
        $magentoAttributeHelper = $this->getHelper('Magento\Attribute');

        $attributesByInputTypes = $this->getAttributesByInputTypes();

        $formData = $this->getFormData();
        $default = $this->getDefault();
        $formData = array_merge($default, $formData);

        $taxCategories = $this->getTaxCategoriesInfo();

        $form = $this->_formFactory->create();

        $form->addField(
            'selling_format_id',
            'hidden',
            [
                'name' => 'selling_format[id]',
                'value' => (!$this->isCustom() && isset($formData['id'])) ? (int)$formData['id'] : ''
            ]
        );

        $form->addField(
            'selling_format_title',
            'hidden',
            [
                'name' => 'selling_format[title]',
                'value' => $this->getTitle()
            ]
        );

        $form->addField(
            'selling_format_is_custom_template',
            'hidden',
            [
                'name' => 'selling_format[is_custom_template]',
                'value' => $this->isCustom() ? 1 : 0
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_template_selling_format_edit_form_general',
            [
                'legend' => $this->__('How You Want To Sell Your Item'),
                'collapsable' => true
            ]
        );

        $preparedAttributes = [];
        if (
            $formData['listing_type'] == SellingFormat::LISTING_TYPE_ATTRIBUTE
            && !$magentoAttributeHelper->isExistInAttributesArray($formData['listing_type_attribute'], $attributes)
            && $formData['listing_type_attribute'] != ''
        ) {
            $preparedAttributes[] = [
                'attrs' => [
                    'attribute_code' => $formData['listing_type_attribute'],
                    'selected' => 'selected'
                ],
                'value' => SellingFormat::LISTING_TYPE_ATTRIBUTE,
                'label' => $magentoAttributeHelper->getAttributeLabel($formData['listing_type_attribute']),
            ];
        }

        foreach ($attributesByInputTypes['text_select'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['listing_type'] == SellingFormat::LISTING_TYPE_ATTRIBUTE
                && $attribute['code'] == $formData['listing_type_attribute']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => SellingFormat::LISTING_TYPE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField('listing_type',
            self::SELECT,
            [
                'name' => 'selling_format[listing_type]',
                'label' => $this->__('Listing Type'),
                'values' => [
                    SellingFormat::LISTING_TYPE_AUCTION => $this->__('Auction'),
                    SellingFormat::LISTING_TYPE_FIXED => $this->__('Fixed Price'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => $formData['listing_type'] != SellingFormat::LISTING_TYPE_ATTRIBUTE
                    ? $formData['listing_type'] : '',
                'create_magento_attribute' => true,
                'tooltip' => $this->__(
                    'Choose whether Products Listed with this Policy on eBay Auction-style or Fixed Price.<br/>
                     Auction-style Listings are best if you are unsure of the value of your Item,
                     eg because it is unique. Fixed Price Listings are good if you know the
                     Price you want to get for your Item.
                     <br/><br/>Alternatively, you can specify a Magento Attribute to set the
                     Listing type depending on the Attribute Value set for each Product.<br/>
                     For Fixed Price Listings, set the Attribute Value to <b>FixedPriceItem</b>;
                     for Auction-style Listings, set the Attribute Value to <b>Chinese</b>.<br/>
                     An empty or wrong value in Attribute will be considered as Fixed Price Listing Type.'
                )
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        $fieldset->addField(
            'listing_type_attribute',
            'hidden',
            [
                'name' => 'selling_format[listing_type_attribute]'
            ]
        );

        $fieldset->addField('listing_is_private',
            self::SELECT,
            [
                'label' => $this->__('Private Listing'),
                'name' => 'selling_format[listing_is_private]',
                'values' => [
                    SellingFormat::LISTING_IS_PRIVATE_NO => $this->__('No'),
                    SellingFormat::LISTING_IS_PRIVATE_YES => $this->__('Yes'),
                ],
                'value' => $formData['listing_is_private'],
                'tooltip' => $this->__(
                    'Making your Listing Private means that the details of the Listing won\'t be shown on the
                    Feedback Profile Page for you or the Buyer.<br/><br/>
                    This can be useful in cases where you or the Buyer might want to be discreet about the
                    Item and/or the final Price - such as the sale of
                    high-priced Items or approved pharmaceutical Products.
                    You should only make your Listing Private for a specific reason.'
                )
            ]
        );

        $fieldset->addField('restricted_to_business',
            self::SELECT,
            [
                'label' => $this->__('For Business Users Only'),
                'name' => 'selling_format[restricted_to_business]',
                'values' => [
                    0 => $this->__('Disabled'),
                    1 => $this->__('Enabled'),
                ],
                'value' => $formData['restricted_to_business'],
                'tooltip' => $this->__(
                    'If <strong>Yes</strong>, this indicates that you elect to offer the
                     Item exclusively to business users. <br/>
                     If <strong>No</strong>, this indicates that you elect to offer the Item to all users. <br/><br/>
                     Applicable only to business Sellers residing in Germany, Austria,
                     or Switzerland who are Listing in a B2B VAT-enabled Category on the eBay Germany (DE),
                     Austria (AT), or Switzerland (CH) Marketplaces. <br/>
                     If this argument is <strong>Yes</strong>, you must have a valid VAT-ID registered with eBay,
                     and <i>BusinessSeller</i> must also be true.'
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_template_selling_format_edit_form_qty_and_duration',
            [
                'legend' => $this->__('Quantity And Duration'),
                'collapsable' => true
            ]
        );

        $preparedAttributes = [];
        foreach ($this->getHelper('Component\Ebay')->getAvailableDurations() as $id => $label) {
            $preparedAttributes[] = [
                'attrs' => ['id' => "durationId$id"],
                'value' => $id,
                'label' => $label
            ];
        }

        $durationTooltip = $this->__(
                'The length of time your eBay Listing will be active. You can have it last 1, 3, 5, 7, 10, 30 days or
                Good \'Til Cancelled.
                <br/>Good \'Til Cancelled Listings renew automatically every 30 days unless all of the Items sell,
                you end the Listing, or the Listing breaches an eBay Policy.'
            )
            . '<span id="duration_attribute_note">'
            . $this->__(
                '<br/>Attribute must contain a whole number. If you choose "Good Till Cancelled"
                the Attribute must contain 100.'
            )
            . '</span>';

        $fieldset->addField('duration_mode',
            self::SELECT,
            [
                'container_id' => 'duration_mode_container',
                'label' => $this->__('Listing Duration'),
                'name' => 'selling_format[duration_mode]',
                'values' => $preparedAttributes,
                'value' => $formData['duration_mode'],
                'tooltip' => $durationTooltip
            ]
        );

        $preparedAttributes = [];
        if (
            $formData['duration_mode'] == SellingFormat::LISTING_TYPE_ATTRIBUTE
            && !$magentoAttributeHelper->isExistInAttributesArray($formData['duration_attribute'], $attributes)
            && $formData['duration_attribute'] != ''
        ) {
            $preparedAttributes[] = [
                'attrs' => [
                    'attribute_code' => $formData['duration_attribute'],
                    'selected' => 'selected',
                ],
                'value' => SellingFormat::LISTING_TYPE_ATTRIBUTE,
                'label' => $magentoAttributeHelper->getAttributeLabel($formData['duration_attribute']),
            ];
        }

        foreach ($attributesByInputTypes['text'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['duration_mode'] == SellingFormat::LISTING_TYPE_ATTRIBUTE
                && $formData['duration_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => SellingFormat::LISTING_TYPE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField('duration_attribute',
            self::SELECT,
            [
                'container_id' => 'duration_attribute_container',
                'label' => $this->__('Listing Duration'),
                'values' => [
                    [
                        'label' => '', 'value' => '',
                        'attrs' => ['style' => 'display: none']
                    ],
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'create_magento_attribute' => true,
                'required' => true,
                'tooltip' => $durationTooltip
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField(
            'listing_duration_attribute_value',
            'hidden',
            [
                'name' => 'selling_format[duration_attribute]',
                'value' => $formData['duration_attribute']
            ]
        );

        $fieldset->addField('out_of_stock_control_mode',
            self::SELECT,
            [
                'name' => 'selling_format[out_of_stock_control]',
                'container_id' => 'out_of_stock_control_tr',
                'label' => $this->__('Out Of Stock Control'),
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value' => $formData['out_of_stock_control'],
                'tooltip' => $this->__(
                    'This is useful if you have run Out Of Stock of an Item that you have Listed on eBay.
                    If you choose <b>Yes</b>, the eBay Listing is hidden temporarily instead of being ended completely.
                    When new Stock for the Item arrives, you can use automatic or manual Revise Rules to
                    update the Inventory and the eBay Listing will appear again.<br/><br/>
                    <b>Note:</b> Once you list a Product with this option it cannot be changed.
                    To cancel it you should end the eBay Listing before Listing the Item again.'
                )
            ]
        );

        $preparedAttributes = [
            [
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT_FIXED,
                'label' => $this->__('QTY')
            ]
        ];

        if (
            $formData['qty_mode'] == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE
            && !$magentoAttributeHelper->isExistInAttributesArray($formData['qty_custom_attribute'], $attributes)
            && $formData['qty_custom_attribute'] != ''
        ) {
            $preparedAttributes[] = [
                'attrs' => [
                    'attribute_code' => $formData['qty_custom_attribute'],
                    'selected' => 'selected',
                ],
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE,
                'label' => $magentoAttributeHelper->getAttributeLabel($formData['qty_custom_attribute']),
            ];
        }

        foreach ($attributesByInputTypes['text'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['qty_mode'] == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE
                && $formData['qty_custom_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField('qty_mode',
            self::SELECT,
            [
                'container_id' => 'qty_mode_tr',
                'name' => 'selling_format[qty_mode]',
                'label' => $this->__('Quantity'),
                'values' => [
                    \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT => $this->__('Product Quantity'),
                    \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_SINGLE => $this->__('Single Item'),
                    \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_NUMBER => $this->__('Custom Value'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                            'new_option_value' => \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE
                        ]
                    ]
                ],
                'value' => $formData['qty_mode'] != \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE
                    ? $formData['qty_mode'] : '',
                'create_magento_attribute' => true,
                'tooltip' => $this->__(
                    'The number of Items you want to sell on eBay.<br/><br/>
                    <b>Product Quantity:</b> the number of Items on eBay will be the same as in Magento.<br/>
                    <b>Single Item:</b> only one Item will be available on eBay.<br/>
                    <b>Custom Value:</b> set a Quantity in the Policy here.<br/>
                    <b>Magento Attribute:</b> takes the number from the Attribute you specify.'
                )
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField('qty_custom_attribute',
            'hidden',
            [
                'name' => 'selling_format[qty_custom_attribute]',
            ]
        );

        $fieldset->addField('qty_custom_value',
            'text',
            [
                'container_id' => 'qty_mode_cv_tr',
                'label' => $this->__('Quantity Value'),
                'name' => 'selling_format[qty_custom_value]',
                'value' => $formData['qty_custom_value'],
                'class' => 'validate-digits',
                'required' => true
            ]
        );

        $preparedAttributes = [];
        for ($i = 100; $i >= 5; $i -= 5) {
            $preparedAttributes[] = [
                'value' => $i,
                'label' => $i . ' %'
            ];
        }

        $fieldset->addField('qty_percentage',
            self::SELECT,
            [
                'container_id' => 'qty_percentage_tr',
                'label' => $this->__('Quantity Percentage'),
                'name' => 'selling_format[qty_percentage]',
                'values' => $preparedAttributes,
                'value' => $formData['qty_percentage'],
                'tooltip' => $this->__(
                    'Sets the percentage for calculation of Items number to be Listed on eBay basing on
                    Product Quantity or Magento Attribute. E.g., if Quantity Percentage is set to 10% and
                    Product Quantity is 100, the Quantity to be Listed on
                    eBay will be calculated as <br/>100 *10%  = 10.<br/>'
                )
            ]
        );

        $fieldset->addField('qty_modification_mode',
            self::SELECT,
            [
                'container_id' => 'qty_modification_mode_tr',
                'label' => $this->__('Conditional Quantity'),
                'name' => 'selling_format[qty_modification_mode]',
                'values' => [
                    SellingFormat::QTY_MODIFICATION_MODE_OFF => $this->__('Disabled'),
                    SellingFormat::QTY_MODIFICATION_MODE_ON => $this->__('Enabled'),
                ],
                'value' => $formData['qty_modification_mode'],
                'tooltip' => $this->__(
                    'Choose whether to limit the amount of Stock you list on eBay, eg because you want to set
                    some Stock aside for sales off eBay.<br/><br/>
                    If this Setting is <b>Enabled</b> you can specify the maximum Quantity to be Listed.
                    If this Setting is <b>Disabled</b> all Stock for the Product will be Listed as available on eBay.'
                )
            ]
        );

        $fieldset->addField('qty_min_posted_value',
            'text',
            [
                'container_id' => 'qty_min_posted_value_tr',
                'label' => $this->__('Minimum Quantity to Be Listed'),
                'name' => 'selling_format[qty_min_posted_value]',
                'value' => $formData['qty_min_posted_value'],
                'class' => 'M2ePro-validate-qty',
                'required' => true,
                'tooltip' => $this->__(
                    'If you have 2 pieces in Stock but set a Minimum Quantity to Be Listed of 5,
                    Item will not be Listed on eBay.<br/>
                    Otherwise, the Item will be Listed with Quantity according to the Settings in the Price,
                    Quantity and Format Policy'
                )
            ]
        );

        $fieldset->addField('qty_max_posted_value',
            'text',
            [
                'container_id' => 'qty_max_posted_value_tr',
                'label' => $this->__('Maximum Quantity to Be Listed'),
                'name' => 'selling_format[qty_max_posted_value]',
                'value' => $formData['qty_max_posted_value'],
                'class' => 'M2ePro-validate-qty',
                'required' => true,
                'tooltip' => $this->__(
                    'Set a maximum number to sell on eBay, e.g. if you have 10 Items in Stock but want
                    to keep 2 Items back, set a Maximum Quantity of 8.'
                )
            ]
        );

        $fieldset->addField('ignore_variations_value',
            self::SELECT,
            [
                'container_id' => 'ignore_variations_value_tr',
                'label' => $this->__('Ignore Variations'),
                'name' => 'selling_format[ignore_variations]',
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value' => $formData['ignore_variations'],
                'tooltip' => $this->__(
                    'Choose how you want to list Configurable, Grouped, Bundle, Simple With Custom Options
                    and Downloadable with Separated Links Products.
                    Choosing <b>Yes</b> will list these types of Products as
                    if they are Simple Products without Variations.'
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_template_selling_format_edit_form_taxation',
            [
                'legend' => $this->__('Taxation'),
                'collapsable' => true
            ]
        );

        $fieldset->addField('vat_percent',
            'text',
            [
                'container_id' => 'vat_percent_tr',
                'label' => $this->__('VAT Rate, %'),
                'name' => 'selling_format[vat_percent]',
                'value' => $formData['vat_percent'],
                'class' => 'M2ePro-validate-vat',
                'tooltip' => $this->__(
                    'To specify a VAT Rate, you must have an eBay business Account and list the Item on a
                    VAT-enabled Site: Ireland, India, Switzerland, Spain, Holland, Belgium, Italy, Germany, France,
                    Austria or United Kingdom. The Item\'s VAT information appears on the Item\'s Listing Page.'
                )
            ]
        );

        $fieldset->addField('tax_table_mode',
            self::SELECT,
            [
                'container_id' => 'tax_table_mode_tr',
                'label' => $this->__('Use eBay Tax Table'),
                'name' => 'selling_format[tax_table_mode]',
                'value' => $formData['tax_table_mode'],
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Choose if you want to use the Tax Table for your eBay Account to charge Sales Tax.
                    Tax Tables are set up directly on eBay, not in M2E Pro. To set up or edit Tax Tables,
                    Log in to your Seller Account on eBay.
                    They are available only for Canada, Canada (Fr), USA and eBay Motors.'
                )
            ]
        );

        if ($this->getHelper('Component\Ebay')->isShowTaxCategory()) {

            $preparedValues = [];

            if (!empty($taxCategories)) {
                $preparedAttributesCategories = [];

                foreach ($taxCategories as $taxCategory) {
                    $attrs = ['attribute_code' => $taxCategory['ebay_id']];
                    if (
                        $formData['tax_category_mode'] == SellingFormat::TAX_CATEGORY_MODE_VALUE
                        && $formData['tax_category_value'] == $taxCategory['ebay_id']
                    ) {
                        $attrs['selected'] = 'selected';
                    }
                    $preparedAttributesCategories[] = [
                        'attrs' => $attrs,
                        'value' => SellingFormat::TAX_CATEGORY_MODE_VALUE,
                        'label' => $taxCategory['title'],
                    ];
                }

                $preparedValues[] = [
                    'label' => $this->__('Ebay Recommended'),
                    'value' => $preparedAttributesCategories
                ];
            }

            if (!empty($attributesByInputTypes['text'])) {
                $preparedAttributes = [];

                foreach ($attributesByInputTypes['text'] as $attribute) {
                    $attrs = ['attribute_code' => $attribute['code']];
                    if (
                        $formData['tax_category_mode'] == SellingFormat::TAX_CATEGORY_MODE_ATTRIBUTE
                        && $formData['tax_category_attribute'] == $attribute['code']
                    ) {
                        $attrs['selected'] = 'selected';
                    }
                    $preparedAttributes[] = [
                        'attrs' => $attrs,
                        'value' => SellingFormat::TAX_CATEGORY_MODE_ATTRIBUTE,
                        'label' => $attribute['label'],
                    ];
                }

                $preparedValues[] = [
                    'label' => $this->__('Magento Attributes'),
                    'value' => $preparedAttributes,
                    'attrs' => ['is_magento_attribute' => true]
                ];
            }

            $fieldset->addField('tax_category_mode',
                self::SELECT,
                [
                    'label' => $this->__('Tax Category'),
                    'values' => array_merge(
                        [SellingFormat::TAX_CATEGORY_MODE_NONE => $this->__('None')],
                        $preparedValues
                    ),
                    'value' => $formData['tax_category_mode'] != SellingFormat::TAX_CATEGORY_MODE_VALUE
                    && $formData['tax_category_mode'] != SellingFormat::TAX_CATEGORY_MODE_ATTRIBUTE
                        ? $formData['tax_category_mode'] : '',
                    'create_magento_attribute' => true,
                ]
            )->addCustomAttribute('allowed_attribute_types', 'text');

            $fieldset->addField('tax_category_value',
                'hidden',
                [
                    'name' => 'selling_format[tax_category_value]',
                    'value' => $formData['tax_category_value']
                ]
            );

            $fieldset->addField('tax_category_attribute',
                'hidden',
                [
                    'name' => 'selling_format[tax_category_attribute]',
                    'value' => $formData['tax_category_attribute']
                ]
            );
        }

        $fieldset = $form->addFieldset('magento_block_ebay_template_selling_format_edit_form_prices',
            [
                'legend' => $this->__('Price'),
                'collapsable' => true
            ]
        );

        $currencyAvailabilityMessage = $this->getCurrencyAvailabilityMessage();
        $fieldset->addField('template_selling_format_messages',
            self::CUSTOM_CONTAINER,
            [
                'text' => $currencyAvailabilityMessage,
                'css_class' => 'm2epro-fieldset-table no-margin-bottom'
            ]
        );

        $fieldset->addField('price_increase_vat_percent',
            self::SELECT,
            [
                'label' => $this->__('Add VAT Percentage'),
                'name' => 'selling_format[price_increase_vat_percent]',
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value' => $formData['price_increase_vat_percent'],
                'tooltip' => $this->__(
                    'Choose whether you want to add VAT to the Price when a Product is Listed on eBay,
                     using the VAT Rate or eBay Tax Table in the Taxation Section above.<br/><br/>
                     <b>Example:</b> For a Product with Magento Price = £10, VAT Rate = 19%,
                     Price change percentage increase = 2%, the final eBay Price will be £12.14.<br/>
                     Magento Price: £10<br/>
                     VAT 19%: £10 * 19% = £1.90<br/>
                     Magento Price + VAT = £10 + £1.90 = £11.90<br/>
                     add percentage increase 2% = £11.90 * 2% = £0.24<br/><br/>
                     <b>Final Price on eBay</b>: £11.90 + £0.24 = £12.14'
                ),
                'css_class' => 'no-margin-top'
            ]
        );

        $fieldset->addField('price_table_container',
            self::CUSTOM_CONTAINER,
            [
                'text' => $this->getPriceTableHtml(),
                'css_class' => 'm2epro-fieldset-table'
            ]
        );

        $fieldset = $form->addFieldset('magento_block_ebay_template_selling_format_edit_form_charity',
            [
                'legend' => $this->__('Donations'),
                'collapsable' => true
            ]
        );

        if ($this->getMarketplace()) {

            $fieldset->addClass('charity-row');

            $charityData = $this->getHelper('Data')->jsonDecode($formData['charity']);

            if (!empty($charityData[$this->getMarketplaceId()])) {
                $charityData = $charityData[$this->getMarketplaceId()];
            } else {
                $charityData = NULL;
            }

            $charityDictionary = $this->getCharityDictionary();

            $featuredCharities = [];
            $selectedCharityExist = false;

            foreach ($charityDictionary[$this->getMarketplaceId()]['charities'] as $charity) {
                $attrs = [];
                if (!empty($charityData) &&
                    $charityData['organization_id'] == $charity['id']) {

                    $selectedCharityExist = true;
                    $attrs['selected'] = 'selected';
                }
                $featuredCharities[] = [
                    'attrs' => $attrs,
                    'value' => $charity['id'],
                    'label' => $charity['name'],
                ];
            }

            $customCharities = [];
            if (!empty($charityData) && !$selectedCharityExist) {
                $customCharities[] = [
                    'attrs' => ['selected' => 'selected'],
                    'value' => $charityData['organization_id'],
                    'label' => $charityData['organization_name'],
                ];
            }

            $values = [
                [
                    'label' => $this->__('None'),
                    'value' => '',
                ],
                [
                    'label' => $this->__('Search for Charity Organization'),
                    'value' => '0',
                    'attrs' => ['class' => 'searchNewCharity'],
                ]
            ];

            if (!empty($customCharities)) {
                $values[] = [
                    'label' => $this->__('Custom'),
                    'value' => $customCharities,
                    'attrs' => ['class' => 'customCharity']
                ];
            }

            if (!empty($featuredCharities)) {
                $values[] = [
                    'label' => $this->__('Featured'),
                    'value' => $featuredCharities,
                    'attrs' => ['class' => 'featuredCharity']
                ];
            }

            $fieldset->addField('charity_organization',
                self::SELECT,
                [
                    'label' => $this->__('Organization'),
                    'name' => 'selling_format[charity][organization_id][0]',
                    'class' => 'charity-organization',
                    'onchange' => 'EbayTemplateSellingFormatObj.charityOrganizationCustomModeChange(this)',
                    'value' => empty($charityData['organization_id']) ?
                        '' : $charityData['organization_id'],
                    'values' => $values,
                    'tooltip' => $this->__(
                        'Choose whether to donate a percentage of your eBay sales to a non-profit/charity.'
                    )
                ]
            );

            $fieldset->addField('organization_name',
                'hidden',
                [
                    'name' => 'selling_format[charity][organization_name][0]',
                    'class' => 'organization_name',
                    'value' => empty($charityData['organization_name']) ?
                        '' : $charityData['organization_name'],
                ]
            );

            $fieldset->addField('organization_custom',
                'hidden',
                [
                    'name' => 'selling_format[charity][organization_custom][0]',
                    'class' => 'organization_custom',
                    'value' => empty($charityData['organization_name']) ?
                        0 : $charityData['organization_name'],

                ]
            );

            $fieldset->addField('charity_marketplace_id',
                'hidden',
                [
                    'id' => 'charity_marketplace_id',
                    'name' => 'selling_format[charity][marketplace_id][0]',
                    'class' => 'charity-marketplace_id',
                    'value' => $this->getMarketplace()->getId()
                ]
            );

            $percentageValues = [[
                'label' => '',
                'value' => '',
                'attrs' => ['class' => 'empty']
            ]]            ;

            if ($this->getMarketplaceId() == \Ess\M2ePro\Helper\Component\Ebay::MARKETPLACE_MOTORS) {
                $percentageValues[] = [
                    'label' => '1%',
                    'value' => 1,
                ];
                $percentageValues[] = [
                    'label' => '5%',
                    'value' => '5'
                ];
            }

            for ($i = 2; $i < 21; $i++) {
                $percentageValues[] = [
                    'value' => $i*5,
                    'label' => $i*5 . '%',
                ];
            }

            $style = empty($charityData['percentage']) ? 'style="display: none;"' : '';

            $fieldset->addField('charity_percentage',
                self::SELECT,
                [
                    'label' => $this->__('Donation Percentage'),
                    'name' => 'selling_format[charity][percentage][0]',
                    'class' => 'charity-percentage M2ePro-required-when-visible',
                    'values' => $percentageValues,
                    'value' => empty($charityData['percentage']) ?
                        '' : $charityData['percentage'],
                    'field_extra_attributes' => 'id="charity_percentage" ' . $style
                ]
            );

        } else {
            $charityBlock =  $this->createBlock('Ebay\Template\SellingFormat\Edit\Form\Charity')->addData([
                'form_data' => $formData
            ]);

            $fieldset->addField('charity_table_container',
                self::CUSTOM_CONTAINER,
                [
                    'text' => $charityBlock->toHtml(),
                    'css_class' => 'm2epro-fieldset-table'
                ]
            );
        }

        $fieldset = $form->addFieldset('magento_block_ebay_template_selling_format_edit_form_best_offer',
            [
                'legend' => $this->__('Best Offer'),
                'collapsable' => true
            ]
        );

        $fieldset->addField('template_selling_format_messages_best_offer',
            self::CUSTOM_CONTAINER,
            [
                'css_class' => 'm2epro-fieldset-table no-margin-bottom'
            ]
        );

        $fieldset->addField('best_offer_mode',
            self::SELECT,
            [
                'label' => $this->__('Allow Best Offer'),
                'name' => 'selling_format[best_offer_mode]',
                'values' => [
                    SellingFormat::BEST_OFFER_MODE_YES => $this->__('Yes'),
                    SellingFormat::BEST_OFFER_MODE_NO => $this->__('No'),
                ],
                'value' => $formData['best_offer_mode'],
                'tooltip' => $this->__(
                    'The Best Offer Option allows you to accept offers from Buyers and negotiate a Price.
                    You can accept Best Offers on fixed Price and Classified Ads in certain Categories,
                    such as eBay Motors.'
                )
            ]
        );

        $bestOfferAcceptValue = $this->elementFactory->create('text', ['data' => [
            'html_id' => 'best_offer_accept_value',
            'name' => 'selling_format[best_offer_accept_value]',
            'value' => $formData['best_offer_accept_value'],
            'class' => 'coef validate-digits M2ePro-required-when-visible',
            'after_element_html' => '%'
        ]]);
        $bestOfferAcceptValue->setForm($form);

        $preparedAttributes = [];

        if (
            $formData['best_offer_accept_mode'] == SellingFormat::BEST_OFFER_ACCEPT_MODE_ATTRIBUTE &&
            !$magentoAttributeHelper->isExistInAttributesArray($formData['best_offer_accept_attribute'], $attributes) &&
            $formData['best_offer_accept_attribute'] != ''
        ) {
            $preparedAttributes[] = [
                'attrs' => [
                    'attribute_code' => $formData['best_offer_accept_attribute'],
                    'selected' => 'selected',
                ],
                'value' => SellingFormat::BEST_OFFER_ACCEPT_MODE_ATTRIBUTE,
                'label' => $magentoAttributeHelper->getAttributeLabel($formData['best_offer_accept_attribute']),
            ];
        }

        foreach ($attributesByInputTypes['text_price'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['best_offer_accept_mode'] == SellingFormat::BEST_OFFER_ACCEPT_MODE_ATTRIBUTE
                && $formData['best_offer_accept_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => SellingFormat::BEST_OFFER_ACCEPT_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField('best_offer_accept_mode',
            self::SELECT,
            [
                'css_class' => 'best_offer_respond_table_container',
                'label' => $this->__('Accept Offers of at Least'),
                'name' => 'selling_format[best_offer_accept_mode]',
                'values' => [
                    SellingFormat::BEST_OFFER_ACCEPT_MODE_NO => $this->__('No'),
                    [
                        'label' => '#',
                        'value' => SellingFormat::BEST_OFFER_ACCEPT_MODE_PERCENTAGE,
                        'attrs' => [
                            'id' => 'best_offer_accept_percentage_option'
                        ]
                    ],
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => $formData['best_offer_accept_mode'] != SellingFormat::BEST_OFFER_ACCEPT_MODE_ATTRIBUTE
                    ? $formData['best_offer_accept_mode'] : '',
                'create_magento_attribute' => true,
                'note' => !is_null($this->getCurrency()) ? $this->__('Currency') . ': ' . $this->getCurrency() : '',
                'after_element_html' => '<span id="best_offer_accept_value_tr">'
                    . $bestOfferAcceptValue->toHtml() . '</span>'
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,price');

        $fieldset->addField('best_offer_accept_custom_attribute',
            'hidden',
            [
                'name' => 'selling_format[best_offer_accept_attribute]'
            ]
        );

        //-----------

        $bestOfferRejectValue = $this->elementFactory->create('text', ['data' => [
            'html_id' => 'best_offer_reject_value',
            'name' => 'selling_format[best_offer_reject_value]',
            'value' => $formData['best_offer_reject_value'],
            'class' => 'coef validate-digits M2ePro-required-when-visible',
            'after_element_html' => '%'
        ]]);
        $bestOfferRejectValue->setForm($form);

        $preparedAttributes = [];

        if (
            $formData['best_offer_reject_mode'] == SellingFormat::BEST_OFFER_REJECT_MODE_ATTRIBUTE &&
            !$magentoAttributeHelper->isExistInAttributesArray($formData['best_offer_reject_attribute'], $attributes) &&
            $formData['best_offer_reject_attribute'] != ''
        ) {
            $preparedAttributes[] = [
                'attrs' => [
                    'attribute_code' => $formData['best_offer_reject_attribute'],
                    'selected' => 'selected',
                ],
                'value' => SellingFormat::BEST_OFFER_REJECT_MODE_ATTRIBUTE,
                'label' => $magentoAttributeHelper->getAttributeLabel($formData['best_offer_reject_attribute']),
            ];
        }

        foreach ($attributesByInputTypes['text_price'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['best_offer_reject_mode'] == SellingFormat::BEST_OFFER_REJECT_MODE_ATTRIBUTE
                && $formData['best_offer_reject_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => SellingFormat::BEST_OFFER_REJECT_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField('best_offer_reject_mode',
            self::SELECT,
            [
                'css_class' => 'best_offer_respond_table_container',
                'label' => $this->__('Decline Offers Less than'),
                'name' => 'selling_format[best_offer_reject_mode]',
                'values' => [
                    SellingFormat::BEST_OFFER_REJECT_MODE_NO => $this->__('No'),
                    [
                        'label' => '#',
                        'value' => SellingFormat::BEST_OFFER_REJECT_MODE_PERCENTAGE,
                        'attrs' => [
                            'id' => 'best_offer_reject_percentage_option'
                        ]
                    ],
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => $formData['best_offer_reject_mode'] != SellingFormat::BEST_OFFER_REJECT_MODE_ATTRIBUTE
                    ? $formData['best_offer_reject_mode'] : '',
                'create_magento_attribute' => true,
                'note' => !is_null($this->getCurrency()) ? $this->__('Currency') . ': ' . $this->getCurrency() : '',
                'after_element_html' => '<span id="best_offer_reject_value_tr">'
                    . $bestOfferRejectValue->toHtml() . '</span>'
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,price');

        $fieldset->addField('best_offer_reject_custom_attribute',
            'hidden',
            [
                'name' => 'selling_format[best_offer_reject_attribute]'
            ]
        );

        $this->setForm($form);

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Template\SellingFormat')
        );
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Ebay\Template\SellingFormat')
        );
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Ebay\Template\Manager')
        );
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Helper\Component\Ebay')
        );

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Ebay\Template\SellingFormat'));

        $this->jsTranslator->addTranslations([
            'Search For Charities' => $this->__('Search For Charities'),
            'Please select a percentage of donation' => $this->__('Please select a percentage of donation'),
            'If you do not see the organization you were looking for, '.
                'try to enter another keywords and run the Search again.' => $this->__(
                'If you do not see the organization you were looking for,
                try to enter another keywords and run the Search again.'
            ),
            'Please, enter the organization name or ID.' => $this->__('Please, enter the organization name or ID.'),
            'wrong_value_more_than_30' => $this->__(
                'Wrong value. Must be no more than 30. Max applicable length is 6 characters,
                 including the decimal (e.g., 12.345).'
            ),

            'Price Change is not valid.' => $this->__('Price Change is not valid.'),
            'Wrong value. Only integer numbers.' => $this->__('Wrong value. Only integer numbers.'),

            'Price' => $this->__('Price'),
            'Fixed Price' => $this->__('Fixed Price'),

            'The Price for Fixed Price Items.' => $this->__('The Price for Fixed Price Items.'),
            'The Fixed Price for immediate purchase.<br/>Find out more about
             <a href="http://sellercentre.ebay.co.uk/add-buy-it-now-price-auction"
                target="_blank">adding a Buy It Now Price</a> to your Listing.' => $this->__(
                'The Fixed Price for immediate purchase.<br/>Find out more about
                 <a href="http://sellercentre.ebay.co.uk/add-buy-it-now-price-auction"
                    target="_blank">adding a Buy It Now Price</a> to your Listing.'
            ),

            '% of Price' => $this->__('% of Price'),
            '% of Fixed Price' => $this->__('% of Fixed Price'),
            'Search for Charity Organization' => $this->__('Search for Charity Organization')
        ]);

        $this->js->add("M2ePro.formData.isStpEnabled = Boolean({$this->isStpAvailable()});");
        $this->js->add("M2ePro.formData.isStpAdvancedEnabled = Boolean({$this->isStpAdvancedAvailable()});");
        $this->js->add("M2ePro.formData.isMapEnabled = Boolean({$this->isMapAvailable()});");

        $this->js->add("M2ePro.formData.outOfStockControl = {$formData['out_of_stock_control']};");
        $this->js->add("M2ePro.formData.duration_mode
            = {$this->getHelper('Data')->escapeJs($formData['duration_mode'])};");
        $this->js->add("M2ePro.formData.qty_mode = {$this->getHelper('Data')->escapeJs($formData['qty_mode'])};");
        $this->js->add("M2ePro.formData.qty_modification_mode
            = {$this->getHelper('Data')->escapeJs($formData['qty_modification_mode'])};");

        if (!is_null($currency = $this->getCurrency())) {
            $this->js->add("M2ePro.formData.currency = '{$this->currency->getCurrency($currency)->getSymbol()}';");
        }

        $charityRenderJs = '';

        if (!$this->getMarketplaceId()) {
            $charityDictionary = $this->getHelper('Data')->jsonEncode($charityBlock->getCharityDictionary());
            if (empty($formData['charity'])) {

                $charityRenderJs = <<<JS
    EbayTemplateSellingFormatObj.charityDictionary = {$charityDictionary};
JS;
            } else {

                $charityRenderJs = <<<JS
    EbayTemplateSellingFormatObj.charityDictionary = {$charityDictionary};
    EbayTemplateSellingFormatObj.renderCharities({$formData['charity']});
JS;
            }
        }

        $this->js->add(<<<JS
    require([
        'M2ePro/Ebay/Template/SellingFormat',
    ], function(){
        window.EbayTemplateSellingFormatObj = new EbayTemplateSellingFormat();
        EbayTemplateSellingFormatObj.initObservers();

        {$charityRenderJs}
    });
JS
        );

        return parent::_prepareForm();
    }

    private function getTitle()
    {
        if ($this->isCustom()) {
            return isset($this->_data['custom_title']) ? $this->_data['custom_title'] : '';
        }

        $template = $this->getHelper('Data\GlobalData')->getValue('ebay_template_selling_format');

        if (is_null($template)) {
            return '';
        }

        return $template->getTitle();
    }

    private function isCustom()
    {
        if (isset($this->_data['is_custom'])) {
            return (bool)$this->_data['is_custom'];
        }

        return false;
    }

    private function getFormData()
    {
        $template = $this->getHelper('Data\GlobalData')->getValue('ebay_template_selling_format');

        if (is_null($template) || is_null($template->getId())) {
            return array();
        }

        $data = array_merge($template->getData(), $template->getChildObject()->getData());

        return $data;
    }

    private function getAttributesByInputTypes()
    {
        $attributes = $this->getHelper('Data\GlobalData')->getValue('ebay_attributes');

        /** @var \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper */
        $magentoAttributeHelper = $this->getHelper('Magento\Attribute');

        return [
            'text' => $magentoAttributeHelper->filterByInputTypes($attributes, array('text')),
            'text_select' => $magentoAttributeHelper->filterByInputTypes($attributes, array('text', 'select')),
            'text_price' => $magentoAttributeHelper->filterByInputTypes($attributes, array('text', 'price')),
        ];
    }

    private function getDefault()
    {
        return $this->activeRecordFactory->getObject('Ebay\Template\SellingFormat')->getDefaultSettings();
    }

    private function getCurrency()
    {
        $marketplace = $this->getHelper('Data\GlobalData')->getValue('ebay_marketplace');

        if (is_null($marketplace)) {
            return NULL;
        }

        return $marketplace->getChildObject()->getCurrency();
    }

    public function getCurrencyAvailabilityMessage()
    {
        $marketplace = $this->getHelper('Data\GlobalData')->getValue('ebay_marketplace');
        $store = $this->getHelper('Data\GlobalData')->getValue('ebay_store');
        $template = $this->getHelper('Data\GlobalData')->getValue('ebay_template_selling_format');

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
                \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT,
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

    /**
     * @return  \Ess\M2ePro\Model\Marketplace|null
     **/
    public function getMarketplace()
    {
        $marketplace = $this->getHelper('Data\GlobalData')->getValue('ebay_marketplace');

        if (is_null($marketplace)) {
            return NULL;
        }

        return $marketplace;
    }

    public function getMarketplaceId()
    {
        $marketplace = $this->getMarketplace();

        if (is_null($marketplace)) {
            return NULL;
        }

        return $marketplace->getId();
    }

    public function isStpAvailable()
    {
        if (is_null($marketplace = $this->getMarketplace())) {
            return true;
        }

        if ($marketplace->getChildObject()->isStpEnabled()) {
            return true;
        }

        return false;
    }

    public function isStpAdvancedAvailable()
    {
        if (is_null($marketplace = $this->getMarketplace())) {
            return true;
        }

        if ($marketplace->getChildObject()->isStpAdvancedEnabled()) {
            return true;
        }

        return false;
    }

    public function isMapAvailable()
    {
        if (is_null($marketplace = $this->getMarketplace())) {
            return true;
        }

        if ($marketplace->getChildObject()->isMapEnabled()) {
            return true;
        }

        return false;
    }

    //########################################

    public function getTaxCategoriesInfo()
    {
        $marketplacesCollection = $this->ebayFactory->getObject('Marketplace')
            ->getCollection()
            ->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)
            ->setOrder('sorder', 'ASC');

        $marketplacesCollection->getSelect()->limit(1);

        $marketplaces = $marketplacesCollection->getItems();

        if (count($marketplaces) == 0) {
            return array();
        }

        return array_shift($marketplaces)->getChildObject()->getTaxCategoryInfo();
    }

    //########################################

    private function getPriceTableHtml()
    {
        return $this->createBlock('Ebay\Template\SellingFormat\Edit\Form\PriceTable')->addData([
            'currency' => $this->getCurrency(),
            'form_data' => $this->getFormData(),
            'default' => $this->getDefault(),
            'attributes_by_input_types' => $this->getAttributesByInputTypes()
        ])->toHtml();
    }

    //########################################

    public function getCharityDictionary()
    {
        $connection = $this->resourceConnection->getConnection();
        $tableDictMarketplace = $this->resourceConnection->getTableName('m2epro_ebay_dictionary_marketplace');

        $dbSelect = $connection->select()
            ->from($tableDictMarketplace, ['marketplace_id', 'charities']);

        $data = $connection->fetchAssoc($dbSelect);

        foreach ($data as $key => $item) {
            $data[$key]['charities'] = $this->getHelper('Data')->jsonDecode($item['charities']);
        }

        return $data;
    }

    //########################################
}