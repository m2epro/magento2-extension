<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\SellingFormat\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Amazon\Template\SellingFormat;

class Form extends AbstractForm
{
    protected function _prepareForm()
    {
        $template = $this->getHelper('Data\GlobalData')->getValue('tmp_template');
        $formData = !is_null($template)
            ? array_merge($template->getData(), $template->getChildObject()->getData()) : [];

        $attributes = $this->getHelper('Magento\Attribute')->getGeneralFromAllAttributeSets();

        $default = array(
            'title' => '',

            'qty_mode' => \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT,
            'qty_custom_value' => 1,
            'qty_custom_attribute' => '',
            'qty_percentage' => 100,
            'qty_modification_mode' => SellingFormat::QTY_MODIFICATION_MODE_OFF,
            'qty_min_posted_value' => SellingFormat::QTY_MIN_POSTED_DEFAULT_VALUE,
            'qty_max_posted_value' => SellingFormat::QTY_MAX_POSTED_DEFAULT_VALUE,

            'price_mode' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_PRODUCT,
            'price_coefficient' => '',
            'price_custom_attribute' => '',

            'map_price_mode' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_NONE,
            'map_price_custom_attribute' => '',

            'sale_price_mode' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_NONE,
            'sale_price_coefficient' => '',
            'sale_price_custom_attribute' => '',

            'price_variation_mode' => SellingFormat::PRICE_VARIATION_MODE_PARENT,

            'sale_price_start_date_mode' => SellingFormat::DATE_VALUE,
            'sale_price_end_date_mode' => SellingFormat::DATE_VALUE,

            'sale_price_start_date_custom_attribute' => '',
            'sale_price_end_date_custom_attribute' => '',

            'sale_price_start_date_value' => $this->getHelper('Data')->getCurrentGmtDate(false, 'Y-m-d'),
            'sale_price_end_date_value' => $this->getHelper('Data')->getCurrentGmtDate(false, 'Y-m-d'),

            'price_vat_percent' => 0
        );

        $formData = array_merge($default, $formData);

        if ($formData['sale_price_start_date_value'] != '') {
            $formData['sale_price_start_date_value'] = $this->getHelper('Data')->getDate(
                $formData['sale_price_start_date_value'],false,'Y-m-d'
            );
        }
        if ($formData['sale_price_end_date_value'] != '') {
            $formData['sale_price_end_date_value'] = $this->getHelper('Data')->getDate(
                $formData['sale_price_end_date_value'],false,'Y-m-d'
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

        $isEdit = !!$this->getRequest()->getParam('id');

        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id'      => 'edit_form',
                    'method'  => 'post',
                    'action'  => $this->getUrl('*/*/save'),
                    'enctype' => 'multipart/form-data',
                    'class' => 'admin__scope-old'
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
                    Selling Format Policy.'
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

        $fieldset = $form->addFieldset('magento_block_amazon_template_selling_format_prices',
            [
                'legend' => $this->__('Price'),
                'collapsable' => false
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByInputTypes['text_price'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['price_mode'] == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_ATTRIBUTE
                && $formData['price_custom_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $priceCoefficient = $this->elementFactory->create('text', ['data' => [
            'html_id' => 'price_coefficient',
            'name' => 'price_coefficient',
            'label' => '',
            'value' => $formData['price_coefficient'],
            'class' => 'M2ePro-validate-price-coefficient',
        ]]);
        $priceCoefficient->setForm($form);

        $tooltipPriceMode = $this->getTooltipHtml(
            '<span id="price_note"></span>'
        );

        $tooltipPriceCoefficient = $this->getTooltipHtml(
            $this->__('Absolute figure (+8,-3), percentage (+15%, -20%) or Currency rate (1.44)')
        );

        $fieldset->addField('price_mode',
            self::SELECT,
            [
                'label' => $this->__('Price'),
                'class' => 'select-main',
                'name' => 'price_mode',
                'values' => [
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_PRODUCT => $this->__('Product Price'),
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_SPECIAL => $this->__('Special Price'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => $formData['price_mode'] != \Ess\M2ePro\Model\Template\SellingFormat::PRICE_ATTRIBUTE
                    ? $formData['price_mode'] : '',
                'create_magento_attribute' => true,
                'after_element_html' => $tooltipPriceMode
                    . '<span id="price_coefficient_td">'
                    . $priceCoefficient->toHtml()
                    . $tooltipPriceCoefficient . '</span>'
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,price');

        $fieldset->addField('price_custom_attribute',
            'hidden',
            [
                'name' => 'price_custom_attribute',
                'value' => $formData['price_custom_attribute']
            ]
        );

        $fieldset->addField('price_variation_mode',
            self::SELECT,
            [
                'label' => $this->__('Variation Price Source'),
                'class' => 'select-main',
                'name' => 'price_variation_mode',
                'values' => [
                    SellingFormat::PRICE_VARIATION_MODE_PARENT => $this->__('Main Product'),
                    SellingFormat::PRICE_VARIATION_MODE_CHILDREN => $this->__('Associated Products')
                ],
                'value' => $formData['price_variation_mode'],
                'tooltip' => $this->__(
                    'Determines where the Price for Bundle Products Options should be taken from.'
                )
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByInputTypes['text_price'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['map_price_mode'] == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_ATTRIBUTE
                && $formData['map_price_custom_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField('map_price_mode',
            self::SELECT,
            [
                'label' => $this->__('Minimum Advertised Price'),
                'class' => 'select-main',
                'name' => 'map_price_mode',
                'values' => [
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_NONE => $this->__('None'),
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_PRODUCT => $this->__('Product Price'),
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_SPECIAL => $this->__('Special Price'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => $formData['map_price_mode'] != \Ess\M2ePro\Model\Template\SellingFormat::PRICE_ATTRIBUTE
                    ? $formData['map_price_mode'] : '',
                'create_magento_attribute' => true,
                'tooltip' => $this->__(
                    'The Selling Price for your Product will not be displayed on the Product Detail
                    Page or Offer Listing Page if it is less than the Minimum Advertised Price.
                    The Customer only sees the Price you\'re selling the Item for if they add the
                    Item to their Shopping Cart.'
                )
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,price');

        $fieldset->addField('map_price_custom_attribute',
            'hidden',
            [
                'name' => 'map_price_custom_attribute',
                'value' => $formData['map_price_custom_attribute']
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByInputTypes['text_price'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['sale_price_mode'] == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_ATTRIBUTE
                && $formData['sale_price_custom_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $salePriceCoefficient = $this->elementFactory->create('text', ['data' => [
            'html_id' => 'sale_price_coefficient',
            'name' => 'sale_price_coefficient',
            'label' => '',
            'value' => $formData['sale_price_coefficient'],
            'class' => 'M2ePro-validate-price-coefficient',
        ]]);
        $salePriceCoefficient->setForm($form);

        $tooltipSalePriceMode = $this->getTooltipHtml(
            '<span id="sale_price_note"></span>'
        );

        $tooltipSalePriceCoefficient = $this->getTooltipHtml(
            $this->__('Absolute figure (+8,-3), percentage (+15%, -20%) or Currency rate (1.44)')
        );

        $fieldset->addField('sale_price_mode',
            self::SELECT,
            [
                'label' => $this->__('Sale Price'),
                'class' => 'select-main',
                'name' => 'sale_price_mode',
                'values' => [
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_NONE => $this->__('None'),
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_PRODUCT => $this->__('Product Price'),
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_SPECIAL => $this->__('Special Price'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => $formData['sale_price_mode'] != \Ess\M2ePro\Model\Template\SellingFormat::PRICE_ATTRIBUTE
                    ? $formData['sale_price_mode'] : '',
                'create_magento_attribute' => true,
                'after_element_html' => $tooltipSalePriceMode
                    . '<span id="sale_price_coefficient_td">'
                    . $salePriceCoefficient->toHtml()
                    . $tooltipSalePriceCoefficient . '</span>'
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $this->css->add(
            'label.mage-error[for="price_coefficient"], label.mage-error[for="sale_price_coefficient"]
                { width: 160px !important; left: 0px !important; }'
        );

        $fieldset->addField('sale_price_custom_attribute',
            'hidden',
            [
                'name' => 'sale_price_custom_attribute',
                'value' => $formData['sale_price_custom_attribute']
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByInputTypes['text_date'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['sale_price_start_date_mode'] == SellingFormat::DATE_ATTRIBUTE
                && $formData['sale_price_start_date_custom_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => SellingFormat::DATE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField('sale_price_start_date_mode',
            self::SELECT,
            [
                'container_id' => 'sale_price_start_date_mode_tr',
                'label' => $this->__('Start Date'),
                'class' => 'select-main',
                'name' => 'sale_price_start_date_mode',
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
                'value' => $formData['sale_price_start_date_mode'] != SellingFormat::DATE_ATTRIBUTE
                    ? $formData['sale_price_start_date_mode'] : '',
                'create_magento_attribute' => true,
                'tooltip' => $this->__('Time and date when the <i>Sale Price</i> will be displayed on Amazon.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,date');

        $fieldset->addField('sale_price_start_date_custom_attribute',
            'hidden',
            [
                'name' => 'sale_price_start_date_custom_attribute',
                'value' => $formData['sale_price_start_date_custom_attribute']
            ]
        );

        $fieldset->addField('sale_price_start_date_value',
            'date',
            [
                'container_id' => 'sale_price_start_date_value_tr',
                'label' => $this->__('Start Date Value'),
                'name' => 'sale_price_start_date_value',
                'value' => $formData['sale_price_start_date_value'],
                'class' => 'M2ePro-input-datetime',
                'date_format' => 'y-MM-dd',
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByInputTypes['text_date'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['sale_price_end_date_mode'] == SellingFormat::DATE_ATTRIBUTE
                && $formData['sale_price_end_date_custom_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => SellingFormat::DATE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField('sale_price_end_date_mode',
            self::SELECT,
            [
                'container_id' => 'sale_price_end_date_mode_tr',
                'label' => $this->__('End Date'),
                'class' => 'select-main',
                'name' => 'sale_price_end_date_mode',
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
                'value' => $formData['sale_price_end_date_mode'] != SellingFormat::DATE_ATTRIBUTE
                    ? $formData['sale_price_end_date_mode'] : '',
                'create_magento_attribute' => true,
                'tooltip' => $this->__('Time and date when the <i>Sale Price</i> will be hidden on Amazon.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,date');

        $fieldset->addField('sale_price_end_date_custom_attribute',
            'hidden',
            [
                'name' => 'sale_price_end_date_custom_attribute',
                'value' => $formData['sale_price_end_date_custom_attribute']
            ]
        );

        $fieldset->addField('sale_price_end_date_value',
            'date',
            [
                'container_id' => 'sale_price_end_date_value_tr',
                'label' => $this->__('End Date Value'),
                'name' => 'sale_price_end_date_value',
                'value' => $formData['sale_price_end_date_value'],
                'class' => 'M2ePro-input-datetime',
                'date_format' => 'y-MM-dd',
            ]
        );

        $fieldset->addField('price_increase_vat_percent',
            self::SELECT,
            [
                'label' => $this->__('Add VAT Percentage'),
                'class' => 'select-main',
                'name' => 'price_increase_vat_percent',
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes')
                ],
                'value' => (int)($formData['price_vat_percent'] > 0),
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

        $fieldset->addField('price_vat_percent',
            'text',
            [
                'container_id' => 'price_vat_percent_tr',
                'label' => $this->__('VAT Rate, %'),
                'name' => 'price_vat_percent',
                'value' => $formData['price_vat_percent'],
                'class' => 'M2ePro-validate-vat-percent',
                'required' => true
            ]
        );

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

            'Product Price for Amazon Listing(s).' => $this->__('Product Price for Amazon Listing(s).'),
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

            'Add Selling Format Policy' => $this->__('Add Selling Format Policy'),
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
            )
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
            'price_custom_attribute',
            'map_price_custom_attribute',
            'sale_price_custom_attribute',
            'sale_price_start_date_custom_attribute',
            'sale_price_end_date_custom_attribute',
        ];

        foreach ($jsFormData as $item) {
            $this->js->add("M2ePro.formData.$item = '{$this->getHelper('Data')->escapeJs($formData[$item])}';");
        }

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
                Selling Format Policy contains Price and Quantity related data for the Items,
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
}