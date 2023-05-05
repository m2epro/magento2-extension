<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\SellingFormat\Edit;

use Ess\M2ePro\Block\Adminhtml\Amazon\Template\SellingFormat\Edit\Form\DiscountTable;
use Ess\M2ePro\Block\Adminhtml\Amazon\Template\SellingFormat\Edit\Form\RepricerTable;
use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Amazon\Template\SellingFormat;

class Form extends AbstractForm
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon\Configuration */
    protected $configuration;
    /** @var \Magento\Customer\Model\Group */
    protected $customerGroup;
    /** @var \Ess\M2ePro\Helper\Magento\Attribute */
    protected $magentoAttributeHelper;
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\Configuration $configuration,
        \Magento\Customer\Model\Group $customerGroup,
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        array $data = []
    ) {
        $this->configuration = $configuration;
        $this->customerGroup = $customerGroup;
        $this->magentoAttributeHelper = $magentoAttributeHelper;
        $this->supportHelper = $supportHelper;
        $this->dataHelper = $dataHelper;
        $this->globalDataHelper = $globalDataHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \ReflectionException
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function _prepareForm(): Form
    {
        /** @var \Ess\M2ePro\Model\Template\SellingFormat $template */
        $template = $this->globalDataHelper->getValue('tmp_template');
        $formData = $template !== null
            ? array_merge($template->getData(), $template->getChildObject()->getData()) : [];

        $attributes = $this->magentoAttributeHelper->getAll();

        $formData['discount_rules'] = $template && $template->getId() ?
            $template->getChildObject()->getBusinessDiscounts() : [];

        $default = $this->modelFactory->getObject('Amazon_Template_SellingFormat_Builder')->getDefaultData();

        $formData = array_merge($default, $formData);

        if ($formData['regular_sale_price_start_date_value'] != '') {
            $dateTime = new \DateTime(
                $formData['regular_sale_price_start_date_value'],
                new \DateTimeZone($this->_localeDate->getDefaultTimezone())
            );
            // UTC Date will be shown on interface
            //$dateTime->setTimezone(new \DateTimeZone($this->_localeDate->getConfigTimezone()));

            $formData['regular_sale_price_start_date_value'] = $dateTime;
        }

        if ($formData['regular_sale_price_end_date_value'] != '') {
            $dateTime = new \DateTime(
                $formData['regular_sale_price_end_date_value'],
                new \DateTimeZone($this->_localeDate->getDefaultTimezone())
            );
            // UTC Date will be shown on interface
            //$dateTime->setTimezone(new \DateTimeZone($this->_localeDate->getConfigTimezone()));

            $formData['regular_sale_price_end_date_value'] = $dateTime;
        }

        $attributesByInputTypes = [
            'text' => $this->magentoAttributeHelper->filterByInputTypes($attributes, ['text']),
            'text_select' => $this->magentoAttributeHelper->filterByInputTypes($attributes, ['text', 'select']),
            'text_price' => $this->magentoAttributeHelper->filterByInputTypes($attributes, ['text', 'price']),
            'text_date' => $this->magentoAttributeHelper->filterByInputTypes($attributes, ['text', 'date']),
        ];

        $groups = $this->customerGroup->getCollection()->toArray();

        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'method' => 'post',
                    'action' => $this->getUrl('*/*/save'),
                    'enctype' => 'multipart/form-data',
                ],
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_selling_format_general',
            [
                'legend' => __('General'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'title',
            'text',
            [
                'name' => 'title',
                'label' => __('Title'),
                'value' => $formData['title'],
                'class' => 'M2ePro-price-tpl-title',
                'required' => true,
            ]
        );

        $fieldset->addField(
            'local_timezone',
            'hidden',
            [
                'value' => $this->_localeDate->getConfigTimezone(),
            ]
        );

        $fieldset->addField(
            'local_date_format',
            'hidden',
            [
                'value' => $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT),
            ]
        );

        if ($this->configuration->isEnabledBusinessMode()) {
            $fieldset = $form->addFieldset(
                'magento_block_amazon_template_selling_format_business',
                [
                    'legend' => __('Selling Type'),
                    'collapsable' => false,
                ]
            );

            $fieldset->addField(
                'is_regular_customer_allowed',
                'select',
                [
                    'label' => __('B2C Enabled'),
                    'name' => 'is_regular_customer_allowed',
                    'values' => [
                        0 => __('No'),
                        1 => __('Yes'),
                    ],
                    'class' => 'M2ePro-customer-allowed-types',
                    'value' => $formData['is_regular_customer_allowed'],
                    'tooltip' => __(
                        'Products with B2C Price will be available for both B2B and B2C
                                            Customers.<br /><strong>Note:</strong> B2B Customers will see the B2B Price
                                            once you additionally enable the ‘B2B pricing’ type of Selling.
                                            '
                    ),
                ]
            );

            $fieldset->addField(
                'is_business_customer_allowed',
                'select',
                [
                    'label' => __('B2B Pricing'),
                    'name' => 'is_business_customer_allowed',
                    'values' => [
                        0 => __('No'),
                        1 => __('Yes'),
                    ],
                    'class' => 'M2ePro-customer-allowed-types',
                    'value' => $formData['is_business_customer_allowed'],
                    'tooltip' => __(
                        'B2B Price will be available only for B2B Customers.<br />
                                            <strong>Note:</strong> B2C Customers will not see these Products if you
                                            disable the ‘B2C enabled’ type of Selling.
                                            '
                    ),
                ]
            );
        }

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_selling_format_qty',
            [
                'legend' => __('Quantity'),
                'collapsable' => false,
            ]
        );

        $preparedAttributes = [
            [
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT_FIXED,
                'label' => __('QTY'),
            ],
        ];

        if (
            $formData['qty_mode'] == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE
            && !$this->magentoAttributeHelper
                ->isExistInAttributesArray($formData['qty_custom_attribute'], $attributes)
            && $formData['qty_custom_attribute'] != ''
        ) {
            $preparedAttributes[] = [
                'attrs' => [
                    'attribute_code' => $formData['qty_custom_attribute'],
                    'selected' => 'selected',
                ],
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE,
                'label' => $this->magentoAttributeHelper->getAttributeLabel($formData['qty_custom_attribute']),
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

        $fieldset->addField(
            'qty_mode',
            self::SELECT,
            [
                'container_id' => 'qty_mode_tr',
                'label' => __('Quantity'),
                'name' => 'qty_mode',
                'values' => [
                    \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT => __('Product Quantity'),
                    \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_NUMBER => __('Custom Value'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'new_option_value' => \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE,
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'value' => $formData['qty_mode'] != \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE
                    ? $formData['qty_mode'] : '',
                'create_magento_attribute' => true,
                'tooltip' => __('Product Quantity for Amazon Listing(s).'),
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField(
            'qty_custom_attribute',
            'hidden',
            [
                'name' => 'qty_custom_attribute',
                'value' => $formData['qty_custom_attribute'],
            ]
        );

        $fieldset->addField(
            'qty_custom_value',
            'text',
            [
                'container_id' => 'qty_custom_value_tr',
                'label' => __('Quantity Value'),
                'name' => 'qty_custom_value',
                'value' => $formData['qty_custom_value'],
                'class' => 'validate-digits',
                'required' => true,
            ]
        );

        $preparedAttributes = [];
        for ($i = 100; $i >= 5; $i -= 5) {
            $preparedAttributes[] = [
                'value' => $i,
                'label' => $i . ' %',
            ];
        }

        $fieldset->addField(
            'qty_percentage',
            self::SELECT,
            [
                'container_id' => 'qty_percentage_tr',
                'label' => __('Quantity Percentage'),
                'name' => 'qty_percentage',
                'values' => $preparedAttributes,
                'value' => $formData['qty_percentage'],
                'tooltip' => __(
                    'Sets the percentage for calculation of Items number to be Listed on Amazon basing on
                    Product Quantity or Magento Attribute. <br/><br/>
                    E.g., if QTY percentage is set to 10% and Product Quantity is 100,
                    the Quantity to be Listed on Amazon will be calculated as <br/> 100 * 10% = 10.'
                ),
            ]
        );

        $fieldset->addField(
            'qty_modification_mode',
            'select',
            [
                'container_id' => 'qty_modification_mode_tr',
                'label' => __('Conditional Quantity'),
                'name' => 'qty_modification_mode',
                'values' => [
                    SellingFormat::QTY_MODIFICATION_MODE_OFF => __('Disabled'),
                    SellingFormat::QTY_MODIFICATION_MODE_ON => __('Enabled'),
                ],
                'value' => $formData['qty_modification_mode'],
                'tooltip' => __('<b>Enables</b> to set the Quantity that will be sent on Amazon.'),
            ]
        );

        $fieldset->addField(
            'qty_min_posted_value',
            'text',
            [
                'container_id' => 'qty_min_posted_value_tr',
                'label' => __('Minimum Quantity to Be Listed'),
                'name' => 'qty_min_posted_value',
                'value' => $formData['qty_min_posted_value'],
                'class' => 'M2ePro-validate-qty',
                'required' => true,
                'tooltip' => __(
                    'If you have 2 pieces in Stock but set a Minimum Quantity to Be Listed of 5,
                    Item will not be Listed on Amazon.
                    Otherwise, the Item will be Listed with Quantity according to the Settings in the Selling Policy.'
                ),
            ]
        );

        $fieldset->addField(
            'qty_max_posted_value',
            'text',
            [
                'container_id' => 'qty_max_posted_value_tr',
                'label' => __('Maximum Quantity to Be Listed'),
                'name' => 'qty_max_posted_value',
                'value' => $formData['qty_max_posted_value'],
                'class' => 'M2ePro-validate-qty',
                'required' => true,
                'tooltip' => __(
                    'If you have 5 pieces in Stock but set a Maximum Quantity of 2 to be Listed,
                    a QTY of 2 will be Listed on Amazon.
                    If you have 1 piece in Stock but Maximum Quantity is set to 3, only 1 will be Listed on Amazon.'
                ),
            ]
        );

        $priceTitle = $this->configuration->isEnabledBusinessMode() ?
            __('Price (B2C)') :
            __('Price');

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_selling_format_prices',
            [
                'legend' => $priceTitle,
                'collapsable' => false,
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

        $tooltipRegularPriceMode = $this->getTooltipHtml(
            '<span id="regular_price_note"></span>'
        );

        $fieldset->addField(
            'regular_price_mode',
            self::SELECT,
            [
                'label' => __('Price'),
                'class' => 'select-main',
                'name' => 'regular_price_mode',
                'values' => [
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT => __('Product Price'),
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL => __('Special Price'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'value' => $formData['regular_price_mode']
                != \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE
                    ? $formData['regular_price_mode'] : '',
                'create_magento_attribute' => true,
                'after_element_html' => $tooltipRegularPriceMode,
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,price');

        $fieldset->addField(
            'regular_price_custom_attribute',
            'hidden',
            [
                'name' => 'regular_price_custom_attribute',
                'value' => $formData['regular_price_custom_attribute'],
            ]
        );

        $this->appendPriceChangeElements(
            $fieldset,
            \Ess\M2ePro\Model\Amazon\Template\SellingFormat::PRICE_TYPE_REGULAR,
            $formData['regular_price_modifier']
        );

        $fieldset->addField(
            'regular_price_variation_mode',
            self::SELECT,
            [
                'label' => __('Variation Price Source'),
                'class' => 'select-main',
                'name' => 'regular_price_variation_mode',
                'values' => [
                    SellingFormat::PRICE_VARIATION_MODE_PARENT => __('Main Product'),
                    SellingFormat::PRICE_VARIATION_MODE_CHILDREN => __('Associated Products'),
                ],
                'value' => $formData['regular_price_variation_mode'],
                'tooltip' => __('Choose the source of the price value for Bundle Products variations.'),
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

        $fieldset->addField(
            'regular_map_price_mode',
            self::SELECT,
            [
                'label' => __('Minimum Advertised Price'),
                'class' => 'select-main',
                'name' => 'regular_map_price_mode',
                'values' => [
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_NONE => __('None'),
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT => __('Product Price'),
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL => __('Special Price'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'value' => $formData['regular_map_price_mode']
                != \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE
                    ? $formData['regular_map_price_mode'] : '',
                'create_magento_attribute' => true,
                'tooltip' => __(
                    'The Selling Price for your Product will not be displayed on the Product Detail
                    Page or Offer Listing Page if it is less than the Minimum Advertised Price.
                    The Customer only sees the Price you\'re selling the Item for if they add the
                    Item to their Shopping Cart.'
                ),
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,price');

        $fieldset->addField(
            'regular_map_price_custom_attribute',
            'hidden',
            [
                'name' => 'regular_map_price_custom_attribute',
                'value' => $formData['regular_map_price_custom_attribute'],
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

        $tooltipSalePriceMode = $this->getTooltipHtml(
            '<span id="regular_sale_price_note"></span>'
        );

        $fieldset->addField(
            'regular_sale_price_mode',
            self::SELECT,
            [
                'label' => __('Sale Price'),
                'class' => 'select-main',
                'name' => 'regular_sale_price_mode',
                'values' => [
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_NONE => __('None'),
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT => __('Product Price'),
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL => __('Special Price'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'value' => $formData['regular_sale_price_mode']
                != \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE
                    ? $formData['regular_sale_price_mode'] : '',
                'create_magento_attribute' => true,
                'after_element_html' => $tooltipSalePriceMode,
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField(
            'regular_sale_price_custom_attribute',
            'hidden',
            [
                'name' => 'regular_sale_price_custom_attribute',
                'value' => $formData['regular_sale_price_custom_attribute'],
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

        $this->appendPriceChangeElements(
            $fieldset,
            \Ess\M2ePro\Model\Amazon\Template\SellingFormat::PRICE_TYPE_REGULAR_SALE,
            $formData['regular_sale_price_modifier']
        );

        $fieldset->addField(
            'regular_sale_price_start_date_mode',
            self::SELECT,
            [
                'container_id' => 'regular_sale_price_start_date_mode_tr',
                'label' => __('Start Date'),
                'class' => 'select-main',
                'name' => 'regular_sale_price_start_date_mode',
                'values' => [
                    SellingFormat::DATE_VALUE => __('Custom Value'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'value' => $formData['regular_sale_price_start_date_mode'] != SellingFormat::DATE_ATTRIBUTE
                    ? $formData['regular_sale_price_start_date_mode'] : '',
                'create_magento_attribute' => true,
                'tooltip' => __('Time and date when the <i>Sale Price</i> will be displayed on Amazon.'),
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,date');

        $fieldset->addField(
            'regular_sale_price_start_date_custom_attribute',
            'hidden',
            [
                'name' => 'regular_sale_price_start_date_custom_attribute',
                'value' => $formData['regular_sale_price_start_date_custom_attribute'],
            ]
        );

        $fieldset->addField(
            'regular_sale_price_start_date_value',
            'date',
            [
                'container_id' => 'regular_sale_price_start_date_value_tr',
                'label' => __('Start Date Value'),
                'name' => 'regular_sale_price_start_date_value',
                'value' => $formData['regular_sale_price_start_date_value'],
                'date_format' => $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT),
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

        $fieldset->addField(
            'regular_sale_price_end_date_mode',
            self::SELECT,
            [
                'container_id' => 'regular_sale_price_end_date_mode_tr',
                'label' => __('End Date'),
                'class' => 'select-main',
                'name' => 'regular_sale_price_end_date_mode',
                'values' => [
                    SellingFormat::DATE_VALUE => __('Custom Value'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'value' => $formData['regular_sale_price_end_date_mode'] != SellingFormat::DATE_ATTRIBUTE
                    ? $formData['regular_sale_price_end_date_mode'] : '',
                'create_magento_attribute' => true,
                'tooltip' => __('Time and date when the <i>Sale Price</i> will be hidden on Amazon.'),
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,date');

        $fieldset->addField(
            'regular_sale_price_end_date_custom_attribute',
            'hidden',
            [
                'name' => 'regular_sale_price_end_date_custom_attribute',
                'value' => $formData['regular_sale_price_end_date_custom_attribute'],
            ]
        );

        $fieldset->addField(
            'regular_sale_price_end_date_value',
            'date',
            [
                'container_id' => 'regular_sale_price_end_date_value_tr',
                'label' => __('End Date Value'),
                'name' => 'regular_sale_price_end_date_value',
                'value' => $formData['regular_sale_price_end_date_value'],
                'date_format' => $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT),
            ]
        );

        $fieldset->addField(
            'regular_sale_price_end_date_value_validation',
            'text',
            [
                'name' => 'regular_sale_price_end_date_value_validation',
                'class' => 'M2ePro-date-range-to',
            ]
        );

        $fieldset->addField(
            'regular_price_increase_vat_percent',
            self::SELECT,
            [
                'label' => __('Add VAT Percentage'),
                'class' => 'select-main',
                'name' => 'regular_price_increase_vat_percent',
                'values' => [
                    0 => __('No'),
                    1 => __('Yes'),
                ],
                'value' => (int)($formData['regular_price_vat_percent'] > 0),
                'tooltip' => __(
                    <<<HTML
Enable this option to add a specified VAT percent value to the Price when a Product is listed on Amazon.
<br/>
<br/>
The final product price on Amazon will be calculated according to the following formula:
<br/>
<br/>
(Product Price + Price Change) + VAT Rate
<br/>
<br/>
<strong>Note:</strong> Amazon considers the VAT rate value sent by M2E Pro as an additional price increase,
not as a proper VAT rate.
HTML
                ),
            ]
        );

        $fieldset->addField(
            'regular_price_vat_percent',
            'text',
            [
                'container_id' => 'regular_price_vat_percent_tr',
                'label' => __('VAT Rate, %'),
                'name' => 'regular_price_vat_percent',
                'value' => $formData['regular_price_vat_percent'],
                'class' => 'M2ePro-validate-vat-percent',
                'required' => true,
            ]
        );

        if ($this->configuration->isEnabledBusinessMode()) {
            $fieldset = $form->addFieldset(
                'magento_block_amazon_template_selling_format_business_prices',
                [
                    'legend' => __('Price (B2B)'),
                    'collapsable' => false,
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

            $tooltipBusinessPriceMode = $this->getTooltipHtml(
                '<span id="business_price_note"></span>'
            );

            $fieldset->addField(
                'business_price_mode',
                self::SELECT,
                [
                    'label' => __('Price'),
                    'class' => 'select-main',
                    'name' => 'business_price_mode',
                    'values' => [
                        \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT => __('Product Price'),
                        \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL => __('Special Price'),
                        [
                            'label' => __('Magento Attributes'),
                            'value' => $preparedAttributes,
                            'attrs' => [
                                'is_magento_attribute' => true,
                            ],
                        ],
                    ],
                    'value' => $formData['business_price_mode']
                    != \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE
                        ? $formData['business_price_mode'] : '',
                    'create_magento_attribute' => true,
                    'after_element_html' => $tooltipBusinessPriceMode,
                ]
            )->addCustomAttribute('allowed_attribute_types', 'text,price');

            $fieldset->addField(
                'business_price_custom_attribute',
                'hidden',
                [
                    'name' => 'business_price_custom_attribute',
                    'value' => $formData['business_price_custom_attribute'],
                ]
            );

            $this->appendPriceChangeElements(
                $fieldset,
                \Ess\M2ePro\Model\Amazon\Template\SellingFormat::PRICE_TYPE_BUSINESS,
                $formData['business_price_modifier']
            );

            $fieldset->addField(
                'business_price_variation_mode',
                self::SELECT,
                [
                    'label' => __('Variation Price Source'),
                    'class' => 'select-main',
                    'name' => 'business_price_variation_mode',
                    'values' => [
                        SellingFormat::PRICE_VARIATION_MODE_PARENT => __('Main Product'),
                        SellingFormat::PRICE_VARIATION_MODE_CHILDREN => __('Associated Products'),
                    ],
                    'value' => $formData['business_price_variation_mode'],
                    'tooltip' => __('Choose the source of the price value for Bundle Products variations.'),
                ]
            );

            $fieldset->addField(
                'business_price_increase_vat_percent',
                self::SELECT,
                [
                    'label' => __('Add VAT Percentage'),
                    'class' => 'select-main',
                    'name' => 'business_price_increase_vat_percent',
                    'values' => [
                        0 => __('No'),
                        1 => __('Yes'),
                    ],
                    'value' => (int)($formData['business_price_vat_percent'] > 0),
                    'tooltip' => __(
                        <<<HTML
Enable this option to add a specified VAT percent value to the Price when a Product is listed on Amazon.
<br/>
<br/>
The final product price on Amazon will be calculated according to the following formula:
<br/>
<br/>
(Product Price + Price Change) + VAT Rate
<br/>
<br/>
<strong>Note:</strong> Amazon considers the VAT rate value sent by M2E Pro as an additional price increase,
not as a proper VAT rate.
HTML
                    ),
                ]
            );

            $fieldset->addField(
                'business_price_vat_percent',
                'text',
                [
                    'container_id' => 'business_price_vat_percent_tr',
                    'label' => __('VAT Rate, %'),
                    'name' => 'business_price_vat_percent',
                    'value' => $formData['business_price_vat_percent'],
                    'class' => 'M2ePro-validate-vat-percent',
                    'required' => true,
                ]
            );

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
                    $this->supportHelper->getDocumentationArticleUrl('x/8oZP')
                )
                . '</span>'
            );

            $fieldset->addField(
                'business_discounts_mode',
                self::SELECT,
                [
                    'label' => __('Discounts'),
                    'class' => 'select-main M2ePro-business-discount-availability',
                    'name' => 'business_discounts_mode',
                    'values' => [
                        SellingFormat::BUSINESS_DISCOUNTS_MODE_NONE => __('None'),
                        SellingFormat::BUSINESS_DISCOUNTS_MODE_TIER => __('Product Tier Price'),
                        SellingFormat::BUSINESS_DISCOUNTS_MODE_CUSTOM_VALUE => __('Custom Value'),
                    ],
                    'value' => $formData['business_discounts_mode'],
                    'after_element_html' => $tooltipDiscountPriceMode,
                ]
            );

            $this->appendPriceChangeElements(
                $fieldset,
                \Ess\M2ePro\Model\Amazon\Template\SellingFormat::PRICE_TYPE_BUSINESS_DISCOUNTS_TIER,
                $formData['business_discounts_tier_modifier']
            );

            $values = [];
            foreach ($groups['items'] as $group) {
                $values[$group['customer_group_id']] = $group['customer_group_code'];
            }

            $fieldset->addField(
                'business_discounts_tier_customer_group_id',
                self::SELECT,
                [
                    'container_id' => 'business_discounts_tier_customer_group_id_tr',
                    'label' => __('Customer Group'),
                    'class' => 'select-main',
                    'name' => 'business_discounts_tier_customer_group_id',
                    'values' => $values,
                    'value' => $formData['business_discounts_tier_customer_group_id'],
                    'required' => true,
                    'tooltip' => __('Select a Customer Group that a tier pricing is available for.'),
                ]
            );

            $discountTableBlock = $this->getLayout()->createBlock(DiscountTable::class)->setData([
                'attributes' => $attributesByInputTypes['text_price'],
            ]);

            $fieldset->addField(
                'business_discounts_table_container',
                self::CUSTOM_CONTAINER,
                [
                    'text' => $discountTableBlock->toHtml(),
                    'css_class' => 'm2epro-fieldset-table',
                ]
            );
        }

        $fieldSet = $form->addFieldset(
            'magento_block_amazon_template_selling_format_repricer',
            [
                'legend' => __('Repricer'),
                'collapsable' => false,
            ]
        );

        $url = $this->getUrl('*/amazon_repricer_settings/index');

        if ($this->activeRecordFactory->getObject('Amazon_Account_Repricing')->getCollection()->getSize() > 0) {
            $repricerTableBlock = $this->getLayout()->createBlock(RepricerTable::class);

            $fieldSet->addField(
                'repricer_table_container',
                self::CUSTOM_CONTAINER,
                [
                    'text' => $repricerTableBlock->toHtml(),
                    'css_class' => 'm2epro-fieldset-table',
                ]
            );

            $fieldSet->addField(
                'repricer_connect_account',
                self::CUSTOM_CONTAINER,
                [
                    'label' => '',
                    'text' => $this->__(
                        '<p>Click <a href="%url%" target="_blank">here</a>
                               to manage your Repricer configurations</p>',
                        $url
                    )
                ]
            );
        } else {
            $fieldSet->addField(
                'repricer_connect_account',
                self::CUSTOM_CONTAINER,
                [
                    'label' => '',
                    'text' => $this->__(
                        '<p><a href="%url%" target="_blank">Connect</a>
                            your Amazon account(s) to Repricer to enable the service for your offers</p>',
                        $url
                    )
                ]
            );
        }

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Template\SellingFormat::class)
        );
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Amazon\Template\SellingFormat::class)
        );
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Helper\Component\Amazon::class)
        );

        $this->jsUrl->addUrls([
            'formSubmit' => $this->getUrl(
                '*/amazon_template_sellingFormat/save',
                ['_current' => true]
            ),
            'formSubmitNew' => $this->getUrl('*/amazon_template_sellingFormat/save'),
            'deleteAction' => $this->getUrl(
                '*/amazon_template_sellingFormat/delete',
                ['_current' => true]
            ),
        ]);

        $this->jsTranslator->addTranslations([
            'QTY' => __('QTY'),
            'Price' => __('Price'),
            'Regular Price' => __('Regular Price'),
            'Wrong date range.' => __('Wrong date range.'),

            'Product Price for Amazon Listing(s).' => __('Product Price for Amazon Listing(s).'),
            'Business Product Price for Amazon Listing(s).' =>
                __('Business Product Price for Amazon Listing(s).'),
            'The Price of Products in Amazon Listing(s).<br/><b>Note:</b>
            The Final Price is only used for Simple Products.' =>
                __(
                    'The Price of Products in Amazon Listing(s).
                    <br/><b>Note:</b> The Final Price is only used for Simple Products.'
                ),

            'The Price, at which you want to sell your Product(s) at specific time.' =>
                __(
                    'The Price, at which you want to sell your Product(s) at specific time.'
                ),
            'The Price, at which you want to sell your Product(s) at specific time.
            <br/><b>Note:</b> The Final Price is only used for Simple Products.' =>
                __(
                    'The Price, at which you want to sell your Product(s) at specific time.
                    <br/><b>Note:</b> The Final Price is only used for Simple Products.'
                ),

            'Add Selling Policy' => __('Add Selling Policy'),
            'The specified Title is already used for other Policy. Policy Title must be unique.' =>
                __(
                    'The specified Title is already used for other Policy. Policy Title must be unique.'
                ),
            'You should select Attribute Sets first and press Confirm Button.' =>
                __(
                    'You should select Attribute Sets first and press Confirm Button.'
                ),
            'Coefficient is not valid.' => __('Coefficient is not valid.'),

            'Wrong value. Only integer numbers.' => __('Wrong value. Only integer numbers.'),
            'wrong_value_more_than_30' => __(
                'Wrong value. Must be no more than 30. Max applicable length is 6 characters,
                 including the decimal (e.g., 12.345).'
            ),
            'At least one Selling Type should be enabled.' => __('At least one Selling Type should be enabled.'),
            'The Quantity value should be unique.' => __('The Quantity value should be unique.'),
            'You should specify a unique pair of Magento Attribute and Price Change value for each Discount Rule.' =>
                __(
                    'You should specify a unique pair of Magento Attribute and Price Change value
                    for each Discount Rule.'
                ),
            'You should add at least one Discount Rule.' => __('You should add at least one Discount Rule.'),
            'Price Change is not valid.' => __('Price Change is not valid.'),
        ]);

        $this->js->add("M2ePro.formData.id = '{$this->getRequest()->getParam('id')}';");
        $this->js->add(
            "M2ePro.formData.title
             = '{$this->dataHelper->escapeJs($this->dataHelper->escapeJs($formData['title']))}';"
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
            $this->js->add("M2ePro.formData.$item = '{$this->dataHelper->escapeJs($formData[$item])}';");
        }

        $jsonFormData = \Ess\M2ePro\Helper\Json::encode($formData['discount_rules']);
        $this->js->add(
            "M2ePro.formData.discount_rules = {$jsonFormData};"
        );

        $injectPriceChangeJs = $this->getPriceChangeInjectorJs($formData);

        $this->js->add(
            <<<JS
    require([
        'M2ePro/Amazon/Template/SellingFormat',
    ], function(){
        window.AmazonTemplateSellingFormatObj = new AmazonTemplateSellingFormat();
        AmazonTemplateSellingFormatObj.initObservers();
        {$injectPriceChangeJs}
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
            'content' => $this->__(
                '
                Using Selling Policy, you should define conditions for selling your products on Amazon, such as Price,
                Quantity, VAT settings, etc.<br/><br/>

                Head over to <a href="%url%" target="_blank" class="external-link">docs</a> for detailed information.',
                $this->supportHelper->getDocumentationArticleUrl('x/Nv8UB')
            ),
        ]);

        parent::_prepareLayout();
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\Fieldset $fieldset
     * @param string $priceType
     * @param string|null $priceModifier
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function appendPriceChangeElements(
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset,
        string $priceType,
        ?string $priceModifier
    ): void {
        $block = $this->getLayout()
                      ->createBlock(\Ess\M2ePro\Block\Adminhtml\Template\SellingFormat\PriceChange::class)
                      ->addData([
                          'price_type' => $priceType,
                          'price_modifier' => (string)$priceModifier,
                      ]);

        $fieldset->addField(
            $priceType . '_change_placement',
            'label',
            [
                'container_id' => $priceType . '_change_placement_tr',
                'label' => '',
                'after_element_html' => $block->toHtml(),
            ]
        );
    }

    /**
     * @param array $formData
     *
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getPriceChangeInjectorJs(array $formData): string
    {
        $result = [];

        $priceTypes = \Ess\M2ePro\Model\Amazon\Template\SellingFormat::getPriceTypes();
        foreach ($priceTypes as $priceType) {
            $key = $priceType . '_modifier';
            if (!empty($formData[$key])) {
                // ensure that data always have a valid json format
                $json = \Ess\M2ePro\Helper\Json::encode(
                    \Ess\M2ePro\Helper\Json::decode($formData[$key]) ?: []
                );

                $result[] = <<<JS
    AmazonTemplateSellingFormatObj.priceChangeHelper.renderPriceChangeRows(
        '{$priceType}',
        {$json}
    );
JS;
            }
        }

        return implode("\n", $result);
    }
}
