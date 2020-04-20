<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\Description\Edit;

use \Ess\M2ePro\Model\Walmart\Template\Description as Description;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Description\Edit\Form
 */
class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    public $templateModel;
    public $formData = [];

    public $generalAttributesByInputTypes = [];
    public $allAttributesByInputTypes     = [];

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartTemplateDescriptionEditForm');
        // ---------------------------------------

        $this->templateModel   = $this->getHelper('Data\GlobalData')->getValue('tmp_template');
        $this->formData        = $this->getFormData();

        /** @var \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper */
        $magentoAttributeHelper = $this->getHelper('Magento\Attribute');
        $allAttributes          = $magentoAttributeHelper->getAll();
        $generalAttributes      = $magentoAttributeHelper->getGeneralFromAllAttributeSets();

        $this->generalAttributesByInputTypes = [
            'text' => $magentoAttributeHelper->filterByInputTypes($generalAttributes, ['text']),
            'text_select' => $magentoAttributeHelper->filterByInputTypes($generalAttributes, ['text', 'select']),
            'text_weight' => $magentoAttributeHelper->filterByInputTypes($generalAttributes, ['text', 'weight']),
            'text_images' => $magentoAttributeHelper->filterByInputTypes(
                $generalAttributes,
                ['text', 'image', 'media_image', 'gallery', 'multiline', 'textarea', 'select', 'multiselect']
            ),
            'text_price' => $magentoAttributeHelper->filterByInputTypes($generalAttributes, ['text', 'price']),
        ];

        $this->allAttributesByInputTypes = [
            'text_select' => $magentoAttributeHelper->filterByInputTypes($allAttributes, ['text', 'select']),
        ];
    }

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(['data' => [
            'id'      => 'edit_form',
            'method'  => 'post',
            'action'  => $this->getUrl('*/*/save'),
            'enctype' => 'multipart/form-data'
        ]]);

        $form->addField(
            'walmart_description_general_help',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    <<<HTML
                <p>Description Policy highlights the key details and features of Product as a physical object that
                is for sale. Providing maximum Product information and high-quality images is critical if you want
                to enhance the buyers’ shopping experience.</p><br>
                <p><strong>Note:</strong> Description Policy is required when you create a new offer on Walmart.</p>

HTML
                ),
                'class' => 'marketplace-required-field marketplace-required-field-id-not-null'
            ]
        );

        $fieldSet = $form->addFieldset('magento_block_walmart_template_description_general', [
            'legend' => $this->__('General'), 'collapsable' => false
        ]);

        $fieldSet->addField(
            'title',
            'text',
            [
                'name' => 'title',
                'label' => $this->__('Title'),
                'title' => $this->__('Title'),
                'value' => $this->formData['title'],
                'class' => 'input-text M2ePro-description-template-title',
                'required' => true,
                'tooltip' => $this->__('Policy Title for your internal use.')
            ]
        );

        $fieldSet = $form->addFieldset('magento_block_walmart_template_description_details', [
            'legend' => $this->__('Details'), 'collapsable' => false
        ]);

        $fieldSet->addField(
            'title_mode',
            self::SELECT,
            [
                'name' => 'title_mode',
                'label' => $this->__('Title'),
                'title' => $this->__('Title'),
                'values' => [
                    Description::TITLE_MODE_PRODUCT => $this->__('Product Name'),
                    Description::TITLE_MODE_CUSTOM => $this->__('Custom Value'),
                ],
                'value' => $this->formData['title_mode'],
                'required' => true,
                'tooltip' => $this->__('Item Name that will be available on Walmart.')
            ]
        );

        $preparedAttributes = [];
        foreach ($this->allAttributesByInputTypes['text_select'] as $attribute) {
            $preparedAttributes[] = [
                'value' => $attribute['code'],
                'label' => $attribute['label'],
            ];
        }

        $fieldSet->addField(
            'title_template',
            'text',
            [
                'container_id' => 'custom_title_tr',
                'label' => $this->__('Title Value'),
                'value' => $this->formData['title_template'],
                'name' => 'title_template',
                'class' => 'input-text-title M2ePro-required-when-visible',
                'required' => true,
                'after_element_html' => $this->createBlock('Magento_Button_MagentoAttribute')->addData([
                    'label' => $this->__('Insert Attribute'),
                    'destination_id' => 'title_template',
                    'magento_attributes' => $preparedAttributes,
                    'class' => 'select_attributes_for_title_button primary',
                    'select_custom_attributes' => [
                        'allowed_attribute_types' => 'text,select,multiselect,boolean,price,date',
                        'apply_to_all_attribute_sets' => 0
                    ],
                ])->toHtml()
            ]
        );

        $fieldSet->addField(
            'brand_custom_attribute',
            'hidden',
            [
                'name' => 'brand_custom_attribute',
                'value' => $this->formData['brand_custom_attribute']
            ]
        );

        // ---------------------------------------

        $defaultValue = '';
        if ($this->formData['brand_mode'] == Description::BRAND_MODE_CUSTOM_VALUE) {
            $defaultValue = $this->formData['brand_mode'];
        }

        $fieldSet->addField(
            'brand_mode',
            self::SELECT,
            [
                'name' => 'brand_mode',
                'label' => $this->__('Brand'),
                'title' => $this->__('Brand'),
                'class' => 'select',
                'values' => $this->getBrandOptions(),
                'value' => $defaultValue,
                'required' => true,
                'create_magento_attribute' => true,
                'tooltip' => $this->__('Specify the Item brand.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        $fieldSet->addField(
            'brand_custom_value',
            'text',
            [
                'name' => 'brand_custom_value',
                'label' => $this->__('Brand Value'),
                'title' => $this->__('Brand Value'),
                'value' => $this->formData['brand_custom_value'],
                'class' => 'input-text M2ePro-required-when-visible',
                'required' => true,
                'field_extra_attributes' => 'id="brand_custom_value_tr" style="display: none;"',
            ]
        );

        // ---------------------------------------

        $fieldSet->addField(
            'manufacturer_custom_attribute',
            'hidden',
            [
                'name' => 'manufacturer_custom_attribute',
                'value' => $this->formData['manufacturer_custom_attribute']
            ]
        );

        $defaultValue = '';
        if ($this->formData['manufacturer_mode'] == Description::MANUFACTURER_MODE_NONE ||
            $this->formData['manufacturer_mode'] == Description::MANUFACTURER_MODE_CUSTOM_VALUE) {
            $defaultValue = $this->formData['manufacturer_mode'];
        }

        $fieldSet->addField(
            'manufacturer_mode',
            self::SELECT,
            [
                'name' => 'manufacturer_mode',
                'label' => $this->__('Manufacturer'),
                'title' => $this->__('Manufacturer'),
                'values' => $this->getManufacturerOptions(),
                'value' => $defaultValue,
                'class' => 'select',
                'required' => true,
                'create_magento_attribute' => true,
                'tooltip' => $this->__('Specify the Item manufacturer name.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        $fieldSet->addField(
            'manufacturer_custom_value',
            'text',
            [
                'name' => 'manufacturer_custom_value',
                'label' => $this->__('Manufacturer Value'),
                'title' => $this->__('Manufacturer Value'),
                'value' => $this->formData['manufacturer_custom_value'],
                'class' => 'input-text M2ePro-required-when-visible',
                'required' => true,
                'field_extra_attributes' => 'id="manufacturer_custom_value_tr" style="display: none;"',
            ]
        );

        // ---------------------------------------

        $fieldSet->addField(
            'manufacturer_part_number_custom_attribute',
            'hidden',
            [
                'name' => 'manufacturer_part_number_custom_attribute',
                'value' => $this->formData['manufacturer_part_number_custom_attribute']
            ]
        );

        $defaultValue = '';
        if ($this->formData['manufacturer_part_number_mode'] == Description::MANUFACTURER_PART_NUMBER_MODE_NONE ||
            $this->formData['manufacturer_part_number_mode'] == Description::MANUFACTURER_PART_NUMBER_MODE_CUSTOM_VALUE
        ) {
            $defaultValue = $this->formData['manufacturer_part_number_mode'];
        }

        $fieldSet->addField(
            'manufacturer_part_number_mode',
            self::SELECT,
            [
                'name' => 'manufacturer_part_number_mode',
                'label' => $this->__('Manufacturer Part Number'),
                'title' => $this->__('Manufacturer Part Number'),
                'values' => $this->getManufacturerPartNumberOptions(),
                'value' => $defaultValue,
                'class' => 'select',
                'create_magento_attribute' => true,
                'tooltip' => $this->__('A unique identifier assigned to Product by manufacturer.
                May be identical to the Model Number.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldSet->addField(
            'manufacturer_part_number_custom_value',
            'text',
            [
                'name' => 'manufacturer_part_number_custom_value',
                'label' => $this->__('Manufacturer Part Number Value'),
                'title' => $this->__('Manufacturer Part Number Value'),
                'value' => $this->formData['manufacturer_part_number_custom_value'],
                'class' => 'input-text M2ePro-required-when-visible',
                'required' => true,
                'field_extra_attributes' => 'id="manufacturer_part_number_custom_value_tr" style="display: none;"',
            ]
        );

        // ---------------------------------------

        $fieldSet->addField(
            'model_number_custom_attribute',
            'hidden',
            [
                'name' => 'model_number_custom_attribute',
                'value' => $this->formData['model_number_custom_attribute']
            ]
        );

        $defaultValue = '';
        if ($this->formData['model_number_mode'] == Description::MODEL_NUMBER_MODE_NONE ||
            $this->formData['model_number_mode'] == Description::MODEL_NUMBER_MODE_CUSTOM_VALUE
        ) {
            $defaultValue = $this->formData['model_number_mode'];
        }

        $fieldSet->addField(
            'model_number_mode',
            self::SELECT,
            [
                'name' => 'model_number_mode',
                'label' => $this->__('Model Number'),
                'title' => $this->__('Model Number'),
                'values' => $this->getModelNumberOptions(),
                'value' => $defaultValue,
                'class' => 'select',
                'create_magento_attribute' => true,
                'tooltip' => $this->__('A unique number given to Product model by manufacturer.
                May be identical to the Manufacturer Part Number.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldSet->addField(
            'model_number_custom_value',
            'text',
            [
                'name' => 'model_number_custom_value',
                'label' => $this->__('Model Number Value'),
                'title' => $this->__('Model Number Value'),
                'value' => $this->formData['model_number_custom_value'],
                'class' => 'input-text M2ePro-required-when-visible',
                'required' => true,
                'field_extra_attributes' => 'id="model_number_custom_value_tr" style="display: none;"',
            ]
        );

        // ---------------------------------------

        $fieldSet = $form->addFieldset('magento_block_walmart_template_description_price', [
            'legend' => $this->__('Price'), 'collapsable' => false
        ]);

        $fieldSet->addField(
            'msrp_rrp_custom_attribute',
            'hidden',
            [
                'name' => 'msrp_rrp_custom_attribute',
                'value' => $this->formData['msrp_rrp_custom_attribute']
            ]
        );

        $defaultValue = '';
        if ($this->formData['msrp_rrp_mode'] == Description::MSRP_RRP_MODE_NONE) {
            $defaultValue = $this->formData['msrp_rrp_mode'];
        }

        $fieldSet->addField(
            'msrp_rrp_mode',
            self::SELECT,
            [
                'name' => 'msrp_rrp_mode',
                'label' => $this->__('MSRP / RRP'),
                'title' => $this->__('MSRP / RRP'),
                'values' => $this->getMsrpRrpModeOptions(),
                'value' => $defaultValue,
                'class' => 'select',
                'create_magento_attribute' => true,
                'tooltip' => $this->__('Manufacturer\'s suggested retail price (MSRP), or recommended retail price (RRP)
                                    is a price at which manufacturer recommends the retailers to sell the Product<br/>
                                    <strong>Note:</strong> it is not the price that buyers will pay for your Item. Your
                                    retail price should be set in Selling Policy.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,price');

        // ---------------------------------------

        $fieldSet = $form->addFieldset('magento_block_walmart_template_description_image', [
            'legend' => $this->__('Images'), 'collapsable' => false
        ]);

        // ---------------------------------------

        $fieldSet->addField(
            'image_main_attribute',
            'hidden',
            [
                'name' => 'image_main_attribute',
                'value' => $this->formData['image_main_attribute']
            ]
        );

        $defaultValue = '';
        if ($this->formData['image_main_mode'] == Description::IMAGE_MAIN_MODE_NONE ||
            $this->formData['image_main_mode'] == Description::IMAGE_MAIN_MODE_PRODUCT
        ) {
            $defaultValue = $this->formData['image_main_mode'];
        }

        $fieldSet->addField(
            'image_main_mode',
            self::SELECT,
            [
                'name' => 'image_main_mode',
                'label' => $this->__('Main Image'),
                'title' => $this->__('Main Image'),
                'values' => $this->getImageMainOptions(),
                'value' => $defaultValue,
                'class' => 'select',
                'create_magento_attribute' => true,
                'tooltip' => $this->__('The primary Product image that will be shown on your Walmart
                                Item page and next to your Item in the search results.<br>
                                <strong>Note:</strong> Selected Magento Attribute should contain absolute url. e.g.<br>
                                <i>http://mymagentostore.com/images/main_image.jpg</i>')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,textarea,select,multiselect');

        // ---------------------------------------

        $fieldSet->addField(
            'gallery_images_limit',
            'hidden',
            [
                'name' => 'gallery_images_limit',
                'value' => $this->formData['gallery_images_limit'],
            ]
        );

        $fieldSet->addField(
            'gallery_images_attribute',
            'hidden',
            [
                'name' => 'gallery_images_attribute',
                'value' => $this->formData['gallery_images_attribute'],
            ]
        );

        $fieldSet->addField(
            'gallery_images_mode',
            self::SELECT,
            [
                'container_id' => 'gallery_images_mode_tr',
                'name' => 'gallery_images_mode',
                'label' => $this->__('Additional Images'),
                'values' => $this->getGalleryImagesModeOptions(),
                'create_magento_attribute' => true,
                'tooltip' => $this->__(
                    'Additional images that may show different views of the Product. Images will be displayed as
                    thumbnails under the Main Image on your Walmart Item page.<br>
                    <strong>Note:</strong> Magento Attributes with Text, Multiple Select or Dropdown type can be used.
                    Enter a comma separated list of absolute image urls, e.g.<br>
                    <i>http://mymagentostore.com/images/image1.jpg, http://mymagentostore.com/images/image2.jpg</i>'
                )
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,textarea,select,multiselect');

        // ---------------------------------------

        $fieldSet->addField(
            'image_variation_difference_attribute',
            'hidden',
            [
                'name' => 'image_variation_difference_attribute',
                'value' => $this->formData['image_variation_difference_attribute']
            ]
        );

        $defaultValue = '';
        if ($this->formData['image_variation_difference_mode'] == Description::IMAGE_VARIATION_DIFFERENCE_MODE_NONE ||
            $this->formData['image_variation_difference_mode'] == Description::IMAGE_VARIATION_DIFFERENCE_MODE_PRODUCT
        ) {
            $defaultValue = $this->formData['image_variation_difference_mode'];
        }

        $fieldSet->addField(
            'image_variation_difference_mode',
            self::SELECT,
            [
                'name' => 'image_variation_difference_mode',
                'label' => $this->__('Swatch Image'),
                'title' => $this->__('Swatch Image'),
                'values' => $this->getImageVariationDifferenceModeOptions(),
                'value' => $defaultValue,
                'class' => 'select',
                'create_magento_attribute' => true,
                'tooltip' => $this->__('
                    Image that shows a selected Item Variation on Walmart Item page.<br><br>

                    <strong>Note:</strong> The option is applied only for Configurable and Grouped Magento
                    Products.<br>
                    Image will be taken from the Child Products of Magento Variational Product.<br><br>

                    <strong>Note:</strong> To show the Swatch Image on Walmart, you must define a Swatch
                    Variant Attribute.<br>
                    In M2E Pro Listing, click Manage Variations next to the Variational Item.<br>
                    Select the Swatch Variant Attribute under the Settings
                    tab of Manage Variations pop-up.
                ')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,textarea,select,multiselect');

        // ---------------------------------------

        $fieldSet = $form->addFieldset('magento_block_walmart_template_description_description', [
            'legend' => $this->__('Description'), 'collapsable' => false
        ]);

        // ---------------------------------------

        $fieldSet->addField(
            'description_mode',
            'select',
            [
                'label' => $this->__('Description'),
                'name' => 'description_mode',
                'values' => [
                    Description::DESCRIPTION_MODE_PRODUCT => $this->__('Product Description'),
                    Description::DESCRIPTION_MODE_SHORT   => $this->__('Product Short Description'),
                    Description::DESCRIPTION_MODE_CUSTOM  => $this->__('Custom Value'),
                ],
                'value' => $this->formData['description_mode'],
                'tooltip' => $this->__('
                        The key Product details and features that will be displayed on your Walmart Item page.<br>
                        You can use Magento Product data or create a Custom description.<br>
                        To complete your description with Magento Attribute data, use the Custom Inserts tool.')
            ]
        );

        $fieldSet->addField(
            'description_template',
            'editor',
            [
                'container_id' => 'description_template_tr',
                'css_class' => 'c-custom_description_tr _required',
                'label' => $this->__('Description Value'),
                'name' => 'description_template',
                'value' => $this->formData['description_template'],
                'class' => ' admin__control-textarea left M2ePro-validate-description-template',
                'wysiwyg' => false,
                'after_element_html' => $this->createBlock('Magento_Button_MagentoAttribute')->addData([
                    'label' => $this->__('Custom Inserts'),
                    'destination_id' => 'description_template',
                    'magento_attributes' => $preparedAttributes,
                    'class' => 'select_attributes_for_description_button primary',
                    'select_custom_attributes' => [
                        'allowed_attribute_types' => 'text,select,multiselect,boolean,price,date',
                        'apply_to_all_attribute_sets' => 0
                    ],
                ])->toHtml()
            ]
        );

        // ---------------------------------------

        $fieldSet = $form->addFieldset('magento_block_walmart_template_description_packaging', [
            'legend' => $this->__('Packaging'), 'collapsable' => false
        ]);

        // ---------------------------------------

        $fieldSet->addField(
            'multipack_quantity_custom_attribute',
            'hidden',
            [
                'name' => 'multipack_quantity_custom_attribute',
                'value' => $this->formData['multipack_quantity_custom_attribute']
            ]
        );

        $defaultValue = '';
        if ($this->formData['multipack_quantity_mode'] == Description::MULTIPACK_QUANTITY_MODE_NONE ||
            $this->formData['multipack_quantity_mode'] == Description::MULTIPACK_QUANTITY_MODE_CUSTOM_VALUE
        ) {
            $defaultValue = $this->formData['multipack_quantity_mode'];
        }

        $fieldSet->addField(
            'multipack_quantity_mode',
            self::SELECT,
            [
                'name' => 'multipack_quantity_mode',
                'label' => $this->__('Multipack Quantity'),
                'title' => $this->__('Multipack Quantity'),
                'values' => $this->getMultipackQuantityModeOptions(),
                'value' => $defaultValue,
                'class' => 'select',
                'create_magento_attribute' => true,
                'tooltip' => $this->__('The number of identical, individually packaged-for-sale
                                Items inside the Product.<br/>
                                <strong>For example</strong>, the case/box containing 3 packs of 50
                                pencils has Multipack Quantity = 3.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        $fieldSet->addField(
            'multipack_quantity_custom_value',
            'text',
            [
                'name' => 'multipack_quantity_custom_value',
                'label' => $this->__('Number of Items Value'),
                'title' => $this->__('Number of Items Value'),
                'value' => $this->formData['multipack_quantity_custom_value'],
                'class' => 'input-text M2ePro-required-when-visible',
                'required' => true,
                'field_extra_attributes' => 'id="multipack_quantity_custom_value_tr" style="display: none;"',
            ]
        );

        // ---------------------------------------

        $fieldSet->addField(
            'count_per_pack_custom_attribute',
            'hidden',
            [
                'name' => 'count_per_pack_custom_attribute',
                'value' => $this->formData['count_per_pack_custom_attribute']
            ]
        );

        $defaultValue = '';
        if ($this->formData['count_per_pack_mode'] == Description::COUNT_PER_PACK_MODE_NONE ||
            $this->formData['count_per_pack_mode'] == Description::COUNT_PER_PACK_MODE_CUSTOM_VALUE
        ) {
            $defaultValue = $this->formData['count_per_pack_mode'];
        }

        $fieldSet->addField(
            'count_per_pack_mode',
            self::SELECT,
            [
                'name' => 'count_per_pack_mode',
                'label' => $this->__('Count Per Pack'),
                'title' => $this->__('Count Per Pack'),
                'values' => $this->getCountPerPackModeOptions(),
                'value' => $defaultValue,
                'class' => 'select',
                'create_magento_attribute' => true,
                'tooltip' => $this->__('The number of identical Items included in each individual pack
                                given by the Multipack Quantity attribute.<br>
                                <strong>For example</strong>, the case/box containing 3 packs of 50 pencils
                                has Count Per Pack = 50.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        $fieldSet->addField(
            'count_per_pack_custom_value',
            'text',
            [
                'name' => 'count_per_pack_custom_value',
                'label' => $this->__('Package Quantity Value'),
                'title' => $this->__('Package Quantity Value'),
                'value' => $this->formData['count_per_pack_custom_value'],
                'class' => 'input-text M2ePro-required-when-visible',
                'required' => true,
                'field_extra_attributes' => 'id="count_per_pack_custom_value_tr" style="display: none;"',
            ]
        );

        // ---------------------------------------

        $fieldSet->addField(
            'total_count_custom_attribute',
            'hidden',
            [
                'name' => 'total_count_custom_attribute',
                'value' => $this->formData['total_count_custom_attribute']
            ]
        );

        $defaultValue = '';
        if ($this->formData['total_count_mode'] == Description::TOTAL_COUNT_MODE_NONE ||
            $this->formData['total_count_mode'] == Description::TOTAL_COUNT_MODE_CUSTOM_VALUE
        ) {
            $defaultValue = $this->formData['total_count_mode'];
        }

        $fieldSet->addField(
            'total_count_mode',
            self::SELECT,
            [
                'name' => 'total_count_mode',
                'label' => $this->__('Total Count'),
                'title' => $this->__('Total Count'),
                'values' => $this->getTotalCountModeOptions(),
                'value' => $defaultValue,
                'class' => 'select',
                'create_magento_attribute' => true,
                'tooltip' => $this->__('The total number of identical Items inside the Product. Total Count is a
                                result of multiplication of Multipack Quantity by Count Per Pack.<br>
                                <strong>For example</strong>, the case/box containing 3 packs of 50 pencils
                                has Total Count = 150.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        $fieldSet->addField(
            'total_count_custom_value',
            'text',
            [
                'name' => 'total_count_custom_value',
                'label' => $this->__('Total Count Value'),
                'title' => $this->__('Total Count Value'),
                'value' => $this->formData['total_count_custom_value'],
                'class' => 'input-text M2ePro-required-when-visible',
                'required' => true,
                'field_extra_attributes' => 'id="total_count_custom_value_tr" style="display: none;"',
            ]
        );

        // ---------------------------------------

        $fieldSet = $form->addFieldset('magento_block_walmart_template_description_additional', [
            'legend' => $this->__('Additional'), 'collapsable' => false
        ]);

        // ---------------------------------------

        $fieldSet->addField(
            'key_features_mode',
            self::SELECT,
            [
                'name' => 'key_features_mode',
                'label' => $this->__('Key Features'),
                'title' => $this->__('Key Features'),
                'values' => [
                    ['value' => Description::KEY_FEATURES_MODE_NONE, 'label' => $this->__('None')],
                    ['value' => Description::KEY_FEATURES_MODE_CUSTOM, 'label' => $this->__('Custom Value')],
                ],
                'value' => $this->formData['key_features_mode'],
                'tooltip' => $this->__('Specify up to 5 features that highlight the most
                                essential information about your Item.<br>
                                The text will appear as bulleted list on your Walmart Item page
                                and in the search results.')
            ]
        );

        $this->appendKeywordsFields($fieldSet, 5, 'key_features', $this->__('Key Features'));

        $fieldSet->addField('key_features_mode_separator', self::SEPARATOR, []);

        // ---------------------------------------

        $fieldSet->addField(
            'other_features_mode',
            self::SELECT,
            [
                'name' => 'other_features_mode',
                'label' => $this->__('Other Features'),
                'title' => $this->__('Other Features'),
                'values' => [
                    ['value' => Description::OTHER_FEATURES_MODE_NONE, 'label' => $this->__('None')],
                    ['value' => Description::OTHER_FEATURES_MODE_CUSTOM, 'label' => $this->__('Custom Value')],
                ],
                'value' => $this->formData['other_features_mode'],
                'tooltip' => $this->__('Specify up to 5 additional features that describe your Item.')
            ]
        );

        $this->appendKeywordsFields($fieldSet, 5, 'other_features', $this->__('Other Features'));

        $fieldSet->addField('other_features_mode_separator', self::SEPARATOR, []);

        // ---------------------------------------

        $fieldSet->addField(
            'keywords_custom_attribute',
            'hidden',
            [
                'name' => 'keywords_custom_attribute',
                'value' => $this->formData['keywords_custom_attribute']
            ]
        );

        $defaultValue = '';
        if ($this->formData['keywords_mode'] == Description::KEYWORDS_MODE_NONE ||
            $this->formData['keywords_mode'] == Description::KEYWORDS_MODE_CUSTOM_VALUE
        ) {
            $defaultValue = $this->formData['keywords_mode'];
        }

        $fieldSet->addField(
            'keywords_mode',
            self::SELECT,
            [
                'name' => 'keywords_mode',
                'label' => $this->__('Keywords'),
                'title' => $this->__('Keywords'),
                'values' => $this->getKeywordsModeOptions(),
                'value' => $defaultValue,
                'class' => 'select',
                'create_magento_attribute' => true,
                'tooltip' => $this->__('Specify the relevant keywords that buyer would use to find your Item.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        $fieldSet->addField(
            'keywords_custom_value',
            'textarea',
            [
                'name' => 'keywords_custom_value',
                'label' => $this->__('Keywords Value'),
                'title' => $this->__('Keywords Value'),
                'value' => $this->formData['keywords_custom_value'],
                'class' => 'input-text M2ePro-required-when-visible',
                'required' => true,
                'field_extra_attributes' => 'id="keywords_custom_value_tr" style="display: none;"',
            ]
        );

        // ---------------------------------------

        $fieldSet->addField('keywords_custom_mode_separator', self::SEPARATOR, []);

        // ---------------------------------------

        $fieldSet->addField(
            'attributes_mode',
            self::SELECT,
            [
                'name' => 'attributes_mode',
                'label' => $this->__('Attributes'),
                'title' => $this->__('Attributes'),
                'values' => [
                    ['value' => Description::ATTRIBUTES_MODE_NONE, 'label' => $this->__('None')],
                    ['value' => Description::ATTRIBUTES_MODE_CUSTOM, 'label' => $this->__('Custom Value')],
                ],
                'value' => $this->formData['attributes_mode'],
                'tooltip' => $this->__('Specify up to 5 additional features that describe your Item.')
            ]
        );

        $this->appendAttributesFields($fieldSet, 5, 'attributes');

        // ---------------------------------------

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    // ---------------------------------------

    public function getBrandOptions()
    {
        $optionsResult = [
            ['value' => Description::BRAND_MODE_CUSTOM_VALUE, 'label' => $this->__('Custom Value')],
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            Description::BRAND_MODE_CUSTOM_ATTRIBUTE,
            'brand_custom_attribute',
            'text_select'
        ));
    }

    public function getManufacturerOptions()
    {
        $optionsResult = [
            ['value' => Description::MANUFACTURER_MODE_NONE, 'label' => $this->__('None')],
            ['value' => Description::MANUFACTURER_MODE_CUSTOM_VALUE, 'label' => $this->__('Custom Value')],
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            Description::MANUFACTURER_MODE_CUSTOM_ATTRIBUTE,
            'manufacturer_custom_attribute',
            'text_select'
        ));
    }

    public function getManufacturerPartNumberOptions()
    {
        $optionsResult = [
            ['value' => Description::MANUFACTURER_PART_NUMBER_MODE_NONE, 'label' => $this->__('None')],
            [
                'value' => Description::MANUFACTURER_PART_NUMBER_MODE_CUSTOM_VALUE,
                'label' => $this->__('Custom Value')
            ],
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            Description::MANUFACTURER_PART_NUMBER_MODE_CUSTOM_ATTRIBUTE,
            'manufacturer_part_number_custom_attribute',
            'text_select'
        ));
    }

    public function getModelNumberOptions()
    {
        $optionsResult = [
            ['value' => Description::MODEL_NUMBER_MODE_NONE, 'label' => $this->__('None')],
            [
                'value' => Description::MODEL_NUMBER_MODE_CUSTOM_VALUE,
                'label' => $this->__('Custom Value')
            ],
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            Description::MODEL_NUMBER_MODE_CUSTOM_ATTRIBUTE,
            'model_number_custom_attribute',
            'text_select'
        ));
    }

    public function getMsrpRrpModeOptions()
    {
        $optionsResult = [
            ['value' => Description::MSRP_RRP_MODE_NONE, 'label' => $this->__('None')],
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            Description::MSRP_RRP_MODE_ATTRIBUTE,
            'msrp_rrp_custom_attribute',
            'text_price'
        ));
    }

    public function getImageMainOptions()
    {
        $optionsResult = [
            ['value' => Description::IMAGE_MAIN_MODE_NONE, 'label' => $this->__('None')],
            [
                'value' => Description::IMAGE_MAIN_MODE_PRODUCT,
                'label' => $this->__('Product Base Image')
            ],
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            Description::IMAGE_MAIN_MODE_ATTRIBUTE,
            'image_main_attribute',
            'text_images'
        ));
    }

    public function getGalleryImagesModeOptions()
    {
        $preparedImages = [];
        for ($i = 2; $i <= 8; $i++) {
            $attrs = ['attribute_code' => $i];

            if ($i == $this->formData['gallery_images_limit'] &&
                $this->formData['gallery_images_mode'] == Description::GALLERY_IMAGES_MODE_PRODUCT
            ) {
                $attrs['selected'] = 'selected';
            }

            $preparedImages[] = [
                'value' => Description::GALLERY_IMAGES_MODE_PRODUCT,
                'label' => $i == 1 ? $i : ($this->__('Up to') . " $i"),
                'attrs' => $attrs
            ];
        }

        $optionsResult = [
            ['value' => Description::GALLERY_IMAGES_MODE_NONE, 'label' => $this->__('None')],
            [
                'label' => $this->__('Product Images'),
                'value' => $preparedImages,
            ],
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            Description::GALLERY_IMAGES_MODE_ATTRIBUTE,
            'gallery_images_attribute',
            'text_images'
        ));
    }

    public function getImageVariationDifferenceModeOptions()
    {
        $optionsResult = [
            ['value' => Description::IMAGE_VARIATION_DIFFERENCE_MODE_NONE, 'label' => $this->__('None')],
            [
                'value' => Description::IMAGE_VARIATION_DIFFERENCE_MODE_PRODUCT,
                'label' => $this->__('Product Base Image')
            ],
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            Description::IMAGE_VARIATION_DIFFERENCE_MODE_ATTRIBUTE,
            'image_variation_difference_attribute',
            'text_images'
        ));
    }

    public function getMultipackQuantityModeOptions()
    {
        $optionsResult = [
            ['value' => Description::MULTIPACK_QUANTITY_MODE_NONE, 'label' => $this->__('None')],
            [
                'value' => Description::MULTIPACK_QUANTITY_MODE_CUSTOM_VALUE,
                'label' => $this->__('Custom Value')
            ],
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            Description::MULTIPACK_QUANTITY_MODE_CUSTOM_ATTRIBUTE,
            'multipack_quantity_custom_attribute',
            'text_select'
        ));
    }

    public function getCountPerPackModeOptions()
    {
        $optionsResult = [
            ['value' => Description::COUNT_PER_PACK_MODE_NONE, 'label' => $this->__('None')],
            [
                'value' => Description::COUNT_PER_PACK_MODE_CUSTOM_VALUE,
                'label' => $this->__('Custom Value')
            ],
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            Description::COUNT_PER_PACK_MODE_CUSTOM_ATTRIBUTE,
            'count_per_pack_custom_attribute',
            'text_select'
        ));
    }

    public function getTotalCountModeOptions()
    {
        $optionsResult = [
            ['value' => Description::TOTAL_COUNT_MODE_NONE, 'label' => $this->__('None')],
            [
                'value' => Description::TOTAL_COUNT_MODE_CUSTOM_VALUE,
                'label' => $this->__('Custom Value')
            ],
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            Description::TOTAL_COUNT_MODE_CUSTOM_ATTRIBUTE,
            'total_count_custom_attribute',
            'text_select'
        ));
    }

    public function getKeywordsModeOptions()
    {
        $optionsResult = [
            ['value' => Description::KEYWORDS_MODE_NONE, 'label' => $this->__('None')],
            [
                'value' => Description::KEYWORDS_MODE_CUSTOM_VALUE,
                'label' => $this->__('Custom Value')
            ],
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            Description::KEYWORDS_MODE_CUSTOM_ATTRIBUTE,
            'keywords_custom_attribute',
            'text_select'
        ));
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Model\Walmart\Template\Description::class)
        );

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Helper\Component\Walmart::class)
        );

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Walmart_Template_Description'));
        $this->jsUrl->addUrls([
            'formSubmit'    => $this->getUrl(
                '*/walmart_template_description/save',
                ['_current' => true]
            ),
            'formSubmitNew' => $this->getUrl('*/walmart_template_description/save'),
            'deleteAction'  => $this->getUrl(
                '*/walmart_template_description/delete',
                ['_current' => true]
            ),

            'm2epro_skin_url' => $this->getViewFileUrl('Ess_M2ePro')
        ]);

        $this->jsTranslator->addTranslations([
            'Add Description Policy' => $this->__('Add Description Policy'),

            'Change Category' => $this->__('Change Category'),
            'Not Selected'    => $this->__('Not Selected'),
            'Select'          => $this->__('Select'),

            'The specified Title is already used for another Policy. Policy Title must be unique.' =>
                $this->__('The specified Title is already used for another Policy. Policy Title must be unique.'),
            'You should select Marketplace first.' => $this->__('You should select Marketplace first.'),
            'You should select Category and Product Type first' =>
                $this->__('You should select Category and Product Type first'),

            'Recommended' => $this->__('Recommended'),
            'Recent'      => $this->__('Recent'),
        ]);

        $formData = $this->getHelper('Data')->jsonEncode($this->formData);
        $isEdit = $this->templateModel->getId() ? 'true' : 'false';
        $allAttributes = $this->getHelper('Data')->jsonEncode($this->getHelper('Magento\Attribute')->getAll());

        $this->js->addRequireJs([
            'jQuery' => 'jquery',
            'attr' => 'M2ePro/Attribute',
            'description' => 'M2ePro/Walmart/Template/Description',

            'attribute_button' => 'M2ePro/Plugin/Magento/Attribute/Button'
        ], <<<JS

        M2ePro.formData = {$formData};

        M2ePro.customData.is_edit = {$isEdit};

        if (typeof AttributeObj === 'undefined') {
            window.AttributeObj = new Attribute();
        }
        window.AttributeObj.setAvailableAttributes({$allAttributes});

        window.WalmartTemplateDescriptionObj = new WalmartTemplateDescription();
        window.MagentoAttributeButtonObj = new MagentoAttributeButton();

        jQuery(function() {
            WalmartTemplateDescriptionObj.initObservers();
        });
JS
        );

        return parent::_beforeToHtml();
    }

    //########################################

    public function getFormData()
    {
        $default = [
            'id'             => '',
            'title'          => '',

            'title_mode'     => Description::TITLE_MODE_PRODUCT,
            'title_template' => '',

            'brand_mode'             => Description::BRAND_MODE_CUSTOM_VALUE,
            'brand_custom_value'     => '',
            'brand_custom_attribute' => '',

            'manufacturer_mode'             => Description::MANUFACTURER_MODE_NONE,
            'manufacturer_custom_value'     => '',
            'manufacturer_custom_attribute' => '',

            'manufacturer_part_number_mode'             => Description::MANUFACTURER_PART_NUMBER_MODE_NONE,
            'manufacturer_part_number_custom_value'     => '',
            'manufacturer_part_number_custom_attribute' => '',

            // ---

            'model_number_mode'             => Description::MODEL_NUMBER_MODE_NONE,
            'model_number_custom_value'     => '',
            'model_number_custom_attribute' => '',

            'total_count_mode'             => Description::TOTAL_COUNT_MODE_NONE,
            'total_count_custom_value'     => '',
            'total_count_custom_attribute' => '',

            'count_per_pack_mode'             => Description::COUNT_PER_PACK_MODE_NONE,
            'count_per_pack_custom_value'     => '',
            'count_per_pack_custom_attribute' => '',

            'multipack_quantity_mode'             => Description::MULTIPACK_QUANTITY_MODE_NONE,
            'multipack_quantity_custom_value'     => '',
            'multipack_quantity_custom_attribute' => '',

            // ---

            'msrp_rrp_mode'             => Description::MSRP_RRP_MODE_NONE,
            'msrp_rrp_custom_attribute' => '',

            // ---

            'description_mode'     => '',
            'description_template' => '',

            'image_main_mode'      => Description::IMAGE_MAIN_MODE_PRODUCT,
            'image_main_attribute' => '',

            'image_variation_difference_mode'      => Description::IMAGE_VARIATION_DIFFERENCE_MODE_NONE,
            'image_variation_difference_attribute' => '',

            'gallery_images_mode'      => Description::GALLERY_IMAGES_MODE_NONE,
            'gallery_images_limit'     => 1,
            'gallery_images_attribute' => '',

            'key_features_mode' => Description::KEY_FEATURES_MODE_NONE,
            'key_features'      => [],

            'other_features_mode' => Description::OTHER_FEATURES_MODE_NONE,
            'other_features'      => [],

            'keywords_mode'             => Description::KEYWORDS_MODE_NONE,
            'keywords_custom_value'     => '',
            'keywords_custom_attribute' => '',

            'attributes_mode' => Description::ATTRIBUTES_MODE_NONE,
            'attributes'      => [],
        ];

        if (!$this->templateModel || !$this->templateModel->getId()) {
            return $default;
        }

        $data = array_merge(
            $this->templateModel->getData(),
            $this->templateModel->getChildObject()->getData()
        );
        $helper = $this->getHelper('Data');

        if (!empty($data['key_features'])) {
            $data['key_features'] = $helper->jsonDecode($data['key_features'], true);
        }

        if (!empty($data['other_features'])) {
            $data['other_features'] = $helper->jsonDecode($data['other_features'], true);
        }

        if (!empty($data['attributes'])) {
            $data['attributes'] = $helper->jsonDecode($data['attributes'], true);
        }

        return array_merge($default, $data);
    }

    //########################################

    public function appendKeywordsFields(
        \Magento\Framework\Data\Form\Element\Fieldset $fieldSet,
        $fieldCount,
        $name,
        $fieldTitle
    ) {
        $helper = $this->getHelper('Data');
        for ($i = 0; $i < $fieldCount; $i++) {
            $button = $this->getMultiElementButton($name, $i);

            $value = '';
            if (!empty($this->formData[$name][$i])) {
                $value = $helper->escapeHtml($this->formData[$name][$i]);
            }

            $fieldSet->addField(
                $name.'_'.$i,
                'text',
                [
                    'name' => $name.'[]',
                    'label' => $this->__('%title% Value #%number%', $fieldTitle, $i + 1),
                    'title' => $this->__('%title% Value #%number%', $fieldTitle, $i + 1),
                    'value' => $value,
                    'onkeyup' => 'WalmartTemplateDescriptionObj.multi_element_keyup(\''.$name.'\',this)',
                    'class' => 'M2ePro-required-when-visible',
                    'style' => 'width: 65%',
                    'required' => true,
                    'css_class' => $name.'_tr no-margin-bottom',
                    'field_extra_attributes' => 'style="display: none;"',
                    'after_element_html' => <<<HTML
<span class="fix-magento-tooltip" style="margin-left: 10px; margin-right: 10px; margin-bottom: 5px;">
    {$this->getTooltipHtml($this->__('Max. 50 characters.'))}
</span>
{$button->toHtml()}
HTML
                ]
            );
        }

        $fieldSet->addField(
            $name.'_actions',
            self::CUSTOM_CONTAINER,
            [
                'label' => '',
                'text' => <<<HTML
                <a id="show_{$name}_action"
                   href="javascript: void(0);"
                   onclick="WalmartTemplateDescriptionObj.showElement('{$name}');">
                   {$this->__('Add New')}
                </a>
                        /
                <a id="hide_{$name}_action"
                   href="javascript: void(0);"
                   onclick="WalmartTemplateDescriptionObj.hideElement('{$name}');">
                   {$this->__('Remove')}
                </a>
HTML
                ,
                'field_extra_attributes' => 'id="'.$name.'_actions_tr" style="display: none;"',
            ]
        );
    }

    public function appendAttributesFields(
        \Magento\Framework\Data\Form\Element\Fieldset $fieldSet,
        $fieldCount,
        $name
    ) {
        $helper = $this->getHelper('Data');
        for ($i = 0; $i < $fieldCount; $i++) {
            $value = '';
            if (!empty($this->formData[$name][$i]['name'])) {
                $value = $helper->escapeHtml($this->formData[$name][$i]['name']);
            }

            $nameBlock = $this->elementFactory->create(
                'text',
                [
                    'data' => [
                        'name'  => $name.'_name[]',
                        'value' => $value,
                        'onkeyup' => 'WalmartTemplateDescriptionObj.multi_element_keyup(\''.$name.'\',this)',
                        'class' => 'M2ePro-required-when-visible',
                        'style' => 'width: 49%',
                        'css_class' => $name.'_tr no-margin-bottom'
                    ]
                ]
            );
            $nameBlock->setId($name.'_name_'.$i);
            $nameBlock->setForm($fieldSet->getForm());

            $value = '';
            if (!empty($this->formData[$name][$i]['value'])) {
                $value = $helper->escapeHtml($this->formData[$name][$i]['value']);
            }

            $valueBlock = $this->elementFactory->create(
                'text',
                [
                    'data' => [
                        'name' => $name.'_value[]',
                        'value' => $value,
                        'onkeyup' => 'WalmartTemplateDescriptionObj.multi_element_keyup(\''.$name.'\',this)',
                        'class' => 'M2ePro-required-when-visible',
                        'style' => 'width: 49%',
                        'css_class' => $name.'_tr no-margin-bottom',
                        'tooltip' => $this->__('Max. 100 characters.')
                    ]
                ]
            );
            $valueBlock->setId($name.'_value_'.$i);
            $valueBlock->setForm($fieldSet->getForm());

            $button = $this->createBlock('Magento_Button_MagentoAttribute')->addData([
                'label' => $this->__('Insert Attribute'),
                'destination_id' => $name.'_value_'.$i,
                'magento_attributes' => $this->getClearAttributesByInputTypesOptions(),
                'on_click_callback' => "function() {
                    WalmartTemplateDescriptionObj.multi_element_keyup('{$name}',$('{$name}_value_{$i}'));
                }",
                'class' => 'primary attributes-container-td',
                'style' => 'display: inline-block; margin: 0;',
                'select_custom_attributes' => [
                    'allowed_attribute_types' => 'text,select',
                    'apply_to_all_attribute_sets' => 0
                ]
            ]);

            $fieldSet->addField(
                'attributes_container_'.$i,
                self::CUSTOM_CONTAINER,
                [
                    'label' => $this->__('Attributes (name / value) #%number%', $i + 1),
                    'title' => $this->__('Attributes (name / value) #%number%', $i + 1),
                    'style' => 'padding-top: 0; width: 70%; display: inline-block;',
                    'text' => $nameBlock->toHtml() . $valueBlock->toHtml(),
                    'after_element_html' => $button->toHtml(),
                    'css_class' => 'attributes_tr',
                    'field_extra_attributes' => 'style="display: none;"'
                ]
            );
        }

        $fieldSet->addField(
            $name.'_actions',
            self::CUSTOM_CONTAINER,
            [
                'label' => '',
                'text' => <<<HTML
                <a id="show_{$name}_action"
                   href="javascript: void(0);"
                   onclick="WalmartTemplateDescriptionObj.showElement('{$name}');">
                   {$this->__('Add New')}
                </a>
                        /
                <a id="hide_{$name}_action"
                   href="javascript: void(0);"
                   onclick="WalmartTemplateDescriptionObj.hideElement('{$name}');">
                   {$this->__('Remove')}
                </a>
HTML
                ,
                'field_extra_attributes' => 'id="'.$name.'_actions_tr" style="display: none;"',
            ]
        );
    }

    private function getMultiElementButton($type, $index)
    {
        return $this->createBlock('Magento_Button_MagentoAttribute')->addData([
            'label' => $this->__('Insert Attribute'),
            'destination_id' => $type.'_'.$index,
            'magento_attributes' => $this->getClearAttributesByInputTypesOptions(),
            'on_click_callback' => "function() {
                WalmartTemplateDescriptionObj.multi_element_keyup('{$type}',$('{$type}_{$index}'));
            }",
            'class' => 'primary attributes-container-td',
            'style' => 'margin: 0;',
            'select_custom_attributes' => [
                'allowed_attribute_types' => 'text,select',
                'apply_to_all_attribute_sets' => 0
            ]
        ]);
    }

    //########################################

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
                function ($attribute) use ($attributeName) {
                    return $attribute['code'] == $this->formData[$attributeName];
                }
            ),
            'label' => 'Magento Attribute',
            'attrs' => ['is_magento_attribute' => true]
        ];

        return $optionsResult;
    }

    public function getForceAddedAttributeOption($attributeCode, $availableValues, $value = null)
    {
        if (empty($attributeCode) ||
            $this->getHelper('Magento\Attribute')->isExistInAttributesArray($attributeCode, $availableValues)) {
            return '';
        }

        $attributeLabel = $this->getHelper('Data')
                               ->escapeHtml($this->getHelper('Magento\Attribute')->getAttributeLabel($attributeCode));

        $result = ['value' => $value, 'label' => $attributeLabel];

        if ($value === null) {
            return $result;
        }

        $result['attrs'] = ['attrbiute_code' => $attributeCode];
        return $result;
    }

    public function getClearAttributesByInputTypesOptions()
    {
        $optionsResult = [];
        $helper = $this->getHelper('Data');

        foreach ($this->allAttributesByInputTypes['text_select'] as $attribute) {
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

        foreach ($this->generalAttributesByInputTypes[$attributeType] as $attribute) {
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

    //########################################
}
