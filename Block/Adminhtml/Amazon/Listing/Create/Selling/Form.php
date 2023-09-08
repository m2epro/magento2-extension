<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Create\Selling;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Amazon\Listing as AmazonListing;
use Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\Sku\General as ValidatorSkuGeneral;

class Form extends AbstractForm
{
    protected $useFormContainer = true;

    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory */
    protected $amazonFactory;

    /** @var \Ess\M2ePro\Helper\Magento\Attribute */
    protected $magentoAttributeHelper;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $sessionDataHelper;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\Session $sessionDataHelper,
        array $data = []
    ) {
        $this->amazonFactory = $amazonFactory;
        $this->magentoAttributeHelper = $magentoAttributeHelper;
        $this->dataHelper = $dataHelper;
        $this->sessionDataHelper = $sessionDataHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'method' => 'post',
                    'action' => 'javascript:void(0)',
                    'enctype' => 'multipart/form-data',
                    'class' => 'admin__scope-old',
                ],
            ]
        );

        $attributes = $this->magentoAttributeHelper->getAll();
        $attributesByTypes = [
            'boolean' => $this->magentoAttributeHelper->filterByInputTypes(
                $attributes,
                ['boolean']
            ),
            'text' => $this->magentoAttributeHelper->filterByInputTypes(
                $attributes,
                ['text']
            ),
            'text_textarea' => $this->magentoAttributeHelper->filterByInputTypes(
                $attributes,
                ['text', 'textarea']
            ),
            'text_date' => $this->magentoAttributeHelper->filterByInputTypes(
                $attributes,
                ['text', 'date', 'datetime']
            ),
            'text_select' => $this->magentoAttributeHelper->filterByInputTypes(
                $attributes,
                ['text', 'select']
            ),
            'text_images' => $this->magentoAttributeHelper->filterByInputTypes(
                $attributes,
                ['text', 'image', 'media_image', 'gallery', 'multiline', 'textarea', 'select', 'multiselect']
            ),
        ];

        $formData = $this->getListingData();

        $form->addField(
            'marketplace_id',
            'hidden',
            [
                'value' => $formData['marketplace_id'],
            ]
        );

        $form->addField(
            'store_id',
            'hidden',
            [
                'value' => $formData['store_id'],
            ]
        );

        // SKU Settings
        $fieldset = $form->addFieldset(
            'sku_settings_fieldset',
            [
                'legend' => $this->__('SKU Settings'),
                'collapsable' => true,
            ]
        );

        $fieldset->addField(
            'sku_custom_attribute',
            'hidden',
            [
                'name' => 'sku_custom_attribute',
                'value' => $formData['sku_custom_attribute'],
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByTypes['text'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['sku_mode'] == AmazonListing::SKU_MODE_CUSTOM_ATTRIBUTE
                && $attribute['code'] == $formData['sku_custom_attribute']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => AmazonListing::SKU_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'sku_mode',
            self::SELECT,
            [
                'name' => 'sku_mode',
                'label' => $this->__('Source'),
                'values' => [
                    AmazonListing::SKU_MODE_PRODUCT_ID => $this->__('Product ID'),
                    AmazonListing::SKU_MODE_DEFAULT => $this->__('Product SKU'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'value' => $formData['sku_mode'] != AmazonListing::SKU_MODE_CUSTOM_ATTRIBUTE ?
                    $formData['sku_mode'] : '',
                'tooltip' => $this->__(
                    'Is used to identify Amazon Items, which you list, in Amazon Seller Central Inventory.
                    <br/>
                    <br/>
                    <b>Note:</b> If you list a Magento Product and M2E Pro find an Amazon Item with the same
                    <i>Merchant SKU</i> in Amazon Inventory, they will be Mapped.'
                ),

                'create_magento_attribute' => true,
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField(
            'sku_modification_mode',
            self::SELECT,
            [
                'label' => $this->__('Modification'),
                'name' => 'sku_modification_mode',
                'values' => [
                    AmazonListing::SKU_MODIFICATION_MODE_NONE => $this->__('None'),
                    AmazonListing::SKU_MODIFICATION_MODE_PREFIX => $this->__('Prefix'),
                    AmazonListing::SKU_MODIFICATION_MODE_POSTFIX => $this->__('Postfix'),
                    AmazonListing::SKU_MODIFICATION_MODE_TEMPLATE => $this->__('Template'),
                ],
                'value' => $formData['sku_modification_mode'],
                'tooltip' => $this->__(
                    'Choose from the available options to modify Amazon Item SKU from the Source attribute.'
                ),
            ]
        );

        $fieldStyle = '';
        if ($formData['sku_modification_mode'] == AmazonListing::SKU_MODIFICATION_MODE_NONE) {
            $fieldStyle = 'style="display: none"';
        }

        $fieldset->addField(
            'sku_modification_custom_value',
            'text',
            [
                'container_id' => 'sku_modification_custom_value_tr',
                'label' => $this->__('Modification Value'),
                'name' => 'sku_modification_custom_value',
                'required' => true,
                'value' => $formData['sku_modification_custom_value'],
                'class' => 'M2ePro-validate-sku-modification-custom-value
                    M2ePro-validate-sku-modification-custom-value-max-length',
                'field_extra_attributes' => $fieldStyle,
            ]
        );

        $fieldset->addField(
            'generate_sku_mode',
            self::SELECT,
            [
                'label' => $this->__('Generate'),
                'name' => 'generate_sku_mode',
                'values' => [
                    AmazonListing::GENERATE_SKU_MODE_NO => $this->__('No'),
                    AmazonListing::GENERATE_SKU_MODE_YES => $this->__('Yes'),
                ],
                'value' => $formData['generate_sku_mode'],
                'tooltip' => $this->__(
                    'Enable this option to allow M2E Pro to generate a new SKU on Amazon
                     if the product\'s SKU already exists there. Useful for listing the same Magento product on Amazon
                      more than once (e.g., listing it as both AFN and MFN).'
                ),
            ]
        );

        // Policies
        $fieldset = $form->addFieldset(
            'policies_fieldset',
            [
                'legend' => $this->__('Policies'),
                'collapsable' => true,
            ]
        );

        $fieldset->addField(
            'template_selling_format_messages',
            self::CUSTOM_CONTAINER,
            [
                'style' => 'display: block;',
                'css_class' => 'm2epro-fieldset-table no-margin-bottom',
            ]
        );

        $sellingFormatTemplates = $this->getSellingFormatTemplates();
        $style = count($sellingFormatTemplates) === 0 ? 'display: none' : '';

        $templateSellingFormat = $this->elementFactory->create(
            'select',
            [
                'data' => [
                    'html_id' => 'template_selling_format_id',
                    'name' => 'template_selling_format_id',
                    'style' => 'width: 50%;' . $style,
                    'no_span' => true,
                    'values' => array_merge(
                        [
                            '' => '',
                        ],
                        $sellingFormatTemplates
                    ),
                    'value' => $formData['template_selling_format_id'],
                    'required' => true,
                ],
            ]
        );
        $templateSellingFormat->setForm($form);

        $editPolicyTooltip = $this->getTooltipHtml(
            $this->__(
                'You can edit the saved Policy any time you need. However, the changes you make will automatically
            affect all of the Products which are listed using this Policy.'
            )
        );

        $style = count($sellingFormatTemplates) === 0 ? '' : 'display: none';
        $fieldset->addField(
            'template_selling_format_container',
            self::CUSTOM_CONTAINER,
            [
                'label' => $this->__('Selling Policy'),
                'style' => 'line-height: 34px; display: initial;',
                'field_extra_attributes' => 'style="margin-bottom: 5px"',
                'required' => true,
                'text' => <<<HTML
    <span id="template_selling_format_label" style="padding-right: 25px; {$style}">
        {$this->__('No Policies available.')}
    </span>
    {$templateSellingFormat->toHtml()}
HTML
                ,
                'after_element_html' => <<<HTML
&nbsp;
<span style="line-height: 30px;">
    <span id="edit_selling_format_template_link" style="color:#41362f">
        <a href="javascript: void(0);" style="" onclick="AmazonListingSettingsObj.editTemplate(
            M2ePro.url.get('editSellingFormatTemplate'),
            $('template_selling_format_id').value,
            AmazonListingSettingsObj.newSellingFormatTemplateCallback
        );">
            {$this->__('View')}&nbsp;/&nbsp;{$this->__('Edit')}
        </a>
        <div style="width: 45px;
                    display: inline-block;
                    margin-left: -10px;
                    margin-right: 5px;
                    position: relative;
                    bottom: 5px;">
        {$editPolicyTooltip}</div>
        <span>{$this->__('or')}</span>
    </span>
    <a id="add_selling_format_template_link" href="javascript: void(0);"
        onclick="AmazonListingSettingsObj.addNewTemplate(
            M2ePro.url.get('addNewSellingFormatTemplate'),
            AmazonListingSettingsObj.newSellingFormatTemplateCallback
    );">{$this->__('Add New')}</a>
</span>
HTML
            ,
            ]
        );

        $synchronizationTemplates = $this->getSynchronizationTemplates();
        $style = count($synchronizationTemplates) === 0 ? 'display: none' : '';

        $templateSynchronization = $this->elementFactory->create(
            'select',
            [
                'data' => [
                    'html_id' => 'template_synchronization_id',
                    'name' => 'template_synchronization_id',
                    'style' => 'width: 50%;' . $style,
                    'no_span' => true,
                    'values' => array_merge(
                        [
                            '' => '',
                        ],
                        $synchronizationTemplates
                    ),
                    'value' => $formData['template_synchronization_id'],
                    'required' => true,
                ],
            ]
        );
        $templateSynchronization->setForm($form);

        $style = count($synchronizationTemplates) === 0 ? '' : 'display: none';
        $fieldset->addField(
            'template_synchronization_container',
            self::CUSTOM_CONTAINER,
            [
                'label' => $this->__('Synchronization Policy'),
                'style' => 'line-height: 34px;display: initial;',
                'field_extra_attributes' => 'style="margin-bottom: 5px"',
                'required' => true,
                'text' => <<<HTML
    <span id="template_synchronization_label" style="padding-right: 25px; {$style}">
        {$this->__('No Policies available.')}
    </span>
    {$templateSynchronization->toHtml()}
HTML
                ,
                'after_element_html' => <<<HTML
&nbsp;
<span style="line-height: 30px;">
    <span id="edit_synchronization_template_link" style="color:#41362f">
        <a href="javascript: void(0);" onclick="AmazonListingSettingsObj.editTemplate(
            M2ePro.url.get('editSynchronizationTemplate'),
            $('template_synchronization_id').value,
            AmazonListingSettingsObj.newSynchronizationTemplateCallback
        );">
            {$this->__('View')}&nbsp;/&nbsp;{$this->__('Edit')}
        </a>
        <div style="width: 45px;
                    display: inline-block;
                    margin-left: -10px;
                    margin-right: 5px;
                    position: relative;
                    bottom: 5px;">
        {$editPolicyTooltip}</div>
        <span>{$this->__('or')}</span>
    </span>
    <a id="add_synchronization_template_link" href="javascript: void(0);"
        onclick="AmazonListingSettingsObj.addNewTemplate(
            M2ePro.url.get('addNewSynchronizationTemplate'),
            AmazonListingSettingsObj.newSynchronizationTemplateCallback
    );">{$this->__('Add New')}</a>
</span>
HTML
            ,
            ]
        );

        $shippingTemplates = $this->getShippingTemplates($formData['account_id']);
        $style = count($shippingTemplates) === 0 ? 'display: none' : '';

        $templateShipping = $this->elementFactory->create(
            'select',
            [
                'data' => [
                    'html_id' => 'template_shipping_id',
                    'name' => 'template_shipping_id',
                    'style' => 'width: 50%;' . $style,
                    'no_span' => true,
                    'values' => array_merge(['' => ' '], $shippingTemplates),
                    'value' => $formData['template_shipping_id'],
                    'required' => false,
                ],
            ]
        );
        $templateShipping->setForm($form);

        $style = count($shippingTemplates) === 0 ? '' : 'display: none';
        $fieldset->addField(
            'template_shipping_container',
            self::CUSTOM_CONTAINER,
            [
                'label' => $this->__('Shipping Policy'),
                'style' => 'line-height: 34px;display: initial;',
                'field_extra_attributes' => 'style="margin-bottom: 5px"',
                'required' => false,
                'text' => <<<HTML
    <span id="template_shipping_label" style="padding-right: 25px; {$style}">
        {$this->__('No Policies available.')}
    </span>
    {$templateShipping->toHtml()}
HTML
                ,
                'after_element_html' => <<<HTML
&nbsp;
<span style="line-height: 30px;">
    <span id="edit_shipping_template_link" style="color:#41362f">
        <a href="javascript: void(0);" onclick="AmazonListingSettingsObj.editTemplate(
            M2ePro.url.get('editShippingTemplate'),
            $('template_shipping_id').value,
            AmazonListingSettingsObj.newShippingTemplateCallback
        );">
            {$this->__('View')}&nbsp;/&nbsp;{$this->__('Edit')}
        </a>
        <div style="width: 45px;
                    display: inline-block;
                    margin-left: -10px;
                    margin-right: 5px;
                    position: relative;
                    bottom: 5px;">
        {$editPolicyTooltip}</div>
        <span>{$this->__('or')}</span>
    </span>
    <a id="add_shipping_template_link" href="javascript: void(0);"
        onclick="AmazonListingSettingsObj.addNewTemplate(
            M2ePro.url.get('addNewShippingTemplate'),
            AmazonListingSettingsObj.newShippingTemplateCallback
    );">{$this->__('Add New')}</a>
</span>
HTML
            ,
            ]
        );

        // Condition Settings
        $fieldset = $form->addFieldset(
            'condition_settings_fieldset',
            [
                'legend' => $this->__('Condition Settings'),
                'collapsable' => true,
            ]
        );

        $fieldset->addField(
            'condition_custom_attribute',
            'hidden',
            [
                'name' => 'condition_custom_attribute',
                'value' => $formData['condition_custom_attribute'],
            ]
        );

        $fieldset->addField(
            'condition_value',
            'hidden',
            [
                'name' => 'condition_value',
                'value' => $formData['condition_value'],
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByTypes['text_select'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['condition_mode'] == AmazonListing::SKU_MODE_CUSTOM_ATTRIBUTE
                && $attribute['code'] == $formData['condition_custom_attribute']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => AmazonListing::SKU_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'condition_mode',
            self::SELECT,
            [
                'name' => 'condition_mode',
                'label' => $this->__('Condition'),
                'values' => [
                    [
                        'label' => $this->__('Recommended Value'),
                        'value' => $this->getRecommendedConditionValues(),
                    ],
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'tooltip' => $this->__(
                    <<<HTML
                    <p>Specify the condition that best describes the current state of your product.</p><br>

                    <p>By providing accurate information about the product condition, you improve the visibility
                    of your listings, ensure fair pricing, and increase customer satisfaction.</p>
HTML
                ),
                'create_magento_attribute' => true,
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        $fieldset->addField(
            'condition_note_mode',
            self::SELECT,
            [
                'container_id' => 'condition_note_mode_tr',
                'label' => $this->__('Condition Note'),
                'name' => 'condition_note_mode',
                'values' => [
                    AmazonListing::CONDITION_NOTE_MODE_NONE => $this->__('None'),
                    AmazonListing::CONDITION_NOTE_MODE_CUSTOM_VALUE => $this->__('Custom Value'),
                ],
                'value' => $formData['condition_note_mode'],
                'tooltip' => $this->__('Short Description of Item(s) Condition.'),
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByTypes['text_textarea'] as $attribute) {
            $attribute['value'] = $attribute['code'];
            $preparedAttributes[] = $attribute;
        }

        $button = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button\MagentoAttribute::class)
                       ->addData([
                           'label' => $this->__('Insert'),
                           'destination_id' => 'condition_note_value',
                           'class' => 'primary',
                           'style' => 'display: inline-block;',
                       ]);

        $fieldset->addField(
            'condition_note_value',
            'textarea',
            [
                'container_id' => 'condition_note_value_tr',
                'name' => 'condition_note_value',
                'label' => $this->__('Condition Note Value'),
                'style' => 'width: 70%;',
                'class' => 'textarea M2ePro-validate-condition-note-length',
                'required' => true,
                'value' => $formData['condition_note_value'],
            ]
        );

        $fieldset->addField(
            'selectAttr_condition_note_value',
            self::SELECT,
            [
                'container_id' => 'condition_custom_tr',
                'label' => $this->__('Product Attribute'),
                'title' => $this->__('Product Attribute'),
                'values' => $preparedAttributes,
                'create_magento_attribute' => true,
                'after_element_html' => $button->toHtml(),
            ]
        );

        // Gift Wrap
        $fieldset = $form->addFieldset(
            'magento_block_amazon_listing_gift_settings',
            [
                'legend' => $this->__('Gift Settings'),
                'collapsable' => true,
            ]
        );

        $fieldset->addField(
            'gift_wrap_attribute',
            'hidden',
            [
                'name' => 'gift_wrap_attribute',
                'value' => $formData['gift_wrap_attribute'],
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByTypes['boolean'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['gift_wrap_mode'] == AmazonListing::GIFT_WRAP_MODE_ATTRIBUTE
                && $attribute['code'] == $formData['gift_wrap_attribute']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => AmazonListing::GIFT_WRAP_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'gift_wrap_mode',
            self::SELECT,
            [
                'name' => 'gift_wrap_mode',
                'label' => $this->__('Gift Wrap'),
                'values' => [
                    AmazonListing::GIFT_WRAP_MODE_NO => $this->__('No'),
                    AmazonListing::GIFT_WRAP_MODE_YES => $this->__('Yes'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                            'new_option_value' => AmazonListing::GIFT_WRAP_MODE_ATTRIBUTE,
                        ],
                    ],
                ],
                'value' => $formData['gift_wrap_mode'] != AmazonListing::GIFT_WRAP_MODE_ATTRIBUTE
                    ? $formData['gift_wrap_mode'] : '',
                'tooltip' => $this->__(
                    'Enable this Option in case you want Gift Wrapped Option be applied to the
                    Products you are going to sell.'
                ),

                'create_magento_attribute' => true,
            ]
        )->addCustomAttribute('allowed_attribute_types', 'boolean');

        $fieldset->addField(
            'gift_message_attribute',
            'hidden',
            [
                'name' => 'gift_message_attribute',
                'value' => $formData['gift_message_attribute'],
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByTypes['boolean'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['gift_message_mode'] == AmazonListing::GIFT_MESSAGE_MODE_ATTRIBUTE
                && $attribute['code'] == $formData['gift_message_attribute']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => AmazonListing::GIFT_MESSAGE_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'gift_message_mode',
            self::SELECT,
            [
                'name' => 'gift_message_mode',
                'label' => $this->__('Gift Message'),
                'values' => [
                    AmazonListing::GIFT_MESSAGE_MODE_NO => $this->__('No'),
                    AmazonListing::GIFT_MESSAGE_MODE_YES => $this->__('Yes'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                            'new_option_value' => AmazonListing::GIFT_MESSAGE_MODE_ATTRIBUTE,
                        ],
                    ],
                ],
                'value' => $formData['gift_message_mode'] != AmazonListing::GIFT_MESSAGE_MODE_ATTRIBUTE
                    ? $formData['gift_message_mode'] : '',
                'tooltip' => $this->__(
                    'Enable this Option in case you want Gift Message Option be applied to the
                    Products you are going to sell.'
                ),

                'create_magento_attribute' => true,
            ]
        )->addCustomAttribute('allowed_attribute_types', 'boolean');

        // Gift Wrap
        $fieldset = $form->addFieldset(
            'magento_block_amazon_listing_add_additional',
            [
                'legend' => $this->__('Additional Settings'),
                'collapsable' => true,
            ]
        );

        $fieldset->addField(
            'handling_time_custom_attribute',
            'hidden',
            [
                'name' => 'handling_time_custom_attribute',
                'value' => $formData['handling_time_custom_attribute'],
            ]
        );

        $fieldset->addField(
            'handling_time_value',
            'hidden',
            [
                'name' => 'handling_time_value',
                'value' => $formData['handling_time_value'],
            ]
        );

        $recommendedValuesOptions = [];
        for ($i = 1; $i <= 30; $i++) {
            $option = [
                'attrs' => ['attribute_code' => $i],
                'value' => AmazonListing::HANDLING_TIME_MODE_RECOMMENDED,
                'label' => $i . ' ' . $this->__('day(s)'),
            ];

            if ($formData['handling_time_value'] == $i) {
                $option['attrs']['selected'] = 'selected';
            }

            $recommendedValuesOptions[] = $option;
        }

        $preparedAttributes = [];
        foreach ($attributesByTypes['text_select'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['handling_time_mode'] == AmazonListing::HANDLING_TIME_MODE_CUSTOM_ATTRIBUTE
                && $attribute['code'] == $formData['handling_time_custom_attribute']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => AmazonListing::HANDLING_TIME_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldConfig = [
            'name' => 'handling_time_mode',
            'label' => $this->__('Production Time'),
            'values' => [
                AmazonListing::HANDLING_TIME_MODE_NONE => $this->__('None'),
                [
                    'label' => $this->__('Recommended Value'),
                    'value' => $recommendedValuesOptions,
                ],
                [
                    'label' => $this->__('Magento Attribute'),
                    'value' => $preparedAttributes,
                    'attrs' => [
                        'is_magento_attribute' => true,
                    ],
                ],
            ],
            'tooltip' => $this->__('Time that is needed to prepare an Item to be shipped.'),

            'create_magento_attribute' => true,
        ];

        if ($formData['handling_time_mode'] == AmazonListing::HANDLING_TIME_MODE_NONE) {
            $fieldConfig['value'] = $formData['handling_time_mode'];
        }

        $fieldset->addField(
            'handling_time_mode',
            self::SELECT,
            $fieldConfig
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        $fieldset->addField(
            'restock_date_custom_attribute',
            'hidden',
            [
                'name' => 'restock_date_custom_attribute',
                'value' => $formData['restock_date_custom_attribute'],
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByTypes['text_date'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['restock_date_mode'] == AmazonListing::RESTOCK_DATE_MODE_CUSTOM_ATTRIBUTE
                && $attribute['code'] == $formData['restock_date_custom_attribute']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => AmazonListing::RESTOCK_DATE_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'restock_date_mode',
            self::SELECT,
            [
                'name' => 'restock_date_mode',
                'label' => $this->__('Restock Date'),
                'values' => [
                    AmazonListing::RESTOCK_DATE_MODE_NONE => $this->__('None'),
                    AmazonListing::RESTOCK_DATE_MODE_CUSTOM_VALUE => $this->__('Custom Value'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'value' => $formData['restock_date_mode'] != AmazonListing::RESTOCK_DATE_MODE_CUSTOM_ATTRIBUTE
                    ? $formData['restock_date_mode'] : '',
                'tooltip' => $this->__(
                    'The date you will be able to ship any back-ordered Items to a Customer.
                     Enter the date in the format YYYY-MM-DD.'
                ),

                'create_magento_attribute' => true,
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,date');

        $fieldset->addField(
            'restock_date_value',
            'date',
            [
                'container_id' => 'restock_date_value_tr',
                'name' => 'restock_date_value',
                'label' => $this->__('Restock Date'),
                'required' => true,
                'date_format' => $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT),
                'time_format' => $this->_localeDate->getTimeFormat(\IntlDateFormatter::SHORT),
                'value' => $formData['restock_date_value'],
            ]
        );

        $form->setUseContainer($this->useFormContainer);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Create\Selling\Form|\Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \ReflectionException
     */
    protected function _prepareLayout()
    {
        $this->jsPhp->addConstants(
            $this->dataHelper
                ->getClassConstants(\Ess\M2ePro\Helper\Component\Amazon::class)
        );
        $this->jsPhp->addConstants(
            $this->dataHelper
                ->getClassConstants(AmazonListing::class)
        );
        $this->jsPhp->addConstants($this->dataHelper->getClassConstants(ValidatorSkuGeneral::class));

        $this->jsUrl->addUrls(
            [
                'templateCheckMessages' => $this->getUrl(
                    '*/template/checkMessages',
                    [
                        'component_mode' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
                    ]
                ),
                'addNewSellingFormatTemplate' => $this->getUrl(
                    '*/amazon_template_sellingFormat/new',
                    [
                        'wizard' => $this->getRequest()->getParam('wizard'),
                        'close_on_save' => 1,
                    ]
                ),
                'editSellingFormatTemplate' => $this->getUrl(
                    '*/amazon_template_sellingFormat/edit',
                    [
                        'wizard' => $this->getRequest()->getParam('wizard'),
                        'close_on_save' => 1,
                    ]
                ),
                'getSellingFormatTemplates' => $this->getUrl(
                    '*/general/modelGetAll',
                    [
                        'model' => 'Template_SellingFormat',
                        'id_field' => 'id',
                        'data_field' => 'title',
                        'sort_field' => 'title',
                        'sort_dir' => 'ASC',
                        'component_mode' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
                    ]
                ),
                'addNewSynchronizationTemplate' => $this->getUrl(
                    '*/amazon_template_synchronization/new',
                    [
                        'wizard' => $this->getRequest()->getParam('wizard'),
                        'close_on_save' => 1,
                    ]
                ),
                'editSynchronizationTemplate' => $this->getUrl(
                    '*/amazon_template_synchronization/edit',
                    [
                        'wizard' => $this->getRequest()->getParam('wizard'),
                        'close_on_save' => 1,
                    ]
                ),
                'getSynchronizationTemplates' => $this->getUrl(
                    '*/general/modelGetAll',
                    [
                        'model' => 'Template_Synchronization',
                        'id_field' => 'id',
                        'data_field' => 'title',
                        'sort_field' => 'title',
                        'sort_dir' => 'ASC',
                        'component_mode' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
                    ]
                ),
                'addNewShippingTemplate' => $this->getUrl(
                    '*/amazon_template_shipping/new',
                    [
                        'wizard' => $this->getRequest()->getParam('wizard'),
                        'close_on_save' => 1,
                    ]
                ),
                'editShippingTemplate' => $this->getUrl(
                    '*/amazon_template_shipping/edit',
                    [
                        'wizard' => $this->getRequest()->getParam('wizard'),
                        'close_on_save' => 1,
                    ]
                ),
                'getShippingTemplates' => $this->getUrl(
                    '*/general/modelGetAll',
                    [
                        'model' => 'Amazon_Template_Shipping',
                        'id_field' => 'id',
                        'data_field' => 'title',
                        'sort_field' => 'title',
                        'sort_dir' => 'ASC',
                    ]
                ),
            ]
        );

        $this->jsTranslator->addTranslations(
            [
                'condition_note_length_error' => $this->__(
                    'Must be not more than 2000 characters long.'
                ),
                'sku_modification_custom_value_error' => $this->__(
                    '%value% placeholder should be specified'
                ),
                'sku_modification_custom_value_max_length_error' => $this->__(
                    'The SKU length must be less than %value%.',
                    ValidatorSkuGeneral::SKU_MAX_LENGTH
                ),
            ]
        );

        $this->js->add(
            <<<JS
    require([
        'M2ePro/TemplateManager',
        'M2ePro/Amazon/Listing/Settings',
        'M2ePro/Amazon/Listing/Create/Selling',
        'M2ePro/Plugin/Magento/Attribute/Button'
    ], function(){
        window.TemplateManagerObj = new TemplateManager();

        window.MagentoAttributeButtonObj = new MagentoAttributeButton();

        window.AmazonListingSettingsObj = new AmazonListingSettings();
        window.AmazonListingCreateSellingObj = new AmazonListingCreateSelling();

        AmazonListingSettingsObj.initObservers();
    });
JS
        );

        return parent::_prepareLayout();
    }

    /**
     * @return array[]
     */
    private function getRecommendedConditionValues(): array
    {
        $formData = $this->getListingData();

        $recommendedValues = [
            [
                'attrs' => ['attribute_code' => AmazonListing::CONDITION_NEW],
                'value' => AmazonListing::CONDITION_MODE_DEFAULT,
                'label' => $this->__('New'),
            ],
            [
                'attrs' => ['attribute_code' => AmazonListing::CONDITION_NEW_OEM],
                'value' => AmazonListing::CONDITION_MODE_DEFAULT,
                'label' => $this->__('New - OEM'),
            ],
            [
                'attrs' => ['attribute_code' => AmazonListing::CONDITION_NEW_OPEN_BOX],
                'value' => AmazonListing::CONDITION_MODE_DEFAULT,
                'label' => $this->__('New - Open Box'),
            ],
            [
                'attrs' => ['attribute_code' => AmazonListing::CONDITION_USED_LIKE_NEW],
                'value' => AmazonListing::CONDITION_MODE_DEFAULT,
                'label' => $this->__('Used - Like New'),
            ],
            [
                'attrs' => ['attribute_code' => AmazonListing::CONDITION_USED_VERY_GOOD],
                'value' => AmazonListing::CONDITION_MODE_DEFAULT,
                'label' => $this->__('Used - Very Good'),
            ],
            [
                'attrs' => ['attribute_code' => AmazonListing::CONDITION_USED_GOOD],
                'value' => AmazonListing::CONDITION_MODE_DEFAULT,
                'label' => $this->__('Used - Good'),
            ],
            [
                'attrs' => ['attribute_code' => AmazonListing::CONDITION_USED_ACCEPTABLE],
                'value' => AmazonListing::CONDITION_MODE_DEFAULT,
                'label' => $this->__('Used - Acceptable'),
            ],
            [
                'attrs' => ['attribute_code' => AmazonListing::CONDITION_COLLECTIBLE_LIKE_NEW],
                'value' => AmazonListing::CONDITION_MODE_DEFAULT,
                'label' => $this->__('Collectible - Like New'),
            ],
            [
                'attrs' => ['attribute_code' => AmazonListing::CONDITION_COLLECTIBLE_VERY_GOOD],
                'value' => AmazonListing::CONDITION_MODE_DEFAULT,
                'label' => $this->__('Collectible - Very Good'),
            ],
            [
                'attrs' => ['attribute_code' => AmazonListing::CONDITION_COLLECTIBLE_GOOD],
                'value' => AmazonListing::CONDITION_MODE_DEFAULT,
                'label' => $this->__('Collectible - Good'),
            ],
            [
                'attrs' => ['attribute_code' => AmazonListing::CONDITION_COLLECTIBLE_ACCEPTABLE],
                'value' => AmazonListing::CONDITION_MODE_DEFAULT,
                'label' => $this->__('Collectible - Acceptable'),
            ],
            [
                'attrs' => ['attribute_code' => AmazonListing::CONDITION_REFURBISHED],
                'value' => AmazonListing::CONDITION_MODE_DEFAULT,
                'label' => $this->__('Refurbished'),
            ],
            [
                'attrs' => ['attribute_code' => AmazonListing::CONDITION_CLUB],
                'value' => AmazonListing::CONDITION_MODE_DEFAULT,
                'label' => $this->__('Club'),
            ],
        ];

        foreach ($recommendedValues as &$value) {
            if ($value['attrs']['attribute_code'] == $formData['condition_value']) {
                $value['attrs']['selected'] = 'selected';
            }
        }

        return $recommendedValues;
    }

    /**
     * @return array
     */
    public function getDefaultFieldsValues(): array
    {
        return [
            'sku_mode' => AmazonListing::SKU_MODE_DEFAULT,
            'sku_custom_attribute' => '',
            'sku_modification_mode' => AmazonListing::SKU_MODIFICATION_MODE_NONE,
            'sku_modification_custom_value' => '',
            'generate_sku_mode' => AmazonListing::GENERATE_SKU_MODE_NO,

            'template_selling_format_id' => '',
            'template_synchronization_id' => '',
            'template_shipping_id' => '',

            'condition_mode' => AmazonListing::CONDITION_MODE_DEFAULT,
            'condition_value' => AmazonListing::CONDITION_NEW,
            'condition_custom_attribute' => '',
            'condition_note_mode' => AmazonListing::CONDITION_NOTE_MODE_NONE,
            'condition_note_value' => '',

            'gift_wrap_mode' => AmazonListing::GIFT_WRAP_MODE_NO,
            'gift_wrap_attribute' => '',

            'gift_message_mode' => AmazonListing::GIFT_MESSAGE_MODE_NO,
            'gift_message_attribute' => '',

            'handling_time_mode' => AmazonListing::HANDLING_TIME_MODE_NONE,
            'handling_time_value' => '',
            'handling_time_custom_attribute' => '',

            'restock_date_mode' => AmazonListing::RESTOCK_DATE_MODE_NONE,
            'restock_date_value' => $this->dataHelper->getCurrentTimezoneDate(),
            'restock_date_custom_attribute' => '',
        ];
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getListingData(): array
    {
        if ($this->getRequest()->getParam('id') !== null) {
            $data = array_merge($this->getListing()->getData(), $this->getListing()->getChildObject()->getData());
        } else {
            $data = $this->sessionDataHelper->getValue(
                AmazonListing::CREATE_LISTING_SESSION_DATA
            );
            $data = array_merge($this->getDefaultFieldsValues(), $data);
        }

        if ($data['restock_date_value'] != '') {
            $dateTime = new \DateTime(
                $data['restock_date_value'],
                new \DateTimeZone($this->_localeDate->getDefaultTimezone())
            );
            if ($this->getRequest()->getParam('id') !== null) {
                $dateTime->setTimezone(new \DateTimeZone($this->_localeDate->getConfigTimezone()));
            }

            $data['restock_date_value'] = $dateTime;
        }

        return $data;
    }

    /**
     * @return \Ess\M2ePro\Model\ActiveRecord\AbstractModel|\Ess\M2ePro\Model\Listing
     */
    protected function getListing()
    {
        if ($this->listing === null && $this->getRequest()->getParam('id')) {
            $this->listing = $this->amazonFactory->getCachedObjectLoaded(
                'Listing',
                $this->getRequest()->getParam('id')
            );
        }

        return $this->listing;
    }

    /**
     * @return mixed
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getSellingFormatTemplates()
    {
        $collection = $this->amazonFactory->getObject('Template\SellingFormat')->getCollection();
        $collection->setOrder('title', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS)->columns(
            [
                'value' => 'id',
                'label' => 'title',
            ]
        );

        $result = $collection->toArray();

        return $result['items'];
    }

    /**
     * @return mixed
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getSynchronizationTemplates()
    {
        $collection = $this->amazonFactory->getObject('Template\Synchronization')->getCollection();
        $collection->setOrder('title', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS)->columns(
            [
                'value' => 'id',
                'label' => 'title',
            ]
        );

        $result = $collection->toArray();

        return $result['items'];
    }

    /**
     * @param int $accountId
     *
     * @return mixed
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getShippingTemplates(int $accountId)
    {
        $collection = $this->activeRecordFactory->getObject('Amazon_Template_Shipping')->getCollection();
        $collection->addFieldToFilter('account_id', $accountId);
        $collection->setOrder('title', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);
        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS)->columns(
            [
                'value' => 'id',
                'label' => 'title',
            ]
        );

        $result = $collection->toArray();

        return $result['items'];
    }

    /**
     * @param boolean $useFormContainer
     *
     * @return $this
     */
    public function setUseFormContainer(bool $useFormContainer): self
    {
        $this->useFormContainer = $useFormContainer;

        return $this;
    }
}
