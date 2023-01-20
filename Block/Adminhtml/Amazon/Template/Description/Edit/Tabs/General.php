<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Edit\Tabs;

use Ess\M2ePro\Model\Amazon\Template\Description;

class General extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    public $templateModel;
    public $formData = [];
    public $marketplaceData = [];

    /** @var \Ess\M2ePro\Helper\Magento\Attribute */
    protected $magentoAttributeHelper;

    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    private $amazonHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Component\Amazon $amazonHelper,
        array $data = []
    ) {
        $this->magentoAttributeHelper = $magentoAttributeHelper;
        $this->supportHelper = $supportHelper;
        $this->dataHelper = $dataHelper;
        $this->globalDataHelper = $globalDataHelper;
        $this->amazonHelper = $amazonHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonTemplateDescriptionEditTabsGeneral');
        // ---------------------------------------

        $this->templateModel = $this->globalDataHelper->getValue('tmp_template');
        $this->formData = $this->getFormData();
        $marketplaces = $this->amazonHelper->getMarketplacesAvailableForAsinCreation();
        $marketplaces = $marketplaces->toArray();
        $this->marketplaceData = $marketplaces['items'];
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        // ---------------------------------------

        $form->addField(
            'general_id',
            'hidden',
            [
                'name' => 'general[id]',
                'value' => $this->formData['id'],
            ]
        );

        $form->addField(
            'amazon_template_description_general',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    'This Tab contains main Settings of Description Policy such as selection of Amazon Category
                     and preparing Description Policy for New ASIN/ISBN Creation. <br/><br/>

                    The Description Policy has to be Created for a particular Marketplace,
                    so it can be used only for Listings with the same Marketplace. <br/>

                    You can select Amazon Category, in which Amazon Products should be placed.
                    Category must be selected to Create New ASIN/ISBN. <br/>

                    In case you are planning to use Description Policy for New ASIN/ISBN Creation,
                    you should enable New ASIN/ISBN Creation feature.<br/><br/>
                    More detailed information about ability to work with this Page you can find
                    <a href="%url%" target="_blank" class="external-link">here</a>.',
                    $this->supportHelper->getDocumentationArticleUrl('x/OP8UB')
                ),
            ]
        );

        // ---------------------------------------

        // ---------------------------------------
        // General
        // ---------------------------------------

        $fieldSet = $form->addFieldset('magento_block_template_description_edit_general', [
            'legend' => $this->__('General'),
            'collapsable' => false,
        ]);

        $additionalData = [];
        if ($this->isMarketplaceLocked() || $this->getRequest()->getParam('marketplace_id')) {
            $additionalData = [
                'disabled' => true,
                'after_element_html' => $this->getMarketplaceWarningMessageHtml() .
                    '<input id="marketplace_hidden_input"
                                                type="hidden"
                                                name="general[marketplace_id]"
                                                value="' . $this->formData['marketplace_id'] . '" />',
            ];
        }

        $fieldSet->addField(
            'marketplace_id',
            self::SELECT,
            array_merge(
                [
                    'name' => 'general[marketplace_id]',
                    'label' => $this->__('Marketplace'),
                    'title' => $this->__('Marketplace'),
                    'values' => $this->getMarketplaceDataOptions(),
                    'value' => $this->formData['marketplace_id'],
                    'required' => true,
                ],
                $additionalData
            )
        );

        // ---------------------------------------

        // ---------------------------------------
        // Category
        // ---------------------------------------

        $fieldSet = $form->addFieldset('magento_block_template_description_edit_category', [
            'legend' => $this->__('Category'),
            'collapsable' => false,
            'tooltip' => $this->__(
                '
                You can choose <strong>Amazon Category</strong>, in which your Amazon Products should be shown and the
                <strong>Product Type</strong>.<br/><br/>
                Selection of Category is necessary in case you are going to create
                New ASIN/ISBN using this Description Policy.<br/><br/>
                Since Amazon does not have a single structural
                Categories Tree and there are several of them, a tree that
                is presented in M2E Pro can be rather different from those you used earlier on
                Amazon or in other Applications.<br/>
                The list of available Specifics on Specifics tab depends on the selected Product Type.<br/><br/>
                <strong>Note:</strong> Possibility to specify Product Specifics becomes available only when Category
                and Product Type are selected.
            '
            ),
        ]);

        $fieldSet->addField(
            'template_description_edit_category_container',
            self::CUSTOM_CONTAINER,
            [
                'container_id' => 'category_path_container',
                'label' => $this->__('Category'),
                'title' => $this->__('Category'),
                'text' => $this->getCategoryHtml(),
            ]
        );

        $this->css->add('label.mage-error[for="category_path"] { width: 160px !important; left: initial !important; }');

        $productDataNick = $this->dataHelper->escapeHtml($this->formData['product_data_nick']);

        $fieldSet->addField(
            'product_data_nick_select',
            self::SELECT,
            [
                'label' => $this->__('Product Type'),
                'title' => $this->__('Product Type'),
                'class' => 'select M2ePro-required-when-visible',
                'field_extra_attributes' => 'id="product_data_nick_tr" style="display: none;"',
                'after_element_html' => '<input type="hidden"
                                                name="general[product_data_nick]"
                                                id="product_data_nick"
                                                value="' . $productDataNick . '" />',
            ]
        );

        // ---------------------------------------

        // ---------------------------------------
        // New ASIN/ISBN Creation
        // ---------------------------------------

        $fieldSet = $form->addFieldset('magento_block_template_description_edit_new_asin_creation', [
            'legend' => $this->__('New ASIN/ISBN Creation'),
            'collapsable' => false,
            'tooltip' => $this->__(
                'In case you are planning to create New ASIN/ISBN based on this
                 Description Policy you have to obviously enable this Option. <br/><br/>

                 To create New ASIN/ISBN it is also necessary to specify some required fields such as Title,
                 Brand, Manufacturer, Main Image, Category and required Specifics. <br/><br/>

                 To create New Amazon Product it is necessary to enter a valid value for UPC/EAN.
                 This UPC/EAN should not be used on Amazon at the moment.
                 If UPC/EAN does not exist for your Product you can use Product ID Override feature.'
            ),
        ]);

        $additionalData = [];
        if ($this->isNewAsinSwitcherLocked() || $this->getRequest()->getParam('is_new_asin_accepted')) {
            $additionalData['disabled'] = 'disabled';
        }

        if ($this->isNewAsinSwitcherLocked() || $this->getRequest()->getParam('is_new_asin_accepted')) {
            $additionalData['after_element_html'] = $this->getNewAsinLockedWarningMessage() .
                '<input id="new_asin_accepted_hidden_input"
                                                            type="hidden" name="general[is_new_asin_accepted]"
                                                            value="' . $this->formData['is_new_asin_accepted'] . '" />';
        }

        $fieldSet->addField(
            'new_asin_accepted',
            self::SELECT,
            array_merge(
                [
                    'name' => 'general[is_new_asin_accepted]',
                    'label' => $this->__('Enabled'),
                    'title' => $this->__('Enabled'),
                    'values' => [
                        ['value' => 0, 'label' => $this->__('No')],
                        ['value' => 1, 'label' => $this->__('Yes')],
                    ],
                    'value' => $this->formData['is_new_asin_accepted'],
                    'class' => 'required-entry',
                ],
                $additionalData
            )
        );

        // ---------------------------------------

        $this->setForm($form);

        return parent::_prepareForm();
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

    public function getNewAsinLockedWarningMessage()
    {
        return <<<HTML
        <div id="new_asin_locked_warning_message" class="m2epro-field-tooltip admin__field-tooltip">
            <a class="admin__field-tooltip-action" href="javascript://"></a>
            <div class="admin__field-tooltip-content">
                {$this->__(
            'You cannot turn off this Option because there are Item(s) for which new ASIN(s)/ISBN(s)
                             will be Created or being created based on this Policy. <br/>
                             Also this Option cannot be turned off in case there is a possibility
                             of Creation New Child ASIN(s)/ISBN(s) for your Parent Product based on this Policy.'
        )}
            </div>
        </div>
HTML;
    }

    public function getCategoryHtml()
    {
        $html = '<span style="font-style: italic; color: grey;">' . $this->__('Not Selected') . '</span>';
        if (!empty($this->formData['category_path']) && !empty($this->formData['browsenode_id'])) {
            $html = '<span>' . $this->escapeHtml(
                "{$this->formData['category_path']} ({$this->formData['browsenode_id']})"
            ) . '</span>';
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
            'This category is no longer available. Replace it with the category that suits your product the best and set Specifics.'
        )}
        </div>
    </div>
    <div id="category_variation_warning_message" class="category-warning-item m2epro-field-tooltip admin__field-tooltip"
    style="display: none;">
        <a class="admin__field-tooltip-action" href="javascript://"></a>
        <div class="admin__field-tooltip-content">
            {$this->__(
            'You have chosen Category in which Amazon Parent/Child Products are not allowed.'
        )}
        </div>
    </div>
</div>

HTML;

        return $html . <<<HTML
        <input type="hidden"
               name="general[category_path]"
               id="category_path"
               value="{$this->dataHelper->escapeHtml($this->formData['category_path'])}" />
        <input type="hidden"
               name="general[browsenode_id]"
               id="browsenode_id"
               value="{$this->formData['browsenode_id']}" />

       {$tooltip}
       <a id="edit_category_link" href="#" style="margin-left: 5px;">{$this->__('Edit')}</a>
HTML;
    }

    public function getMarketplaceDataOptions()
    {
        $optionsResult = [
            ['value' => '', 'label' => '', 'attrs' => ['style' => 'display: none;']],
        ];
        foreach ($this->marketplaceData as $marketplace) {
            $optionsResult[] = [
                'value' => $marketplace['id'],
                'label' => $this->__($marketplace['title']),
            ];
        }

        return $optionsResult;
    }

    public function getFormData()
    {
        $default = array_merge(
            $this->modelFactory->getObject('Amazon_Template_Description_Builder')->getDefaultData(),
            [
                'marketplace_id' => $this->getRequest()->getParam('marketplace_id', ''),
                'is_new_asin_accepted' => $this->getRequest()->getParam('is_new_asin_accepted', 0),
            ]
        );

        if (!$this->templateModel->getId()) {
            return $default;
        }

        $parentData = $this->templateModel->getData();
        $childData = $this->templateModel->getChildObject()->getData();

        return array_merge($default, $parentData, $childData);
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
            return $this->templateModel->getChildObject()->isLockedForCategoryChange();
        }

        return false;
    }

    public function isNewAsinSwitcherLocked()
    {
        if ($this->templateModel && $this->templateModel->getId()) {
            return $this->templateModel->getChildObject()->isLockedForNewAsinCreation();
        }

        return false;
    }

    protected function _beforeToHtml()
    {
        $this->jsTranslator->addTranslations([
            'Add Description Policy' => $this->__('Add Description Policy'),

            'Change Category' => $this->__('Edit Category'),
            'Not Selected' => $this->__('Not Selected'),
            'Select' => $this->__('Select'),

            'The specified Title is already used for another Policy. Policy Title must be unique.' => $this->__(
                'The specified Title is already used for another Policy. Policy Title must be unique.'
            ),
            'Please enter a valid number value in a specified range.' => $this->__(
                'Please enter a valid number value in a specified range.'
            ),

            'You should select Marketplace first.' => $this->__('You should select Marketplace first.'),
            'You should select Category and Product Type first' => $this->__(
                'You should select Category and Product Type first'
            ),

            'Recommended' => $this->__('Recommended'),
            'Recent' => $this->__('Recent'),
        ]);

        $formData = \Ess\M2ePro\Helper\Json::encode($this->formData);
        $isEdit = $this->templateModel->getId() ? 'true' : 'false';
        $isCategoryLocked = $this->isCategoryLocked() ? 'true' : 'false';
        $isMarketplaceLocked = $this->isMarketplaceLocked() ? 'true' : 'false';
        $marketplaceForceSet = \Ess\M2ePro\Helper\Json::encode(
            (bool)(int)$this->getRequest()->getParam('marketplace_id')
        );
        $isLockedNewAsin = $this->isNewAsinSwitcherLocked() ? 'true' : 'false';
        $newAsinSwitcherForceSet = \Ess\M2ePro\Helper\Json::encode(
            (bool)(int)$this->getRequest()->getParam('is_new_asin_accepted')
        );
        $allAttributes = \Ess\M2ePro\Helper\Json::encode($this->magentoAttributeHelper->getAll());

        $this->js->addRequireJs(
            [
            'jQuery' => 'jquery',
            'attr' => 'M2ePro/Attribute',
            'description' => 'M2ePro/Amazon/Template/Description/Description',
            'definition' => 'M2ePro/Amazon/Template/Description/Definition',
            'chooser' => 'M2ePro/Amazon/Template/Description/Category/Chooser',
            'specific' => 'M2ePro/Amazon/Template/Description/Category/Specific',

            'attribute_button' => 'M2ePro/Plugin/Magento/Attribute/Button',

            'blockRenderer' => 'M2ePro/Amazon/Template/Description/Category/Specific/BlockRenderer',
            'dictionary' => 'M2ePro/Amazon/Template/Description/Category/Specific/Dictionary',
            'renderer' => 'M2ePro/Amazon/Template/Description/Category/Specific/Renderer',

            'rowattributerenderer' => 'M2ePro/Amazon/Template/Description/Category/Specific/Grid/RowAttributeRenderer',
            'rowrenderer' => 'M2ePro/Amazon/Template/Description/Category/Specific/Grid/RowRenderer',

            'addspecificrenderer' => 'M2ePro/Amazon/Template/Description/Category/Specific/Block/AddSpecificRenderer',
            'gridrenderer' => 'M2ePro/Amazon/Template/Description/Category/Specific/Block/GridRenderer',
            ],
            <<<JS

        M2ePro.formData = {$formData};

        M2ePro.customData.is_edit = {$isEdit};

        M2ePro.customData.category_locked             = {$isCategoryLocked};
        M2ePro.customData.marketplace_locked          = {$isMarketplaceLocked};
        M2ePro.customData.marketplace_force_set       = {$marketplaceForceSet};
        M2ePro.customData.new_asin_switcher_locked    = {$isLockedNewAsin};
        M2ePro.customData.new_asin_switcher_force_set = {$newAsinSwitcherForceSet};

        if (typeof AttributeObj === 'undefined') {
            window.AttributeObj = new Attribute();
        }
        window.AttributeObj.setAvailableAttributes({$allAttributes});

        window.AmazonTemplateDescriptionObj                 = new AmazonTemplateDescription();
        window.AmazonTemplateDescriptionCategoryChooserObj  = new AmazonTemplateDescriptionCategoryChooser();
        window.AmazonTemplateDescriptionCategorySpecificObj = new AmazonTemplateDescriptionCategorySpecific();

        window.MagentoAttributeButtonObj = new MagentoAttributeButton();

        window.AmazonTemplateDescriptionDefinitionObj = new AmazonTemplateDescriptionDefinition();

        AmazonTemplateDescriptionObj.setSpecificHandler(AmazonTemplateDescriptionCategorySpecificObj);
        AmazonTemplateDescriptionCategoryChooserObj.setSpecificHandler(AmazonTemplateDescriptionCategorySpecificObj);

        jQuery(function() {
            AmazonTemplateDescriptionObj.initObservers();

            if ({$isEdit}) {
                AmazonTemplateDescriptionObj.prepareEditMode();
            }

            AmazonTemplateDescriptionDefinitionObj.initObservers();
        });
JS
        );

        return parent::_beforeToHtml();
    }
}
