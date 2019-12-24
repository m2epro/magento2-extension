<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Edit;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Edit\Form
 */
class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    public $templateModel = null;
    public $formData = [];
    public $marketplaceData = [];
    public $generalAttributesByInputTypes = [];
    public $allAttributes = [];

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartTemplateDescriptionEditForm');
        // ---------------------------------------

        $this->templateModel = $this->getHelper('Data\GlobalData')->getValue('tmp_template');
        $this->formData = $this->getFormData();
        $marketplaces = $this->getHelper('Component\Walmart')->getMarketplacesAvailableForApiCreation();
        $marketplaces = $marketplaces->toArray();
        $this->marketplaceData = $marketplaces['items'];

        $magentoAttributeHelper = $this->getHelper('Magento\Attribute');

        $this->allAttributes = $magentoAttributeHelper->getAll();

        $generalAttributes = $magentoAttributeHelper->getGeneralFromAllAttributeSets();

        $this->generalAttributesByInputTypes = [
            'text' => $magentoAttributeHelper->filterByInputTypes($generalAttributes, ['text']),
            'text_select' => $magentoAttributeHelper->filterByInputTypes($generalAttributes, ['text', 'select'])
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
            'walmart_category_general_help',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    <<<HTML
                <p>In Category Policy, select Walmart Category/Subcategory for your
                offer and complete the Item Specifics.</p><br>
                <p><strong>Note:</strong> Item Specifics vary by Category/Subcategory.
                Choose correctly to complete the relevant Specifics for your Item</p><br>
                <p>To start configuring Item Specifics, press Add Specifics. You can use search and filter tools
                to narrow your search results. Item Specifics have a nested structure so the same Specific
                can be used in different groups. Insert a custom value or select the relevant Magento Attribute.
                Duplicate Item Specific if you need to provide several values for that Specific.
                You can delete the records that no longer needed.</p><br>
                <p><strong>Note:</strong> Category Policy is created per marketplace that cannot be changed
                after the Policy is assigned to the Listing Products.</p><br>
                <p><strong>Note:</strong> Category Policy is required when you create a new offer on Walmart.</p><br>

HTML
                ),
                'class' => 'marketplace-required-field marketplace-required-field-id-not-null'
            ]
        );

        $fieldSet = $form->addFieldset('magento_block_template_category_edit_general', [
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
                'class' => 'input-text M2ePro-category-template-title',
                'required' => true,
                'tooltip' => $this->__('Policy Title for your internal use.')
            ]
        );

        $additionalData = [];
        if ($this->isMarketplaceLocked() || $this->getRequest()->getParam('marketplace_id')) {
            $additionalData = [
                'disabled'           => true,
                'after_element_html' => $this->getMarketplaceWarningMessageHtml() .
                                        '<input id="marketplace_hidden_input"
                                                type="hidden"
                                                name="marketplace_id"
                                                value="' . $this->formData['marketplace_id'] . '" />'
            ];
        }

        $fieldSet->addField(
            'marketplace_id',
            self::SELECT,
            array_merge(
                [
                    'name'     => 'marketplace_id',
                    'label'    => $this->__('Marketplace'),
                    'title'    => $this->__('Marketplace'),
                    'values'   => $this->getMarketplaceDataOptions(),
                    'value'    => $this->formData['marketplace_id'],
                    'required' => true,
                ],
                $additionalData
            )
        );

        $fieldSet->addField(
            'template_category_path_container',
            self::CUSTOM_CONTAINER,
            [
                'container_id' => 'category_path_container',
                'label' => $this->__('Category'),
                'title' => $this->__('Category'),
                'required' => true,
                'text' => $this->getCategoryHtml()
            ]
        );

        $this->css->add('label.mage-error[for="category_path"] { width: 160px !important; left: initial !important; }');

        $fieldSet = $form->addFieldset('magento_block_template_category_edit_specifics', [
            'legend' => $this->__('Specifics'), 'collapsable' => false
        ]);

        $fieldSet->addField(
            'template_category_specifics_container',
            self::CUSTOM_CONTAINER,
            [
                'text' => $this->getSpecificsHtml()
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    public function _beforeToHtml()
    {
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Model\Walmart\Template\Category::class)
        );

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Helper\Component\Walmart::class)
        );

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Walmart_Template_Category'));
        $this->jsUrl->addUrls([
            'formSubmit'    => $this->getUrl(
                '*/walmart_template_category/save',
                ['_current' => true]
            ),
            'formSubmitNew' => $this->getUrl('*/walmart_template_category/save'),
            'deleteAction'  => $this->getUrl(
                '*/walmart_template_category/delete',
                ['_current' => true]
            ),

            'walmart_marketplace/index' => $this->getUrl('*/walmart_marketplace/index'),
            'm2epro_skin_url' => $this->getViewFileUrl('Ess_M2ePro')
        ]);

        $this->jsTranslator->addTranslations([
            'Add Category Policy' => $this->__('Add Category Policy'),

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

            'Add Specifics'        => $this->__('Add Specifics'),
            'Remove this specific' => $this->__('Remove this specific'),

            'Total digits (not more):' => $this->__('Total digits (not more):'),
            'Type: Numeric.' => $this->__('Type: Numeric.'),
            'Min:'           => $this->__('Min:'),
            'Max:'           => $this->__('Max:'),

            'Can take any value.' => $this->__('Can take any value.'),
            'Two uppercase letters or "unknown".' => $this->__('Two uppercase letters or "unknown".'),
            'The value is incorrect.' => $this->__('The value is incorrect.'),
            'Type: String.'   => $this->__('Type: String.'),
            'Min length:'     => $this->__('Min length:'),
            'Max length:'     => $this->__('Max length:'),

            'Type: Date time. Format: YYYY-MM-DD hh:mm:ss' => $this->__('Type: Date time. Format: YYYY-MM-DD hh:mm:ss'),
            'Type: Numeric floating point.'                => $this->__('Type: Numeric floating point.'),
            'Decimal places (not more):'                   => $this->__('Decimal places (not more):'),

            'Recommended Values' => $this->__('Recommended Values'),
            'Allowed Values'     => $this->__('Allowed Values'),
            'Custom Attribute'   => $this->__('Custom Attribute'),
            'Custom Value'       => $this->__('Custom Value'),
            'None'               => $this->__('None'),

            'Definition:'    => $this->__('Definition:'),
            'Tips:'          => $this->__('Tips:'),
            'Examples:'      => $this->__('Examples:'),
            'Desired'        => $this->__('Desired'),

            'Duplicate specific' => $this->__('Duplicate specific'),
            'Delete specific'    => $this->__('Delete specific'),
            'Add Specific into current container' => $this->__('Add Specific into current container'),

            'Value of this Specific can be automatically overwritten by M2E Pro.' => $this->__(
                'If you select this Item Specific as Walmart Variant Attribute in the Manage Variation pop-up,
                its current value will be automatically overwritten with the related Attribute values of Magento Child
                Products. Below you can see the Walmart Variant Attribute(s)
                that will be used instead of this Item Specific:'
            ),
            'Walmart Parentage Specific will be overridden notice.' => $this->__(
                'The Value of this Specific can be necessary due to technical reasons, so there is no ability to
                Edit the Attribute parentage and also it has no semantic load. In case this Description Policy uses for
                creation of new Walmart Parent-Child Product, this Value will be overwritten and the Value you selected
                will not be/cannot be used.'
            )
        ]);

        $formData = $this->getHelper('Data')->jsonEncode($this->formData);
        $isEdit = $this->templateModel->getId() ? 'true' : 'false';
        $isCategoryLocked = $this->isCategoryLocked() ? 'true' : 'false';
        $isMarketplaceLocked = $this->isMarketplaceLocked() ? 'true' : 'false';
        $marketplaceForceSet = $this->getHelper('Data')->jsonEncode(
            (bool)(int)$this->getRequest()->getParam('marketplace_id')
        );
        $allAttributes = $this->getHelper('Data')->jsonEncode($this->getHelper('Magento\Attribute')->getAll());
        $specifics = $this->getHelper('Data')->jsonEncode($this->formData['specifics']);

        $this->js->addRequireJs([
            'jQuery' => 'jquery',
            'attr' => 'M2ePro/Attribute',
            'description' => 'M2ePro/Walmart/Template/Category/Category',
            'chooser' => 'M2ePro/Walmart/Template/Category/Categories/Chooser',
            'specific' => 'M2ePro/Walmart/Template/Category/Categories/Specific',

            'attribute_button' => 'M2ePro/Plugin/Magento/Attribute/Button',

            'blockRenderer' => 'M2ePro/Walmart/Template/Category/Categories/Specific/BlockRenderer',
            'dictionary' => 'M2ePro/Walmart/Template/Category/Categories/Specific/Dictionary',
            'renderer' => 'M2ePro/Walmart/Template/Category/Categories/Specific/Renderer',

            'rowattributerenderer' => 'M2ePro/Walmart/Template/Category/Categories/Specific/Grid/RowAttributeRenderer',
            'rowrenderer' => 'M2ePro/Walmart/Template/Category/Categories/Specific/Grid/RowRenderer',

            'addspecificrenderer' => 'M2ePro/Walmart/Template/Category/Categories/Specific/Block/AddSpecificRenderer',
            'gridrenderer' => 'M2ePro/Walmart/Template/Category/Categories/Specific/Block/GridRenderer',
        ], <<<JS

        M2ePro.formData = {$formData};

        M2ePro.customData.is_edit = {$isEdit};

        M2ePro.customData.category_locked             = {$isCategoryLocked};
        M2ePro.customData.marketplace_locked          = {$isMarketplaceLocked};
        M2ePro.customData.marketplace_force_set       = {$marketplaceForceSet};

        if (typeof AttributeObj === 'undefined') {
            window.AttributeObj = new Attribute();
        }
        window.AttributeObj.setAvailableAttributes({$allAttributes});

        window.WalmartTemplateCategoryObj                 = new WalmartTemplateCategory();
        window.WalmartTemplateCategoryCategoriesChooserObj  = new WalmartTemplateCategoryCategoriesChooser();
        window.WalmartTemplateCategoryCategoriesSpecificObj = new WalmartTemplateCategoryCategoriesSpecific();

        window.MagentoAttributeButtonObj = new MagentoAttributeButton();

        WalmartTemplateCategoryObj.setSpecificHandler(WalmartTemplateCategoryCategoriesSpecificObj);
        WalmartTemplateCategoryCategoriesChooserObj.setSpecificHandler(WalmartTemplateCategoryCategoriesSpecificObj);

        WalmartTemplateCategoryCategoriesSpecificObj.setFormDataSpecifics({$specifics});

        jQuery(function() {
            WalmartTemplateCategoryObj.initObservers();

            if ({$isEdit}) {
                WalmartTemplateCategoryObj.prepareEditMode();
            }
        });
JS
        );

        return parent::_beforeToHtml();
    }

    // ---------------------------------------

    public function getMarketplaceDataOptions()
    {
        $optionsResult = [
            ['value' => '', 'label' => '', 'attrs' => ['style' => 'display: none;']]
        ];
        foreach ($this->marketplaceData as $marketplace) {
            $optionsResult[] = [
                'value' => $marketplace['id'],
                'label' => $this->__($marketplace['title'])
            ];
        }

        return $optionsResult;
    }

    //########################################

    public function getFormData()
    {
        $default = [
            'id'                => '',
            'title'             => '',
            'marketplace_id'    => $this->getRequest()->getParam('marketplace_id', ''),
            'category_path'     => '',
            'browsenode_id'     => '',
            'product_data_nick' => '',
            'specifics'         => []
        ];

        if (!$this->templateModel || !$this->templateModel->getId()) {
            return $default;
        }

        $data = $this->templateModel->getData();
        $data['specifics'] = $this->templateModel->getSpecifics();

        return array_merge($default, $data);
    }

    public function isMarketplaceLocked()
    {
        if ($this->templateModel && $this->templateModel->getId()) {
            return $this->templateModel->isLocked();
        }

        return false;
    }

    public function isCategoryLocked()
    {
        if ($this->templateModel && $this->templateModel->getId()) {
            return $this->templateModel->isLockedForCategoryChange();
        }

        return false;
    }

    //########################################

    public function getCategoryHtml()
    {
        $html = '<span style="font-style: italic; color: grey;">'.$this->__('Not Selected').'</span>';
        if (!empty($this->formData['category_path']) && !empty($this->formData['browsenode_id'])) {
            $html = '<span>'.$this->escapeHtml(
                "{$this->formData['category_path']} ({$this->formData['browsenode_id']})"
            ).'</span>';
        }

        $html = '<span id="category_path_span">' . $html . '</span>';

        $tooltip = <<<HTML
<div id="category_warning_messages" style="display: none;">
    <div id="category_locked_warning_message" class="category-warning-item m2epro-field-tooltip admin__field-tooltip"
    style="display: none;">
        <a class="admin__field-tooltip-action" href="javascript://"></a>
        <div class="admin__field-tooltip-content">
            {$this->__(
            'You cannot edit existing Category because currently the new ASIN/ISBN is being created based on this Policy
or there is possibility of
                 creating new Child Products based for the Parent Product with this Description Policy. <br/><br/>
                 It is done to make sure that Parent and Child Products will be Listed to the same Category.'
        )}
        </div>
    </div>
    <div id="category_is_not_accessible_message" class="category-warning-item m2epro-field-tooltip admin__field-tooltip"
    style="display: none;">
        <a class="admin__field-tooltip-action" href="javascript://"></a>
        <div class="admin__field-tooltip-content">
            {$this->__(
            'It is impossible to use the Category, you have chosen before. <br/> You should select new Category which
mostly suits to that you used earlier. Also you need to set Specifics Values on Specifics Tab.'
        )}
        </div>
    </div>
    <div id="category_variation_warning_message" class="category-warning-item m2epro-field-tooltip admin__field-tooltip"
    style="display: none;">
        <a class="admin__field-tooltip-action" href="javascript://"></a>
        <div class="admin__field-tooltip-content">
            {$this->__(
            'You have chosen Category in which Walmart Parent/Child Products are not allowed.'
        )}
        </div>
    </div>
</div>

HTML;

        return $html . <<<HTML
        <span>
            <input type="hidden"
                   name="category_path"
                   id="category_path"
                   value="{$this->getHelper('Data')->escapeHtml($this->formData['category_path'])}" />
            <input type="hidden"
                   name="browsenode_id"
                   id="browsenode_id"
                   value="{$this->formData['browsenode_id']}" />
            <input type="text"
                   style="display: none;"
                   name="product_data_nick"
                   id="product_data_nick"
                   value="{$this->formData['product_data_nick']}"
                   class="required-entry"/>
        </span>
       {$tooltip}
       <a id="edit_category_link" href="#" style="margin-left: 5px;">{$this->__('Edit')}</a>
HTML;
    }

    public function getMarketplaceWarningMessageHtml()
    {
        return <<<HTML
        <div id="marketplace_locked_warning_message" class="m2epro-field-tooltip admin__field-tooltip">
            <a class="admin__field-tooltip-action" href="javascript://"></a>
            <div class="admin__field-tooltip-content">
                {$this->__(
            'Marketplace cannot be changed because this Description Policy is assigned to the Product(s) in Listing(s).'
        )}
            </div>
        </div>
HTML;
    }

    public function getSpecificsHtml()
    {
        return <<<HTML
    <input id="encoded_specifics_data" type="hidden" name="encoded_data" value="">

    <div class="fieldset">
        <div class="hor-scroll" style="padding-bottom: 10px;">
            <div id="specifics_container">
                <span style="font-style: italic; color: grey;">Select Category First</span>
            </div>
        </div>
    </div>

    <!-- specifics grid template start -->
    <!-- ########################################################## -->
    <div id="specifics_list_grid_template" style="display: none;">

        <table class="form-list entry-edit" cellspacing="0" cellpadding="0" style="width: 100%;">

            <tr class="item-specifics-tr">
                <td class="grid admin__data-grid-wrap specifics-grid" colspan="2">

                    <table class="border data-grid data-grid-not-hovered data-grid-striped"
                           cellpadding="0" cellspacing="0">
                        <thead>
                        <tr class="headings">
                            <th style="width: 35%;">{$this->__('Name')}</th>
                            <th style="width: 26%;">{$this->__('Mode')}</th>
                            <th style="width: 30%;">{$this->__('Value')}</th>
                            <th style="width: 8%; text-align: center;">&nbsp;</th>
                        </tr>
                        </thead>
                        <tbody>
                        <!-- #specific_table_row_template inserts here -->
                        </tbody>
                    </table>

                </td>
            </tr>

        </table>
    </div>
    <!-- ########################################################## -->

    <!-- specifics add ror start -->
    <!-- ########################################################## -->
    <div id="specifics_add_row_template" style="display: none;">

        <table style="width: 100%; background-color: #EFEFEF;margin-bottom: 30px;" cellpadding="0" cellspacing="0">
            <tfoot>
            <tr>
                <td valign="middle" align="right" style="vertical-align: middle; padding: 1.5em; text-align: center;">
                    <button title="Add Specifics" type="button"
                            class="scalable add add_custom_specific_button primary" style="margin-right: 15px;">
                        <span><span><span>{$this->__('Add Specifics')}</span></span></span>
                    </button>
                </td>
            </tr>
            </tfoot>
        </table>

    </div>
    <!-- ########################################################## -->
HTML;
    }

    //########################################
}
