<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\SellingFormat\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Amazon\Template\SellingFormat;

class Form extends AbstractForm
{
    protected $customerGroup;

    //########################################

    public function __construct(
        \Magento\Customer\Model\Group $customerGroup,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->customerGroup = $customerGroup;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    //########################################

    protected function _prepareForm()
    {
        /** @var \Ess\M2ePro\Model\Template\SellingFormat $template */
        $template = $this->getHelper('Data\GlobalData')->getValue('tmp_template');
        $formData = !is_null($template)
            ? array_merge($template->getData(), $template->getChildObject()->getData()) : [];

        $attributes = $this->getHelper('Magento\Attribute')->getGeneralFromAllAttributeSets();

        $formData['discount_rules'] = $template && $template->getId() ?
            $template->getChildObject()->getBusinessDiscounts() : array();

        $default = array(
            'title' => '',

            'is_regular_customer_allowed' => 1,
            'is_business_customer_allowed' => 0,

            'qty_mode' => \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT,
            'qty_custom_value' => 1,
            'qty_custom_attribute' => '',
            'qty_percentage' => 100,
            'qty_modification_mode' => SellingFormat::QTY_MODIFICATION_MODE_OFF,
            'qty_min_posted_value' => SellingFormat::QTY_MIN_POSTED_DEFAULT_VALUE,
            'qty_max_posted_value' => SellingFormat::QTY_MAX_POSTED_DEFAULT_VALUE,

            'regular_price_mode' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT,
            'regular_price_coefficient' => '',
            'regular_price_custom_attribute' => '',

            'regular_map_price_mode' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_NONE,
            'regular_map_price_custom_attribute' => '',

            'regular_sale_price_mode' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_NONE,
            'regular_sale_price_coefficient' => '',
            'regular_sale_price_custom_attribute' => '',

            'regular_price_variation_mode' => SellingFormat::PRICE_VARIATION_MODE_PARENT,

            'regular_sale_price_start_date_mode' => SellingFormat::DATE_VALUE,
            'regular_sale_price_end_date_mode' => SellingFormat::DATE_VALUE,

            'regular_sale_price_start_date_custom_attribute' => '',
            'regular_sale_price_end_date_custom_attribute' => '',

            'regular_sale_price_start_date_value' => $this->getHelper('Data')->getCurrentGmtDate(false, 'Y-m-d'),
            'regular_sale_price_end_date_value' => $this->getHelper('Data')->getCurrentGmtDate(false, 'Y-m-d'),

            'regular_price_vat_percent' => 0,

            'business_price_mode' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT,
            'business_price_coefficient' => '',
            'business_price_custom_attribute' => '',

            'business_price_variation_mode' => SellingFormat::PRICE_VARIATION_MODE_PARENT,

            'business_price_vat_percent' => 0,

            'business_discounts_mode' => 0,
            'business_discounts_tier_coefficient' => '',
            'business_discounts_tier_customer_group_id' => NULL,

            'discount_rules' => array()
        );

        $formData = array_merge($default, $formData);

        if ($formData['regular_sale_price_start_date_value'] != '') {
            $formData['regular_sale_price_start_date_value'] = $this->_localeDate->formatDate(
                $formData['regular_sale_price_start_date_value']
            );
        }
        if ($formData['regular_sale_price_end_date_value'] != '') {
            $formData['regular_sale_price_end_date_value'] = $this->_localeDate->formatDate(
                $formData['regular_sale_price_end_date_value']
            );
        }

        /** @var \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper */
        $magentoAttributeHelper = $this->getHelper('Magento\Attribute');

        $attributesByInputTypes = array(
            'text' => $magentoAttributeHelper->filterByInputTypes($attributes, array('text')),
            'text_select' => $magentoAttributeHelper->filterByInputTypes($attributes, array('text', 'select')),
            'text_price' => $magentoAttributeHelper->filterByInputTypes($attributes, array('text', 'price')),
            'text_date' => $magentoAttributeHelper->filterByInputTypes($attributes, array('text', 'date')),
        );

        $groups = $this->customerGroup->getCollection()->toArray();

        $isEdit = !!$this->getRequest()->getParam('id');

        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id'      => 'edit_form',
                    'method'  => 'post',
                    'action'  => $this->getUrl('*/*/save'),
                    'enctype' => 'multipart/form-data'
                ]
            ]
        );

        $fieldset = $form->addFieldset('magento_block_amazon_template_selling_format_general',
            [
                'legend' => $this->__('General'),
                'collapsable' => false
            ]
        );

        $fieldset->addField('title',
            'text',
            [
                'name' => 'title',
                'label' => $this->__('Title'),
                'value' => $formData['title'],
                'class' => 'M2ePro-price-tpl-title',
                'required' => true,
            ]
        );

        if ($this->getHelper('Component\Amazon\Business')->isEnabled()) {
            $fieldset = $form->addFieldset('magento_block_amazon_template_selling_format_business',
                [
                    'legend' => $this->__('Selling Type'),
                    'collapsable' => false
                ]
            );

            $fieldset->addField('is_regular_customer_allowed',
                'select',
                [
                    'label' => $this->__('B2C Enabled'),
                    'name' => 'is_regular_customer_allowed',
                    'values' => [
                        0 => $this->__('No'),
                        1 => $this->__('Yes'),
                    ],
                    'class' => 'M2ePro-customer-allowed-types',
                    'value' => $formData['is_regular_customer_allowed'],
                    'tooltip' => $this->__('Products with B2C Price will be available for both B2B and B2C
                                            Customers.<br /><strong>Note:</strong> B2B Customers will see the B2B Price
                                            once you additionally enable the ‘B2B pricing’ type of Selling.
                                            ')
                ]
            );

            $fieldset->addField('is_business_customer_allowed',
                'select',
                [
                    'label' => $this->__('B2B Pricing'),
                    'name' => 'is_business_customer_allowed',
                    'values' => [
                        0 => $this->__('No'),
                        1 => $this->__('Yes'),
                    ],
                    'class' => 'M2ePro-customer-allowed-types',
                    'value' => $formData['is_business_customer_allowed'],
                    'tooltip' => $this->__('B2B Price will be available only for B2B Customers.<br />
                                            <strong>Note:</strong> B2C Customers will not see these Products if you
                                            disable the ‘B2C enabled’ type of Selling.
                                            ')
                ]
            );
        }

        $fieldset = $form->addFieldset('magento_block_amazon_template_selling_format_qty',
            [
                'legend' => $this->__('Quantity'),
                'collapsable' => false
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
                'label' => $this->__('Quantity'),
                'name' => 'qty_mode',
                'values' => [
                    \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT => $this->__('Product Quantity'),
                    \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_SINGLE => $this->__('Single Item'),
                    \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_NUMBER => $this->__('Custom Value'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'new_option_value' => \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE,
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => $formData['qty_mode'] != \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE
                    ? $formData['qty_mode'] : '',
                'create_magento_attribute' => true,
                'tooltip' => $this->__('Product Quantity for Amazon Listing(s).')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField('qty_custom_attribute',
            'hidden',
            [
                'name' => 'qty_custom_attribute',
                'value' => $formData['qty_custom_attribute']
            ]
        );

        $fieldset->addField('qty_custom_value',
            'text',
            [
                'container_id' => 'qty_custom_value_tr',
                'label' => $this->__('Quantity Value'),
                'name' => 'qty_custom_value',
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
                'name' => 'qty_percentage',
                'values' => $preparedAttributes,
                'value' => $formData['qty_percentage'],
                'tooltip' => $this->__(
                    'Sets the percentage for calculation of Items number to be Listed on Amazon basing on
                    Product Quantity or Magento Attribute. <br/><br/>
                    E.g., if QTY percentage is set to 10% and Product Quantity is 100,
                    the Quantity to be Listed on Amazon will be calculated as <br/> 100 * 10% = 10.')
            ]
        );

        $fieldset->addField('qty_modification_mode',
            'select',
            [
                'container_id' => 'qty_modification_mode_tr',
                'label' => $this->__('Conditional Quantity'),
                'name' => 'qty_modification_mode',
                'values' => [
                    SellingFormat::QTY_MODIFICATION_MODE_OFF => $this->__('Disabled'),
                    SellingFormat::QTY_MODIFICATION_MODE_ON => $this->__('Enabled'),
                ],
                'value' => $formData['qty_modification_mode'],
                'tooltip' => $this->__('<b>Enables</b> to set the Quantity that will be sent on Amazon.')
            ]
        );

        $fieldset->addField('qty_min_posted_value',
            'text',
            [
                'container_id' => 'qty_min_posted_value_tr',
                'label' => $this->__('Minimum Quantity to Be Listed'),
                'name' => 'qty_min_posted_value',
                'value' => $formData['qty_min_posted_value'],
                'class' => 'M2ePro-validate-qty',
                'required' => true,
                'tooltip' => $this->__(
                    'If you have 2 pieces in Stock but set a Minimum Quantity to Be Listed of 5,
                    Item will not be Listed on Amazon.
                    Otherwise, the Item will be Listed with Quantity according to the Settings in the
                    Price, Quantity and Format Policy.'
                )
            ]
        );

        $fieldset->addField('qty_max_posted_value',
            'text',
            [
                'container_id' => 'qty_max_posted_value_tr',
                'label' => $this->__('Maximum Quantity to Be Listed'),
                'name' => 'qty_max_posted_value',
                'value' => $formData['qty_max_posted_value'],
                'class' => 'M2ePro-validate-qty',
                'required' => true,
                'tooltip' => $this->__(
                    'If you have 5 pieces in Stock but set a Maximum Quantity of 2 to be Listed,
                    a QTY of 2 will be Listed on Amazon.
                    If you have 1 piece in Stock but Maximum Quantity is set to 3, only 1 will be Listed on Amazon.'
                )
            ]
        );

        $priceTitle = $this->getHelper('Component\Amazon\Business')->isEnabled() ?
            $this->__('Price (B2C)') :
            $this->__('Price');

        $fieldset = $form->addFieldset('magento_block_amazon_template_selling_format_prices',
            [
                'legend' => $priceTitle,
                'collapsable' => false
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByInputTypes['text_price'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['regular_price_mode'] == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE
                && $formData['regular_price_custom_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $priceRegularCoefficient = $this->elementFactory->create('text', ['data' => [
            'html_id' => 'regular_price_coefficient',
            'name' => 'regular_price_coefficient',
            'label' => '',
            'value' => $formData['regular_price_coefficient'],
            'class' => 'M2ePro-validate-price-coefficient',
        ]]);
        $priceRegularCoefficient->setForm($form);

        $tooltipRegularPriceMode = $this->getTooltipHtml(
            '<span id="regular_price_note"></span>'
        );

        $tooltipRegularPriceCoefficient = $this->getTooltipHtml(
            $this->__('Absolute figure (+8,-3), percentage (+15%, -20%) or Currency rate (1.44)')
        );

        $fieldset->addField('regular_price_mode',
            self::SELECT,
            [
                'label' => $this->__('Price'),
                'class' => 'select-main',
                'name' => 'regular_price_mode',
                'values' => [
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT => $this->__('Product Price'),
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL => $this->__('Special Price'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => $formData['regular_price_mode']
                            != \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE
                            ? $formData['regular_price_mode'] : '',
                'create_magento_attribute' => true,
                'after_element_html' => $tooltipRegularPriceMode
                    . '<span id="regular_price_coefficient_td">'
                    . $priceRegularCoefficient->toHtml()
                    . $tooltipRegularPriceCoefficient . '</span>'
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,price');

        $fieldset->addField('regular_price_custom_attribute',
            'hidden',
            [
                'name' => 'regular_price_custom_attribute',
                'value' => $formData['regular_price_custom_attribute']
            ]
        );

        $fieldset->addField('regular_price_variation_mode',
            self::SELECT,
            [
                'label' => $this->__('Variation Price Source'),
                'class' => 'select-main',
                'name' => 'regular_price_variation_mode',
                'values' => [
                    SellingFormat::PRICE_VARIATION_MODE_PARENT => $this->__('Main Product'),
                    SellingFormat::PRICE_VARIATION_MODE_CHILDREN => $this->__('Associated Products')
                ],
                'value' => $formData['regular_price_variation_mode'],
                'tooltip' => $this->__(
                    'Determines where the Price for Bundle Products Options should be taken from.'
                )
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByInputTypes['text_price'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['regular_map_price_mode'] == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE
                && $formData['regular_map_price_custom_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField('regular_map_price_mode',
            self::SELECT,
            [
                'label' => $this->__('Minimum Advertised Price'),
                'class' => 'select-main',
                'name' => 'regular_map_price_mode',
                'values' => [
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_NONE => $this->__('None'),
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT => $this->__('Product Price'),
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL => $this->__('Special Price'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => $formData['regular_map_price_mode']
                    != \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE
                    ? $formData['regular_map_price_mode'] : '',
                'create_magento_attribute' => true,
                'tooltip' => $this->__(
                    'The Selling Price for your Product will not be displayed on the Product Detail
                    Page or Offer Listing Page if it is less than the Minimum Advertised Price.
                    The Customer only sees the Price you\'re selling the Item for if they add the
                    Item to their Shopping Cart.'
                )
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,price');

        $fieldset->addField('regular_map_price_custom_attribute',
            'hidden',
            [
                'name' => 'regular_map_price_custom_attribute',
                'value' => $formData['regular_map_price_custom_attribute']
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByInputTypes['text_price'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['regular_sale_price_mode'] == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE
                && $formData['regular_sale_price_custom_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $salePriceCoefficient = $this->elementFactory->create('text', ['data' => [
            'html_id' => 'regular_sale_price_coefficient',
            'name' => 'regular_sale_price_coefficient',
            'label' => '',
            'value' => $formData['regular_sale_price_coefficient'],
            'class' => 'M2ePro-validate-price-coefficient',
        ]]);
        $salePriceCoefficient->setForm($form);

        $tooltipSalePriceMode = $this->getTooltipHtml(
            '<span id="regular_sale_price_note"></span>'
        );

        $tooltipSalePriceCoefficient = $this->getTooltipHtml(
            $this->__('Absolute figure (+8,-3), percentage (+15%, -20%) or Currency rate (1.44)')
        );

        $fieldset->addField('regular_sale_price_mode',
            self::SELECT,
            [
                'label' => $this->__('Sale Price'),
                'class' => 'select-main',
                'name' => 'regular_sale_price_mode',
                'values' => [
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_NONE => $this->__('None'),
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT => $this->__('Product Price'),
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL => $this->__('Special Price'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => $formData['regular_sale_price_mode']
                    != \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE
                    ? $formData['regular_sale_price_mode'] : '',
                'create_magento_attribute' => true,
                'after_element_html' => $tooltipSalePriceMode
                    . '<span id="regular_sale_price_coefficient_td">'
                    . $salePriceCoefficient->toHtml()
                    . $tooltipSalePriceCoefficient . '</span>'
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $this->css->add(
            'label.mage-error[for="price_coefficient"], label.mage-error[for="regular_sale_price_coefficient"]
                { width: 160px !important; left: 0px !important; }'
        );

        $fieldset->addField('regular_sale_price_custom_attribute',
            'hidden',
            [
                'name' => 'regular_sale_price_custom_attribute',
                'value' => $formData['regular_sale_price_custom_attribute']
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByInputTypes['text_date'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['regular_sale_price_start_date_mode'] == SellingFormat::DATE_ATTRIBUTE
                && $formData['regular_sale_price_start_date_custom_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => SellingFormat::DATE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField('regular_sale_price_start_date_mode',
            self::SELECT,
            [
                'container_id' => 'regular_sale_price_start_date_mode_tr',
                'label' => $this->__('Start Date'),
                'class' => 'select-main',
                'name' => 'regular_sale_price_start_date_mode',
                'values' => [
                    SellingFormat::DATE_VALUE => $this->__('Custom Value'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => $formData['regular_sale_price_start_date_mode'] != SellingFormat::DATE_ATTRIBUTE
                    ? $formData['regular_sale_price_start_date_mode'] : '',
                'create_magento_attribute' => true,
                'tooltip' => $this->__('Time and date when the <i>Sale Price</i> will be displayed on Amazon.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,date');

        $fieldset->addField('regular_sale_price_start_date_custom_attribute',
            'hidden',
            [
                'name' => 'regular_sale_price_start_date_custom_attribute',
                'value' => $formData['regular_sale_price_start_date_custom_attribute']
            ]
        );

        $fieldset->addField('regular_sale_price_start_date_value',
            'date',
            [
                'container_id' => 'regular_sale_price_start_date_value_tr',
                'label' => $this->__('Start Date Value'),
                'name' => 'regular_sale_price_start_date_value',
                'value' => $formData['regular_sale_price_start_date_value'],
                'date_format' => $this->_localeDate->getDateFormatWithLongYear(),
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByInputTypes['text_date'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['regular_sale_price_end_date_mode'] == SellingFormat::DATE_ATTRIBUTE
                && $formData['regular_sale_price_end_date_custom_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => SellingFormat::DATE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField('regular_sale_price_end_date_mode',
            self::SELECT,
            [
                'container_id' => 'regular_sale_price_end_date_mode_tr',
                'label' => $this->__('End Date'),
                'class' => 'select-main',
                'name' => 'regular_sale_price_end_date_mode',
                'values' => [
                    SellingFormat::DATE_VALUE => $this->__('Custom Value'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => $formData['regular_sale_price_end_date_mode'] != SellingFormat::DATE_ATTRIBUTE
                    ? $formData['regular_sale_price_end_date_mode'] : '',
                'create_magento_attribute' => true,
                'tooltip' => $this->__('Time and date when the <i>Sale Price</i> will be hidden on Amazon.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,date');

        $fieldset->addField('regular_sale_price_end_date_custom_attribute',
            'hidden',
            [
                'name' => 'regular_sale_price_end_date_custom_attribute',
                'value' => $formData['regular_sale_price_end_date_custom_attribute']
            ]
        );

        $fieldset->addField('regular_sale_price_end_date_value',
            'date',
            [
                'container_id' => 'regular_sale_price_end_date_value_tr',
                'label' => $this->__('End Date Value'),
                'name' => 'regular_sale_price_end_date_value',
                'value' => $formData['regular_sale_price_end_date_value'],
                'date_format' => $this->_localeDate->getDateFormatWithLongYear(),
            ]
        );

        $fieldset->addField('regular_sale_price_end_date_value_validation',
            'text',
            [
                'name'  => 'regular_sale_price_end_date_value_validation',
                'class' => 'M2ePro-date-range-to'
            ]
        );

        $fieldset->addField('regular_price_increase_vat_percent',
            self::SELECT,
            [
                'label' => $this->__('Add VAT Percentage'),
                'class' => 'select-main',
                'name' => 'regular_price_increase_vat_percent',
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes')
                ],
                'value' => (int)($formData['regular_price_vat_percent'] > 0),
                'tooltip' => $this->__('
                    Choose whether you want to add VAT to the Price when a Product is Listed on Amazon and
                    provide the appropriate VAT Percent Value.<br/><br/>

                    <strong>Example:</strong>
                    For a Product with Magento Price = £10<br/>
                    VAT Rate = 15%<br/>
                    Magento Price: £10<br/>
                    VAT 15%: £10 * 15% = £1.50<br/>
                    Final Price on Amazon (Magento Price + VAT) = £10 + £1.50 = £11.50<br/><br/>

                    <strong>Note:</strong> No VAT Rate Value will be sent on Amazon. Only the Item\'s Price
                    on Amazon will be increased.
                ')
            ]
        );

        $fieldset->addField('regular_price_vat_percent',
            'text',
            [
                'container_id' => 'regular_price_vat_percent_tr',
                'label' => $this->__('VAT Rate, %'),
                'name' => 'regular_price_vat_percent',
                'value' => $formData['regular_price_vat_percent'],
                'class' => 'M2ePro-validate-vat-percent',
                'required' => true
            ]
        );

        if ($this->getHelper('Component\Amazon\Business')->isEnabled()) {

            $fieldset = $form->addFieldset('magento_block_amazon_template_selling_format_business_prices',
                [
                    'legend' => $this->__('Price (B2B)'),
                    'collapsable' => false
                ]
            );

            $preparedAttributes = [];
            foreach ($attributesByInputTypes['text_price'] as $attribute) {
                $attrs = ['attribute_code' => $attribute['code']];
                if (
                    $formData['business_price_mode'] == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE
                    && $formData['business_price_custom_attribute'] == $attribute['code']
                ) {
                    $attrs['selected'] = 'selected';
                }
                $preparedAttributes[] = [
                    'attrs' => $attrs,
                    'value' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE,
                    'label' => $attribute['label'],
                ];
            }

            $businessPriceCoefficient = $this->elementFactory->create('text', ['data' => [
                'html_id' => 'business_price_coefficient',
                'name' => 'business_price_coefficient',
                'label' => '',
                'value' => $formData['business_price_coefficient'],
                'class' => 'M2ePro-validate-price-coefficient',
            ]]);
            $businessPriceCoefficient->setForm($form);

            $tooltipBusinessPriceMode = $this->getTooltipHtml(
                '<span id="business_price_note"></span>'
            );

            $tooltipBusinessPriceCoefficient = $this->getTooltipHtml(
                $this->__('Absolute figure (+8,-3), percentage (+15%, -20%) or Currency rate (1.44)')
            );

            $fieldset->addField('business_price_mode',
                self::SELECT,
                [
                    'label' => $this->__('Price'),
                    'class' => 'select-main',
                    'name' => 'business_price_mode',
                    'values' => [
                        \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT => $this->__('Product Price'),
                        \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL => $this->__('Special Price'),
                        [
                            'label' => $this->__('Magento Attributes'),
                            'value' => $preparedAttributes,
                            'attrs' => [
                                'is_magento_attribute' => true
                            ]
                        ]
                    ],
                    'value' => $formData['business_price_mode']
                    != \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE
                        ? $formData['business_price_mode'] : '',
                    'create_magento_attribute' => true,
                    'after_element_html' => $tooltipBusinessPriceMode
                        . '<span id="business_price_coefficient_td">'
                        . $businessPriceCoefficient->toHtml()
                        . $tooltipBusinessPriceCoefficient . '</span>'
                ]
            )->addCustomAttribute('allowed_attribute_types', 'text,price');

            $fieldset->addField('business_price_custom_attribute',
                'hidden',
                [
                    'name' => 'business_price_custom_attribute',
                    'value' => $formData['business_price_custom_attribute']
                ]
            );

            $fieldset->addField('business_price_variation_mode',
                self::SELECT,
                [
                    'label' => $this->__('Variation Price Source'),
                    'class' => 'select-main',
                    'name' => 'business_price_variation_mode',
                    'values' => [
                        SellingFormat::PRICE_VARIATION_MODE_PARENT => $this->__('Main Product'),
                        SellingFormat::PRICE_VARIATION_MODE_CHILDREN => $this->__('Associated Products')
                    ],
                    'value' => $formData['business_price_variation_mode'],
                    'tooltip' => $this->__(
                        'Determines where the Price for Bundle Products Options should be taken from.'
                    )
                ]
            );

            $fieldset->addField('business_price_increase_vat_percent',
                self::SELECT,
                [
                    'label' => $this->__('Add VAT Percentage'),
                    'class' => 'select-main',
                    'name' => 'business_price_increase_vat_percent',
                    'values' => [
                        0 => $this->__('No'),
                        1 => $this->__('Yes')
                    ],
                    'value' => (int)($formData['business_price_vat_percent'] > 0),
                    'tooltip' => $this->__('
                    Choose whether you want to add VAT to the Price when a Product is Listed on Amazon and
                    provide the appropriate VAT Percent Value.<br/><br/>

                    <strong>Example:</strong>
                    For a Product with Magento Price = £10 and VAT Rate = 15%<br/>
                    VAT = £10 * 15% = £1.50<br/>
                    Final Price on Amazon (Magento Price + VAT) = £10 + £1.50 = £11.50<br/><br/>

                    <strong>Note:</strong> No VAT Rate Value will be sent on Amazon.
                    Only the Item Price will be increased on the Channel.
                ')
                ]
            );

            $fieldset->addField('business_price_vat_percent',
                'text',
                [
                    'container_id' => 'business_price_vat_percent_tr',
                    'label' => $this->__('VAT Rate, %'),
                    'name' => 'business_price_vat_percent',
                    'value' => $formData['business_price_vat_percent'],
                    'class' => 'M2ePro-validate-vat-percent',
                    'required' => true
                ]
            );

            $businessDiscountCoefficient = $this->elementFactory->create('text', ['data' => [
                'html_id' => 'business_discounts_tier_coefficient',
                'name' => 'business_discounts_tier_coefficient',
                'label' => '',
                'value' => $formData['business_discounts_tier_coefficient'],
                'class' => 'M2ePro-validate-price-coefficient',
            ]]);
            $businessDiscountCoefficient->setForm($form);

            $tooltipDiscountPriceMode = $this->getTooltipHtml(
                '<span id="discount_price_note">' . $this->__(
                    'Allows enabling the <strong>Quantity Discount</strong> feature. Choose the way of Discount
                    calculation for multiple Items purchased:<br /><br />

                    <strong>Product Tier Price</strong> - the discounted Price value will be taken from a Tier
                    Price Attribute of Magento Product.<br />
                    Please, find the details on how the Product Tier Price is calculated for different types of
                    Magento Products <a target="_blank" href="%url%" class="external-link">here</a>.<br /><br />

                    <strong>Custom Value</strong> - the discounted Price value will be calculated as a Price from
                    selected Magento Attribute with a Price Change option applied.<br />
                    <strong>Example</strong>: for purchased Products with QTY >= 5 you set the Price = 7£ and
                    Price Change = -2,25. Business Buyers will see the next record once they click
                    ‘Request a quantity discount’: QTY >= 5, price 4,75 £, where: QTY <= 5 is the number of
                    purchased Products to which the Discount will be applied, price 4,75 £ is the final Price
                    per Product with the Discount applied (7£ - 2,25).',
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/twtPAQ')
                )
                . '</span>'
            );

            $tooltipDiscountPriceCoefficient = $this->getTooltipHtml(
                $this->__('Absolute figure (+8,-3), percentage (+15%, -20%) or Currency rate (1.44)')
            );

            $fieldset->addField('business_discounts_mode',
                self::SELECT,
                [
                    'label' => $this->__('Discounts'),
                    'class' => 'select-main M2ePro-business-discount-availability',
                    'name' => 'business_discounts_mode',
                    'values' => [
                        SellingFormat::BUSINESS_DISCOUNTS_MODE_NONE => $this->__('None'),
                        SellingFormat::BUSINESS_DISCOUNTS_MODE_TIER => $this->__('Product Tier Price'),
                        SellingFormat::BUSINESS_DISCOUNTS_MODE_CUSTOM_VALUE => $this->__('Custom Value'),
                    ],
                    'value' => $formData['business_discounts_mode'],
                    'after_element_html' => $tooltipDiscountPriceMode
                        . '<span id="business_discounts_tier_coefficient_td">'
                        . $businessDiscountCoefficient->toHtml()
                        . $tooltipDiscountPriceCoefficient . '</span>',
                ]
            );

            $values = [];
            foreach ($groups['items'] as $group) {
                $values[$group['customer_group_id']] = $group['customer_group_code'];
            }

            $fieldset->addField('business_discounts_tier_customer_group_id',
                self::SELECT,
                [
                    'container_id' => 'business_discounts_tier_customer_group_id_tr',
                    'label' => $this->__('Customer Group'),
                    'class' => 'select-main',
                    'name' => 'business_discounts_tier_customer_group_id',
                    'values' => $values,
                    'value' => $formData['business_discounts_tier_customer_group_id'],
                    'required' => true,
                    'tooltip' => $this->__('Select a Customer Group that a tier pricing is available for.')
                ]
            );

            $discountTableBlock = $this->createBlock('Amazon\Template\SellingFormat\Edit\Form\DiscountTable')->setData([
                'attributes' => $attributesByInputTypes['text_price']
            ]);

            $fieldset->addField('business_discounts_table_container', self::CUSTOM_CONTAINER,
                [
                    'text' => $discountTableBlock->toHtml(),
                    'css_class' => 'm2epro-fieldset-table',
                ]
            );
        }

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Template\SellingFormat')
        );
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Amazon\Template\SellingFormat')
        );
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Helper\Component\Amazon')
        );

        $this->jsUrl->addUrls([
            'formSubmit'    => $this->getUrl(
                '*/amazon_template_sellingFormat/save', array('_current' => true)
            ),
            'formSubmitNew' => $this->getUrl('*/amazon_template_sellingFormat/save'),
            'deleteAction'  => $this->getUrl(
                '*/amazon_template_sellingFormat/delete', array('_current' => true)
            )
        ]);

        $this->jsTranslator->addTranslations([
            'QTY' => $this->__('QTY'),
            'Price' => $this->__('Price'),
            'Regular Price' => $this->__('Regular Price'),
            'Wrong date range.' => $this->__('Wrong date range.'),

            'Product Price for Amazon Listing(s).' => $this->__('Product Price for Amazon Listing(s).'),
            'Business Product Price for Amazon Listing(s).' =>
                $this->__('Business Product Price for Amazon Listing(s).'),
            'The Price of Products in Amazon Listing(s).<br/><b>Note:</b>
            The Final Price is only used for Simple Products.' => $this->__(
                'The Price of Products in Amazon Listing(s).
                 <br/><b>Note:</b> The Final Price is only used for Simple Products.'
            ),

            'The Price, at which you want to sell your Product(s) at specific time.' => $this->__(
                'The Price, at which you want to sell your Product(s) at specific time.'
            ),
            'The Price, at which you want to sell your Product(s) at specific time.
            <br/><b>Note:</b> The Final Price is only used for Simple Products.' => $this->__(
                'The Price, at which you want to sell your Product(s) at specific time.
                <br/><b>Note:</b> The Final Price is only used for Simple Products.'
            ),

            'Add Price, Quantity and Format Policy' => $this->__('Add Price, Quantity and Format Policy'),
            'The specified Title is already used for other Policy. Policy Title must be unique.' => $this->__(
                'The specified Title is already used for other Policy. Policy Title must be unique.'
            ),
            'You should select Attribute Sets first and press Confirm Button.' => $this->__(
                'You should select Attribute Sets first and press Confirm Button.'
            ),
            'Coefficient is not valid.' => $this->__('Coefficient is not valid.'),

            'Wrong value. Only integer numbers.' => $this->__('Wrong value. Only integer numbers.'),
            'Wrong value. Must be no more than 30. Max applicable length is 6 characters, including the decimal '
            . '(e.g., 12.345).' => $this->__(
                'Wrong value. Must be no more than 30. Max applicable length is 6 characters, including the decimal '
                .'(e.g., 12.345).'
            ),
            'At least one Selling Type should be enabled.' => $this->__('At least one Selling Type should be enabled.'),
            'The Quantity value should be unique.' => $this->__('The Quantity value should be unique.'),
            'You should specify a unique pair of Magento Attribute and Price Change value for each Discount Rule.'
                => $this->__('You should specify a unique pair of Magento Attribute and Price Change value
                              for each Discount Rule.'),
            'You should add at least one Discount Rule.' => $this->__('You should add at least one Discount Rule.')
        ]);

        $this->js->add("M2ePro.formData.id = '{$this->getRequest()->getParam('id')}';");
        $this->js->add(
            "M2ePro.formData.title
             = '{$this->getHelper('Data')->escapeJs($this->getHelper('Data')->escapeJs($formData['title']))}';"
        );

        $jsFormData = [
            'qty_mode',
            'qty_modification_mode',
            'qty_custom_attribute',
            'regular_price_custom_attribute',
            'regular_map_price_custom_attribute',
            'regular_sale_price_custom_attribute',
            'regular_sale_price_start_date_custom_attribute',
            'regular_sale_price_end_date_custom_attribute',
            'business_price_custom_attribute',
        ];

        foreach ($jsFormData as $item) {
            $this->js->add("M2ePro.formData.$item = '{$this->getHelper('Data')->escapeJs($formData[$item])}';");
        }

        $this->js->add(
            "M2ePro.formData.discount_rules = {$this->getHelper('Data')->jsonEncode($formData['discount_rules'])};"
        );

        $this->js->add(<<<JS
    require([
        'M2ePro/Amazon/Template/SellingFormat',
    ], function(){
        window.AmazonTemplateSellingFormatObj = new AmazonTemplateSellingFormat();
        AmazonTemplateSellingFormatObj.initObservers();
    });
JS
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _prepareLayout()
    {
        $this->appendHelpBlock([
            'content' => $this->__('
                Price, Quantity and Format Policy contains Price and Quantity related data for the Items,
                which will be Listed on Amazon.<br/><br/>

                While Listing on Amazon, the Magento Price can be modified by providing a Price Change Value.
                There is a Price
                Change box next to each Price Options Dropdown.<br/><br/>

                <strong>Examples:</strong><br/>

                <ul class="list">
                    <li>If you want the Price on Amazon to be greater by 15% than the Price in Magento,
                    you should set +15% in the Price Change field.<br/>
                    <i>Amazon Price = Magento Price + Magento Price * 0.15</i></li>
                    <li>If you want the Price on Amazon to be less by 10 Currency units than the Price in Magento,
                    you should set -10 in the Price Change field.<br/>
                    <i>Amazon Price = Magento Price - 10</i></li>
                    <li>If you want the Price on Amazon to be multiplied by coefficient 1.2,
                    you should set 1.2 in the Price Change field.<br/>
                    <i>Amazon Price = Magento Price * 1.2</i></li>
                </ul>

                <strong>Note:</strong> If the Special Price is chosen in the <strong>Price</strong> Option,
                but it is not set in Magento
                Product Settings or has already expired, the Product Price will be used instead.<br/>
                If the Special Price is chosen in the <strong>Sale Price</strong> Option,
                its Magento <strong>From</strong>, <strong>To</strong> dates will be taken into
                consideration. Once the Sale Price has expired, the Price will be used.<br/>
                If those dates are not specified, the <strong>Sale Price</strong>
                will be set <strong>From</strong> this moment <strong>To</strong>
                the year ahead.<br/><br/>

                <strong>Note:</strong> Attributes must contain only Numeric Values.'
            )
        ]);

        parent::_prepareLayout();
    }

    //########################################
}