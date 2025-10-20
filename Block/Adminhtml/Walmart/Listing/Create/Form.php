<?php

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Create;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Block\Adminhtml\StoreSwitcher;
use Ess\M2ePro\Model\Walmart\Listing as WalmartListing;
use Ess\M2ePro\Model\ResourceModel\Walmart\Listing as WalmartListingResource;

class Form extends AbstractForm
{
    private \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper;
    private \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory;
    private \Ess\M2ePro\Helper\Data $dataHelper;
    private \Ess\M2ePro\Model\Walmart\Marketplace\Repository $marketplaceRepository;

    public function __construct(
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper,
        \Ess\M2ePro\Model\Walmart\Marketplace\Repository $marketplaceRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->magentoAttributeHelper = $magentoAttributeHelper;
        $this->walmartFactory = $walmartFactory;
        $this->dataHelper = $dataHelper;
        $this->marketplaceRepository = $marketplaceRepository;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'method' => 'post',
                    'action' => $this->getUrl('*/walmart_listing/save'),
                    'enctype' => 'multipart/form-data',
                    'class' => 'admin__scope-old',
                ],
            ]
        );

        $formData = $this->getDefaultFieldsValues();

        $fieldset = $form->addFieldset(
            'general_fieldset',
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
                'required' => true,
                'class' => 'M2ePro-listing-title',
                'tooltip' => __('Listing Title for your internal use.'),
            ]
        );

        $fieldset = $form->addFieldset(
            'walmart_settings_fieldset',
            [
                'legend' => __('Walmart Settings'),
                'collapsable' => false,
            ]
        );

        $accountsCollection = $this->walmartFactory->getObject('Account')->getCollection()
                                                   ->setOrder('title', 'ASC');

        $accountsCollection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS)
                           ->columns(
                               [
                                   'value' => 'id',
                                   'label' => 'title',
                               ]
                           );

        $accountSelectionDisabled = false;

        $accountId = $formData['account_id'];
        if ($this->getRequest()->getParam('account_id')) {
            $accountId = $this->getRequest()->getParam('account_id');
            $fieldset->addField(
                'account_id_hidden',
                'hidden',
                [
                    'name' => 'account_id',
                    'value' => $accountId,
                ]
            );
            $accountSelectionDisabled = true;
        }

        $accounts = $accountsCollection->getConnection()->fetchAssoc($accountsCollection->getSelect());
        $accountSelect = $this->elementFactory->create(
            'select',
            [
                'data' => [
                    'html_id' => 'account_id',
                    'name' => 'account_id',
                    'style' => 'width: 50%;',
                    'value' => $accountId,
                    'values' => $accounts,
                    'required' => count($accounts) > 1,
                    'disabled' => $accountSelectionDisabled,
                ],
            ]
        );
        $accountSelect->setForm($form);

        $isAddAccountButtonHidden = $this->getRequest()->getParam('wizard', false) ? ' display: none;' : '';

        $addAnotherAccountButton = $this
            ->getLayout()
            ->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Magento\Button\DropDown::class
            );

        $addAnotherAccountButton->setData([
            'id' => 'add_account_button',
            'label' => __('Add Another'),
            'style' => 'margin-left: 5px;' . $isAddAccountButtonHidden,
            'class' => 'primary',
            'options' => $this->getDropdownOptions()
        ]);

        $fieldset->addField(
            'account_container',
            self::CUSTOM_CONTAINER,
            [
                'label' => __('Account'),
                'style' => 'line-height: 32px; display: initial;',
                'tooltip' => __('Select Account under which you want to manage this Listing.'),
                'text' => <<<HTML
    <span id="account_label"></span>
    {$accountSelect->toHtml()}
HTML
                ,

                'after_element_html' => sprintf(
                    '<div style="margin-left:5px; display: inline-block; position:absolute;%s">%s</div>',
                    $isAddAccountButtonHidden ? 'display: none;' : '',
                    $addAnotherAccountButton->toHtml()
                ),
            ]
        );

        $fieldset->addField(
            'marketplace_info',
            self::CUSTOM_CONTAINER,
            [
                'css_class' => 'no-margin-bottom',
                'label' => __('Marketplace'),
                'text' => '<span id="marketplace_title"></span><p class="note" id="marketplace_url"></p>',

                'field_extra_attributes' => 'id="marketplace_info" style="display: none; margin-top: 0px"',
            ]
        );

        $fieldset->addField(
            'marketplace_id',
            'hidden',
            [
                'value' => '',
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_fieldset',
            [
                'legend' => __('Magento Settings'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'store_id',
            self::STORE_SWITCHER,
            [
                'name' => 'store_id',
                'label' => __('Magento Store View'),
                'value' => $formData['store_id'],
                'required' => true,
                'has_empty_option' => true,
                'tooltip' => __(
                    'Choose the Magento Store View you want to use for this M2E Pro Listing.'
                ),

                'display_default_store_mode' => StoreSwitcher::DISPLAY_DEFAULT_STORE_MODE_DOWN,
            ]
        );

        $fieldset = $form->addFieldset(
            'policies_settings',
            [
                'legend' => __('Policies'),
                'collapsable' => false,
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
                    'values' => $sellingFormatTemplates,
                    'value' => $formData['template_selling_format_id'],
                    'required' => true,
                ],
            ]
        );
        $templateSellingFormat->setForm($form);

        $editPolicyTooltip = $this->getTooltipHtml(
            __(
                'At any time, you can edit the saved Policy. <br><br>
            <strong>Note:</strong> The changes you made will automatically affect all of the
            Products which were listed using this Policy.'
            )
        );

        $style = count($sellingFormatTemplates) === 0 ? '' : 'display: none';
        $fieldset->addField(
            'template_selling_format_container',
            self::CUSTOM_CONTAINER,
            [
                'label' => __('Selling Policy'),
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
<span style="line-height: 30px;">
    <span id="edit_selling_format_template_link" style="color:#41362f">
        <a href="javascript: void(0);" style="" onclick="WalmartListingSettingsObj.editTemplate(
            M2ePro.url.get('editSellingFormatTemplate'),
            $('template_selling_format_id').value,
            WalmartListingSettingsObj.newSellingFormatTemplateCallback
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
        onclick="WalmartListingSettingsObj.addNewTemplate(
            M2ePro.url.get('addNewSellingFormatTemplate'),
            WalmartListingSettingsObj.newSellingFormatTemplateCallback
    );">{$this->__('Add New')}</a>
</span>
HTML
            ,
            ]
        );

        $descriptionTemplates = $this->getDescriptionTemplates();
        $style = count($descriptionTemplates) === 0 ? 'display: none' : '';

        $templateDescription = $this->elementFactory->create(
            'select',
            [
                'data' => [
                    'html_id' => 'template_description_id',
                    'name' => 'template_description_id',
                    'style' => 'width: 50%;' . $style,
                    'no_span' => true,
                    'values' => $descriptionTemplates,
                    'value' => $formData['template_description_id'],
                    'required' => true,
                ],
            ]
        );
        $templateDescription->setForm($form);

        $style = count($descriptionTemplates) === 0 ? '' : 'display: none';
        $fieldset->addField(
            'template_description_container',
            self::CUSTOM_CONTAINER,
            [
                'label' => __('Description Policy'),
                'style' => 'line-height: 34px;display: initial;',
                'required' => true,
                'text' => <<<HTML
    <span id="template_description_label" style="padding-right: 25px; {$style}">
        {$this->__('No Policies available.')}
    </span>
    {$templateDescription->toHtml()}
HTML
                ,
                'after_element_html' => <<<HTML
&nbsp;
<span style="line-height: 30px;">
    <span id="edit_description_template_link" style="color:#41362f">
        <a href="javascript: void(0);" onclick="WalmartListingSettingsObj.editTemplate(
            M2ePro.url.get('editDescriptionTemplate'),
            $('template_description_id').value,
            WalmartListingSettingsObj.newDescriptionTemplateCallback
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
    <a id="add_description_template_link" href="javascript: void(0);"
        onclick="WalmartListingSettingsObj.addNewTemplate(
            M2ePro.url.get('addNewDescriptionTemplate'),
            WalmartListingSettingsObj.newDescriptionTemplateCallback
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
                    'values' => $synchronizationTemplates,
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
                'label' => __('Synchronization Policy'),
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
        <a href="javascript: void(0);" onclick="WalmartListingSettingsObj.editTemplate(
            M2ePro.url.get('editSynchronizationTemplate'),
            $('template_synchronization_id').value,
            WalmartListingSettingsObj.newSynchronizationTemplateCallback
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
        onclick="WalmartListingSettingsObj.addNewTemplate(
            M2ePro.url.get('addNewSynchronizationTemplate'),
            WalmartListingSettingsObj.newSynchronizationTemplateCallback
    );">{$this->__('Add New')}</a>
</span>
HTML
            ,
            ]
        );

        $this->addConditionFieldset($form, $formData);

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    // ---------------------------------------

    private function getDropdownOptions(): array
    {
        return [
            [
                'label' => __('United States'),
                'id' => 'account-us',
                'onclick' => 'setLocation(this.getAttribute("data-url"))',
                'data_attribute' => [
                    'url' => $this->getUrl(
                        '*/walmart_account_unitedStates/beforeGetToken',
                        [
                            '_current' => true,
                            'marketplace_id' => \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_US,
                            'specific_end_url' => $this->getUrl('*/*/*', ['_current' => true]),
                            'wizard' => (bool)$this->getRequest()->getParam('wizard', false),
                        ]
                    ),
                ],
            ],
            [
                'label' => __('Canada'),
                'id' => 'account-ca',
                'on_click' => '',
                'data_attribute' => [
                    'marketplace_id' => \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_CA,
                    'specific_end_url' => $this->getUrl('*/*/*', ['_current' => true]),
                    'wizard' => (bool)$this->getRequest()->getParam('wizard', false),
                ],
            ],
        ];
    }

    private function addConditionFieldset(\Magento\Framework\Data\Form $form, array $formData): void
    {
        $fieldset = $form->addFieldset(
            'condition_settings_fieldset',
            [
                'legend' => __('Condition Settings'),
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
        $magentoSelectTextAttrs = $this->magentoAttributeHelper->filterByInputTypes(
            $this->magentoAttributeHelper->getAll(),
            ['text', 'select']
        );
        foreach ($magentoSelectTextAttrs as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['condition_mode'] == \Ess\M2ePro\Model\Walmart\Listing::CONDITION_MODE_CUSTOM_ATTRIBUTE
                && $attribute['code'] == $formData['condition_custom_attribute']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \Ess\M2ePro\Model\Walmart\Listing::CONDITION_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'condition_mode',
            self::SELECT,
            [
                'name' => 'condition_mode',
                'label' => __('Condition'),
                'values' => [
                    [
                        'label' => __('Recommended Value'),
                        'value' => $this->getRecommendedConditionValues($formData),
                    ],
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'tooltip' => __('Specify the condition that best describes the current state of your product.'),
                'create_magento_attribute' => true,
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');
    }

    private function getRecommendedConditionValues(array $formData): array
    {
        $values = [];
        foreach (\Ess\M2ePro\Model\Walmart\Listing::CONDITION_RECOMMENDED_VALUES as $condition) {
            $value = [
                'attrs' => ['attribute_code' => $condition],
                'value' => \Ess\M2ePro\Model\Walmart\Listing::CONDITION_MODE_RECOMMENDED,
                'label' => __($condition),
            ];

            if ($condition === $formData[\Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_CONDITION_VALUE]) {
                $value['attrs']['selected'] = 'selected';
            }

            $values[] = $value;
        }

        return $values;
    }

    // ---------------------------------------

    protected function _prepareLayout()
    {
        $this->jsPhp->addConstants($this->dataHelper->getClassConstants(\Ess\M2ePro\Helper\Component\Walmart::class))
                    ->addConstants([
                        '\Ess\M2ePro\Model\Walmart\Listing::CONDITION_MODE_RECOMMENDED'
                        => \Ess\M2ePro\Model\Walmart\Listing::CONDITION_MODE_RECOMMENDED,
                    ]);

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Walmart\Account'));
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Walmart\Marketplace'));
        $this->jsUrl->addUrls([
            'walmart_marketplace_withProductType/runSynchNow' => $this->getUrl(
                '*/walmart_marketplace_withProductType/runSynchNow'
            ),
            'walmart_marketplace_withProductType/synchGetExecutingInfo' => $this->getUrl(
                '*/walmart_marketplace_withProductType/synchGetExecutingInfo'
            ),
        ]);

        $this->jsUrl->addUrls(
            [
                'templateCheckMessages' => $this->getUrl(
                    '*/template/checkMessages',
                    [
                        'component_mode' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
                    ]
                ),
                'logViewUrl' => $this->getUrl(
                    '*/walmart_synchronization_log/index',
                    [
                        'wizard' => (bool)$this->getRequest()->getParam('wizard', false),
                    ]
                ),
                'addNewSellingFormatTemplate' => $this->getUrl(
                    '*/walmart_template_sellingFormat/new',
                    [
                        'wizard' => $this->getRequest()->getParam('wizard'),
                        'close_on_save' => 1,
                    ]
                ),
                'editSellingFormatTemplate' => $this->getUrl(
                    '*/walmart_template_sellingFormat/edit',
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
                        'component_mode' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
                    ]
                ),
                'addNewDescriptionTemplate' => $this->getUrl(
                    '*/walmart_template_description/new',
                    [
                        'wizard' => $this->getRequest()->getParam('wizard'),
                        'close_on_save' => 1,
                    ]
                ),
                'editDescriptionTemplate' => $this->getUrl(
                    '*/walmart_template_description/edit',
                    [
                        'wizard' => $this->getRequest()->getParam('wizard'),
                        'close_on_save' => 1,
                    ]
                ),
                'getDescriptionTemplates' => $this->getUrl(
                    '*/general/modelGetAll',
                    [
                        'model' => 'Template_Description',
                        'id_field' => 'id',
                        'data_field' => 'title',
                        'sort_field' => 'title',
                        'sort_dir' => 'ASC',
                        'component_mode' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
                    ]
                ),
                'addNewSynchronizationTemplate' => $this->getUrl(
                    '*/walmart_template_synchronization/new',
                    [
                        'wizard' => $this->getRequest()->getParam('wizard'),
                        'close_on_save' => 1,
                    ]
                ),
                'editSynchronizationTemplate' => $this->getUrl(
                    '*/walmart_template_synchronization/edit',
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
                        'component_mode' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
                    ]
                ),
            ]
        );

        $this->jsTranslator->add(
            'The specified Title is already used for other Listing. Listing Title must be unique.',
            __(
                'The specified Title is already used for other Listing. Listing Title must be unique.'
            )
        );
        $this->jsTranslator->add(
            'Account not found, please create it.',
            __('Account not found, please create it.')
        );
        $this->jsTranslator->add('Add Another', __('Add Another'));
        $this->jsTranslator->add(
            'Please wait while Synchronization is finished.',
            __('Please wait while Synchronization is finished.')
        );

        $marketplacesSyncSettings = \json_encode($this->getMarketplacesSyncSettings());
        $this->js->add(
            <<<JS
    require([
        'M2ePro/TemplateManager',
        'M2ePro/Walmart/Listing/Settings',
        'M2ePro/Walmart/Listing/Create/General'
    ], function(){
        M2ePro.formData.wizard = {$this->getRequest()->getParam('wizard', 0)};

        window.TemplateManagerObj = new TemplateManager();

        window.WalmartListingCreateGeneralObj = new WalmartListingCreateGeneral();
        window.WalmartListingCreateGeneralObj.setMarketplacesSyncSettings($marketplacesSyncSettings);
        window.WalmartListingSettingsObj = new WalmartListingSettings();

        WalmartListingCreateGeneralObj.initObservers();
        WalmartListingSettingsObj.initObservers();
    });
JS
        );

        return parent::_prepareLayout();
    }

    // ---------------------------------------

    protected function getSellingFormatTemplates()
    {
        $collection = $this->walmartFactory->getObject('Template\SellingFormat')->getCollection();
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

    protected function getDescriptionTemplates()
    {
        $collection = $this->walmartFactory->getObject('Template\Description')->getCollection();
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

    protected function getSynchronizationTemplates()
    {
        $collection = $this->walmartFactory->getObject('Template\Synchronization')->getCollection();
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

    // ---------------------------------------

    public function getDefaultFieldsValues(): array
    {
        return [
            'title' => $this->walmartFactory->getObject('Listing')->getCollection()
                                            ->getSize() == 0 ? 'Default' : '',
            'account_id' => '',
            'store_id' => '',

            'template_selling_format_id' => '',
            'template_description_id' => '',
            'template_synchronization_id' => '',

            WalmartListingResource::COLUMN_CONDITION_MODE => WalmartListing::CONDITION_MODE_NONE,
            WalmartListingResource::COLUMN_CONDITION_VALUE => '',
            WalmartListingResource::COLUMN_CONDITION_CUSTOM_ATTRIBUTE => '',
        ];
    }

    private function getMarketplacesSyncSettings(): array
    {
        $result = [];
        foreach ($this->marketplaceRepository->findActive() as $marketplace) {
            $result[] = [
                'marketplace_id' => (int)$marketplace->getId(),
                'is_sync_with_product_type' => $marketplace->getChildObject()
                                                           ->isSupportedProductType(),
            ];
        }

        return $result;
    }
}
