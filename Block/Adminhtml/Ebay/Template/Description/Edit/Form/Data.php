<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Description\Edit\Form;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Ebay\Template\Description;

class Data extends AbstractForm
{
    protected function _prepareForm()
    {
        /** @var \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper */
        $magentoAttributeHelper = $this->getHelper('Magento\Attribute');
        
        $attributeSets = $this->getHelper('Data\GlobalData')->getValue('ebay_attribute_sets');
        $attributes = $magentoAttributeHelper->getAll();
        $generalAttributes = $magentoAttributeHelper->getGeneralFromAllAttributeSets();
        $attributesConfigurable = $magentoAttributeHelper->getAllConfigurable();

        $M2eProAttributes = array(
            'title' => $this->__('Title'),
            'subtitle' => $this->__('Subtitle'),
            'condition' => $this->__('Condition'),
            'condition_description' => $this->__('Seller Notes'),
            'fixed_price' => $this->__('Fixed Price'),
            'start_price' => $this->__('Start Price'),
            'reserve_price' => $this->__('Reserve Price'),
            'buyitnow_price' => $this->__('Buy It Now Price'),
            'qty' => $this->__('QTY'),
            'listing_type' => $this->__('Listing Type'),
            'listing_duration' => $this->__('Listing Duration'),
            'handling_time' => $this->__('Dispatch Time'),
            'primary_category_id' => $this->__('Primary Category Id'),
            'secondary_category_id' => $this->__('Secondary Category Id'),
            'store_primary_category_id' => $this->__('Store Primary Category Id'),
            'store_secondary_category_id' => $this->__('Store Secondary Category Id'),
            'primary_category_name' => $this->__('Primary Category Name'),
            'secondary_category_name' => $this->__('Secondary Category Name'),
            'store_primary_category_name' => $this->__('Store Primary Category Name'),
            'store_secondary_category_name' => $this->__('Store Secondary Category Name'),
            'domestic_shipping_method[1]' => $this->__('Domestic Shipping First Method'),
            'domestic_shipping_cost[1]' => $this->__('Domestic Shipping First Cost'),
            'domestic_shipping_additional_cost[1]' =>
                $this->__('Domestic Shipping First Additional Cost'),
            'international_shipping_method[1]' => $this->__('International Shipping First Method'),
            'international_shipping_cost[1]' => $this->__('International Shipping First Cost'),
            'international_shipping_additional_cost[1]' =>
                $this->__('International Shipping First Additional Cost'),
        );
        
        $attributesByInputTypes = array(
            'text' => $magentoAttributeHelper->filterByInputTypes($attributes, array('text')),
            'text_select' => $magentoAttributeHelper->filterByInputTypes($attributes, array('text', 'select')),
        );

        $generalAttributesByTypes = array(
            'text' => $magentoAttributeHelper->filterByInputTypes($generalAttributes, array('text')),
            'text_select' => $magentoAttributeHelper->filterByInputTypes($generalAttributes, array('text', 'select')),
            'text_images' => $magentoAttributeHelper->filterByInputTypes(
                $generalAttributes,
                array('text', 'image', 'media_image', 'gallery', 'multiline', 'textarea', 'select', 'multiselect')
            ),
        );

        $formData = $this->getFormData();

        $default = $this->getDefault();
        $formData = $this->getHelper('Data')->arrayReplaceRecursive($default, $formData);

        $tinymceEnabledForJavascript = $formData['editor_type'] == Description::EDITOR_TYPE_TINYMCE ? 'true' : 'false';

        $isCustomDescription = ($formData['description_mode'] == Description::DESCRIPTION_MODE_CUSTOM);

        $form = $this->_formFactory->create();

        $form->addField('description_id',
            'hidden',
            [
                'name' => 'description[id]',
                'value' => (!$this->isCustom() && isset($formData['id'])) ? (int)$formData['id'] : ''
            ]
        );

        $form->addField('description_title',
            'hidden',
            [
                'name' => 'description[title]',
                'value' => $this->getHelper('Data')->escapeHtml($this->getTitle())
            ]
        );

        $form->addField('description_is_custom_template',
            'hidden',
            [
                'name' => 'description[is_custom_template]',
                'value' => $this->isCustom() ? 1 : 0
            ]
        );

        $form->addField('description_editor_type',
            'hidden',
            [
                'name' => 'description[editor_type]',
                'value' => $formData['editor_type']
            ]
        );

        $fieldset = $form->addFieldset('magento_block_ebay_template_description_form_data_condition',
            [
                'legend' => $this->__('Condition'),
                'collapsable' => false
            ]
        );

        $conditions = [
            [
                'label' => $this->__('New, New with tags, New with box, Brand New'),
                'attrs' => ['attribute_code' => Description::CONDITION_EBAY_NEW]
            ],
            [
                'label' => $this->__('New Other, New without tags, New without box'),
                'attrs' => ['attribute_code' => Description::CONDITION_EBAY_NEW_OTHER]
            ],
            [
                'label' => $this->__('New With Defects'),
                'attrs' => ['attribute_code' => Description::CONDITION_EBAY_NEW_WITH_DEFECT]
            ],
            [
                'label' => $this->__('Manufacturer Refurbished'),
                'attrs' => ['attribute_code' => Description::CONDITION_EBAY_MANUFACTURER_REFURBISHED]
            ],
            [
                'label' => $this->__('Seller Refurbished, Re-manufactured'),
                'attrs' => ['attribute_code' => Description::CONDITION_EBAY_SELLER_REFURBISHED]
            ],
            [
                'label' => $this->__('Used, Pre-owned, Like new'),
                'attrs' => ['attribute_code' => Description::CONDITION_EBAY_USED]
            ],
            [
                'label' => $this->__('Very Good'),
                'attrs' => ['attribute_code' => Description::CONDITION_EBAY_VERY_GOOD]
            ],
            ['label' => $this->__('Good'), 'attrs' => ['attribute_code' => Description::CONDITION_EBAY_GOOD]],
            [
                'label' => $this->__('Acceptable'),
                'attrs' => ['attribute_code' => Description::CONDITION_EBAY_ACCEPTABLE]
            ],
            [
                'label' => $this->__('For Parts or Not Working'),
                'attrs' => ['attribute_code' => Description::CONDITION_EBAY_NOT_WORKING]
            ],
        ];

        $preparedConditions = [];
        foreach ($conditions as $condition) {
            if (
                $formData['condition_mode'] == Description::CONDITION_MODE_EBAY
                && $formData['condition_value'] == $condition['attrs']['attribute_code']
            ) {
                $condition['attrs']['selected'] = 'selected';
            }
            $condition['value'] = Description::CONDITION_MODE_EBAY;
            $preparedConditions[] = $condition;
        }

        $preparedAttributes = [];
        foreach ($generalAttributesByTypes['text_select'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['condition_mode'] == Description::CONDITION_MODE_ATTRIBUTE
                && $formData['condition_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => Description::CONDITION_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField('item_condition',
            self::SELECT,
            [
                'name' => 'description[condition_mode]',
                'label' => $this->__('Condition'),
                'class' => 'M2ePro-custom-attribute-can-be-created',
                'values' => [
                    Description::CONDITION_MODE_NONE => $this->__('None'),
                    [
                        'label' => $this->__('eBay Conditions'),
                        'value' => $preparedConditions,
                    ],
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'class' => 'duration_attribute M2ePro-custom-attribute-optgroup'
                        ]
                    ]
                ],
                'value' => $formData['condition_mode'] == Description::CONDITION_MODE_NONE
                    ? $formData['condition_mode'] : -1,
                'tooltip' => $this->__('Condition is one of the top factors Buyers consider when
                                deciding whether to purchase an Item.
                                When you create your Listing, let your Buyers know exactly what they\'ll
                                be receiving by specifying its Condition
                                Some Conditions, such as \'new\', are grouped as single selections
                                because they appear on eBay under slightly different names depending on the Category
                                of the Item.
                                <br/><br/>If you choose to use a Magento Attribute to set the Condition per Product,
                                the Attribute Value must be an Item Condition ID. See
                                <a target="_blank"
                                   href="http://developer.ebay.com/devzone/finding/callref/Enums/conditionIdList.html">
                                   the eBay API reference: Item Condition IDs and Names</a> for more details.')
            ]
        );

        $fieldset->addField('condition_value',
            'hidden',
            [
                'name' => 'description[condition_value]',
                'value' => $formData['condition_value']
            ]
        );

        $fieldset->addField('condition_attribute',
            'hidden',
            [
                'name' => 'description[condition_attribute]',
                'value' => $formData['condition_attribute']
            ]
        );

        $fieldset->addField('condition_note_mode',
            'select',
            [
                'container_id' => 'condition_note_tr',
                'label' => $this->__('Seller Notes'),
                'name' => 'description[condition_note_mode]',
                'values' => [
                    Description::CONDITION_NOTE_MODE_NONE => $this->__('None'),
                    Description::CONDITION_NOTE_MODE_CUSTOM => $this->__('Custom Value'),
                ],
                'value' => $formData['condition_note_mode'],
                'tooltip' => $this->__(
                    'If Item is not new, you can provide additional details about the Item\'s Condition,
                    such as whether it has defects, missing parts, scratches, or other wear and tear.
                    You have up to 1000 characters.'
                )
            ]
        );

        $preparedAttributes = [];
        foreach ($attributes as $attribute) {
            $preparedAttributes[] = [
                'value' => $attribute['code'],
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField('condition_note_template',
            'textarea',
            [
                'container_id' => 'custom_condition_note_tr',
                'label' => $this->__('Seller Notes Value'),
                'value' => $formData['condition_note_template'],
                'name' => 'description[condition_note_template]',
                'style' => 'width: 70%;margin-top: 0px;margin-bottom: 0px;height: 101px;',
                'class' => 'M2ePro-validate-condition-note-length',
                'required' => true,
                'after_element_html' => $this->createBlock('Magento\Button\MagentoAttribute')->addData([
                    'label' => $this->__('Insert Attribute'),
                    'destination_id' => 'condition_note_template',
                    'magento_attributes' => $preparedAttributes,
                    'class' => 'select_attributes_for_title_button primary',
                    'style' => 'margin-left: 0px; float: right;'
                ])->toHtml()
            ]
        );

        $fieldset = $form->addFieldset('magento_block_ebay_template_description_form_data_image',
            [
                'legend' => $this->__('Images'),
                'collapsable' => true,
                'tooltip' => $this->__(
                    'The images that your potential Buyers will see on your eBay Listing. Using high-quality 
                    pictures ensures a better shopping experience and improves your chances of a sale.'
                )
            ]
        );

        $preparedAttributes = [];
        foreach ($generalAttributesByTypes['text_images'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['image_main_mode'] == Description::IMAGE_MAIN_MODE_ATTRIBUTE
                && $formData['image_main_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => Description::IMAGE_MAIN_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField('image_main',
            self::SELECT,
            [
                'name' => 'description[image_main_mode]',
                'label' => $this->__('Main Image'),
                'class' => 'M2ePro-custom-attribute-can-be-created',
                'values' => [
                    Description::IMAGE_MAIN_MODE_NONE => $this->__('None'),
                    Description::IMAGE_MAIN_MODE_PRODUCT => $this->__('Product Base Image'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'class' => 'M2ePro-custom-attribute-optgroup'
                        ]
                    ]
                ],
                'value' => $formData['image_main_mode'] != Description::IMAGE_MAIN_MODE_ATTRIBUTE
                    ? $formData['image_main_mode'] : '',
                'tooltip' => $this->__(
                    'The first photo appears in the top left of your eBay Item and next to your Item\'s Title
                    in Search results.
                    <br/><b>Note:</b> Media Image Attribute or Attribute should contain absolute url.
                    e.g. http://mymagentostore.com/images/baseimage.jpg'
                )
            ]
        );

        $fieldset->addField('image_main_attribute',
            'hidden',
            [
                'name' => 'description[image_main_attribute]',
                'value' => $formData['image_main_attribute'],
            ]
        );

        $fieldset->addField('default_image_url',
            'text',
            [
                'container_id' => 'default_image_url_tr',
                'label' => 'Default Image Url',
                'name' => 'description[default_image_url]',
                'value' => $formData['default_image_url'],
                'tooltip' => $this->__(
                    'Enter the full URL of the image - eg http://mymagentostore.com/images/defaultimage.jpg -
                    to use for the eBay Listing if there is no image available from the Magento Attribute
                    you specified for <b>Main Image</b>. All eBay Listings must have an image.'
                )
            ]
        );

        $fieldset->addField('use_supersize_images',
            'select',
            [
                'container_id' => 'use_supersize_images_tr',
                'label' => $this->__('Super Size'),
                'name' => 'description[use_supersize_images]',
                'values' => [
                    Description::USE_SUPERSIZE_IMAGES_NO => $this->__('No'),
                    Description::USE_SUPERSIZE_IMAGES_YES => $this->__('Yes'),
                ],
                'value' => $formData['use_supersize_images'],
                'tooltip' => $this->__(
                    '<p>Displays larger images (up to 800w x 800h pixels - 800 pixels on the longest side).</p><br>
                    <p><strong>Note:</strong> An additional fee may be charged for Supersize images. </p>'
                )
            ]
        );

        $fieldset->addField('gallery_images_limit',
            'hidden',
            [
                'name' => 'description[gallery_images_limit]',
                'value' => $formData['gallery_images_limit'],
            ]
        );

        $fieldset->addField('gallery_images_attribute',
            'hidden',
            [
                'name' => 'description[gallery_images_attribute]',
                'value' => $formData['gallery_images_attribute'],
            ]
        );

        $preparedImages = [];
        for ($i = 1; $i <= Description::GALLERY_IMAGES_COUNT_MAX; $i++) {
            $attrs = ['attribute_code' => $i];

            if ($i == $formData['gallery_images_limit']
                && $formData['gallery_images_mode'] == Description::GALLERY_IMAGES_MODE_PRODUCT) {
                $attrs['selected'] = 'selected';
            }

            $preparedImages[] = [
                'value' => Description::GALLERY_IMAGES_MODE_PRODUCT,
                'label' => $i == 1 ? $i : ($this->__('Up to') . " $i"),
                'attrs' => $attrs
            ];
        }

        $preparedAttributes = [];
        foreach ($generalAttributesByTypes['text_images'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];

            if ($formData['gallery_images_mode'] == Description::GALLERY_IMAGES_MODE_ATTRIBUTE
                && $formData['gallery_images_attribute'] == $attribute['code']) {
                $attrs['selected'] = 'selected';
            }

            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => Description::GALLERY_IMAGES_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField('gallery_images',
            self::SELECT,
            [
                'container_id' => 'gallery_images_mode_tr',
                'name' => 'description[gallery_images_mode]',
                'label' => $this->__('Gallery Images'),
                'class' => 'M2ePro-custom-attribute-can-be-created',
                'values' => [
                    Description::GALLERY_IMAGES_MODE_NONE => $this->__('None'),
                    [
                        'label' => $this->__('Product Images'),
                        'value' => $preparedImages,
                    ],
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'class' => 'M2ePro-custom-attribute-optgroup'
                        ]
                    ]
                ],
                'tooltip' => $this->__(
                    'Adds small thumbnails that appear under the large Base Image.
                     You can add up to 11 additional photos to each Listing on eBay.
                        <br/><b>Note:</b> Text, Multiple Select or Dropdown type Attribute can be used.
                        The value of Attribute must contain absolute urls.
                        <br/>In Text type Attribute urls must be separated with comma.
                        <br/>e.g. http://mymagentostore.com/images/baseimage1.jpg,
                        http://mymagentostore.com/images/baseimage2.jpg'
                )
            ]
        );

        if (count($attributesConfigurable) > 0) {
            $preparedAttributes = [];
            foreach ($attributesConfigurable as $attribute) {
                $preparedAttributes[] = [
                    'value' => $attribute['code'],
                    'label' => $attribute['label']
                ];
            }

            $fieldset->addField('variation_configurable_images',
                'multiselect',
                [
                    'container_id' => 'variation_configurable_images_container',
                    'label' => $this->__('Change Images for Attributes'),
                    'name' => 'description[variation_configurable_images][]',
                    'values' => $preparedAttributes,
                    'value' => $formData['variation_configurable_images'],
                    'tooltip' => $this->__('
                    This Option applies only to <strong>Configurable Products</strong>.
                    It allows you to choose all of the relevant Magento Variation Attributes
                    and select the one based on which Images for Multi-Variation Items will change.
                ')
                ]
            );
        }

        $fieldset->addField('variation_images_limit',
            'hidden',
            [
                'name' => 'description[variation_images_limit]',
                'value' => $formData['variation_images_limit'],
            ]
        );

        $fieldset->addField('variation_images_attribute',
            'hidden',
            [
                'name' => 'description[variation_images_attribute]',
                'value' => $formData['variation_images_attribute'],
            ]
        );

        $preparedImages = [];
        $attrs = ['attribute_code' => 1];
        if ($formData['variation_images_limit'] == 1) {
            $attrs['selected'] = 'selected';
        }
        $preparedImages[] = [
            'value' => Description::VARIATION_IMAGES_MODE_PRODUCT,
            'label' => 1,
            'attrs' => $attrs
        ];

        for ($i = 2; $i <= Description\Source::VARIATION_IMAGES_COUNT_MAX; $i++) {
            $attrs = ['attribute_code' => $i];
            if (
                $i == $formData['variation_images_limit']
                && $formData['variation_images_mode'] == Description::VARIATION_IMAGES_MODE_PRODUCT
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedImages[] = [
                'value' => Description::VARIATION_IMAGES_MODE_PRODUCT,
                'label' => $this->__('Up to') . " $i",
                'attrs' => $attrs
            ];
        }

        $preparedAttributes = [];
        foreach ($generalAttributesByTypes['text_images'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['variation_images_mode'] == Description::VARIATION_IMAGES_MODE_ATTRIBUTE
                && $formData['variation_images_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => Description::VARIATION_IMAGES_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField('variation_images',
            self::SELECT,
            [
                'container_id' => 'variation_images_mode_tr',
                'name' => 'description[variation_images_mode]',
                'label' => $this->__('Variation Attribute Images'),
                'class' => 'M2ePro-custom-attribute-can-be-created',
                'values' => [
                    Description::VARIATION_IMAGES_MODE_NONE => $this->__('None'),
                    [
                        'label' => $this->__('Product Images'),
                        'value' => $preparedImages,
                    ],
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'class' => 'M2ePro-custom-attribute-optgroup'
                        ]
                    ]
                ],
                'value' => $formData['variation_images_mode'] != Description::VARIATION_IMAGES_MODE_ATTRIBUTE
                    && $formData['variation_images_mode'] != Description::VARIATION_IMAGES_MODE_PRODUCT
                    ? $formData['variation_images_mode'] : '',
                'tooltip' => $this->__(
                    'Allows to add Images from Product Variations. You can add up to 12 additional photos for each
                    Variation. Please be thoughtful, as the the big number of added Images can decrease the Performance
                    of Item Updating on eBay.<br/><br/>
                    <strong>Note:</strong> Text, Multiple Select or Dropdown type Attribute can be used. The Value
                    of Attribute must contain absolute urls. In Text type Attribute urls must be separated with comma.
                    <br/>
                    <strong>e.g.</strong> http://mymagentostore.com/images/baseimage1.jpg,
                    http://mymagentostore.com/images/baseimage2.jpg
                ')
            ]
        );

        // TODO NOT SUPPORTED FEATURES
        // watermark

        $fieldset = $form->addFieldset('magento_block_ebay_template_description_form_data_details',
            [
                'legend' => $this->__('Details'),
                'collapsable' => true,
            ]
        );

        $fieldset->addField('title_mode',
            'select',
            [
                'label' => $this->__('Title'),
                'name' => 'description[title_mode]',
                'values' => [
                    Description::TITLE_MODE_PRODUCT => $this->__('Product Name'),
                    Description::TITLE_MODE_CUSTOM => $this->__('Custom Value'),
                ],
                'value' => $formData['title_mode'],
                'tooltip' => $this->__(
                    'This is the Title Buyers will see on eBay. A good Title means more Buyers viewing
                    your Listing and your Item selling for the best Price.'
                )
            ]
        );

        $preparedAttributes = [];
        foreach ($attributes as $attribute) {
            $preparedAttributes[] = [
                'value' => $attribute['code'],
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField('title_template',
            'text',
            [
                'container_id' => 'custom_title_tr',
                'label' => $this->__('Title Value'),
                'value' => $formData['title_template'],
                'name' => 'description[title_template]',
                'class' => 'input-text-title',
                'required' => true,
                'after_element_html' => $this->createBlock('Magento\Button\MagentoAttribute')->addData([
                    'label' => $this->__('Insert Attribute'),
                    'destination_id' => 'title_template',
                    'magento_attributes' => $preparedAttributes,
                    'class' => 'select_attributes_for_title_button primary'
                ])->toHtml()
            ]
        );

        $fieldset->addField('subtitle_mode',
            'select',
            [
                'label' => $this->__('Subtitle'),
                'name' => 'description[subtitle_mode]',
                'values' => [
                    Description::SUBTITLE_MODE_NONE => $this->__('None'),
                    Description::SUBTITLE_MODE_CUSTOM => $this->__('Custom Value'),
                ],
                'value' => $formData['subtitle_mode'],
                'tooltip' => $this->__(
                    'Choose if you want to add a Subtitle to your eBay Listing.
                    Adding a Subtitle to your Listing can increase Buyer interest by providing more descriptive
                    information about an Item.<br/><br/>
                    <b>Note:</b> An additional fee may be charged for an Item Subtitle.'
                )
            ]
        );

        $preparedAttributes = [];
        foreach ($attributes as $attribute) {
            $preparedAttributes[] = [
                'value' => $attribute['code'],
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField('subtitle_template',
            'text',
            [
                'container_id' => 'custom_subtitle_tr',
                'label' => $this->__('Subtitle Value'),
                'value' => $formData['subtitle_template'],
                'name' => 'description[subtitle_template]',
                'class' => 'input-text-title',
                'required' => true,
                'after_element_html' => $this->createBlock('Magento\Button\MagentoAttribute')->addData([
                    'label' => $this->__('Insert Attribute'),
                    'destination_id' => 'subtitle_template',
                    'magento_attributes' => $preparedAttributes,
                    'class' => 'select_attributes_for_title_button primary'
                ])->toHtml()
            ]
        );

        $fieldset->addField('cut_long_titles',
            'select',
            [
                'label' => $this->__('Cut Long Titles'),
                'name' => 'description[cut_long_titles]',
                'values' => [
                    Description::CUT_LONG_TITLE_DISABLED => $this->__('No'),
                    Description::CUT_LONG_TITLE_ENABLED => $this->__('Yes'),
                ],
                'value' => $formData['cut_long_titles'],
                'tooltip' => $this->__(
                    'Automatically shortens Titles to 80 characters and Subtitles to 55 characters to fit on eBay.'
                )
            ]
        );

        $fieldset = $form->addFieldset('magento_block_ebay_template_description_form_data_description',
            [
                'legend' => $this->__('Description'),
                'collapsable' => true,
            ]
        );

        // TODO NOT SUPPORTED FEATURES
//        $button = $this->createBlock('Magento\Button')->addData([
//            'label' => $this->__('Preview'),
//            'onclick' => 'EbayTemplateDescriptionObj.preview_click(\''.implode(',', $attributeSets).'\')',
//            'class' => 'bt_preview'
//        ]);

        $fieldset->addField('description_mode',
            'select',
            [
                'label' => $this->__('Description'),
                'name' => 'description[description_mode]',
                'values' => [
                    Description::DESCRIPTION_MODE_PRODUCT => $this->__('Product Description'),
                    Description::DESCRIPTION_MODE_SHORT => $this->__('Product Short Description'),
                    Description::DESCRIPTION_MODE_CUSTOM => $this->__('Custom Value'),
                ],
                'value' => $formData['description_mode'],
//                'after_element_html' => $button->toHtml(),
                'tooltip' => $this->__(
                    <<<HTML
                    <p>Choose whether to use Magento <strong>Product Description</strong> or <strong>Product Short 
                    Description</strong> for the eBay Listing Description</p><br>
                    <p>Alternatively, you can create a <strong>Custom Description</strong> and apply it to all 
                    of the Items Listed on eBay using this M2E Pro Listing. </p>
HTML
                )
            ]
        );

        if ($isCustomDescription) {
            $fieldset->addField('view_edit_custom_description_link',
                'link',
                [
                    'container_id' => 'view_edit_custom_description',
                    'label' => '',
                    'value' => $this->__('View / Edit Custom Description'),
                    'onclick' => 'EbayTemplateDescriptionObj.view_edit_custom_change()',
                    'href' => 'javascript://',
                    'style' => 'text-decoration: underline;' //todo
                ]
            );
        }

        $fieldset->addField('description_template',
            'textarea',
            [
                'css_class' => 'c-custom_description_tr',
                'label' => $this->__('Description Value'),
                'name' => 'description[description_template]',
                'value' => $formData['description_template'],
                'required' => true
            ]
        );

        // TODO NOT SUPPORTED FEATURES
        // WYSWYG editor
        // custom inserts

        $fieldset = $form->addFieldset('magento_block_ebay_template_description_form_data_product_details',
            [
                'legend' => $this->__('eBay Catalog Identifiers'),
                'collapsable' => true,
                'tooltip' => $this->__('
                    In this section you can specify a <strong>Magento Attribute</strong> that contains Product
                    UPC/EAN/ISBN/Brand/MPN Value.
                    If selected Value will be sent on eBay and according Product will be found in eBay Catalog, Title,
                    Subtitle, Images and other data will be taken from eBay Catalog.<br/><br/>

                    If you are going to list Variational Item, Brand Value will be the same for all Item. However,
                    UPC/EAN/ISBN/MPN Value will be taken for each Variation separately.<br/>
                    If your Item is Configurable Magento Product or Grouped Magento Product, the Value will be taken as
                    an Attribute Value for each Child Product.<br/>
                    If your Item is Bundle Magento Product or Simple Magento Product with Custom Options, the Value
                    should be provided in Manage Variations Grid, which you can find by clicking on Manage Variations
                    Option for your Product on eBay View Mode of the Listing.<br/><br/>

                    <strong>Note:</strong> if UPC/EAN/ISBN etc. Value is specified for particular Variation in Manage
                    Variations pop-up, only for that Variation the Settings of this section will be ignored.<br/><br/>

                    <strong>Note:</strong> some eBay Categories require some of these Values. Once you select such
                    eBay Primary Category, you will see a notification which will
                    show what Identifier(s) must be specified.
                ')
            ]
        );

        $preparedAttributes = [];
        foreach ($generalAttributesByTypes['text'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['product_details']['upc']['mode'] == Description::PRODUCT_DETAILS_MODE_ATTRIBUTE
                && $formData['product_details']['upc']['attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => Description::PRODUCT_DETAILS_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField('product_details_upc',
            self::SELECT,
            [
                'container_id' => 'product_details_upc_tr',
                'label' => $this->__('UPC'),
                'name' => 'description[product_details][upc][mode]',
                'values' => [
                    Description::PRODUCT_DETAILS_MODE_NONE => $this->__('None'),
                    Description::PRODUCT_DETAILS_MODE_DOES_NOT_APPLY => $this->__('Does Not Apply'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'class' => 'M2ePro-custom-attribute-optgroup'
                        ]
                    ]
                ],
                'value' => $formData['product_details']['upc']['mode'] != Description::PRODUCT_DETAILS_MODE_ATTRIBUTE
                        ? $formData['product_details']['upc']['mode'] : '',
                'tooltip' => $this->__('
                    Choose the Magento Attribute that contains the UPC for a Product or use a
                    "Does not apply" Option in case your Product does not have an UPC Value.<br/><br/>
                    The UPC or Universal Product Code is a 12 digit unique Identifier for a Product.
                ')
            ]
        );

        $fieldset->addField('product_details_upc_attribute',
            'hidden',
            [
                'name' => 'description[product_details][upc][attribute]',
                'value' => $formData['product_details']['upc']['attribute'],
            ]
        );

        $preparedAttributes = [];
        foreach ($generalAttributesByTypes['text'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['product_details']['ean']['mode'] == Description::PRODUCT_DETAILS_MODE_ATTRIBUTE
                && $formData['product_details']['ean']['attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => Description::PRODUCT_DETAILS_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField('product_details_ean',
            self::SELECT,
            [
                'container_id' => 'product_details_ean_tr',
                'label' => $this->__('EAN'),
                'name' => 'description[product_details][ean][mode]',
                'values' => [
                    Description::PRODUCT_DETAILS_MODE_NONE => $this->__('None'),
                    Description::PRODUCT_DETAILS_MODE_DOES_NOT_APPLY => $this->__('Does Not Apply'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'class' => 'M2ePro-custom-attribute-optgroup'
                        ]
                    ]
                ],
                'value' => $formData['product_details']['ean']['mode'] != Description::PRODUCT_DETAILS_MODE_ATTRIBUTE
                    ? $formData['product_details']['ean']['mode'] : '',
                'tooltip' => $this->__('
                    Choose the Magento Attribute that contains the EAN for a Product or use a
                    "Does not apply" Option in case your Product does not have an EAN Value.<br/><br/>
                    The EAN or European Article Number, now renamed International Article Number, is
                    the 13 digit unique Identifier for a Product.
                ')
            ]
        );

        $fieldset->addField('product_details_ean_attribute',
            'hidden',
            [
                'name' => 'description[product_details][ean][attribute]',
                'value' => $formData['product_details']['ean']['attribute'],
            ]
        );

        $preparedAttributes = [];
        foreach ($generalAttributesByTypes['text'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['product_details']['isbn']['mode'] == Description::PRODUCT_DETAILS_MODE_ATTRIBUTE
                && $formData['product_details']['isbn']['attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => Description::PRODUCT_DETAILS_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField('product_details_isbn',
            self::SELECT,
            [
                'container_id' => 'product_details_isbn_tr',
                'label' => $this->__('ISBN'),
                'name' => 'description[product_details][isbn][mode]',
                'values' => [
                    Description::PRODUCT_DETAILS_MODE_NONE => $this->__('None'),
                    Description::PRODUCT_DETAILS_MODE_DOES_NOT_APPLY => $this->__('Does Not Apply'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'class' => 'M2ePro-custom-attribute-optgroup'
                        ]
                    ]
                ],
                'value' => $formData['product_details']['isbn']['mode'] != Description::PRODUCT_DETAILS_MODE_ATTRIBUTE
                    ? $formData['product_details']['isbn']['mode'] : '',
                'tooltip' => $this->__('
                    Choose the Magento Attribute that contains the ISBN for a Product or use a
                    "Does not apply" Option in case your Product does not have an ISBN Value.<br/><br/>
                    The ISBN or International Standard Book Number is a unique Identifier for a book.
                    ')
            ]
        );

        $fieldset->addField('product_details_isbn_attribute',
            'hidden',
            [
                'name' => 'description[product_details][isbn][attribute]',
                'value' => $formData['product_details']['isbn']['attribute'],
            ]
        );

        $preparedAttributes = [];
        foreach ($generalAttributesByTypes['text'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['product_details']['epid']['mode'] == Description::PRODUCT_DETAILS_MODE_ATTRIBUTE
                && $formData['product_details']['epid']['attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => Description::PRODUCT_DETAILS_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField('product_details_epid',
            self::SELECT,
            [
                'container_id' => 'product_details_epid_tr',
                'label' => $this->__('ePID (Product Reference ID)'),
                'name' => 'description[product_details][epid][mode]',
                'values' => [
                    Description::PRODUCT_DETAILS_MODE_NONE => $this->__('None'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'class' => 'M2ePro-custom-attribute-optgroup'
                        ]
                    ]
                ],
                'value' => $formData['product_details']['epid']['mode'] != Description::PRODUCT_DETAILS_MODE_ATTRIBUTE
                    ? $formData['product_details']['epid']['mode'] : '',
                'tooltip' => $this->__('An eBay Product ID is eBay\'s global reference ID for a Catalog Product.')
            ]
        );

        $fieldset->addField('product_details_epid_attribute',
            'hidden',
            [
                'name' => 'description[product_details][epid][attribute]',
                'value' => $formData['product_details']['epid']['attribute'],
            ]
        );

        $preparedAttributes = [];
        foreach ($generalAttributesByTypes['text'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['product_details']['brand']['mode'] == Description::PRODUCT_DETAILS_MODE_ATTRIBUTE
                && $formData['product_details']['brand']['attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => Description::PRODUCT_DETAILS_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField('product_details_brand',
            self::SELECT,
            [
                'container_id' => 'product_details_brand_tr',
                'label' => $this->__('Brand'),
                'name' => 'description[product_details][brand][mode]',
                'values' => [
                    Description::PRODUCT_DETAILS_MODE_NONE => $this->__('None'),
                    Description::PRODUCT_DETAILS_MODE_DOES_NOT_APPLY => $this->__('Unbranded'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'class' => 'M2ePro-custom-attribute-optgroup'
                        ]
                    ]
                ],
                'value' => $formData['product_details']['brand']['mode'] != Description::PRODUCT_DETAILS_MODE_ATTRIBUTE
                    ? $formData['product_details']['brand']['mode'] : '',
                'tooltip' => $this->__(
                    'Choose the Magento Attribute that contains the Brand for a Product or use an
                    "Unbranded" Option in case your Product does not have an Brand Value.'
                )
            ]
        );

        $fieldset->addField('product_details_brand_attribute',
            'hidden',
            [
                'name' => 'description[product_details][brand][attribute]',
                'value' => $formData['product_details']['brand']['attribute'],
            ]
        );

        $preparedAttributes = [];
        foreach ($generalAttributesByTypes['text'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['product_details']['mpn']['mode'] == Description::PRODUCT_DETAILS_MODE_ATTRIBUTE
                && $formData['product_details']['mpn']['attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => Description::PRODUCT_DETAILS_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField('product_details_mpn',
            self::SELECT,
            [
                'container_id' => 'product_details_mpn_tr',
                'label' => $this->__('MPN'),
                'name' => 'description[product_details][mpn][mode]',
                'values' => [
                    Description::PRODUCT_DETAILS_MODE_DOES_NOT_APPLY => $this->__('Does Not Apply'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'class' => 'M2ePro-custom-attribute-optgroup'
                        ]
                    ]
                ],
                'value' => $formData['product_details']['mpn']['mode'] != Description::PRODUCT_DETAILS_MODE_ATTRIBUTE
                    ? $formData['product_details']['mpn']['mode'] : '',
                'tooltip' => $this->__('
                    Choose the Magento Attribute that contains the MPN for a Product or use a
                    "Does not apply" Option in case your Product does not have an MPN Value.<br/><br/>
                    The MPN or Manufacturer Part Number is a Identifier specified by the manufacturer.
                    ')
            ]
        );

        $fieldset->addField('product_details_mpn_attribute',
            'hidden',
            [
                'name' => 'description[product_details][mpn][attribute]',
                'value' => $formData['product_details']['mpn']['attribute'],
            ]
        );

        $fieldset->addField(
            'product_details_specification_separator',
            self::SEPARATOR,
            [
            ]
        );

        $fieldset->addField('product_details_include_description',
            self::SELECT,
            [
                'css_class' => 'product-details-specification',
                'container_id' => 'product_details_include_description_tr',
                'label' => $this->__('Use Description From eBay Catalog'),
                'name' => 'description[product_details][include_description]',
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value' => $formData['product_details']['include_description'],
                'tooltip' => $this->__(
                    '<p>Specify if the Listing should include additional information about the Product,
                    such as a publisher\'s Description or film credits from the prefilled information in 
                    eBay Catalog.</p>'
                )
            ]
        );

        $fieldset->addField('product_details_include_image',
            self::SELECT,
            [
                'css_class' => 'product-details-specification',
                'container_id' => 'product_details_include_image_tr',
                'label' => $this->__('Use Image From eBay Catalog'),
                'name' => 'description[product_details][include_image]',
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value' => $formData['product_details']['include_image'],
                'tooltip' => $this->__('If true, indicates that the Item Listing includes the Stock photo.')
            ]
        );

        $fieldset = $form->addFieldset('magento_block_ebay_template_description_form_data_upgrade_tools',
            [
                'legend' => $this->__('Upgrade Tools'),
                'collapsable' => true,
            ]
        );

        $fieldset->addField('upgrade_tools_value_pack',
            'checkboxes',
            [
                'label' => $this->__('Value Pack'),
                'name' => 'description[enhancement][]',
                'values' => [
                    ['value' => 'ValuePackBundle', 'label' => $this->__('Value Pack Bundle')]
                ],
                'value' => in_array('ValuePackBundle', $formData['enhancement']) ? 'ValuePackBundle' : '',
                'tooltip' => $this->__('Combine Gallery, Subtitle, and Listing Designer and you will get a discount.')
            ]
        );

        $fieldset->addField('upgrade_tools_listing_upgrades',
            'checkboxes',
            [
                'label' => $this->__('Listing Upgrades'),
                'name' => 'description[enhancement][]',
                'values' => [
                    ['value' => 'BoldTitle', 'label' => $this->__('Bold Listing Title')]
                ],
                'value' => in_array('BoldTitle', $formData['enhancement']) ? 'BoldTitle' : '',
                'tooltip' => $this->__(
                    'Find out about <a href="http://pages.ebay.com/help/sell/fees.html#optional"
                                       target="_blank">eBay optional Listing features</a>'
                )
            ]
        );

        $fieldset->addField('upgrade_tools_highlight',
            'checkboxes',
            [
                'label' => '',
                'name' => 'description[enhancement][]',
                'values' => [
                    ['value' => 'Highlight', 'label' => $this->__('Highlight')]
                ],
                'value' => in_array('Highlight', $formData['enhancement']) ? 'Highlight' : '',
                'tooltip' => $this->__('Adding an eye-catching background to your Listing in Search results.')
            ]
        );

        $fieldset->addField('gallery_type',
            self::SELECT,
            [
                'label' => $this->__('Gallery Type'),
                'name' => 'description[gallery_type]',
                'values' => [
                    Description::GALLERY_TYPE_EMPTY => '',
                    Description::GALLERY_TYPE_NO => $this->__('No'),
                    Description::GALLERY_TYPE_PICTURE => $this->__('Gallery Picture'),
                    Description::GALLERY_TYPE_PLUS => $this->__('Gallery Plus'),
                    Description::GALLERY_TYPE_FEATURED => $this->__('Gallery Featured'),
                ],
                'value' => $formData['gallery_type'],
                'tooltip' => $this->__(
                    'Find out about <a href="http://pages.ebay.com/help/sell/fees.html#optional" target="_blank">
                    eBay optional Listing features</a>'
                )
            ]
        );

        $fieldset->addField('hit_counter',
            self::SELECT,
            [
                'label' => $this->__('Hit Counter'),
                'name' => 'description[hit_counter]',
                'values' => [
                    Description::HIT_COUNTER_NONE => $this->__('No Hit Counter'),
                    Description::HIT_COUNTER_BASIC_STYLE => $this->__('Basic Style'),
                    Description::HIT_COUNTER_GREEN_LED => $this->__('Green LED'),
                    Description::HIT_COUNTER_HIDDEN_STYLE => $this->__('Hidden Style'),
                    Description::HIT_COUNTER_HONESTY_STYLE => $this->__('Honesty Style'),
                    Description::HIT_COUNTER_RETRO_STYLE => $this->__('Retro Style'),
                ],
                'value' => $formData['hit_counter'],
                'tooltip' => $this->__(
                    'Count the number of visitors to your eBay Listing.
                     <br/><b>Note:</b> Green LED and Honesty Style styles are available only in the US.'
                )
            ]
        );

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Ebay\Template\Description')
        );

        $this->jsUrl->addUrls([
            'ebay_template_description/saveWatermarkImage' => $this->getUrl(
                '*/ebay_template_description/saveWatermarkImage/'
            ),
        ]);

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Ebay\Template\Description'));

        $this->jsTranslator->addTranslations([
            'Adding Image' => $this->__('Adding Image'),
            'Seller Notes must be less then 1000 symbols.' => $this->__('Seller Notes must be less then 1000 symbols.')
        ]);

        $this->js->add(<<<JS
    require([
        'M2ePro/Attribute',
        'M2ePro/Ebay/Template/Description',
        'M2ePro/Plugin/Magento/Attribute/Button',
    ], function(){
        window.AttributeObj = new Attribute();
        window.EbayTemplateDescriptionObj = new EbayTemplateDescription();
        EbayTemplateDescriptionObj.initObservers();

        window.MagentoAttributeButtonObj = new MagentoAttributeButton();
    });
JS
        );
        
        $this->setForm($form);

        return parent::_prepareForm();
    }

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

        $template = $this->getHelper('Data\GlobalData')->getValue('ebay_template_description');

        if (is_null($template)) {
            return '';
        }

        return $template->getTitle();
    }

    public function getFormData()
    {
        $template = $this->getHelper('Data\GlobalData')->getValue('ebay_template_description');

        if (is_null($template) || is_null($template->getId())) {
            return array();
        }

        $data = array_merge($template->getData(), $template->getChildObject()->getData());

        if (!empty($data['enhancement']) && is_string($data['enhancement'])) {
            $data['enhancement'] = explode(',', $data['enhancement']);
        } else {
            unset($data['enhancement']);
        }

        if (!empty($data['product_details']) && is_string($data['product_details'])) {
            $data['product_details'] = json_decode($data['product_details'], true);
        } else {
            unset($data['product_details']);
        }

        if (!empty($data['variation_configurable_images']) && is_string($data['variation_configurable_images'])) {
            $data['variation_configurable_images'] = json_decode($data['variation_configurable_images'], true);
        } else {
            unset($data['variation_configurable_images']);
        }

        if (!empty($data['watermark_settings']) && is_string($data['watermark_settings'])) {

            $watermarkSettings = json_decode($data['watermark_settings'], true);
            unset($data['watermark_settings']);

            if (isset($watermarkSettings['position'])) {
                $data['watermark_settings']['position'] = $watermarkSettings['position'];
            }
            if (isset($watermarkSettings['scale'])) {
                $data['watermark_settings']['scale'] = $watermarkSettings['scale'];
            }
            if (isset($watermarkSettings['transparent'])) {
                $data['watermark_settings']['transparent'] = $watermarkSettings['transparent'];
            }

            if (isset($watermarkSettings['hashes']['current'])) {
                $data['watermark_settings']['hashes']['current'] = $watermarkSettings['hashes']['current'];
            }
            if (isset($watermarkSettings['hashes']['previous'])) {
                $data['watermark_settings']['hashes']['previous'] = $watermarkSettings['hashes']['previous'];
            }
        } else {
            unset($data['watermark_settings']);
        }

        return $data;
    }
    
    //########################################

    public function getDefault()
    {
        $default = $this->activeRecordFactory->getObject('Ebay\Template\Description')->getDefaultSettings();

        $default['enhancement'] = explode(',', $default['enhancement']);
        $default['product_details'] = json_decode($default['product_details'], true);
        $default['variation_configurable_images'] = json_decode($default['variation_configurable_images'], true);
        $default['watermark_settings'] = json_decode($default['watermark_settings'], true);

        return $default;
    }
}