<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Edit\Tabs;

use Ess\M2ePro\Model\Amazon\Template\Description\Definition as DefinitionTemplate;

class Definition extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    public $templateModel = null;
    public $formData = [];
    public $generalAttributesByInputTypes = [];
    public $allAttributes = [];
    public $allAttributesByInputTypes = [];

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonTemplateDescriptionEditTabsDefinition');
        // ---------------------------------------

        $this->templateModel = $this->getHelper('Data\GlobalData')->getValue('tmp_template');
        $this->formData = $this->getFormData();

        $magentoAttributeHelper = $this->getHelper('Magento\Attribute');

        $this->allAttributes = $magentoAttributeHelper->getAll();

        $generalAttributes = $magentoAttributeHelper->getGeneralFromAllAttributeSets();

        $this->generalAttributesByInputTypes = [
            'text' => $magentoAttributeHelper->filterByInputTypes($generalAttributes, ['text']),
            'text_select' => $magentoAttributeHelper->filterByInputTypes($generalAttributes, ['text', 'select']),
            'text_weight' => $magentoAttributeHelper->filterByInputTypes($generalAttributes, ['text', 'weight']),
            'text_images' => $magentoAttributeHelper->filterByInputTypes(
                $generalAttributes,
                ['text', 'image', 'media_image', 'gallery', 'multiline', 'textarea', 'select', 'multiselect']
            ),
        ];

        $this->allAttributesByInputTypes = [
            'text_select' => $magentoAttributeHelper->filterByInputTypes($this->allAttributes, ['text', 'select']),
        ];
    }

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();
        $this->setForm($form);

        // ---------------------------------------

        $form->addField(
            'amazon_template_description_definition',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    'On this Tab you can specify Settings for common Product Information. <br/>
                    It is available to choose just those Magento Attributes that are used for all Attribute Sets.
                    <br/><br/>

                    <b>Note:</b> Title value is required for creation of all Description Policies
                    because of technical reasons.<br/><br/>

                    More detailed information about ability to work with this Page you can find
                    <a href="%url%" target="_blank" class="external-link">here</a>.',
                        $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/OAYtAQ')
                )
            ]
        );

        // ---------------------------------------

        // ---------------------------------------
        // General
        // ---------------------------------------

        $fieldSet = $form->addFieldset('magento_block_amazon_template_description_general', [
           'legend' => $this->__('General'), 'collapsable' => false
        ]);

        $fieldSet->addField('title_mode', self::SELECT,
            [
                'name' => 'definition[title_mode]',
                'label' => $this->__('Title'),
                'title' => $this->__('Title'),
                'values' => [
                    ['value' => DefinitionTemplate::TITLE_MODE_PRODUCT, 'label' => $this->__('Product Name')],
                    ['value' => DefinitionTemplate::TITLE_MODE_CUSTOM, 'label' => $this->__('Custom Value')],
                ],
                'value' => $this->formData['title_mode'],
                'class' => 'select required-entry',
                'tooltip' => $this->__('Item\'s Title that buyers will see on Amazon Listing.')
            ]
        );

        // ---------------------------------------

        $fieldSet->addField('title_template', 'text',
            [
                'name' => 'definition[title_template]',
                'label' => $this->__('Title Value'),
                'title' => $this->__('Title Value'),
                'value' => $this->formData['title_template'],
                'class' => 'input-text M2ePro-required-when-visible',
                'required' => true,
                'field_extra_attributes' => 'id="custom_title_tr" style="display: none;"',
                'after_element_html' => $this->createBlock('Magento\Button\MagentoAttribute')->addData([
                    'label' => $this->__('Insert Attribute'),
                    'destination_id' => 'title_template',
                    'magento_attributes' => $this->getClearAttributesByInputTypesOptions(),
                    'class' => 'select_attributes_for_title_button primary',
                    'select_custom_attributes' => [
                        'allowed_attribute_types' => 'text,select',
                        'apply_to_all_attribute_sets' => 0
                    ],
                    'style' => 'display: block; margin-left: 0; margin-top: 5px;'
                ])->toHtml()
            ]
        );

        // ---------------------------------------

        $fieldSet->addField('general_separator_1', self::SEPARATOR, []);

        // ---------------------------------------

        $fieldSet->addField('brand_custom_attribute', 'hidden',
            [
                'name' => 'definition[brand_custom_attribute]',
                'value' => $this->formData['brand_custom_attribute']
            ]
        );

        // ---------------------------------------

        $defaultValue = '';
        if ($this->formData['brand_mode'] == DefinitionTemplate::BRAND_MODE_NONE ||
            $this->formData['brand_mode'] == DefinitionTemplate::BRAND_MODE_CUSTOM_VALUE) {
            $defaultValue = $this->formData['brand_mode'];
        }

        $fieldSet->addField('brand_mode', self::SELECT,
            [
                'name' => 'definition[brand_mode]',
                'label' => $this->__('Brand'),
                'title' => $this->__('Brand'),
                'class' => 'select',
                'values' => $this->getBrandOptions(),
                'value' => $defaultValue,
                'required' => true,
                'create_magento_attribute' => true,
                'tooltip' => $this->__('Required for creating new ASIN/ISBN.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        // ---------------------------------------

        $fieldSet->addField('brand_custom_value', 'text',
            [
                'name' => 'definition[brand_custom_value]',
                'label' => $this->__('Brand Value'),
                'title' => $this->__('Brand Value'),
                'value' => $this->formData['brand_custom_value'],
                'class' => 'input-text M2ePro-required-when-visible',
                'required' => true,
                'field_extra_attributes' => 'id="brand_custom_value_tr" style="display: none;"',
            ]
        );

        // ---------------------------------------

        $fieldSet->addField('manufacturer_custom_attribute', 'hidden',
            [
                'name' => 'definition[manufacturer_custom_attribute]',
                'value' => $this->formData['manufacturer_custom_attribute']
            ]
        );

        // ---------------------------------------

        $defaultValue = '';
        if ($this->formData['manufacturer_mode'] == DefinitionTemplate::MANUFACTURER_MODE_NONE ||
            $this->formData['manufacturer_mode'] == DefinitionTemplate::MANUFACTURER_MODE_CUSTOM_VALUE) {
            $defaultValue = $this->formData['manufacturer_mode'];
        }

        $fieldSet->addField('manufacturer_mode', self::SELECT,
            [
                'name' => 'definition[manufacturer_mode]',
                'label' => $this->__('Manufacturer'),
                'title' => $this->__('Manufacturer'),
                'values' => $this->getManufacturerOptions(),
                'value' => $defaultValue,
                'class' => 'select',
                'required' => true,
                'create_magento_attribute' => true,
                'tooltip' => $this->__('Required for creating new ASIN/ISBN.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        // ---------------------------------------

        $fieldSet->addField('manufacturer_custom_value', 'text',
            [
                'name' => 'definition[manufacturer_custom_value]',
                'label' => $this->__('Manufacturer Value'),
                'title' => $this->__('Manufacturer Value'),
                'value' => $this->formData['manufacturer_custom_value'],
                'class' => 'input-text M2ePro-required-when-visible',
                'required' => true,
                'field_extra_attributes' => 'id="manufacturer_custom_value_tr" style="display: none;"',
            ]
        );

        // ---------------------------------------

        $fieldSet->addField('manufacturer_part_number_custom_attribute', 'hidden',
            [
                'name' => 'definition[manufacturer_part_number_custom_attribute]',
                'value' => $this->formData['manufacturer_part_number_custom_attribute']
            ]
        );

        // ---------------------------------------

        $defaultValue = '';
        if ($this->formData['manufacturer_part_number_mode']
                            == DefinitionTemplate::MANUFACTURER_PART_NUMBER_MODE_NONE ||
            $this->formData['manufacturer_part_number_mode']
                            == DefinitionTemplate::MANUFACTURER_PART_NUMBER_MODE_CUSTOM_VALUE
        ) {
            $defaultValue = $this->formData['manufacturer_part_number_mode'];
        }

        $fieldSet->addField('manufacturer_part_number_mode', self::SELECT,
            [
                'name' => 'definition[manufacturer_part_number_mode]',
                'label' => $this->__('Manufacturer Part Number'),
                'title' => $this->__('Manufacturer Part Number'),
                'values' => $this->getManufacturerPartNumberOptions(),
                'value' => $defaultValue,
                'class' => 'select',
                'create_magento_attribute' => true,
                'tooltip' => $this->__('Manufacturer Part Number of the Product(s). Max. 40 characters.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        // ---------------------------------------

        $fieldSet->addField('manufacturer_part_number_custom_value', 'text',
            [
                'name' => 'definition[manufacturer_part_number_custom_value]',
                'label' => $this->__('Manufacturer Part Number Value'),
                'title' => $this->__('Manufacturer Part Number Value'),
                'value' => $this->formData['manufacturer_part_number_custom_value'],
                'class' => 'input-text M2ePro-required-when-visible',
                'required' => true,
                'field_extra_attributes' => 'id="manufacturer_part_number_custom_value_tr" style="display: none;"',
            ]
        );

        // ---------------------------------------

        $fieldSet->addField('general_separator_2', self::SEPARATOR, []);

        // ---------------------------------------

        $fieldSet->addField('item_package_quantity_custom_attribute', 'hidden',
            [
                'name' => 'definition[item_package_quantity_custom_attribute]',
                'value' => $this->formData['item_package_quantity_custom_attribute']
            ]
        );

        // ---------------------------------------

        $defaultValue = '';
        if ($this->formData['item_package_quantity_mode']
                            == DefinitionTemplate::ITEM_PACKAGE_QUANTITY_MODE_NONE ||
            $this->formData['item_package_quantity_mode']
                            == DefinitionTemplate::ITEM_PACKAGE_QUANTITY_MODE_CUSTOM_VALUE
        ) {
            $defaultValue = $this->formData['item_package_quantity_mode'];
        }

        $fieldSet->addField('item_package_quantity_mode', self::SELECT,
            [
                'name' => 'definition[item_package_quantity_mode]',
                'label' => $this->__('Package Quantity'),
                'title' => $this->__('Package Quantity'),
                'values' => $this->getPackageQuantityOptions(),
                'value' => $defaultValue,
                'create_magento_attribute' => true,
                'tooltip' => $this->__('The number of units included in the Item you are offering for sale,
                <br/>such that each unit is packaged for individual sale.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        // ---------------------------------------

        $fieldSet->addField('item_package_quantity_custom_value', 'text',
            [
                'name' => 'definition[item_package_quantity_custom_value]',
                'label' => $this->__('Package Quantity Value'),
                'title' => $this->__('Package Quantity Value'),
                'value' => $this->formData['item_package_quantity_custom_value'],
                'class' => 'input-text M2ePro-required-when-visible M2ePro-validate-greater-than',
                'required' => true,
                'field_extra_attributes' => 'id="item_package_quantity_custom_value_tr"',
                'css_class' => 'entry-edit'
            ]
        )->addCustomAttribute('min_value', 1);

        // ---------------------------------------

        $fieldSet->addField('number_of_items_custom_attribute', 'hidden',
            [
                'name' => 'definition[number_of_items_custom_attribute]',
                'value' => $this->formData['number_of_items_custom_attribute']
            ]
        );

        // ---------------------------------------

        $defaultValue = '';
        if ($this->formData['number_of_items_mode'] == DefinitionTemplate::NUMBER_OF_ITEMS_MODE_NONE ||
            $this->formData['number_of_items_mode'] == DefinitionTemplate::NUMBER_OF_ITEMS_MODE_CUSTOM_VALUE) {
            $defaultValue = $this->formData['number_of_items_mode'];
        }

        $fieldSet->addField('number_of_items_mode', self::SELECT,
            [
                'name' => 'definition[number_of_items_mode]',
                'label' => $this->__('Number of Items'),
                'title' => $this->__('Number of Items'),
                'values' => $this->getNumberOfItemsOptions(),
                'class' => 'M2ePro-required-when-visible',
                'value' => $defaultValue,
                'create_magento_attribute' => true,
                'tooltip' => $this->__(
                    'The number of discrete Items included in the Item you are offering for sale,
                     such that each Item is not packaged for individual sale.<br/><br/>
                     <strong>For ex.</strong>, if you are selling a case of 10 packages of socks,
                     and each package contains
                     <br/>3 pairs of socks, the case would have Package Quantity = 10 and Number of Items = 30.'
                )
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        // ---------------------------------------

        $fieldSet->addField('number_of_items_custom_value', 'text',
            [
                'name' => 'definition[number_of_items_custom_value]',
                'label' => $this->__('Number of Items Value'),
                'title' => $this->__('Number of Items Value'),
                'value' => $this->formData['number_of_items_custom_value'],
                'class' => 'input-text M2ePro-required-when-visible M2ePro-validate-greater-than',
                'required' => true,
                'field_extra_attributes' => 'id="number_of_items_custom_value_tr"',
                'css_class' => 'entry-edit'
            ]
        )->addCustomAttribute('min_value', 1);

        // ---------------------------------------

        // ---------------------------------------
        // Images
        // ---------------------------------------

        $fieldSet = $form->addFieldset('magento_block_amazon_template_description_image', [
            'legend' => $this->__('Images'), 'collapsable' => true, 'class' => 'entry-edit'
        ]);

        // ---------------------------------------

        $fieldSet->addField('image_main_attribute', 'hidden',
            [
                'name' => 'definition[image_main_attribute]',
                'value' => $this->formData['image_main_attribute']
            ]
        );

        // ---------------------------------------

        $defaultValue = '';
        if ($this->formData['image_main_mode'] == DefinitionTemplate::IMAGE_MAIN_MODE_NONE ||
            $this->formData['image_main_mode'] == DefinitionTemplate::IMAGE_MAIN_MODE_PRODUCT) {
            $defaultValue = $this->formData['image_main_mode'];
        }

        $fieldSet->addField('image_main_mode', self::SELECT,
            [
                'name' => 'definition[image_main_mode]',
                'label' => $this->__('Main Image'),
                'title' => $this->__('Main Image'),
                'values' => $this->getImageMainOptions(),
                'value' => $defaultValue,
                'required' => false,
                'create_magento_attribute' => true,
                'tooltip' => $this->__('Required for creating new ASIN/ISBN.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,textarea,select,multiselect');

        // ---------------------------------------

        $fieldSet->addField('gallery_images_limit', 'hidden',
            [
                'name' => 'definition[gallery_images_limit]',
                'value' => $this->formData['gallery_images_limit']
            ]
        );

        // ---------------------------------------

        $fieldSet->addField('gallery_images_attribute', 'hidden',
            [
                'name' => 'definition[gallery_images_attribute]',
                'value' => $this->formData['gallery_images_attribute']
            ]
        );

        // ---------------------------------------

        $defaultValue = '';
        if ($this->formData['gallery_images_mode'] == DefinitionTemplate::GALLERY_IMAGES_MODE_NONE) {
            $defaultValue = $this->formData['gallery_images_mode'];
        }

        $fieldSet->addField('gallery_images_mode', self::SELECT,
            [
                'name' => 'definition[gallery_images_mode]',
                'label' => $this->__('Additional Images'),
                'title' => $this->__('Additional Images'),
                'values' => $this->getGalleryImageOptions(),
                'value' => $defaultValue,
                'create_magento_attribute' => true,
                'field_extra_attributes' => 'id="gallery_images_mode_tr"',
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,textarea,select,multiselect');

        // ---------------------------------------

        $fieldSet->addField('image_variation_difference_attribute', 'hidden',
            [
                'name' => 'definition[image_variation_difference_attribute]',
                'value' => $this->formData['image_variation_difference_attribute']
            ]
        );

        // ---------------------------------------

        $defaultValue = '';
        if ($this->formData['image_variation_difference_mode']
                                == DefinitionTemplate::IMAGE_VARIATION_DIFFERENCE_MODE_NONE ||
            $this->formData['image_variation_difference_mode']
                                == DefinitionTemplate::IMAGE_VARIATION_DIFFERENCE_MODE_PRODUCT
        ) {
            $defaultValue = $this->formData['image_variation_difference_mode'];
        }

        $fieldSet->addField('image_variation_difference_mode', self::SELECT,
            [
                'name' => 'definition[image_variation_difference_mode]',
                'label' => $this->__('Swatch Image'),
                'title' => $this->__('Swatch Image'),
                'values' => $this->getSwatchImageOptions(),
                'value' => $defaultValue,
                'create_magento_attribute' => true,
                'field_extra_attributes' => 'id="gallery_images_mode_tr"',
                'tooltip' => $this->__('Allows to display Variations of Amazon Product by
                the main images of Child Products.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,textarea,select,multiselect');

        // ---------------------------------------

        // ---------------------------------------
        // Description
        // ---------------------------------------

        $fieldSet = $form->addFieldset('magento_block_amazon_template_description_description', [
            'legend' => $this->__('Description'), 'collapsable' => true, 'class' => 'entry-edit'
        ]);

        // ---------------------------------------

        $fieldSet->addField('description_mode', self::SELECT,
            [
                'name' => 'definition[description_mode]',
                'label' => $this->__('Description'),
                'title' => $this->__('Description'),
                'values' => [
                    ['value' => DefinitionTemplate::DESCRIPTION_MODE_NONE, 'label' => $this->__('None')],
                    [
                        'value' => DefinitionTemplate::DESCRIPTION_MODE_PRODUCT,
                        'label' => $this->__('Product Description')
                    ],
                    [
                        'value' => DefinitionTemplate::DESCRIPTION_MODE_SHORT,
                        'label' => $this->__('Product Short Description')
                    ],
                    ['value' => DefinitionTemplate::DESCRIPTION_MODE_CUSTOM, 'label' => $this->__('Custom Value')],
                ],
                'value' => $this->formData['description_mode'],
                'class' => 'required-entry',
                'tooltip' => $this->__('Description is limited to 2\'000 characters.')
            ]
        );

        // ---------------------------------------

        $options = [];
        $helper = $this->getHelper('Data');
        foreach ($this->allAttributes as $attribute) {
            $options[] = [
                'value' => $attribute['code'],
                'label' => $helper->escapeHtml($attribute['label'])
            ];
        }

        $fieldSet->addField('description_template', 'textarea',
            [
                'name' => 'definition[description_template]',
                'label' => $this->__('Description Value'),
                'title' => $this->__('Description Value'),
                'value' => $this->formData['description_template'],
                'class' => 'textarea M2ePro-required-when-visible',
                'required' => true,
                'css_class' => 'c-custom_description_tr',
                'field_extra_attributes' => 'style="display: none;"',
                'after_element_html' => $this->createBlock('Magento\Button\MagentoAttribute')->addData([
                    'label' => $this->__('Insert Attribute'),
                    'destination_id' => 'description_template',
                    'magento_attributes' => $options,
                    'class' => 'primary',
                    'style' => 'margin-left: 0; margin-top: 5px;'
                ])->toHtml()
            ]
        );

        // ---------------------------------------

        // ---------------------------------------
        // Product Dimensions
        // ---------------------------------------

        $fieldSet = $form->addFieldset('magento_block_amazon_template_description_product_dimensions', [
            'legend' => $this->__('Product Dimensions'), 'collapsable' => true, 'class' => 'entry-edit'
        ]);

        // ---------------------------------------

        $fieldSet->addField('item_dimensions_volume_mode', self::SELECT,
            [
                'name' => 'definition[item_dimensions_volume_mode]',
                'label' => $this->__('Volume'),
                'title' => $this->__('Volume'),
                'values' => [
                    ['value' => DefinitionTemplate::DIMENSION_VOLUME_MODE_NONE, 'label' => $this->__('None')],
                    [
                        'value' => DefinitionTemplate::DIMENSION_VOLUME_MODE_CUSTOM_VALUE,
                        'label' => $this->__('Custom Value')
                    ],
                    [
                        'value' => DefinitionTemplate::DIMENSION_VOLUME_MODE_CUSTOM_ATTRIBUTE,
                        'label' => $this->__('Custom Attribute')
                    ],
                ],
                'value' => $this->formData['item_dimensions_volume_mode'],
                'class' => 'select',
                'tooltip' => $this->__('Physical dimensions of Product without package')
            ]
        );

        // ---------------------------------------

        $lengthBlock = $this->elementFactory->create('text', ['data' => [
            'name' => 'definition[item_dimensions_volume_length_custom_value]',
            'value' => $this->escapeHtml($this->formData['item_dimensions_volume_length_custom_value']),
            'class' => 'input-text M2ePro-required-when-visible M2ePro-validation-float M2ePro-validate-greater-than'
        ]])->addCustomAttribute('min_value', '0.01');
        $lengthBlock->setForm($form);

        $widthBlock = $this->elementFactory->create('text', ['data' => [
            'name' => 'definition[item_dimensions_volume_width_custom_value]',
            'value' => $this->escapeHtml($this->formData['item_dimensions_volume_width_custom_value']),
            'class' => 'input-text M2ePro-required-when-visible M2ePro-validation-float M2ePro-validate-greater-than'
        ]])->addCustomAttribute('min_value', '0.01');
        $widthBlock->setForm($form);

        $heightBlock = $this->elementFactory->create('text', ['data' => [
            'name' => 'definition[item_dimensions_volume_height_custom_value]',
            'value' => $this->escapeHtml($this->formData['item_dimensions_volume_height_custom_value']),
            'class' => 'input-text M2ePro-required-when-visible M2ePro-validation-float M2ePro-validate-greater-than'
        ]])->addCustomAttribute('min_value', '0.01');
        $heightBlock->setForm($form);

        $fieldSet->addField('item_dimensions_volume_custom_value',
            self::CUSTOM_CONTAINER,
            [
                'label' => $this->__('Length x Width x Height'),
                'title' => $this->__('Length x Width x Height'),
                'style' => 'padding-top: 0;',
                'text' => $lengthBlock->toHtml() . ' x '
                          . $widthBlock->toHtml() . ' x '
                          . $heightBlock->toHtml(),
                'required' => true,
                'field_extra_attributes' => 'id="item_dimensions_volume_custom_value_tr" style="display: none;"',

            ]
        );

        // ---------------------------------------

        $lengthBlock = $this->elementFactory->create(self::SELECT, ['data' => [
            'name' => 'definition[item_dimensions_volume_length_custom_attribute]',
            'values' => $this->getDimensionsVolumeAttributesOptions(
                'item_dimensions_volume_length_custom_attribute'
            ),
            'class' => 'M2ePro-required-when-visible',
            'create_magento_attribute' => true,
            'style' => 'width: 30%'
        ]])->addCustomAttribute('allowed_attribute_types', 'text');
        $lengthBlock->setId('item_dimensions_volume_length_custom_attribute');
        $lengthBlock->setForm($form);

        $widthBlock = $this->elementFactory->create(self::SELECT, ['data' => [
            'name' => 'definition[item_dimensions_volume_width_custom_attribute]',
            'values' => $this->getDimensionsVolumeAttributesOptions(
                'item_dimensions_volume_width_custom_attribute'
            ),
            'class' => 'M2ePro-required-when-visible',
            'create_magento_attribute' => true,
            'style' => 'width: 30%'
        ]])->addCustomAttribute('allowed_attribute_types', 'text');
        $widthBlock->setId('item_dimensions_volume_width_custom_attribute');
        $widthBlock->setForm($form);

        $heightBlock = $this->elementFactory->create(self::SELECT, ['data' => [
            'name' => 'definition[item_dimensions_volume_height_custom_attribute]',
            'values' => $this->getDimensionsVolumeAttributesOptions(
                'item_dimensions_volume_height_custom_attribute'
            ),
            'class' => 'M2ePro-required-when-visible',
            'create_magento_attribute' => true,
            'style' => 'width: 30%'
        ]])->addCustomAttribute('allowed_attribute_types', 'text');
        $heightBlock->setId('item_dimensions_volume_height_custom_attribute');
        $heightBlock->setForm($form);

        $fieldSet->addField('item_dimensions_volume_custom_attribute',
            self::CUSTOM_CONTAINER,
            [
                'label' => $this->__('Length x Width x Height'),
                'title' => $this->__('Length x Width x Height'),
                'style' => 'padding-top: 0;',
                'text' => $lengthBlock->toHtml() . ' x '
                          . $widthBlock->toHtml() . ' x '
                          . $heightBlock->toHtml(),
                'required' => true,
                'field_extra_attributes' => 'id="item_dimensions_volume_custom_attribute_tr" style="display: none;"',
            ]
        );

        // ---------------------------------------

        $fieldSet->addField('item_dimensions_volume_unit_of_measure_custom_value', 'hidden',
            [
                'name' => 'definition[item_dimensions_volume_unit_of_measure_custom_value]',
                'value' => $this->formData['item_dimensions_volume_unit_of_measure_custom_value']
            ]
        );

        // ---------------------------------------

        $fieldSet->addField('item_dimensions_volume_unit_of_measure_custom_attribute', 'hidden',
            [
                'name' => 'definition[item_dimensions_volume_unit_of_measure_custom_attribute]',
                'value' => $this->formData['item_dimensions_volume_unit_of_measure_custom_attribute']
            ]
        );

        // ---------------------------------------

        $fieldSet->addField('item_dimensions_volume_unit_of_measure_mode', self::SELECT,
            [
                'name' => 'definition[item_dimensions_volume_unit_of_measure_mode]',
                'label' => $this->__('Measure Units'),
                'title' => $this->__('Measure Units'),
                'values' => $this->getMeasureUnitsOptions(
                    DefinitionTemplate::DIMENSION_VOLUME_UNIT_OF_MEASURE_MODE_CUSTOM_VALUE,
                    DefinitionTemplate::DIMENSION_VOLUME_UNIT_OF_MEASURE_MODE_CUSTOM_ATTRIBUTE,
                    'item_dimensions_volume_unit_of_measure_custom_value',
                    'item_dimensions_volume_unit_of_measure_custom_attribute'
                ),
                'class' => 'select M2ePro-required-when-visible',
                'required' => true,
                'create_magento_attribute' => true,
                'field_extra_attributes'=>'id="item_dimensions_volume_unit_of_measure_mode_tr" style="display: none;"',
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        // ---------------------------------------

        $fieldSet->addField('item_dimensions_weight_custom_attribute', 'hidden',
            [
                'name' => 'definition[item_dimensions_weight_custom_attribute]',
                'value' => $this->formData['item_dimensions_weight_custom_attribute']
            ]
        );

        // ---------------------------------------

        $defaultValue = '';
        if ($this->formData['item_dimensions_weight_mode'] == DefinitionTemplate::WEIGHT_MODE_NONE ||
            $this->formData['item_dimensions_weight_mode'] == DefinitionTemplate::WEIGHT_MODE_CUSTOM_VALUE) {
            $defaultValue = $this->formData['item_dimensions_weight_mode'];
        }

        $fieldSet->addField('item_dimensions_weight_mode', self::SELECT,
            [
                'name' => 'definition[item_dimensions_weight_mode]',
                'label' => $this->__('Weight'),
                'title' => $this->__('Weight'),
                'values' => $this->getItemDimensionsWeightOptions(),
                'value' => $defaultValue,
                'class' => 'select M2ePro-required-when-visible',
                'create_magento_attribute' => true,
                'tooltip' => $this->__('Physical weight of Products without package')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        // ---------------------------------------

        $fieldSet->addField('item_dimensions_weight_custom_value', 'text',
            [
                'name' => 'definition[item_dimensions_weight_custom_value]',
                'label' => $this->__('Weight Value'),
                'title' => $this->__('Weight Value'),
                'value' => $this->formData['item_dimensions_weight_custom_value'],
                'class' => 'input-text M2ePro-required-when-visible M2ePro-validate-greater-than',
                'field_extra_attributes' => 'id="item_dimensions_weight_custom_value_tr" style="display: none;"',
            ]
        )->addCustomAttribute('min_value', '0.01');

        // ---------------------------------------

        $fieldSet->addField('item_dimensions_weight_unit_of_measure_custom_value', 'hidden',
            [
                'name' => 'definition[item_dimensions_weight_unit_of_measure_custom_value]',
                'value' => $this->formData['item_dimensions_weight_unit_of_measure_custom_value']
            ]
        );

        // ---------------------------------------

        $fieldSet->addField('item_dimensions_weight_unit_of_measure_custom_attribute', 'hidden',
            [
                'name' => 'definition[item_dimensions_weight_unit_of_measure_custom_attribute]',
                'value' => $this->formData['item_dimensions_weight_unit_of_measure_custom_attribute']
            ]
        );

        // ---------------------------------------

        $fieldSet->addField('item_dimensions_weight_unit_of_measure_mode', self::SELECT,
            [
                'name' => 'definition[item_dimensions_weight_unit_of_measure_mode]',
                'label' => $this->__('Weight Units'),
                'title' => $this->__('Weight Units'),
                'values' => $this->getWeightUnitOfMeasureOptions(
                    DefinitionTemplate::DIMENSION_VOLUME_MODE_CUSTOM_VALUE,
                    DefinitionTemplate::DIMENSION_VOLUME_MODE_CUSTOM_ATTRIBUTE,
                    'item_dimensions_weight_unit_of_measure_custom_value',
                    'item_dimensions_weight_unit_of_measure_custom_attribute'
                ),
                'required' => true,
                'class' => 'select M2ePro-required-when-visible',
                'create_magento_attribute' => true,
                'field_extra_attributes'=>'id="item_dimensions_weight_unit_of_measure_mode_tr" style="display: none;"',
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        // ---------------------------------------

        // ---------------------------------------
        // Package Dimensions
        // ---------------------------------------

        $fieldSet = $form->addFieldset('magento_block_amazon_template_description_package_dimensions', [
            'legend' => $this->__('Package Dimensions'), 'collapsable' => true, 'class' => 'entry-edit'
        ]);

        // ---------------------------------------

        $fieldSet->addField('package_dimensions_volume_mode', self::SELECT,
            [
                'name' => 'definition[package_dimensions_volume_mode]',
                'label' => $this->__('Volume'),
                'title' => $this->__('Volume'),
                'values' => [
                    ['value' => DefinitionTemplate::DIMENSION_VOLUME_MODE_NONE, 'label' => $this->__('None')],
                    [
                        'value' => DefinitionTemplate::DIMENSION_VOLUME_MODE_CUSTOM_VALUE,
                        'label' => $this->__('Custom Value')
                    ],
                    [
                        'value' => DefinitionTemplate::DIMENSION_VOLUME_MODE_CUSTOM_ATTRIBUTE,
                        'label' => $this->__('Custom Attribute')
                    ],
                ],
                'value' => $this->formData['package_dimensions_volume_mode'],
                'class' => 'select',
                'tooltip' => $this->__('Physical dimensions of package')
            ]
        );

        // ---------------------------------------

        $lengthBlock = $this->elementFactory->create('text', ['data' => [
            'name' => 'definition[package_dimensions_volume_length_custom_value]',
            'value' => $this->escapeHtml($this->formData['package_dimensions_volume_length_custom_value']),
            'class' => 'input-text M2ePro-required-when-visible M2ePro-validation-float M2ePro-validate-greater-than'
        ]])->addCustomAttribute('min_value', '0.01');
        $lengthBlock->setForm($form);

        $widthBlock = $this->elementFactory->create('text', ['data' => [
            'name' => 'definition[package_dimensions_volume_width_custom_value]',
            'value' => $this->escapeHtml($this->formData['package_dimensions_volume_width_custom_value']),
            'class' => 'input-text M2ePro-required-when-visible M2ePro-validation-float M2ePro-validate-greater-than'
        ]])->addCustomAttribute('min_value', '0.01');
        $widthBlock->setForm($form);

        $heightBlock = $this->elementFactory->create('text', ['data' => [
            'name' => 'definition[package_dimensions_volume_height_custom_value]',
            'value' => $this->escapeHtml($this->formData['package_dimensions_volume_height_custom_value']),
            'class' => 'input-text M2ePro-required-when-visible M2ePro-validation-float M2ePro-validate-greater-than'
        ]])->addCustomAttribute('min_value', '0.01');
        $heightBlock->setForm($form);

        $fieldSet->addField('package_dimensions_volume_custom_value',
            self::CUSTOM_CONTAINER,
            [
                'label' => $this->__('Length x Width x Height'),
                'title' => $this->__('Length x Width x Height'),
                'style' => 'padding-top: 0;',
                'text' => $lengthBlock->toHtml() . ' x '
                         . $widthBlock->toHtml() . ' x '
                         . $heightBlock->toHtml(),
                'required' => true,
                'field_extra_attributes' => 'id="package_dimensions_volume_custom_value_tr" style="display: none;"',

            ]
        );

        // ---------------------------------------

        $lengthBlock = $this->elementFactory->create(self::SELECT, ['data' => [
            'name' => 'definition[package_dimensions_volume_length_custom_attribute]',
            'values' => $this->getDimensionsVolumeAttributesOptions(
                'package_dimensions_volume_length_custom_attribute'
            ),
            'class' => 'M2ePro-required-when-visible',
            'create_magento_attribute' => true,
            'style' => 'width: 30%'
        ]])->addCustomAttribute('allowed_attribute_types', 'text');
        $lengthBlock->setId('package_dimensions_volume_length_custom_attribute');
        $lengthBlock->setForm($form);

        $widthBlock = $this->elementFactory->create(self::SELECT, ['data' => [
            'name' => 'definition[package_dimensions_volume_width_custom_attribute]',
            'values' => $this->getDimensionsVolumeAttributesOptions(
                'package_dimensions_volume_width_custom_attribute'
            ),
            'class' => 'M2ePro-required-when-visible',
            'create_magento_attribute' => true,
            'style' => 'width: 30%'
        ]])->addCustomAttribute('allowed_attribute_types', 'text');
        $widthBlock->setId('package_dimensions_volume_width_custom_attribute');
        $widthBlock->setForm($form);

        $heightBlock = $this->elementFactory->create(self::SELECT, ['data' => [
            'name' => 'definition[package_dimensions_volume_height_custom_attribute]',
            'values' => $this->getDimensionsVolumeAttributesOptions(
                'package_dimensions_volume_height_custom_attribute'
            ),
            'class' => 'M2ePro-required-when-visible',
            'create_magento_attribute' => true,
            'style' => 'width: 30%'
        ]])->addCustomAttribute('allowed_attribute_types', 'text');
        $heightBlock->setId('package_dimensions_volume_height_custom_attribute');
        $heightBlock->setForm($form);

        $fieldSet->addField('package_dimensions_volume_custom_attribute',
            self::CUSTOM_CONTAINER,
            [
                'label' => $this->__('Length x Width x Height'),
                'title' => $this->__('Length x Width x Height'),
                'style' => 'padding-top: 0;',
                'text' => $lengthBlock->toHtml() . ' x '
                    . $widthBlock->toHtml() . ' x '
                    . $heightBlock->toHtml(),
                'required' => true,
                'field_extra_attributes' =>
                    'id="package_dimensions_volume_custom_attribute_tr" style="display: none;"',
            ]
        );

        // ---------------------------------------

        $fieldSet->addField('package_dimensions_volume_unit_of_measure_custom_value', 'hidden',
            [
                'name' => 'definition[package_dimensions_volume_unit_of_measure_custom_value]',
                'value' => $this->formData['package_dimensions_volume_unit_of_measure_custom_value']
            ]
        );

        // ---------------------------------------

        $fieldSet->addField('package_dimensions_volume_unit_of_measure_custom_attribute', 'hidden',
            [
                'name' => 'definition[package_dimensions_volume_unit_of_measure_custom_attribute]',
                'value' => $this->formData['package_dimensions_volume_unit_of_measure_custom_attribute']
            ]
        );

        // ---------------------------------------

        $fieldSet->addField('package_dimensions_volume_unit_of_measure_mode', self::SELECT,
            [
                'name' => 'definition[package_dimensions_volume_unit_of_measure_mode]',
                'label' => $this->__('Measure Units'),
                'title' => $this->__('Measure Units'),
                'values' => $this->getMeasureUnitsOptions(
                    DefinitionTemplate::DIMENSION_VOLUME_MODE_CUSTOM_VALUE,
                    DefinitionTemplate::DIMENSION_VOLUME_MODE_CUSTOM_ATTRIBUTE,
                    'package_dimensions_volume_unit_of_measure_custom_value',
                    'package_dimensions_volume_unit_of_measure_custom_attribute'
                ),
                'class' => 'select M2ePro-required-when-visible',
                'required' => true,
                'create_magento_attribute' => true,
                'field_extra_attributes' => 'id="package_dimensions_volume_unit_of_measure_mode_tr"
                                             style="display: none;"',
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        // ---------------------------------------

        // ---------------------------------------
        // Shipping Details
        // ---------------------------------------

        $fieldSet = $form->addFieldset('magento_block_amazon_template_description_shipping_details', [
            'legend' => $this->__('Shipping Details'), 'collapsable' => true, 'class' => 'entry-edit'
        ]);

        // ---------------------------------------

        $fieldSet->addField('package_weight_custom_attribute', 'hidden',
            [
                'name' => 'definition[package_weight_custom_attribute]',
                'value' => $this->formData['package_weight_custom_attribute']
            ]
        );

        // ---------------------------------------

        $defaultValue = '';
        if ($this->formData['package_weight_mode'] == DefinitionTemplate::WEIGHT_MODE_NONE ||
            $this->formData['package_weight_mode'] == DefinitionTemplate::WEIGHT_MODE_CUSTOM_VALUE) {
            $defaultValue = $this->formData['package_weight_mode'];
        }

        $fieldSet->addField('package_weight_mode', self::SELECT,
            [
                'name' => 'definition[package_weight_mode]',
                'label' => $this->__('Package Weight'),
                'title' => $this->__('Package Weight'),
                'values' => $this->getPackageWeightModeOptions(),
                'value' => $defaultValue,
                'class' => 'select M2ePro-required-when-visible',
                'create_magento_attribute' => true,
                'tooltip' => $this->__('Package Weight of the Product(s).')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        // ---------------------------------------

        $fieldSet->addField('package_weight_custom_value', 'text',
            [
                'name' => 'definition[package_weight_custom_value]',
                'label' => $this->__('Package Weight Value'),
                'title' => $this->__('Package Weight Value'),
                'value' => $this->formData['package_weight_custom_value'],
                'class' => 'input-text M2ePro-required-when-visible M2ePro-validate-greater-than',
                'required' => true,
                'field_extra_attributes' => 'id="package_weight_custom_value_tr" style="display: none;"',
            ]
        )->addCustomAttribute('min_value', '0.01');

        // ---------------------------------------

        $fieldSet->addField('package_weight_unit_of_measure_custom_value', 'hidden',
            [
                'name' => 'definition[package_weight_unit_of_measure_custom_value]',
                'value' => $this->formData['package_weight_unit_of_measure_custom_value'],
            ]
        );

        // ---------------------------------------

        $fieldSet->addField('package_weight_unit_of_measure_custom_attribute', 'hidden',
            [
                'name' => 'definition[package_weight_unit_of_measure_custom_attribute]',
                'value' => $this->formData['package_weight_unit_of_measure_custom_attribute'],
            ]
        );

        // ---------------------------------------

        $fieldSet->addField('package_weight_unit_of_measure_mode', self::SELECT,
            [
                'name' => 'definition[package_weight_unit_of_measure_mode]',
                'label' => $this->__('Package Weight Units'),
                'title' => $this->__('Package Weight Units'),
                'values' => $this->getWeightUnitOfMeasureOptions(
                    DefinitionTemplate::WEIGHT_UNIT_OF_MEASURE_MODE_CUSTOM_VALUE,
                    DefinitionTemplate::WEIGHT_UNIT_OF_MEASURE_MODE_CUSTOM_ATTRIBUTE,
                    'package_weight_unit_of_measure_custom_value',
                    'package_weight_unit_of_measure_custom_attribute'
                ),
                'required' => true,
                'create_magento_attribute' => true,
                'class' => 'select M2ePro-required-when-visible',
                'field_extra_attributes' => 'id="package_weight_unit_of_measure_mode_tr" style="display: none;"',
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        // ---------------------------------------

        $fieldSet->addField('shipping_weight_custom_attribute', 'hidden',
            [
                'name' => 'definition[shipping_weight_custom_attribute]',
                'value' => $this->formData['shipping_weight_custom_attribute']
            ]
        );

        // ---------------------------------------

        $defaultValue = '';
        if ($this->formData['shipping_weight_mode'] == DefinitionTemplate::WEIGHT_MODE_NONE ||
            $this->formData['shipping_weight_mode'] == DefinitionTemplate::WEIGHT_MODE_CUSTOM_VALUE) {
            $defaultValue = $this->formData['shipping_weight_mode'];
        }

        $fieldSet->addField('shipping_weight_mode', self::SELECT,
            [
                'name' => 'definition[shipping_weight_mode]',
                'label' => $this->__('Shipping Weight'),
                'title' => $this->__('Shipping Weight'),
                'values' => $this->getShippingWeightModeOptions(),
                'value' => $defaultValue,
                'create_magento_attribute' => true,
                'class' => 'select M2ePro-required-when-visible',
                'tooltip' => $this->__('Shipping Weight of the Product(s).')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        // ---------------------------------------

        $fieldSet->addField('shipping_weight_custom_value', 'text',
            [
                'name' => 'definition[shipping_weight_custom_value]',
                'label' => $this->__('Shipping Weight Value'),
                'title' => $this->__('Shipping Weight Value'),
                'value' => $this->formData['shipping_weight_custom_value'],
                'class' => 'input-text M2ePro-required-when-visible M2ePro-validate-greater-than',
                'required' => true,
                'field_extra_attributes' => 'id="shipping_weight_custom_value_tr" style="display: none;"',
            ]
        )->addCustomAttribute('min_value', '0.01');

        // ---------------------------------------

        $fieldSet->addField('shipping_weight_unit_of_measure_custom_value', 'hidden',
            [
                'name' => 'definition[shipping_weight_unit_of_measure_custom_value]',
                'value' => $this->formData['shipping_weight_unit_of_measure_custom_value'],
            ]
        );

        // ---------------------------------------

        $fieldSet->addField('shipping_weight_unit_of_measure_custom_attribute', 'hidden',
            [
                'name' => 'definition[shipping_weight_unit_of_measure_custom_attribute]',
                'value' => $this->formData['shipping_weight_unit_of_measure_custom_attribute'],
            ]
        );

        // ---------------------------------------

        $fieldSet->addField('shipping_weight_unit_of_measure_mode', self::SELECT,
            [
                'name' => 'definition[shipping_weight_unit_of_measure_mode]',
                'label' => $this->__('Shipping Weight Units'),
                'title' => $this->__('Shipping Weight Units'),
                'values' => $this->getWeightUnitOfMeasureOptions(
                    DefinitionTemplate::WEIGHT_UNIT_OF_MEASURE_MODE_CUSTOM_VALUE,
                    DefinitionTemplate::WEIGHT_UNIT_OF_MEASURE_MODE_CUSTOM_ATTRIBUTE,
                    'shipping_weight_unit_of_measure_custom_value',
                    'shipping_weight_unit_of_measure_custom_attribute'
                ),
                'required' => true,
                'create_magento_attribute' => true,
                'class' => 'select M2ePro-required-when-visible',
                'field_extra_attributes' => 'id="shipping_weight_unit_of_measure_mode_tr" style="display: none;"',
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        // ---------------------------------------

        // ---------------------------------------
        // Keywords
        // ---------------------------------------

        $fieldSet = $form->addFieldset('magento_block_amazon_template_description_keywords', [
            'legend' => $this->__('Keywords'), 'collapsable' => true, 'class' => 'entry-edit'
        ]);

        // ---------------------------------------

        $fieldSet->addField('target_audience_mode', self::SELECT,
            [
                'name' => 'definition[target_audience_mode]',
                'label' => $this->__('Target Audience Mode'),
                'title' => $this->__('Target Audience Mode'),
                'values' => [
                    ['value' => DefinitionTemplate::TARGET_AUDIENCE_MODE_NONE, 'label' => $this->__('None')],
                    ['value' => DefinitionTemplate::TARGET_AUDIENCE_MODE_CUSTOM, 'label' => $this->__('Custom Value')],
                ],
                'value' => $this->formData['target_audience_mode'],
                'tooltip' => $this->__('For whom the Product is intended.')
            ]
        );

        // ---------------------------------------

        $this->appendKeywordsFields($fieldSet, 4, 'target_audience');

        $fieldSet->addField('target_audience_mode_separator', self::SEPARATOR, []);

        // ---------------------------------------

        $fieldSet->addField('search_terms_mode', self::SELECT,
            [
                'name' => 'definition[search_terms_mode]',
                'label' => $this->__('Search Terms Mode'),
                'title' => $this->__('Search Terms Mode'),
                'values' => [
                    ['value' => DefinitionTemplate::SEARCH_TERMS_MODE_NONE, 'label' => $this->__('None')],
                    ['value' => DefinitionTemplate::SEARCH_TERMS_MODE_CUSTOM, 'label' => $this->__('Custom Value')],
                ],
                'value' => $this->formData['search_terms_mode'],
                'tooltip' => $this->__(
                    'Provide specific Search Terms to help customers find your Product(s) on Amazon.'
                )
            ]
        );

        // ---------------------------------------

        $this->appendKeywordsFields($fieldSet, 5, 'search_terms');

        $fieldSet->addField('search_terms_mode_separator', self::SEPARATOR, []);

        // ---------------------------------------

        $fieldSet->addField('bullet_points_mode', self::SELECT,
            [
                'name' => 'definition[bullet_points_mode]',
                'label' => $this->__('Bullet Points Mode'),
                'title' => $this->__('Bullet Points Mode'),
                'values' => [
                    ['value' => DefinitionTemplate::BULLET_POINTS_MODE_NONE, 'label' => $this->__('None')],
                    ['value' => DefinitionTemplate::BULLET_POINTS_MODE_CUSTOM, 'label' => $this->__('Custom Value')],
                ],
                'value' => $this->formData['bullet_points_mode'],
                'tooltip' => $this->__('Allows highlighting some of the Product\'s most important qualities.')
            ]
        );

        // ---------------------------------------

        $this->appendKeywordsFields($fieldSet, 5, 'bullet_points');

        // ---------------------------------------

        return parent::_prepareForm();
    }

    // ---------------------------------------

    public function appendKeywordsFields(\Magento\Framework\Data\Form\Element\Fieldset $fieldSet, $fieldCount, $name)
    {
        $helper = $this->getHelper('Data');
        for ($i = 0; $i < $fieldCount; $i++) {

            $button = $this->getMultiElementButton($name, $i);

            $value = '';
            if (!empty($this->formData[$name][$i])) {
                $value = $helper->escapeHtml($this->formData[$name][$i]);
            }

            $fieldSet->addField($name.'_'.$i, 'text',
                [
                    'name' => 'definition['.$name.']['.$i.']',
                    'label' => $this->__('Target Audience Value #%s%', $i + 1),
                    'title' => $this->__('Target Audience Value #%s%', $i + 1),
                    'value' => $value,
                    'onkeyup' => 'AmazonTemplateDescriptionDefinitionObj.multi_element_keyup(\''.$name.'\',this)',
                    'required' => true,
                    'css_class' => $name.'_tr no-margin-bottom',
                    'field_extra_attributes' => 'style="display: none;"',
                    'after_element_html' => $button->toHtml(),
                    'tooltip' => $this->__('Max. 50 characters.')
                ]
            );
        }

        $fieldSet->addField($name.'_actions',
            self::CUSTOM_CONTAINER,
            [
                'text' => <<<HTML
                <a id="show_{$name}_action"
                   href="javascript: void(0);"
                   onclick="AmazonTemplateDescriptionDefinitionObj.showElement('{$name}');">
                   {$this->__('Add New')}
                </a>
                        /
                <a id="hide_{$name}_action"
                   href="javascript: void(0);"
                   onclick="AmazonTemplateDescriptionDefinitionObj.hideElement('{$name}');">
                   {$this->__('Remove')}
                </a>
HTML
,
                'field_extra_attributes' => 'id="'.$name.'_actions_tr" style="display: none;"',
            ]
        );
    }

    // ---------------------------------------

    public function getClearAttributesByInputTypesOptions()
    {
        $optionsResult = [];
        $helper = $this->getHelper('Data');

        foreach($this->allAttributesByInputTypes['text_select'] as $attribute) {
            $optionsResult[] = [
                'value' => $attribute['code'],
                'label' => $helper->escapeHtml($attribute['label']),
            ];
        }

        return $optionsResult;
    }

    public function getAttributesByInputTypesOptions($value, $attributeType, $conditionCallback = false)
    {
        if (!isset($this->generalAttributesByInputTypes[$attributeType])) {
            return [];
        }

        $optionsResult = [];
        $helper = $this->getHelper('Data');

        foreach($this->generalAttributesByInputTypes[$attributeType] as $attribute) {
            $tmpOption = [
                'value' => $value,
                'label' => $helper->escapeHtml($attribute['label']),
                'attrs' => ['attribute_code' => $attribute['code']]
            ];

            if (is_callable($conditionCallback) && $conditionCallback($attribute)) {
                $tmpOption['attrs']['selected'] = 'selected';
            }

            $optionsResult[] = $tmpOption;
        }

        return $optionsResult;
    }

    public function getMeasureUnitsOptions($valueMode, $attributeMode, $valueName, $attributeName)
    {
        $optionsResult = [
            ['value' => '', 'label' => '', 'attrs' => ['style' => 'display: none;']],
            'custom_opt_group' => [
                'value' => [], 'label' => 'Custom Value'
            ],
            'attr_opt_group' => [
                'value' => [], 'label' => 'Magento Attribute',
                'attrs' => ['is_magento_attribute' => true]
            ],
        ];

        foreach($this->getDimensionsUnits() as $unitName) {
            $tmpOption = [
                'value' => $valueMode,
                'label' => $this->__($unitName),
                'attrs' => ['attribute_code' => $unitName]
            ];

            if ($this->formData[$valueName] == $unitName) {
                $tmpOption['attrs']['selected'] = 'selected';
            }

            $optionsResult['custom_opt_group']['value'][] = $tmpOption;
        }

        $forceAddedAttributeOption = $this->getForceAddedAttributeOption(
            $this->formData[$attributeName],
            $this->generalAttributesByInputTypes['text_select'],
            $attributeMode
        );

        if ($forceAddedAttributeOption) {
            $optionsResult['attr_opt_group']['value'][] = $forceAddedAttributeOption;
        }

        $optionsResult['attr_opt_group']['value'] = array_merge($optionsResult['attr_opt_group']['value'],
            $this->getAttributesByInputTypesOptions(
                $attributeMode,
                'text_select',
                function($attribute) use($attributeName) {
                    return $attribute['code'] == $this->formData[$attributeName];
                }
            ));

        return $optionsResult;
    }

    public function getWeightUnitOfMeasureOptions($valueMode, $attributeMode, $valueName, $attributeName)
    {
        $optionsResult = [
            ['value' => '', 'label' => '', 'attrs' => ['style' => 'display: none;']],
            'custom_opt_group' => [
                'value' => [], 'label' => 'Custom Value'
            ],
            'attr_opt_group' => [
                'value' => [], 'label' => 'Magento Attribute',
                'attrs' => ['is_magento_attribute' => true]
            ],
        ];

        foreach($this->getWeightUnits() as $unitName) {
            $tmpOption = [
                'value' => $valueMode,
                'label' => $this->__($unitName),
                'attrs' => ['attribute_code' => $unitName]
            ];

            if ($this->formData[$valueName] == $unitName) {
                $tmpOption['attrs']['selected'] = 'selected';
            }

            $optionsResult['custom_opt_group']['value'][] = $tmpOption;
        }

        $forceAddedAttributeOption = $this->getForceAddedAttributeOption(
            $this->formData[$attributeName],
            $this->generalAttributesByInputTypes['text_select'],
            $attributeMode
        );

        if ($forceAddedAttributeOption) {
            $optionsResult['attr_opt_group']['value'][] = $forceAddedAttributeOption;
        }

        $optionsResult['attr_opt_group']['value'] = array_merge($optionsResult['attr_opt_group']['value'],
            $this->getAttributesByInputTypesOptions(
                $attributeMode,
                'text_select',
                function($attribute) use($attributeName) {
                    return $attribute['code'] == $this->formData[$attributeName];
                }
            ));

        return $optionsResult;
    }

    public function getDimensionsVolumeAttributesOptions($name)
    {
        $optionsResult = [
            ['value' => '', 'label' => '', 'attrs' => ['style' => 'display: none;']],
        ];

        $forceAddedAttributeOption = $this->getForceAddedAttributeOption(
            $this->formData[$name],
            $this->generalAttributesByInputTypes['text_weight']
        );

        if ($forceAddedAttributeOption) {
            $optionsResult[] = $forceAddedAttributeOption;
        }

        $helper = $this->getHelper('Data');
        foreach($this->generalAttributesByInputTypes['text_weight'] as $attribute) {
            $tmpOption = [
                'value' => $attribute['code'],
                'label' => $helper->escapeHtml($attribute['label'])
            ];

            if ($attribute['code'] == $this->formData[$name]) {
                $tmpOption['attrs']['selected'] = 'selected';
            }

            $optionsResult[] = $tmpOption;
        }

        return $optionsResult;
    }

    protected function getAttributeOptions($attributeMode, $attributeName, $attributeType)
    {
        $optionsResult = [];

        $forceAddedAttributeOption = $this->getForceAddedAttributeOption(
            $this->formData[$attributeName],
            $this->generalAttributesByInputTypes[$attributeType],
            $attributeMode
        );

        if ($forceAddedAttributeOption) {
            $optionsResult[] = $forceAddedAttributeOption;
        }

        $optionsResult[] = [
            'value' => $this->getAttributesByInputTypesOptions(
                $attributeMode,
                $attributeType,
                function($attribute) use ($attributeName) {
                    return $attribute['code'] == $this->formData[$attributeName];
                }
            ),
            'label' => 'Magento Attribute',
            'attrs' => ['is_magento_attribute' => true]
        ];

        return $optionsResult;
    }

    // ---------------------------------------

    public function getBrandOptions()
    {
        $optionsResult = [
            ['value' => DefinitionTemplate::BRAND_MODE_NONE, 'label' => $this->__('None')],
            ['value' => DefinitionTemplate::BRAND_MODE_CUSTOM_VALUE, 'label' => $this->__('Custom Value')],
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            DefinitionTemplate::BRAND_MODE_CUSTOM_ATTRIBUTE,
            'brand_custom_attribute',
            'text_select'
        ));
    }

    public function getManufacturerOptions()
    {
        $optionsResult = [
            ['value' => DefinitionTemplate::MANUFACTURER_MODE_NONE, 'label' => $this->__('None')],
            ['value' => DefinitionTemplate::MANUFACTURER_MODE_CUSTOM_VALUE, 'label' => $this->__('Custom Value')],
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            DefinitionTemplate::MANUFACTURER_MODE_CUSTOM_ATTRIBUTE,
            'manufacturer_custom_attribute',
            'text_select'
        ));
    }

    public function getManufacturerPartNumberOptions()
    {
        $optionsResult = [
            ['value' => DefinitionTemplate::MANUFACTURER_PART_NUMBER_MODE_NONE, 'label' => $this->__('None')],
            [
                'value' => DefinitionTemplate::MANUFACTURER_PART_NUMBER_MODE_CUSTOM_VALUE,
                'label' => $this->__('Custom Value')
            ],
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            DefinitionTemplate::MANUFACTURER_PART_NUMBER_MODE_CUSTOM_ATTRIBUTE,
            'manufacturer_part_number_custom_attribute',
            'text_select'
        ));
    }

    public function getPackageQuantityOptions()
    {
        $optionsResult = [
            ['value' => DefinitionTemplate::ITEM_PACKAGE_QUANTITY_MODE_NONE, 'label' => $this->__('None')],
            [
                'value' => DefinitionTemplate::ITEM_PACKAGE_QUANTITY_MODE_CUSTOM_VALUE,
                'label' => $this->__('Custom Value')
            ],
        ];

        $magentoAttributeHelper = $this->getHelper('Magento\Attribute');
        $IsExistInAttributesArray = $magentoAttributeHelper->isExistInAttributesArray(
            $this->formData['item_package_quantity_custom_attribute'],
            $this->generalAttributesByInputTypes['text_select']
        );
        if ($this->formData['item_package_quantity_custom_attribute'] != '' && !$IsExistInAttributesArray) {
            $optionsResult[] = [
                'value' => DefinitionTemplate::ITEM_PACKAGE_QUANTITY_MODE_CUSTOM_ATTRIBUTE,
                'label' => $this->getHelper('Data')->escapeHtml(
                    $magentoAttributeHelper->getAttributeLabel(
                        $this->formData['item_package_quantity_custom_attribute']
                    )
                ),
                'attrs' => [
                    'selected' => 'selected',
                    'attribute_code' => $this->formData['item_package_quantity_custom_attribute']
                ]
            ];
        }

        $optionsResult[] = [
            'value' => $this->getAttributesByInputTypesOptions(
                DefinitionTemplate::ITEM_PACKAGE_QUANTITY_MODE_CUSTOM_ATTRIBUTE,
                'text_select',
                function($attribute) {
                    return $attribute['code'] == $this->formData['item_package_quantity_custom_attribute'];
                }
            ),
            'label' => 'Magento Attribute',
            'attrs' => ['is_magento_attribute' => true]
        ];

        return $optionsResult;
    }

    public function getNumberOfItemsOptions()
    {
        $optionsResult = [
            ['value' => DefinitionTemplate::NUMBER_OF_ITEMS_MODE_NONE, 'label' => $this->__('None')],
            ['value' => DefinitionTemplate::NUMBER_OF_ITEMS_MODE_CUSTOM_VALUE, 'label' => $this->__('Custom Value')],
        ];

        $magentoAttributeHelper = $this->getHelper('Magento\Attribute');
        $IsExistInAttributesArray = $magentoAttributeHelper->isExistInAttributesArray(
            $this->formData['number_of_items_custom_attribute'],
            $this->generalAttributesByInputTypes['text_select']
        );
        if ($this->formData['number_of_items_custom_attribute'] != '' && !$IsExistInAttributesArray) {
            $optionsResult[] = [
                'value' => DefinitionTemplate::NUMBER_OF_ITEMS_MODE_CUSTOM_ATTRIBUTE,
                'label' => $this->getHelper('Data')->escapeHtml(
                    $magentoAttributeHelper->getAttributeLabel($this->formData['number_of_items_custom_attribute'])
                ),
                'attrs' => [
                    'selected' => 'selected',
                    'attribute_code' => $this->formData['item_package_quantity_custom_attribute']
                ]
            ];
        }

        $optionsResult[] = [
            'value' => $this->getAttributesByInputTypesOptions(
                DefinitionTemplate::NUMBER_OF_ITEMS_MODE_CUSTOM_ATTRIBUTE,
                'text_select',
                function($attribute) {
                    return $attribute['code'] == $this->formData['number_of_items_custom_attribute'];
                }
            ),
            'label' => 'Magento Attribute',
            'attrs' => ['is_magento_attribute' => true]
        ];

        return $optionsResult;
    }

    public function getImageMainOptions()
    {
        $optionsResult = [
            ['value' => DefinitionTemplate::IMAGE_MAIN_MODE_NONE, 'label' => $this->__('None')],
            ['value' => DefinitionTemplate::IMAGE_MAIN_MODE_PRODUCT, 'label' => $this->__('Product Base Image')],
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            DefinitionTemplate::IMAGE_MAIN_MODE_ATTRIBUTE,
            'image_main_attribute',
            'text_images'
        ));
    }

    public function getGalleryImageOptions()
    {
        $optionsResult = [
            ['value' => DefinitionTemplate::GALLERY_IMAGES_MODE_NONE, 'label' => $this->__('None')],
            'opt_group' => ['value' => [], 'label' => 'Product Images Quantity'],
        ];

        for ($i = 1; $i <= 8; $i++) {
            if ($i == 1) {
                $tempOption = ['value' => '-1', 'label' => 1, 'attrs' => ['attribute_code' => 1]];

                if ($this->formData['gallery_images_limit'] == 1
                    && ($this->formData['gallery_images_mode'] != DefinitionTemplate::GALLERY_IMAGES_MODE_NONE)) {
                    $tempOption['attrs']['selected'] = 'selected';
                }
            } else {
                $tempOption = [
                    'value' => DefinitionTemplate::GALLERY_IMAGES_MODE_PRODUCT,
                    'label' => $this->__('Up to') . ' ' . $i,
                    'attrs' => ['attribute_code' => $i]
                ];

                if ($this->formData['gallery_images_limit'] == $i) {
                    $tempOption['attrs']['selected'] = 'selected';
                }
            }

            $optionsResult['opt_group']['value'][] = $tempOption;
        }

        $forceAddedAttributeOption = $this->getForceAddedAttributeOption(
            $this->formData['gallery_images_attribute'],
            $this->generalAttributesByInputTypes['text_images'],
            DefinitionTemplate::GALLERY_IMAGES_MODE_ATTRIBUTE
        );

        if ($forceAddedAttributeOption) {
            $optionsResult[] = $forceAddedAttributeOption;
        }

        $optionsResult[] = [
            'value' => $this->getAttributesByInputTypesOptions(
                DefinitionTemplate::GALLERY_IMAGES_MODE_ATTRIBUTE,
                'text_images',
                function($attribute) {
                    return $attribute['code'] == $this->formData['gallery_images_attribute'];
                }
            ),
            'label' => 'Magento Attribute',
            'attrs' => ['is_magento_attribute' => true]
        ];

        return $optionsResult;
    }

    public function getSwatchImageOptions()
    {
        $optionsResult = [
            ['value' => DefinitionTemplate::IMAGE_VARIATION_DIFFERENCE_MODE_NONE, 'label' => $this->__('None')],
            [
                'value' => DefinitionTemplate::IMAGE_VARIATION_DIFFERENCE_MODE_PRODUCT,
                'label' => $this->__('Product Base Image')
            ],
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            DefinitionTemplate::IMAGE_VARIATION_DIFFERENCE_MODE_ATTRIBUTE,
            'image_variation_difference_attribute',
            'text_images'
        ));
    }

    public function getItemDimensionsWeightOptions()
    {
        $optionsResult = [
            ['value' => DefinitionTemplate::WEIGHT_MODE_NONE, 'label' => $this->__('None')],
            ['value' => DefinitionTemplate::WEIGHT_MODE_CUSTOM_VALUE, 'label' => $this->__('Custom Value')],
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            DefinitionTemplate::WEIGHT_MODE_CUSTOM_ATTRIBUTE,
            'item_dimensions_weight_custom_attribute',
            'text_weight'
        ));
    }

    public function getPackageWeightModeOptions()
    {
        $optionsResult = [
            ['value' => DefinitionTemplate::WEIGHT_MODE_NONE, 'label' => $this->__('None')],
            ['value' => DefinitionTemplate::WEIGHT_MODE_CUSTOM_VALUE, 'label' => $this->__('Custom Value')],
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            DefinitionTemplate::WEIGHT_MODE_CUSTOM_ATTRIBUTE,
            'package_weight_custom_attribute',
            'text_weight'
        ));
    }

    public function getShippingWeightModeOptions()
    {
        $optionsResult = [
            ['value' => DefinitionTemplate::WEIGHT_MODE_NONE, 'label' => $this->__('None')],
            ['value' => DefinitionTemplate::WEIGHT_MODE_CUSTOM_VALUE, 'label' => $this->__('Custom Value')],
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            DefinitionTemplate::WEIGHT_MODE_CUSTOM_ATTRIBUTE,
            'shipping_weight_custom_attribute',
            'text_weight'
        ));
    }

    //########################################

    public function getFormData()
    {
        $default = [
            'title_mode'     => DefinitionTemplate::TITLE_MODE_PRODUCT,
            'title_template' => '',

            'brand_mode'             => DefinitionTemplate::BRAND_MODE_NONE,
            'brand_custom_value'     => '',
            'brand_custom_attribute' => '',

            'manufacturer_mode'             => DefinitionTemplate::MANUFACTURER_MODE_NONE,
            'manufacturer_custom_value'     => '',
            'manufacturer_custom_attribute' => '',

            'manufacturer_part_number_mode'             => DefinitionTemplate::MANUFACTURER_PART_NUMBER_MODE_NONE,
            'manufacturer_part_number_custom_value'     => '',
            'manufacturer_part_number_custom_attribute' => '',

            // ---

            'item_package_quantity_mode'             => DefinitionTemplate::ITEM_PACKAGE_QUANTITY_MODE_NONE,
            'item_package_quantity_custom_value'     => '',
            'item_package_quantity_custom_attribute' => '',

            'number_of_items_mode'             => DefinitionTemplate::NUMBER_OF_ITEMS_MODE_NONE,
            'number_of_items_custom_value'     => '',
            'number_of_items_custom_attribute' => '',

            // ---

            'item_dimensions_volume_mode'                    => DefinitionTemplate::DIMENSION_VOLUME_MODE_NONE,
            'item_dimensions_volume_length_custom_value'     => '',
            'item_dimensions_volume_width_custom_value'      => '',
            'item_dimensions_volume_height_custom_value'     => '',
            'item_dimensions_volume_length_custom_attribute' => '',
            'item_dimensions_volume_width_custom_attribute'  => '',
            'item_dimensions_volume_height_custom_attribute' => '',

            'item_dimensions_volume_unit_of_measure_mode'
                    => DefinitionTemplate::DIMENSION_VOLUME_UNIT_OF_MEASURE_MODE_CUSTOM_VALUE,
            'item_dimensions_volume_unit_of_measure_custom_value'     => '',
            'item_dimensions_volume_unit_of_measure_custom_attribute' => '',

            'item_dimensions_weight_mode' => DefinitionTemplate::WEIGHT_MODE_NONE,
            'item_dimensions_weight_custom_value'     => '',
            'item_dimensions_weight_custom_attribute' => '',

            'item_dimensions_weight_unit_of_measure_mode'
                    => DefinitionTemplate::WEIGHT_UNIT_OF_MEASURE_MODE_CUSTOM_VALUE,
            'item_dimensions_weight_unit_of_measure_custom_value'     => '',
            'item_dimensions_weight_unit_of_measure_custom_attribute' => '',

            // ---

            'package_dimensions_volume_mode'                    => DefinitionTemplate::DIMENSION_VOLUME_MODE_NONE,
            'package_dimensions_volume_length_custom_value'     => '',
            'package_dimensions_volume_width_custom_value'      => '',
            'package_dimensions_volume_height_custom_value'     => '',
            'package_dimensions_volume_length_custom_attribute' => '',
            'package_dimensions_volume_width_custom_attribute'  => '',
            'package_dimensions_volume_height_custom_attribute' => '',

            'package_dimensions_volume_unit_of_measure_mode'
                    => DefinitionTemplate::DIMENSION_VOLUME_UNIT_OF_MEASURE_MODE_CUSTOM_VALUE,
            'package_dimensions_volume_unit_of_measure_custom_value'     => '',
            'package_dimensions_volume_unit_of_measure_custom_attribute' => '',

            // ---

            'package_weight_mode'             => DefinitionTemplate::WEIGHT_MODE_NONE,
            'package_weight_custom_value'     => '',
            'package_weight_custom_attribute' => '',

            'package_weight_unit_of_measure_mode' => DefinitionTemplate::WEIGHT_UNIT_OF_MEASURE_MODE_CUSTOM_VALUE,
            'package_weight_unit_of_measure_custom_value' => '',
            'package_weight_unit_of_measure_custom_attribute' => '',

            'shipping_weight_mode'             => DefinitionTemplate::WEIGHT_MODE_NONE,
            'shipping_weight_custom_value'     => '',
            'shipping_weight_custom_attribute' => '',

            'shipping_weight_unit_of_measure_mode'
                    => DefinitionTemplate::WEIGHT_UNIT_OF_MEASURE_MODE_CUSTOM_VALUE,
            'shipping_weight_unit_of_measure_custom_value'     => '',
            'shipping_weight_unit_of_measure_custom_attribute' => '',

            // ---

            'target_audience_mode' => DefinitionTemplate::TARGET_AUDIENCE_MODE_NONE,
            'target_audience'      => $this->getHelper('Data')->jsonEncode([]),

            'search_terms_mode' => DefinitionTemplate::SEARCH_TERMS_MODE_NONE,
            'search_terms'      => $this->getHelper('Data')->jsonEncode([]),

            'image_main_mode'      => DefinitionTemplate::IMAGE_MAIN_MODE_PRODUCT,
            'image_main_attribute' => '',

            'image_variation_difference_mode'      => DefinitionTemplate::IMAGE_VARIATION_DIFFERENCE_MODE_NONE,
            'image_variation_difference_attribute' => '',

            'gallery_images_mode'      => DefinitionTemplate::GALLERY_IMAGES_MODE_NONE,
            'gallery_images_limit'     => 1,
            'gallery_images_attribute' => '',

            'bullet_points_mode' => DefinitionTemplate::BULLET_POINTS_MODE_NONE,
            'bullet_points'      => $this->getHelper('Data')->jsonEncode([]),

            'description_mode'     => DefinitionTemplate::DESCRIPTION_MODE_NONE,
            'description_template' => '',
        ];

        if (!$this->templateModel->getId()) {
            return $default;
        }
        $formData = array_merge(
            $default,
            $this->templateModel->getChildObject()->getDefinitionTemplate()->getData()
        );

        foreach(['target_audience', 'bullet_points', 'search_terms'] as $formField) {
            if (empty($formData[$formField])) {
                continue;
            }

            $formData[$formField] = $this->getHelper('Data')->jsonDecode($formData[$formField]);
        }

        $formData['package_weight_custom_value'] == 0  && $formData['package_weight_custom_value'] = '';
        $formData['shipping_weight_custom_value'] == 0 && $formData['shipping_weight_custom_value'] = '';
        $formData['item_dimensions_weight_custom_value'] == 0 && $formData['item_dimensions_weight_custom_value'] = '';

        return $formData;
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Amazon\Template\Description\Definition')
        );

        return parent::_beforeToHtml();
    }

    //########################################

    private function getMultiElementButton($type, $index)
    {
        return $this->createBlock('Magento\Button\MagentoAttribute')->addData([
            'label' => $this->__('Insert Attribute'),
            'destination_id' => $type.'_'.$index,
            'magento_attributes' => $this->getClearAttributesByInputTypesOptions(),
            'on_click_callback' => "function() {
                AmazonTemplateDescriptionDefinitionObj.multi_element_keyup('{$type}',{value:' '});
            }",
            'class' => 'primary attributes-container-td',
            'style' => 'display: block; margin: 0; float: right;',
            'select_custom_attributes' => [
                'allowed_attribute_types' => 'text,select',
                'apply_to_all_attribute_sets' => 0
            ]
        ]);
    }

    //########################################

    public function getForceAddedAttributeOption($attributeCode, $availableValues, $value = null)
    {
        if (empty($attributeCode) ||
            $this->getHelper('Magento\Attribute')->isExistInAttributesArray($attributeCode, $availableValues)) {
            return '';
        }

        $attributeLabel = $this->getHelper('Data')
            ->escapeHtml($this->getHelper('Magento\Attribute')->getAttributeLabel($attributeCode));

        $result = ['value' => $value, 'label' => $attributeLabel];

        if (is_null($value)) {
            return $result;
        }

        $result['attrs'] = ['attrbiute_code' => $attributeCode];
        return $result;
    }

    // ---------------------------------------

    public function getWeightUnits()
    {
        return array(
            'GR',
            'KG',
            'OZ',
            'LB',
            'MG'
        );
    }

    public function getDimensionsUnits()
    {
        return array(
            'MM',
            'CM',
            'M',
            'IN',
            'FT'
        );
    }

    //########################################
}