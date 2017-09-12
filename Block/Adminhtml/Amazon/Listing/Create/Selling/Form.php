<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Create\Selling;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Amazon\Listing;

class Form extends AbstractForm
{
    protected $sessionKey = 'amazon_listing_create';
    protected $useFormContainer = true;

    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing;

    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->amazonFactory = $amazonFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id'      => 'edit_form',
                    'method'  => 'post',
                    'action'  => 'javascript:void(0)',
                    'enctype' => 'multipart/form-data',
                    'class' => 'admin__scope-old'
                ]
            ]
        );

        /** @var \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper */
        $magentoAttributeHelper = $this->getHelper('Magento\Attribute');

        $attributesByTypes = array(
            'boolean' => $magentoAttributeHelper->filterByInputTypes(
                $this->getData('all_attributes'), array('boolean')
            ),
            'text' => $magentoAttributeHelper->filterByInputTypes(
                $this->getData('general_attributes'), array('text')
            ),
            'text_textarea' => $magentoAttributeHelper->filterByInputTypes(
                $this->getData('all_attributes'), array('text', 'textarea')
            ),
            'text_date' => $magentoAttributeHelper->filterByInputTypes(
                $this->getData('general_attributes'), array('text', 'date', 'datetime')
            ),
            'text_select' => $magentoAttributeHelper->filterByInputTypes(
                $this->getData('general_attributes'), array('text', 'select')
            ),
            'text_images' => $magentoAttributeHelper->filterByInputTypes(
                $this->getData('general_attributes'),
                array('text', 'image', 'media_image', 'gallery', 'multiline', 'textarea', 'select', 'multiselect')
            )
        );
        $formData = $this->getListingData();

        // SKU Settings
        $fieldset = $form->addFieldset(
            'sku_settings_fieldset',
            [
                'legend' => $this->__('SKU Settings'),
                'collapsable' => true
            ]
        );

        $fieldset->addField(
            'sku_custom_attribute',
            'hidden',
            [
                'name' => 'sku_custom_attribute',
                'value' => $formData['sku_custom_attribute']
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByTypes['text'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['sku_mode'] == \Ess\M2ePro\Model\Amazon\Listing::SKU_MODE_CUSTOM_ATTRIBUTE
                && $attribute['code'] == $formData['sku_custom_attribute']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \Ess\M2ePro\Model\Amazon\Listing::SKU_MODE_CUSTOM_ATTRIBUTE,
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
                    \Ess\M2ePro\Model\Amazon\Listing::SKU_MODE_PRODUCT_ID => $this->__('Product ID'),
                    \Ess\M2ePro\Model\Amazon\Listing::SKU_MODE_DEFAULT => $this->__('Product SKU'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => $formData['sku_mode'] != Listing::SKU_MODE_CUSTOM_ATTRIBUTE ? $formData['sku_mode'] : '',
                'create_magento_attribute' => true,
                'tooltip' => $this->__(
                    'Is used to identify Amazon Items, which you list, in Amazon Seller Central Inventory.
                    <br/>
                    <br/>
                    <b>Note:</b> If you list a Magento Product and M2E Pro find an Amazon Item with the same
                    <i>Merchant SKU</i> in Amazon Inventory, they will be Mapped.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField(
            'sku_modification_mode',
            self::SELECT,
            [
                'label' => $this->__('Modification'),
                'name' => 'sku_modification_mode',
                'values' => [
                    \Ess\M2ePro\Model\Amazon\Listing::SKU_MODIFICATION_MODE_NONE => $this->__('None'),
                    \Ess\M2ePro\Model\Amazon\Listing::SKU_MODIFICATION_MODE_PREFIX => $this->__('Prefix'),
                    \Ess\M2ePro\Model\Amazon\Listing::SKU_MODIFICATION_MODE_POSTFIX => $this->__('Postfix'),
                    \Ess\M2ePro\Model\Amazon\Listing::SKU_MODIFICATION_MODE_TEMPLATE => $this->__('Template'),
                ],
                'value' => $formData['sku_modification_mode'],
                'tooltip' => $this->__(
                    'Select one of the available variants to modify Amazon Item SKU
                    that was formed based on the Source you provided.'
                )
            ]
        );

        $fieldStyle = '';
        if ($formData['sku_modification_mode'] == \Ess\M2ePro\Model\Amazon\Listing::SKU_MODIFICATION_MODE_NONE) {
            $fieldStyle = 'style="display: none"';
        }

        $fieldset->addField(
            'sku_modification_custom_value',
            'text',
            [
                'container_id' => 'sku_modification_custom_value_tr',
                'label' => $this->__('Modification Value'),
                'field_extra_attributes' => $fieldStyle,
                'name' => 'sku_modification_custom_value',
                'value' => $formData['sku_modification_custom_value'],
                'class' => 'M2ePro-validate-sku-modification-custom-value
                            M2ePro-validate-sku-modification-custom-value-max-length',
                'required' => true
            ]
        );

        $fieldset->addField(
            'generate_sku_mode',
            self::SELECT,
            [
                'label' => $this->__('Generate'),
                'name' => 'generate_sku_mode',
                'values' => [
                    \Ess\M2ePro\Model\Amazon\Listing::GENERATE_SKU_MODE_NO => $this->__('No'),
                    \Ess\M2ePro\Model\Amazon\Listing::GENERATE_SKU_MODE_YES => $this->__('Yes')
                ],
                'value' => $formData['generate_sku_mode'],
                'tooltip' => $this->__(
                    'If <strong>Yes</strong>, then if Merchant SKU of the Amazon Item you list is found in the
                    3rd Party Listings,
                    M2E Pro Listings or among the Amazon Items that are currently in process of Listing,
                    another SKU will be automatically created and the Amazon Item will be Listed.  <br/><br/>
                    Has to be set to <strong>Yes</strong> if you are going to use the same
                    Magento Product under different ASIN(s)/ISBN(s)'
                )
            ]
        );

        // Policies
        $fieldset = $form->addFieldset(
            'policies_fieldset',
            [
                'legend' => $this->__('Policies'),
                'collapsable' => true
            ]
        );

        $fieldset->addField('template_selling_format_messages',
            self::CUSTOM_CONTAINER,
            [
                'style' => 'display: block;',
                'css_class' => 'm2epro-fieldset-table no-margin-bottom'
            ]
        );

        $sellingFormatTemplates = $this->getSellingFormatTemplates();
        $style = count($sellingFormatTemplates) === 0 ? 'display: none' : '';

        $templateSellingFormat = $this->elementFactory->create('select', [
            'data' => [
                'html_id' => 'template_selling_format_id',
                'name' => 'template_selling_format_id',
                'style' => 'width: 50%;' . $style,
                'no_span' => true,
                'values' => array_merge([
                    '' => ''
                ], $sellingFormatTemplates),
                'value' => $formData['template_selling_format_id'],
                'required' => true
            ]
        ]);
        $templateSellingFormat->setForm($form);

        $editPolicyTooltip = $this->getTooltipHtml($this->__(
            'You can edit the saved Policy any time you need. However, the changes you make will automatically
            affect all of the Products which are listed using this Policy.'
        ));

        $style = count($sellingFormatTemplates) === 0 ? '' : 'display: none';
        $fieldset->addField(
            'template_selling_format_container',
            self::CUSTOM_CONTAINER,
            [
                'label' => $this->__('Price, Quantity and Format Policy'),
                'style' => 'line-height: 34px; display: initial;',
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
<span style="line-height: 20px;">
    <span id="edit_selling_format_template_link" style="color:#41362f">
        <a href="javascript: void(0);" style="" onclick="AmazonListingSettingsObj.openWindow(
            M2ePro.url.get('editSellingFormatTemplate', {id: $('template_selling_format_id').value, close_on_save: 1})
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
    <a href="javascript: void(0);" onclick="AmazonListingSettingsObj.addNewTemplate(
        M2ePro.url.get(
                'addNewSellingFormatTemplate',
                {close_on_save: 1}),
                AmazonListingSettingsObj.newSellingFormatTemplateCallback
    );">{$this->__('Add New')}</a>
</span>
HTML
            ]
        );

        $synchronizationTemplates = $this->getSynchronizationTemplates();
        $style = count($synchronizationTemplates) === 0 ? 'display: none' : '';

        $templateSynchronization = $this->elementFactory->create('select', [
            'data' => [
                'html_id' => 'template_synchronization_id',
                'name' => 'template_synchronization_id',
                'style' => 'width: 50%;' . $style,
                'no_span' => true,
                'values' => array_merge([
                    '' => ''
                ], $synchronizationTemplates),
                'value' => $formData['template_synchronization_id'],
                'required' => true
            ]
        ]);
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
<span style="line-height: 20px;">
    <span id="edit_synchronization_template_link" style="color:#41362f">
        <a href="javascript: void(0);" onclick="AmazonListingSettingsObj.openWindow(
            M2ePro.url.get(
                    'editSynchronizationTemplate',
                    {id: $('template_synchronization_id').value,
                    close_on_save: 1}
            )
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
    <a href="javascript: void(0);" onclick="AmazonListingSettingsObj.addNewTemplate(
        M2ePro.url.get(
                'addNewSynchronizationTemplate',
                {close_on_save: 1}),
                AmazonListingSettingsObj.newSynchronizationTemplateCallback
    );">{$this->__('Add New')}</a>
</span>
HTML
            ]
        );

        // Condition Settings
        $fieldset = $form->addFieldset(
            'condition_settings_fieldset',
            [
                'legend' => $this->__('Condition Settings'),
                'collapsable' => true
            ]
        );

        $fieldset->addField(
            'condition_custom_attribute',
            'hidden',
            [
                'name' => 'condition_custom_attribute',
                'value' => $formData['condition_custom_attribute']
            ]
        );

        $fieldset->addField(
            'condition_value',
            'hidden',
            [
                'name' => 'condition_value',
                'value' => $formData['condition_value']
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByTypes['text_select'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['condition_mode'] == \Ess\M2ePro\Model\Amazon\Listing::SKU_MODE_CUSTOM_ATTRIBUTE
                && $attribute['code'] == $formData['condition_custom_attribute']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \Ess\M2ePro\Model\Amazon\Listing::SKU_MODE_CUSTOM_ATTRIBUTE,
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
                        'value' => $this->getRecommendedConditionValues()
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
                'tooltip' => $this->__(
                    <<<HTML
                    <p>The Condition settings will be used not only to create new Amazon Products, but
                    also during a Full Revise of the Product on the channel. However, it is not recommended
                    to change the Condition settings of the already existing Amazon Products as the ability to
                    edit this kind of information in Seller Central is not available.</p><br>

                    <p>On the other hand, Amazon MWS API allows changing the Condition of the existing Amazon
                    Product following the list of technical limitations. It is required to provide the Condition
                    value when the Condition Note should be updated.</p><br>

                    <p><strong>For example</strong>, you are listing a New Product on Amazon with the Condition Note
                    ‘totally new’.
                    Then, you are changing the Condition to Used and Condition Note to ‘a bit used’.
                    The modified values will be updated during the next Revise action. As a result, the Condition
                    will be set to Used and the Condition Note will be ‘a bit used’ for the Product on Amazon.</p>
HTML
                )
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
                    \Ess\M2ePro\Model\Amazon\Listing::CONDITION_NOTE_MODE_NONE => $this->__('None'),
                    \Ess\M2ePro\Model\Amazon\Listing::CONDITION_NOTE_MODE_CUSTOM_VALUE => $this->__('Custom Value')
                ],
                'value' => $formData['condition_note_mode'],
                'tooltip' => $this->__('Short Description of Item(s) Condition.')
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByTypes['text_textarea'] as $attribute) {
            $attribute['value'] = $attribute['code'];
            $preparedAttributes[] = $attribute;
        }

        $fieldset->addField(
            'condition_note_value',
            'textarea',
            [
                'container_id' => 'condition_note_value_tr',
                'name'         => 'condition_note_value',
                'label'        => $this->__('Condition Note Value'),
                'style'        => 'width: 70%;',
                'class'        => 'textarea M2ePro-required-when-visible',
                'required'     => true,
                'after_element_html' => $this->createBlock('Magento\Button\MagentoAttribute')->addData([
                    'label' => $this->__('Insert Attribute'),
                    'destination_id' => 'condition_note_value',
                    'magento_attributes' => $preparedAttributes,
                    'class' => 'primary',
                    'style' => 'display: block; margin: 0; float: right;',
                    'select_custom_attributes' => [
                        'allowed_attribute_types' => 'text,textarea',
                        'apply_to_all_attribute_sets' => 0
                    ],
                ])->toHtml(),
                'value' => $this->getData('condition_note_value')
            ]
        );

        // Listing Photos
        $fieldset = $form->addFieldset(
            'magento_block_amazon_listing_add_images',
            [
                'legend' => $this->__('Listing Photos'),
                'collapsable' => true
            ]
        );

        $fieldset->addField(
            'image_main_attribute',
            'hidden',
            [
                'name' => 'image_main_attribute',
                'value' => $formData['image_main_attribute']
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByTypes['text_images'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['image_main_mode'] == \Ess\M2ePro\Model\Amazon\Listing::IMAGE_MAIN_MODE_ATTRIBUTE
                && $attribute['code'] == $formData['image_main_attribute']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \Ess\M2ePro\Model\Amazon\Listing::IMAGE_MAIN_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'image_main_mode',
            self::SELECT,
            [
                'name' => 'image_main_mode',
                'label' => $this->__('Main Image'),
                'required' => true,
                'values' => [
                    \Ess\M2ePro\Model\Amazon\Listing::IMAGE_MAIN_MODE_NONE => $this->__('None'),
                    \Ess\M2ePro\Model\Amazon\Listing::IMAGE_MAIN_MODE_PRODUCT => $this->__('Product Base Image'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => $formData['image_main_mode'] != Listing::IMAGE_MAIN_MODE_ATTRIBUTE
                    ? $formData['image_main_mode'] : '',
                'create_magento_attribute' => true,
                'tooltip' => $this->__(
                    'You have an ability to add Photos for your Items to be displayed on the More Buying Choices Page.
                    <br/>It is available only for Items with Used or Collectible Condition.'
                )
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,textarea,select,multiselect');

        $fieldset->addField(
            'gallery_images_limit',
            'hidden',
            [
                'name' => 'gallery_images_limit',
                'value' => $formData['gallery_images_limit']
            ]
        );

        $fieldset->addField(
            'gallery_images_attribute',
            'hidden',
            [
                'name' => 'gallery_images_attribute',
                'value' => $formData['gallery_images_attribute']
            ]
        );

        $preparedLimitOptions[] = [
            'attrs' => ['attribute_code' => 1],
            'value' => 1,
            'label' => 1,
        ];
        if ($formData['gallery_images_limit'] == 1 &&
            $formData['gallery_images_mode'] != \Ess\M2ePro\Model\Amazon\Listing::GALLERY_IMAGES_MODE_NONE) {
            $preparedLimitOptions[0]['attrs']['selected'] = 'selected';
        }

        for ($i = 2; $i <= \Ess\M2ePro\Model\Amazon\Listing::GALLERY_IMAGES_COUNT_MAX; $i++) {
            $option = [
                'attrs' => ['attribute_code' => $i],
                'value' => \Ess\M2ePro\Model\Amazon\Listing::GALLERY_IMAGES_MODE_PRODUCT,
                'label' => $this->__('Up to').' '.$i,
            ];

            if ($formData['gallery_images_limit'] == $i) {
                $option['attrs']['selected'] = 'selected';
            }

            $preparedLimitOptions[] = $option;
        }

        $preparedAttributes = [];
        foreach ($attributesByTypes['text_images'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['gallery_images_mode'] == \Ess\M2ePro\Model\Amazon\Listing::GALLERY_IMAGES_MODE_ATTRIBUTE
                && $attribute['code'] == $formData['gallery_images_attribute']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \Ess\M2ePro\Model\Amazon\Listing::GALLERY_IMAGES_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldConfig = [
            'container_id' => 'gallery_images_mode_tr',
            'name' => 'gallery_images_mode',
            'label' => $this->__('Additional Images'),
            'values' => [
                \Ess\M2ePro\Model\Amazon\Listing::GALLERY_IMAGES_MODE_NONE => $this->__('None'),
                [
                    'label' => $this->__('Product Images Quantity'),
                    'value' => $preparedLimitOptions
                ],
                [
                    'label' => $this->__('Magento Attribute'),
                    'value' => $preparedAttributes,
                    'attrs' => [
                        'is_magento_attribute' => true
                    ]
                ]
            ],
            'create_magento_attribute' => true,
        ];

        if ($formData['gallery_images_mode'] == \Ess\M2ePro\Model\Amazon\Listing::GALLERY_IMAGES_MODE_NONE) {
            $fieldConfig['value'] = $formData['gallery_images_mode'];
        }

        $fieldset->addField(
            'gallery_images_mode',
            self::SELECT,
            $fieldConfig
        )->addCustomAttribute('allowed_attribute_types', 'text,textarea,select,multiselect');

        // Gift Wrap
        $fieldset = $form->addFieldset(
            'magento_block_amazon_listing_gift_settings',
            [
                'legend' => $this->__('Gift Settings'),
                'collapsable' => true
            ]
        );

        $fieldset->addField(
            'gift_wrap_attribute',
            'hidden',
            [
                'name' => 'gift_wrap_attribute',
                'value' => $formData['gift_wrap_attribute']
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByTypes['boolean'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['gift_wrap_mode'] == \Ess\M2ePro\Model\Amazon\Listing::GIFT_WRAP_MODE_ATTRIBUTE
                && $attribute['code'] == $formData['gift_wrap_attribute']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \Ess\M2ePro\Model\Amazon\Listing::GIFT_WRAP_MODE_ATTRIBUTE,
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
                    \Ess\M2ePro\Model\Amazon\Listing::GIFT_WRAP_MODE_NO => $this->__('No'),
                    \Ess\M2ePro\Model\Amazon\Listing::GIFT_WRAP_MODE_YES => $this->__('Yes'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                            'new_option_value' => \Ess\M2ePro\Model\Amazon\Listing::GIFT_WRAP_MODE_ATTRIBUTE
                        ]
                    ]
                ],
                'value' => $formData['gift_wrap_mode'] != Listing::GIFT_WRAP_MODE_ATTRIBUTE
                    ? $formData['gift_wrap_mode'] : '',
                'create_magento_attribute' => true,
                'tooltip' => $this->__(
                    'Enable this Option in case you want Gift Wrapped Option be applied to the
                    Products you are going to sell.'
                )
            ]
        )->addCustomAttribute('allowed_attribute_types', 'boolean');

        $fieldset->addField(
            'gift_message_attribute',
            'hidden',
            [
                'name' => 'gift_message_attribute',
                'value' => $formData['gift_message_attribute']
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByTypes['boolean'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['gift_message_mode'] == \Ess\M2ePro\Model\Amazon\Listing::GIFT_MESSAGE_MODE_ATTRIBUTE
                && $attribute['code'] == $formData['gift_message_attribute']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \Ess\M2ePro\Model\Amazon\Listing::GIFT_MESSAGE_MODE_ATTRIBUTE,
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
                    \Ess\M2ePro\Model\Amazon\Listing::GIFT_MESSAGE_MODE_NO => $this->__('No'),
                    \Ess\M2ePro\Model\Amazon\Listing::GIFT_MESSAGE_MODE_YES => $this->__('Yes'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                            'new_option_value' => \Ess\M2ePro\Model\Amazon\Listing::GIFT_MESSAGE_MODE_ATTRIBUTE
                        ]
                    ]
                ],
                'value' => $formData['gift_message_mode'] != Listing::GIFT_MESSAGE_MODE_ATTRIBUTE
                    ? $formData['gift_message_mode'] : '',
                'create_magento_attribute' => true,
                'tooltip' => $this->__(
                    'Enable this Option in case you want Gift Message Option be applied to the
                    Products you are going to sell.'
                )
            ]
        )->addCustomAttribute('allowed_attribute_types', 'boolean');

        // Gift Wrap
        $fieldset = $form->addFieldset(
            'magento_block_amazon_listing_add_additional',
            [
                'legend' => $this->__('Additional Settings'),
                'collapsable' => true
            ]
        );

        $fieldset->addField(
            'handling_time_custom_attribute',
            'hidden',
            [
                'name' => 'handling_time_custom_attribute',
                'value' => $formData['handling_time_custom_attribute']
            ]
        );

        $fieldset->addField(
            'handling_time_value',
            'hidden',
            [
                'name' => 'handling_time_value',
                'value' => $formData['handling_time_value']
            ]
        );

        $recommendedValuesOptions = [];
        for ($i = 1; $i <= 30; $i++) {
            $option = [
                'attrs' => ['attribute_code' => $i],
                'value' => \Ess\M2ePro\Model\Amazon\Listing::HANDLING_TIME_MODE_RECOMMENDED,
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
                $formData['handling_time_mode'] == \Ess\M2ePro\Model\Amazon\Listing::HANDLING_TIME_MODE_CUSTOM_ATTRIBUTE
                && $attribute['code'] == $formData['handling_time_custom_attribute']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \Ess\M2ePro\Model\Amazon\Listing::HANDLING_TIME_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldConfig = [
            'name' => 'handling_time_mode',
            'label' => $this->__('Production Time'),
            'values' => [
                \Ess\M2ePro\Model\Amazon\Listing::HANDLING_TIME_MODE_NONE => $this->__('None'),
                [
                    'label' => $this->__('Recommended Value'),
                    'value' => $recommendedValuesOptions
                ],
                [
                    'label' => $this->__('Magento Attribute'),
                    'value' => $preparedAttributes,
                    'attrs' => [
                        'is_magento_attribute' => true
                    ]
                ]
            ],
            'create_magento_attribute' => true,
            'tooltip' => $this->__('Time that is needed to prepare an Item to be shipped.')
        ];

        if ($formData['handling_time_mode'] == \Ess\M2ePro\Model\Amazon\Listing::HANDLING_TIME_MODE_NONE) {
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
                'value' => $formData['restock_date_custom_attribute']
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByTypes['text_date'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['restock_date_mode'] == \Ess\M2ePro\Model\Amazon\Listing::RESTOCK_DATE_MODE_CUSTOM_ATTRIBUTE
                && $attribute['code'] == $formData['restock_date_custom_attribute']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \Ess\M2ePro\Model\Amazon\Listing::RESTOCK_DATE_MODE_CUSTOM_ATTRIBUTE,
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
                    \Ess\M2ePro\Model\Amazon\Listing::RESTOCK_DATE_MODE_NONE => $this->__('None'),
                    \Ess\M2ePro\Model\Amazon\Listing::RESTOCK_DATE_MODE_CUSTOM_VALUE => $this->__('Custom Value'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'create_magento_attribute' => true,
                'value' => $formData['restock_date_mode'] != Listing::RESTOCK_DATE_MODE_CUSTOM_ATTRIBUTE
                    ? $formData['restock_date_mode'] : '',
                'tooltip' => $this->__(
                    'The date you will be able to ship any back-ordered Items to a Customer.'
                )
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
                'date_format' => $this->_localeDate->getDateFormatWithLongYear(),
                'time_format' => $this->_localeDate->getTimeFormat(\IntlDateFormatter::SHORT),
                'value' => $this->_localeDate->formatDate(
                    $formData['restock_date_value'],
                    \IntlDateFormatter::SHORT,
                    true
                )
            ]
        );

        $form->setUseContainer($this->useFormContainer);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################

    private function getRecommendedConditionValues()
    {
        $formData = $this->getListingData();

        $recommendedValues = [[
            'attrs' => ['attribute_code' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_NEW],
            'value' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_MODE_DEFAULT,
            'label' => $this->__('New'),
        ],[
            'attrs' => ['attribute_code' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_USED_LIKE_NEW],
            'value' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_MODE_DEFAULT,
            'label' => $this->__('Used - Like New'),
        ],[
            'attrs' => ['attribute_code' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_USED_VERY_GOOD],
            'value' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_MODE_DEFAULT,
            'label' => $this->__('Used - Very Good'),
        ],[
            'attrs' => ['attribute_code' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_USED_GOOD],
            'value' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_MODE_DEFAULT,
            'label' => $this->__('Used - Good'),
        ],[
            'attrs' => ['attribute_code' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_USED_ACCEPTABLE],
            'value' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_MODE_DEFAULT,
            'label' => $this->__('Used - Acceptable'),
        ],[
            'attrs' => ['attribute_code' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_COLLECTIBLE_LIKE_NEW],
            'value' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_MODE_DEFAULT,
            'label' => $this->__('Collectible - Like New'),
        ],[
            'attrs' => ['attribute_code' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_COLLECTIBLE_VERY_GOOD],
            'value' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_MODE_DEFAULT,
            'label' => $this->__('Collectible - Very Good'),
        ],[
            'attrs' => ['attribute_code' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_COLLECTIBLE_GOOD],
            'value' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_MODE_DEFAULT,
            'label' => $this->__('Collectible - Good'),
        ],[
            'attrs' => ['attribute_code' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_COLLECTIBLE_ACCEPTABLE],
            'value' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_MODE_DEFAULT,
            'label' => $this->__('Collectible - Acceptable'),
        ],[
            'attrs' => ['attribute_code' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_REFURBISHED],
            'value' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_MODE_DEFAULT,
            'label' => $this->__('Refurbished'),
        ],[
            'attrs' => ['attribute_code' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_CLUB],
            'value' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_MODE_DEFAULT,
            'label' => $this->__('Club'),
        ]];

        foreach ($recommendedValues as &$value) {
            if ($value['attrs']['attribute_code'] == $formData['condition_value']) {
                $value['attrs']['selected'] = 'selected';
            }
        }

        return $recommendedValues;
    }

    //########################################

    protected function _toHtml()
    {
        $this->jsTranslator->addTranslations([
            'condition_note_length_error' => $this->__('Must be not more than 2000 characters long.'),
            'sku_modification_custom_value_error' => $this->__('%value% placeholder should be specified'),
            'sku_modification_custom_value_max_length_error' => $this->__('The SKU length must be less than %value%.',
                \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\Sku\General::SKU_MAX_LENGTH
            )
        ]);

        $this->jsUrl->add($this->getUrl('*/template/checkMessages',
            ['component_mode' => \Ess\M2ePro\Helper\Component\Amazon::NICK]), 'templateCheckMessages');
        $this->jsUrl->add($this->getUrl('*/amazon_template_sellingFormat/new',
            ['wizard' => $this->getRequest()->getParam('wizard')]), 'addNewSellingFormatTemplate');
        $this->jsUrl->add($this->getUrl('*/amazon_template_synchronization/new',
            ['wizard' => $this->getRequest()->getParam('wizard')]), 'addNewSynchronizationTemplate');
        $this->jsUrl->add($this->getUrl('*/amazon_template_sellingFormat/edit',
            ['wizard' => $this->getRequest()->getParam('wizard')]), 'editSellingFormatTemplate');
        $this->jsUrl->add($this->getUrl('*/amazon_template_synchronization/edit',
            ['wizard' => $this->getRequest()->getParam('wizard')]), 'editSynchronizationTemplate');
        $this->jsUrl->add($this->getUrl('*/general/modelGetAll', [
            'model'=>'Template_SellingFormat',
            'id_field'=>'id',
            'data_field'=>'title',
            'sort_field'=>'title',
            'sort_dir'=>'ASC',
            'component_mode' => \Ess\M2ePro\Helper\Component\Amazon::NICK
        ]), 'getSellingFormatTemplates');
        $this->jsUrl->add($this->getUrl('*/general/modelGetAll', [
            'model'=>'Template_Synchronization',
            'id_field'=>'id',
            'data_field'=>'title',
            'sort_field'=>'title',
            'sort_dir'=>'ASC',
            'component_mode' => \Ess\M2ePro\Helper\Component\Amazon::NICK
        ]), 'getSynchronizationTemplates');

        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Helper\Component\Amazon'));
        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Listing'));
        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Amazon\Listing'));
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(
                '\Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\Sku\General'
            )
        );

        $listingData = $this->getListingData();

        $marketplaceId = null;
        $storeId = null;

        if (isset($listingData['marketplace_id'])) {
            $marketplaceId = (int)$listingData['marketplace_id'];
        } else if (isset($listingData['account_id'])) {
            $accountObj =$this->amazonFactory->getCachedObjectLoaded('Account', (int)$listingData['account_id']);
            $marketplaceId = (int)$accountObj->getChildObject()->getMarketplaceId();
        }

        if (isset($listingData['store_id'])) {
            $storeId = (int)$listingData['store_id'];
        }

        $this->js->add(<<<JS
require([
    'M2ePro/TemplateHandler',
    'M2ePro/Amazon/Listing/Settings',
    'M2ePro/Amazon/Listing/Create/Selling',
    'M2ePro/Plugin/Magento/Attribute/Button'
], function(){

    window.TemplateHandlerObj = new TemplateHandler();

    window.AmazonListingSettingsObj = new AmazonListingSettings();
    window.AmazonListingSettingsObj.storeId = '{$storeId}';
    window.AmazonListingSettingsObj.marketplaceId = '{$marketplaceId}';

    window.AmazonListingCreateSellingObj = new AmazonListingCreateSelling();
    window.MagentoAttributeButtonObj = new MagentoAttributeButton();

    $('template_selling_format_id').observe('change', function() {
        if ($('template_selling_format_id').value) {
            $('edit_selling_format_template_link').show();
        } else {
            $('edit_selling_format_template_link').hide();
        }
    });
    $('template_selling_format_id').simulate('change');

    $('template_synchronization_id').observe('change', function() {
        if ($('template_synchronization_id').value) {
            $('edit_synchronization_template_link').show();
        } else {
            $('edit_synchronization_template_link').hide();
        }
    });
    $('template_synchronization_id').simulate('change');

    $('template_selling_format_id').observe('change', AmazonListingSettingsObj.selling_format_template_id_change)
    if ($('template_selling_format_id').value) {
        $('template_selling_format_id').simulate('change');
    }

    $('template_synchronization_id').observe('change', AmazonListingSettingsObj.synchronization_template_id_change)
    if ($('template_synchronization_id').value) {
        $('template_synchronization_id').simulate('change');
    }

    $('sku_mode').observe('change', AmazonListingCreateSellingObj.sku_mode_change);

    $('sku_modification_mode')
        .observe('change', AmazonListingCreateSellingObj.sku_modification_mode_change);

    $('condition_mode').observe('change', AmazonListingCreateSellingObj.condition_mode_change)
        .simulate('change');

    $('condition_note_mode').observe('change', AmazonListingCreateSellingObj.condition_note_mode_change);

    $('image_main_mode')
        .observe('change', AmazonListingCreateSellingObj.image_main_mode_change)
        .simulate('change');

    $('gallery_images_mode')
        .observe('change', AmazonListingCreateSellingObj.gallery_images_mode_change)
        .simulate('change');

    $('gift_wrap_mode')
        .observe('change', AmazonListingCreateSellingObj.gift_wrap_mode_change)
        .simulate('change');

    $('gift_message_mode')
        .observe('change', AmazonListingCreateSellingObj.gift_message_mode_change)
        .simulate('change');

    $('handling_time_mode')
        .observe('change', AmazonListingCreateSellingObj.handling_time_mode_change)
        .simulate('change');

    $('restock_date_mode')
        .observe('change', AmazonListingCreateSellingObj.restock_date_mode_change)
        .simulate('change');
});
JS
);

        return parent::_toHtml();
    }

    //########################################

    public function getDefaultFieldsValues()
    {
        return array(
            'sku_mode' => \Ess\M2ePro\Model\Amazon\Listing::SKU_MODE_DEFAULT,
            'sku_custom_attribute' => '',
            'sku_modification_mode' => \Ess\M2ePro\Model\Amazon\Listing::SKU_MODIFICATION_MODE_NONE,
            'sku_modification_custom_value' => '',
            'generate_sku_mode' => \Ess\M2ePro\Model\Amazon\Listing::GENERATE_SKU_MODE_NO,

            'template_selling_format_id' => '',
            'template_synchronization_id' => '',

            'condition_mode' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_MODE_DEFAULT,
            'condition_value' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_NEW,
            'condition_custom_attribute' => '',
            'condition_note_mode' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_NOTE_MODE_NONE,
            'condition_note_value' => '',

            'image_main_mode' => \Ess\M2ePro\Model\Amazon\Listing::IMAGE_MAIN_MODE_NONE,
            'image_main_attribute' => '',
            'gallery_images_mode' => \Ess\M2ePro\Model\Amazon\Listing::GALLERY_IMAGES_MODE_NONE,
            'gallery_images_limit' => '',
            'gallery_images_attribute' => '',

            'gift_wrap_mode' => \Ess\M2ePro\Model\Amazon\Listing::GIFT_WRAP_MODE_NO,
            'gift_wrap_attribute' => '',

            'gift_message_mode' => \Ess\M2ePro\Model\Amazon\Listing::GIFT_MESSAGE_MODE_NO,
            'gift_message_attribute' => '',

            'handling_time_mode' => \Ess\M2ePro\Model\Amazon\Listing::HANDLING_TIME_MODE_NONE,
            'handling_time_value' => '',
            'handling_time_custom_attribute' => '',

            'restock_date_mode' => \Ess\M2ePro\Model\Amazon\Listing::RESTOCK_DATE_MODE_NONE,
            'restock_date_value' => $this->getHelper('Data')->getCurrentTimezoneDate(),
            'restock_date_custom_attribute' => ''
        );
    }

    //########################################

    protected function _beforeToHtml()
    {
        // ---------------------------------------
        $data = $this->getListingData();

        $this->setData(
            'general_attributes',
            $this->getHelper('Magento\Attribute')->getGeneralFromAllAttributeSets()
        );

        $this->setData(
            'all_attributes',
            $this->getHelper('Magento\Attribute')->getAll()
        );

        foreach ($data as $key=>$value) {
            $this->setData($key, $value);
        }
        // ---------------------------------------

        return parent::_beforeToHtml();
    }

    protected function getSellingFormatTemplates()
    {
        $collection = $this->amazonFactory->getObject('Template\SellingFormat')->getCollection();
        $collection->setOrder('title', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS)->columns([
            'value' => 'id',
            'label' => 'title'
        ]);

        return $collection->getConnection()->fetchAssoc($collection->getSelect());
    }

    protected function getSynchronizationTemplates()
    {
        $collection = $this->amazonFactory->getObject('Template\Synchronization')->getCollection();
        $collection->setOrder('title', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS)->columns([
            'value' => 'id',
            'label' => 'title'
        ]);

        return $collection->getConnection()->fetchAssoc($collection->getSelect());
    }

    //########################################

    protected function getListingData()
    {
        if (!is_null($this->getRequest()->getParam('id'))) {
            $data = array_merge($this->getListing()->getData(), $this->getListing()->getChildObject()->getData());
        } else {
            $data = $this->getHelper('Data\Session')->getValue($this->sessionKey);
            $data = array_merge($this->getDefaultFieldsValues(), $data);
        }

        return $data;
    }

    //########################################

    protected function getListing()
    {
        if (!$listingId = $this->getRequest()->getParam('id')) {
            throw new \Ess\M2ePro\Model\Exception('Listing is not defined');
        }

        if (is_null($this->listing)) {
            $this->listing = $this->amazonFactory->getCachedObjectLoaded('Listing', $listingId);
        }

        return $this->listing;
    }

    //########################################

    /**
     * @param boolean $useFormContainer
     */
    public function setUseFormContainer($useFormContainer)
    {
        $this->useFormContainer = $useFormContainer;
    }

    //########################################
}