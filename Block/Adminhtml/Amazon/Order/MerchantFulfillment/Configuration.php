<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Order\MerchantFulfillment;

use Ess\M2ePro\Helper\Component\Amazon\MerchantFulfillment;

/**
 * Class Ess\M2ePro\Block\Adminhtml\Amazon\Order\MerchantFulfillment\Configuration
 */
class Configuration extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /**
     * @var \Magento\Framework\Locale\CurrencyInterface
     */
    private $localeCurrency;
    /**
     * @var \Magento\Framework\App\Config\ReinitableConfigInterface
     */
    private $storeConfig;

    //########################################

    public function __construct(
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\App\Config\ReinitableConfigInterface $storeConfig,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->localeCurrency = $localeCurrency;
        $this->storeConfig = $storeConfig;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->setId('amazonOrderMerchantFulfillmentConfiguration');
    }

    //########################################

    protected function _prepareForm()
    {
        $cachedData = $this->getHelper('Data_Cache_Permanent')->getValue('amazon_merchant_fulfillment_data');
        if (!$cachedData) {
            $cachedData = [];
        }

        $defaults = [
            'must_arrive_by_date'                       => '',
            'declared_value'                            => $this->getData('declared_value'),
            'ship_from_address_phone'                   => '',
            'package_dimension_source'                  => MerchantFulfillment::DIMENSION_SOURCE_NONE,
            'package_dimension_predefined'              => '',
            'package_dimension_length'                  => '',
            'package_dimension_width'                   => '',
            'package_dimension_height'                  => '',
            'package_dimension_length_custom_attribute' => '',
            'package_dimension_width_custom_attribute'  => '',
            'package_dimension_height_custom_attribute' => '',
            'package_dimension_measure'                 => MerchantFulfillment::DIMENSION_MEASURE_INCHES,
            'package_weight_source'                     => '',
            'package_weight_custom_value'               => '',
            'package_weight_custom_attribute'           => '',
            'package_weight_measure'                    => MerchantFulfillment::WEIGHT_MEASURE_OUNCES,
        ];

        if (!empty($this->getData('delivery_date_to'))) {
            $mustArriveByDate = new \DateTime($this->getData('delivery_date_to'));
            $defaults['must_arrive_by_date'] = $mustArriveByDate->format('Y-m-d');
        }

        if (empty($cachedData)) {
            $userData = $this->getUserData();
            $shippingOriginData = $this->getShippingOriginData();

            $customerName = [];
            isset($userData['lastname']) && $customerName[] = $userData['lastname'];
            isset($userData['firstname']) && $customerName[] = $userData['firstname'];

            $defaultsCachedData = [
                'ship_from_address_name'           => implode(' ', $customerName),
                'ship_from_address_email'          => isset($userData['email']) ? $userData['email'] : '',
                'ship_from_address_country'        => $shippingOriginData['country_id'],
                'ship_from_address_region_state'   => $shippingOriginData['region_id'],
                'ship_from_address_postal_code'    => $shippingOriginData['postal_code'],
                'ship_from_address_city'           => $shippingOriginData['city'],
                'ship_from_address_address_line_1' => $shippingOriginData['street_line1'],
                'ship_from_address_address_line_2' => $shippingOriginData['street_line2'],

                'delivery_experience' => MerchantFulfillment::DELIVERY_EXPERIENCE_WITHOUT_SIGNATURE,
                'carrier_will_pickup' => 0
            ];

            $formData = array_merge($defaults, $defaultsCachedData);
        } else {
            $formData = array_merge($defaults, $cachedData);
        }

        /** @var \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper */
        $magentoAttributeHelper = $this->getHelper('Magento_Attribute');

        $allAttributes = $magentoAttributeHelper->getAll();

        $attributesByInputTypes = [
            'text' => $magentoAttributeHelper->filterByInputTypes($allAttributes, ['text']),
        ];

        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'order_merchantFulfillment_configuration',
                ]
            ]
        );

        $fieldset = $form->addFieldset(
            'products_fieldset',
            [
                'legend' => $this->__('Products')
            ]
        );

        $productsHtml = '';

        foreach ($this->getOrderItems() as $orderItem) {
            $productsHtml .= <<<HTML
    <tr>
        <td>{$orderItem['title']}</td>
        <td>{$orderItem['sku']}</td>
        <td>{$orderItem['asin']}</td>
        <td>{$orderItem['qty']}</td>
        <td>{$this->localeCurrency->getCurrency($orderItem['currency'])->toCurrency($orderItem['price'])}</td>
    </tr>
HTML;
        }

        $html = <<<HTML
    <table class="border data-grid data-grid-not-hovered" cellpadding="0" cellspacing="0">
        <thead>
            <tr class="headings">
                <th class="data-grid-th">{$this->__('Title')}</th>
                <th class="data-grid-th" style="width: 150px;">{$this->__('SKU')}</th>
                <th class="data-grid-th" style="width: 150px;">{$this->__('ASIN')}</th>
                <th class="data-grid-th" style="width: 100px;">{$this->__('Quantity')}</th>
                <th class="data-grid-th" style="width: 200px;">{$this->__('Price')}</th>
            </tr>
        </thead>
        <tbody>
            {$productsHtml}
        </tbody>
    </table>
HTML;

        $fieldset->addField(
            'products_grid',
            self::CUSTOM_CONTAINER,
            [
                'text'      => $html,
                'css_class' => 'm2epro-custom-container-full-width'
            ]
        );

        $fieldset = $form->addFieldset(
            'general_fieldset',
            [
                'legend' => $this->__('General')
            ]
        );

        $fieldset->addField(
            'must_arrive_by_date',
            'text',
            [
                'name'     => 'must_arrive_by_date',
                'label'    => $this->__('Must Arrive By Date'),
                'title'    => $this->__('Must Arrive By Date'),
                'class'    => 'M2ePro-validate-must-arrive-date',
                'value'    => $formData['must_arrive_by_date'],
                'required' => true,
                'tooltip'  => $this->__('Enter the date by which the Item must be delivered to a Buyer.')
            ]
        );

        $fieldset->addField(
            'declared_value',
            'text',
            [
                'name'     => 'declared_value',
                'label'    => $this->__('Declared Value'),
                'title'    => $this->__('Declared Value'),
                'class'    => 'validate-greater-than-zero',
                'value'    => $formData['declared_value'],
                'required' => true,
                'tooltip'  => $this->__(
                    'Based on this Value, the Carrier will determine for how much to insure the Shipment. By default,
                    this Value is calculated as a subtotal of all of the purchased Items.'
                ),
                'note'     => <<<HTML
    <span style="color: grey;">[{$this->getData('order_currency')}]</span>
HTML
            ]
        );

        $fieldset = $form->addFieldset(
            'package_fieldset',
            [
                'legend' => $this->__('Package')
            ]
        );

        $sourcesArray = [
            MerchantFulfillment::DIMENSION_SOURCE_NONE             => '-- ' . $this->__('Please select') . ' --',
            MerchantFulfillment::DIMENSION_SOURCE_CUSTOM           => $this->__('Custom Value'),
            MerchantFulfillment::DIMENSION_SOURCE_CUSTOM_ATTRIBUTE => $this->__('Custom Attribute'),
        ];

        if (!$this->canUseProductAttributes()) {
            unset($sourcesArray[MerchantFulfillment::DIMENSION_SOURCE_CUSTOM_ATTRIBUTE]);
        }

        $predefinedPackageDimensions = $this->getHelper('Component_Amazon_MerchantFulfillment')
            ->getPredefinedPackageDimensions();

        foreach ($predefinedPackageDimensions as $groupTitle => $predefinedPackageGroup) {
            $groupValues = [];
            foreach ($predefinedPackageGroup as $dimensionCode => $dimensionData) {

                $attrs = ['dimension_code' => $dimensionCode];

                if ($formData['package_dimension_source'] == MerchantFulfillment::DIMENSION_SOURCE_PREDEFINED &&
                    $formData['package_dimension_predefined'] == $dimensionCode) {
                    $attrs['selected'] = 'selected';
                }

                if (is_array($dimensionData)) {
                    $attrs = array_merge($attrs, $dimensionData);
                    unset($attrs['title']);

                    $groupValues[] = [
                        'label' => $dimensionData['title'],
                        'value' => MerchantFulfillment::DIMENSION_SOURCE_PREDEFINED,
                        'attrs' => $attrs
                    ];
                } else {
                    $groupValues[] = [
                        'label' => $dimensionData,
                        'value' => MerchantFulfillment::DIMENSION_SOURCE_PREDEFINED,
                        'attrs' => $attrs
                    ];
                }
            }

            $sourcesArray[] = [
                'label' => $groupTitle,
                'value' => $groupValues
            ];
        }

        $fieldset->addField(
            'package_dimension_source',
            self::SELECT,
            [
                'name'     => 'package_dimension_source',
                'label'    => $this->__('Dimension'),
                'values'   => $sourcesArray,
                'value'    => $formData['package_dimension_source'],
                'class'    => 'M2ePro-validate-dimension',
                'required' => true,
                'tooltip'  => $this->__(
                    <<<HTML
    You can select between a <strong>Custom Value</strong> and the <strong>Predefined Package Dimensions</strong>
    offered by the Carriers. It is recommended to use Custom Value only if the Item Package Dimensions do not confirm
    to the predefined Options.
HTML
                )
            ]
        )->addCustomAttribute('style', 'width: 70%;');

        $fieldset->addField(
            'package_dimension_predefined',
            'hidden',
            [
                'name'  => 'package_dimension_predefined',
                'value' => $formData['package_dimension_predefined']
            ]
        );

        $lengthInput = $this->elementFactory->create(
            'text',
            [
                'data' => [
                    'name'  => 'package_dimension_length',
                    'value' => $formData['package_dimension_length'],
                    'class' => 'M2ePro-validate-required-custom-dimension M2ePro-validate-custom-dimension'
                ]
            ]
        );
        $lengthInput->setId('package_dimension_length');
        $lengthInput->addCustomAttribute('style', 'width: 21.5%');
        $lengthInput->setForm($form);

        $widthInput = $this->elementFactory->create(
            'text',
            [
                'data' => [
                    'name'  => 'package_dimension_width',
                    'value' => $formData['package_dimension_width'],
                    'class' => 'M2ePro-validate-required-custom-dimension M2ePro-validate-custom-dimension'
                ]
            ]
        );
        $widthInput->setId('package_dimension_width');
        $widthInput->addCustomAttribute('style', 'width: 22%');
        $widthInput->setForm($form);

        $heightInput = $this->elementFactory->create(
            'text',
            [
                'data' => [
                    'name'  => 'package_dimension_height',
                    'value' => $formData['package_dimension_height'],
                    'class' => 'M2ePro-validate-required-custom-dimension M2ePro-validate-custom-dimension'
                ]
            ]
        );
        $heightInput->setId('package_dimension_height');
        $heightInput->addCustomAttribute('style', 'width: 22%');
        $heightInput->setForm($form);

        $preparedAttributes = [];
        foreach ($attributesByInputTypes['text'] as $attribute) {
            $preparedAttributes[] = [
                'value' => $attribute['code'],
                'label' => $attribute['label'],
            ];
        }

        $lengthSelect = $this->elementFactory->create(
            self::SELECT,
            [
                'data' => [
                    'name'   => 'package_dimension_length_custom_attribute',
                    'values' => $preparedAttributes,
                    'value'  => $formData['package_dimension_length_custom_attribute'],
                    'class'  => 'M2ePro-required-when-visible',
                    'style'  => 'width: 21.5%'
                ]
            ]
        );
        $lengthSelect->setId('package_dimension_length_custom_attribute');
        $lengthSelect->setForm($form);

        $widthSelect = $this->elementFactory->create(
            self::SELECT,
            [
                'data' => [
                    'name'   => 'package_dimension_width_custom_attribute',
                    'values' => $preparedAttributes,
                    'value'  => $formData['package_dimension_width_custom_attribute'],
                    'class'  => 'M2ePro-required-when-visible',
                    'style'  => 'width: 22%'
                ]
            ]
        );
        $widthSelect->setId('package_dimension_width_custom_attribute');
        $widthSelect->setForm($form);

        $heightSelect = $this->elementFactory->create(
            self::SELECT,
            [
                'data' => [
                    'name'   => 'package_dimension_height_custom_attribute',
                    'values' => $preparedAttributes,
                    'value'  => $formData['package_dimension_height_custom_attribute'],
                    'class'  => 'M2ePro-required-when-visible',
                    'style'  => 'width: 22%'
                ]
            ]
        );
        $heightSelect->setId('package_dimension_height_custom_attribute');
        $heightSelect->setForm($form);

        $measureSelect = $this->elementFactory->create(
            self::SELECT,
            [
                'data' => [
                    'name'   => 'package_dimension_measure',
                    'values' => [
                        MerchantFulfillment::DIMENSION_MEASURE_INCHES      => $this->__('in'),
                        MerchantFulfillment::DIMENSION_MEASURE_CENTIMETERS => $this->__('cm')
                    ],
                    'value'  => $formData['package_dimension_measure'],
                    'class'  => 'M2ePro-required-when-visible',
                    'style'  => 'width: 40px'
                ]
            ]
        );
        $measureSelect->setId('package_dimension_measure');
        $measureSelect->setForm($form);

        $fieldset->addField(
            'package_dimension_custom_container',
            self::CUSTOM_CONTAINER,
            [
                'label'    => $this->__('Length x Width x Height'),
                'title'    => $this->__('Length x Width x Height'),
                'style'    => 'padding-top: 0;',
                'text'     => <<<HTML
<span id="package_dimension_custom_value" style="display: none">
    {$lengthInput->toHtml()} x {$widthInput->toHtml()} x {$heightInput->toHtml()}
</span>
<span id="package_dimension_custom_attribute" style="display: none">
    {$lengthSelect->toHtml()} x {$widthSelect->toHtml()} x {$heightSelect->toHtml()}
</span>
{$measureSelect->toHtml()}
HTML
                ,
                'required' => true,

                'field_extra_attributes' => 'id="package_dimension_custom" style="display: none;"',
            ]
        );

        $measureSelect = $this->elementFactory->create(
            self::SELECT,
            [
                'data' => [
                    'name'   => 'package_weight_measure',
                    'values' => [
                        MerchantFulfillment::WEIGHT_MEASURE_OUNCES => $this->__('oz'),
                        MerchantFulfillment::WEIGHT_MEASURE_GRAMS  => $this->__('g')
                    ],
                    'value'  => $formData['package_weight_measure'],
                    'style'  => 'width: 40px'
                ]
            ]
        );
        $measureSelect->setId('package_weight_measure');
        $measureSelect->setForm($form);

        if ($this->canUseProductAttributes()) {
            $fieldset->addField(
                'package_weight_custom_attribute',
                'hidden',
                [
                    'name'  => 'package_weight_custom_attribute',
                    'value' => $formData['package_weight_custom_attribute']
                ]
            );

            $preparedAttributes = [];
            foreach ($attributesByInputTypes['text'] as $attribute) {
                $attrs = ['attribute_code' => $attribute['code']];
                if ($formData['package_weight_source'] == MerchantFulfillment::WEIGHT_SOURCE_CUSTOM_ATTRIBUTE
                    && $attribute['code'] == $formData['package_weight_custom_attribute']
                ) {
                    $attrs['selected'] = 'selected';
                }
                $preparedAttributes[] = [
                    'attrs' => $attrs,
                    'value' => MerchantFulfillment::WEIGHT_SOURCE_CUSTOM_ATTRIBUTE,
                    'label' => $attribute['label'],
                ];
            }

            $packageWeightSourceSelect = $this->elementFactory->create(
                self::SELECT,
                [
                    'data' => [
                        'name'   => 'package_weight_source',
                        'values' => [
                            MerchantFulfillment::WEIGHT_SOURCE_NONE         => '-- ' . $this->__(
                                    'Please select'
                                ) . ' --',
                            MerchantFulfillment::WEIGHT_SOURCE_CUSTOM_VALUE => $this->__('Custom Value'),
                            [
                                'label' => $this->__('Magento Attributes'),
                                'value' => $preparedAttributes,
                                'attrs' => [
                                    'is_magento_attribute' => true
                                ]
                            ]
                        ],
                        'value'  => isset($formData['package_weight_source']) &&
                            $formData['package_weight_source'] != MerchantFulfillment::WEIGHT_SOURCE_CUSTOM_ATTRIBUTE
                            ? $formData['package_weight_source'] : '',
                        'class'  => 'M2ePro-validate-weight',
                        'style'  => 'width: 70%'
                    ]
                ]
            );
            $packageWeightSourceSelect->setId('package_weight_source');
            $packageWeightSourceSelect->setForm($form);

            $tooltipHtml = $this->getTooltipHtml(
                $this->__(
                    'Enter a Weight Value and select the appropriate Measurement Units. Please, note the
                            selected Measurement Units will be Saved Up till the next Changes are Made.'
                )
            );
            $fieldset->addField(
                'package_weight_container',
                self::CUSTOM_CONTAINER,
                [
                    'label'    => $this->__('Weight'),
                    'title'    => $this->__('Weight'),
                    'style'    => 'padding-top: 0;',
                    'text'     => <<<HTML
{$packageWeightSourceSelect->toHtml()}
{$measureSelect->toHtml()}
{$tooltipHtml}
HTML
                    ,
                    'required' => true
                ]
            );

            $fieldset->addField(
                'package_weight_custom_value',
                'text',
                [
                    'name'                   => 'package_weight_custom_value',
                    'label'                  => $this->__('Weight Value'),
                    'title'                  => $this->__('Weight Value'),
                    'class'                  => 'M2ePro-required-when-visible validate-greater-than-zero',
                    'value'                  => $this->getData('total_weight') > 0 ? $this->getData(
                        'total_weight'
                    ) : '',
                    'required'               => true,
                    'field_extra_attributes' => 'id="package_weight_custom_value_tr"',
                ]
            );
        } else {
            $packageWeightInput = $this->elementFactory->create(
                'text',
                [
                    'data' => [
                        'name'  => 'fulfillment_package_weight',
                        'value' => $this->getData('total_weight') > 0 ? $this->getData('total_weight') : '',
                        'class' => 'M2ePro-required-when-visible validate-greater-than-zero'
                    ]
                ]
            );
            $packageWeightInput->setId('fulfillment_package_weight');
            $packageWeightInput->setForm($form);

            $fieldset->addField(
                'package_weight_container',
                self::CUSTOM_CONTAINER,
                [
                    'label'    => $this->__('Weight'),
                    'title'    => $this->__('Weight'),
                    'style'    => 'padding-top: 0;',
                    'text'     => <<<HTML
{$packageWeightInput->toHtml()}
{$measureSelect->toHtml()}
HTML
                    ,
                    'required' => true,
                    'tooltip'  => $this->__(
                        'Enter a Weight Value and select the appropriate Measurement Units. Please, note the selected
                        Measurement Units will be Saved Up till the next Changes are Made.'
                    )
                ]
            );
        }

        $fieldset = $form->addFieldset(
            'shipping_origin_fieldset',
            [
                'legend' => $this->__('Shipping Origin')
            ]
        );

        $fieldset->addField(
            'ship_from_address_name',
            'text',
            [
                'name'     => 'ship_from_address_name',
                'label'    => $this->__('Name'),
                'title'    => $this->__('Name'),
                'value'    => $formData['ship_from_address_name'],
                'required' => true
            ]
        );

        $fieldset->addField(
            'ship_from_address_email',
            'text',
            [
                'name'     => 'ship_from_address_email',
                'label'    => $this->__('Email'),
                'title'    => $this->__('Email'),
                'class'    => 'M2ePro-validate-email',
                'value'    => $formData['ship_from_address_email'],
                'required' => true
            ]
        );

        $fieldset->addField(
            'ship_from_address_phone',
            'text',
            [
                'name'     => 'ship_from_address_phone',
                'label'    => $this->__('Phone'),
                'title'    => $this->__('Phone'),
                'value'    => $formData['ship_from_address_phone'],
                'required' => true
            ]
        );

        $fieldset->addField(
            'ship_from_address_country',
            self::SELECT,
            [
                'name'     => 'ship_from_address_country',
                'label'    => $this->__('Country'),
                'title'    => $this->__('Country'),
                'values'   => $this->getHelper('Magento')->getCountries(),
                'value'    => $formData['ship_from_address_country'],
                'required' => true
            ]
        )->addCustomAttribute('style', 'width: 70%;');

        $regionStateInput = $this->elementFactory->create(
            'text',
            [
                'data' => [
                    'name'  => 'ship_from_address_region_state',
                    'value' => $formData['ship_from_address_region_state'],
                    'class' => 'M2ePro-required-when-visible '
                ]
            ]
        );
        $regionStateInput->setId('ship_from_address_region_state_input');
        $regionStateInput->addCustomAttribute('display', 'none');
        $regionStateInput->setForm($form);

        $regionStateSelect = $this->elementFactory->create(
            self::SELECT,
            [
                'data' => [
                    'name'   => 'ship_from_address_region_state',
                    'values' => [
                        [
                            'label' => $formData['ship_from_address_region_state'],
                            'value' => $formData['ship_from_address_region_state']
                        ]
                    ],
                    'value'  => $formData['ship_from_address_region_state'],
                    'class'  => 'M2ePro-required-when-visible',
                    'style'  => 'width: 70%; display: none;'
                ]
            ]
        );
        $regionStateSelect->setId('ship_from_address_region_state_select');
        $regionStateSelect->setForm($form);

        $fieldset->addField(
            'ship_from_address_region_state_container',
            self::CUSTOM_CONTAINER,
            [
                'label'    => $this->__('Region/State'),
                'title'    => $this->__('Region/State'),
                'style'    => 'padding-top: 0;',
                'text'     => <<<HTML
{$regionStateInput->toHtml()}
{$regionStateSelect->toHtml()}
HTML
                ,
                'required' => true
            ]
        );

        $fieldset->addField(
            'ship_from_address_city',
            'text',
            [
                'name'     => 'ship_from_address_city',
                'label'    => $this->__('City'),
                'title'    => $this->__('City'),
                'value'    => $formData['ship_from_address_city'],
                'required' => true
            ]
        );

        $fieldset->addField(
            'ship_from_address_address_line_1',
            'text',
            [
                'name'     => 'ship_from_address_address_line_1',
                'label'    => $this->__('Street Address'),
                'title'    => $this->__('Street Address'),
                'value'    => $formData['ship_from_address_address_line_1'],
                'required' => true
            ]
        );

        $fieldset->addField(
            'ship_from_address_address_line_2',
            'text',
            [
                'name'     => 'ship_from_address_address_line_2',
                'label'    => $this->__('Street Address Line 2'),
                'title'    => $this->__('Street Address Line 2'),
                'value'    => $formData['ship_from_address_address_line_2'],
                'required' => true
            ]
        );

        $fieldset->addField(
            'ship_from_address_postal_code',
            'text',
            [
                'name'     => 'ship_from_address_postal_code',
                'label'    => $this->__('Postal Code'),
                'title'    => $this->__('Postal Code'),
                'value'    => $formData['ship_from_address_postal_code'],
                'required' => true
            ]
        );

        $fieldset = $form->addFieldset(
            'additional_settings_fieldset',
            [
                'legend' => $this->__('Additional Settings')
            ]
        );

        $fieldset->addField(
            'carrier_will_pickup',
            self::SELECT,
            [
                'name'     => 'carrier_will_pickup',
                'label'    => $this->__('Carrier Will Pickup'),
                'title'    => $this->__('Carrier Will Pickup'),
                'values'   => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes')
                ],
                'value'    => $formData['carrier_will_pickup'],
                'required' => true,
                'tooltip'  => $this->__(
                    'Indicates whether the Carrier will pick up the Package. Please, note the selected Values will be
                    Saved Up till the next changes are made.'
                )
            ]
        )->addCustomAttribute('style', 'width: 70%;');

        $fieldset->addField(
            'delivery_experience',
            self::SELECT,
            [
                'name'     => 'delivery_experience',
                'label'    => $this->__('Delivery Experience'),
                'title'    => $this->__('Delivery Experience'),
                'values'   => [
                    MerchantFulfillment::DELIVERY_EXPERIENCE_WITH_ADULT_SIGNATURE =>
                        $this->__('Delivery Confirmation With Adult Signature'),
                    MerchantFulfillment::DELIVERY_EXPERIENCE_WITH_SIGNATURE       =>
                        $this->__('Delivery Confirmation With Signature'),
                    MerchantFulfillment::DELIVERY_EXPERIENCE_WITHOUT_SIGNATURE    =>
                        $this->__('Delivery Confirmation Without Signature'),
                    MerchantFulfillment::DELIVERY_EXPERIENCE_NO_TRACKING          =>
                        $this->__('No Delivery Confirmation'),
                ],
                'value'    => $formData['delivery_experience'],
                'required' => true,
                'tooltip'  => $this->__(
                    'Select a delivery confirmation level. Please, note the selected Value will be Saved Up till the
                    next changes are made.'
                )
            ]
        )->addCustomAttribute('style', 'width: 70%;');

        $form->setUseContainer(true);
        $this->setForm($form);

        return $this;
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Helper\Component\Amazon\MerchantFulfillment::class)
        );

        $this->js->add(
            <<<JS

    window.minimumDeclaredValue = {$this->getData('declared_value')};

    $('package_dimension_source')
        .observe('change', AmazonOrderMerchantFulfillmentObj.packageDimensionSourceChange)
        .simulate('change');

    $('ship_from_address_country')
        .observe('change', AmazonOrderMerchantFulfillmentObj.shippingCountryChange)
        .simulate('change');
JS
        );

        if ($this->canUseProductAttributes()) {
            $this->js->add(
                <<<JS
    $('package_weight_source')
        .observe('change', AmazonOrderMerchantFulfillmentObj.fulfillmentPackageWeightChange)
        .simulate('change');
JS
            );
        }

        return parent::_prepareLayout();
    }

    //########################################

    protected function _toHtml()
    {
        $helpBlock = $this->createBlock('HelpBlock');
        $helpBlock->setData(
            [
                'title'   => $this->__('Amazon\'s Shipping Services'),
                'content' => $this->__(
                    <<<HTML
<p>Amazon's Shipping Services offer a variety of <strong>Shipping Benefits</strong>, including several Shipping Options
if you need to expedite your delivery.</p>
<br/>
<p>This Tool provides <strong>programmatic access</strong> to Amazon’s Shipping Services for Sellers, including
competitive rates from Amazon-partnered Carriers. Sellers can find out what Shipping Service offers are available by
<strong>submitting information</strong> about a proposed Shipment, such as <strong>Package Size</strong> and
<strong>Weight</strong>, <strong>Shipment Origin</strong>, and <strong>Delivery Date</strong> requirements. Sellers
can choose from the Shipping Service offers returned by Amazon, and then Purchase Shipping Labels for Fulfilling
their Orders.</p>
<br/>
<p>For more information about Amazon's Shipping Services Program, see the Seller Central Help.</p>
<br/>
<p>Amazon's Shipping Service tool is required to be used for Amazon Prime Orders.</p>
HTML
                )
            ]
        );

        $breadcrumb = $this->createBlock('Amazon_Order_MerchantFulfillment_Breadcrumb')
            ->setSelectedStep(1);

        return $helpBlock->toHtml() .
            $breadcrumb->toHtml() .
            parent::_toHtml();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Order
     */
    protected function getOrder()
    {
        return $this->getData('order');
    }

    protected function getOrderItems()
    {
        $data = [];
        $totalWeight = 0;

        foreach ($this->getData('order_items') as $parentOrderItem) {
            /**
             * @var $parentOrderItem \Ess\M2ePro\Model\Order\Item
             */
            $parentOrderItem->getMagentoProduct();

            $orderItem = $parentOrderItem->getChildObject();

            $orderItemProduct = $parentOrderItem->getProduct();
            if ($orderItemProduct !== null) {
                $weight = $orderItemProduct->getWeight();
                if ($weight !== null) {
                    $totalWeight += $weight;
                }
            }

            $data[] = [
                'title'    => $orderItem->getTitle(),
                'sku'      => $orderItem->getSku(),
                'asin'     => $orderItem->getGeneralId(),
                'qty'      => $orderItem->getQtyPurchased(),
                'price'    => $orderItem->getPrice(),
                'currency' => $orderItem->getCurrency(),
            ];
        }

        $this->setData('total_weight', $totalWeight);

        return $data;
    }

    protected function getShippingOriginData()
    {
        return [
            'country_id'   => $this->getStoreConfig('shipping/origin/country_id'),
            'region_id'    => $this->getStoreConfig('shipping/origin/region_id'),
            'postal_code'  => $this->getStoreConfig('shipping/origin/postcode'),
            'city'         => $this->getStoreConfig('shipping/origin/city'),
            'street_line1' => $this->getStoreConfig('shipping/origin/street_line1'),
            'street_line2' => $this->getStoreConfig('shipping/origin/street_line2'),
        ];
    }

    protected function getStoreConfig($key)
    {
        return $this->storeConfig->getValue(
            $key,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getOrder()->getStore()->getCode()
        );
    }

    protected function getUserData()
    {
        return $this->getHelper('Module')->getRegistry()->getValueFromJson('/wizard/license_form_data/');
    }

    protected function canUseProductAttributes()
    {
        $orderItems = $this->getData('order_items');

        if (empty($orderItems) || count($orderItems) !== 1) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Order\Item $item */
        $item = array_shift($orderItems);

        if ($item->getMagentoProduct() === null) {
            return false;
        }

        return true;
    }

    //########################################
}
