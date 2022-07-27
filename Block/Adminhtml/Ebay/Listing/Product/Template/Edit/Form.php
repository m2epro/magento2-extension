<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Template\Edit;

use \Ess\M2ePro\Model\Ebay\Template\Manager as TemplateManager;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    private const VALUE_USE_FROM_LISTING = '';
    private const VALUE_DIFFERENT_TEMPLATES = '0';

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    private $ebayFactory;

    /** @var \Ess\M2ePro\Helper\Data */
    private $helperData;

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $helperDataGlobal;

    /**
     * @param \Ess\M2ePro\Helper\Data $helperData
     * @param \Ess\M2ePro\Helper\Data\GlobalData $helperDataGlobal
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Data\GlobalData $helperDataGlobal,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->helperData       = $helperData;
        $this->helperDataGlobal = $helperDataGlobal;
        $this->ebayFactory      = $ebayFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @return Form
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm(): Form
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id'     => 'edit_form',
                    'method' => 'post',
                    'action' => $this->getUrl('*/ebay_template/save'),
                ]
            ]
        );

        $formData = $this->getListingProductsData();

        $marketplace = $this->helperDataGlobal->getValue('ebay_marketplace');
        $store = $this->helperDataGlobal->getValue('ebay_store');

        $formData['marketplace_id'] = $marketplace->getId();
        $formData['store_id'] = $store->getId();

        $form->addField(
            'marketplace_id',
            'hidden',
            [
                'value' => $formData['marketplace_id']
            ]
        );

        $form->addField(
            'store_id',
            'hidden',
            [
                'value' => $formData['store_id']
            ]
        );

        $fieldset = $form->addFieldset(
            'payment_and_shipping_settings',
            [
                'legend'      => $this->__('Shipping'),
                'collapsable' => false
            ]
        );

        $shippingTemplates = $this->getShippingTemplates($formData['marketplace_id']);
        $style = count($shippingTemplates) === 0 ? 'display: none' : '';

        $templateShipping = $this->elementFactory->create(
            'select',
            [
                'data' => [
                    'html_id'  => 'template_shipping_id',
                    'name'     => 'template_shipping_id',
                    'style'    => 'width: 50%;' . $style,
                    'no_span'  => true,
                    'values'   => $this->getTemplateValues($shippingTemplates),
                    'value'    => $formData['template_shipping_id'],
                    'class'    => 'template-switcher M2ePro-validate-ebay-template-switcher'
                        . ' listing-policy-template-switcher',
                ]
            ]
        );
        $templateShipping->setForm($form);

        $style = count($shippingTemplates) === 0 ? '' : 'display: none';
        $fieldset->addField(
            'template_shipping_container',
            self::CUSTOM_CONTAINER,
            [
                'label'                  => $this->__('Shipping Policy'),
                'style'                  => 'line-height: 34px;display: initial;',
                'field_extra_attributes' => 'style="margin-bottom: 5px"',
                'required'               => true,
                'text'                   => <<<HTML
    <span id="template_shipping_label" style="{$style}">
        {$this->__('No Policies available.')}
    </span>
    {$templateShipping->toHtml()}
HTML
                ,
                'after_element_html'     => <<<HTML
&nbsp;
<span style="line-height: 30px;">
    <span id="edit_shipping_template_link" style="color:#41362f">
        <a href="javascript: void(0);" onclick="EbayListingProductSettingsObj.editTemplate(
            '{$this->getEditUrl(TemplateManager::TEMPLATE_SHIPPING)}',
            $('template_shipping_id').value,
            EbayListingProductSettingsObj.newShippingTemplateCallback
        );">{$this->__('View')}&nbsp;/&nbsp;{$this->__('Edit')}</a>
        <span>{$this->__('or')}</span>
    </span>
    <a id="add_shipping_template_link" href="javascript: void(0);"
        onclick="EbayListingProductSettingsObj.addNewTemplate(
        '{$this->getAddNewUrl($formData['marketplace_id'], TemplateManager::TEMPLATE_SHIPPING)}',
        EbayListingProductSettingsObj.newShippingTemplateCallback
    );">{$this->__('Add New')}</a>
    <span id="specify_shipping_template_link" class="specify-template-span">
        {$this->__('Please, specify a value suitable for all chosen Products.')}
    </span>
</span>
HTML
            ]
        );

        $returnPolicyTemplates = $this->getReturnPolicyTemplates($formData['marketplace_id']);
        $style = count($returnPolicyTemplates) === 0 ? 'display: none' : '';

        $templateReturnPolicy = $this->elementFactory->create(
            'select',
            [
                'data' => [
                    'html_id'  => 'template_return_policy_id',
                    'name'     => 'template_return_policy_id',
                    'style'    => 'width: 50%;' . $style,
                    'no_span'  => true,
                    'values'   => $this->getTemplateValues($returnPolicyTemplates),
                    'value'    => $formData['template_return_policy_id'],
                    'class'    => 'template-switcher M2ePro-validate-ebay-template-switcher'
                        . ' listing-policy-template-switcher',
                ]
            ]
        );
        $templateReturnPolicy->setForm($form);

        $style = count($returnPolicyTemplates) === 0 ? '' : 'display: none';
        $fieldset->addField(
            'template_return_policy_container',
            self::CUSTOM_CONTAINER,
            [
                'label'                  => $this->__('Return Policy'),
                'style'                  => 'line-height: 34px;display: initial;',
                'field_extra_attributes' => 'style="margin-bottom: 5px"',
                'required'               => true,
                'text'                   => <<<HTML
    <span id="template_return_policy_label" style="{$style}">
        {$this->__('No Policies available.')}
    </span>
    {$templateReturnPolicy->toHtml()}
HTML
                ,
                'after_element_html'     => <<<HTML
&nbsp;
<span style="line-height: 30px;">
    <span id="edit_return_policy_template_link" style="color:#41362f">
        <a href="javascript: void(0);" onclick="EbayListingProductSettingsObj.editTemplate(
            '{$this->getEditUrl(TemplateManager::TEMPLATE_RETURN_POLICY)}',
            $('template_return_policy_id').value,
            EbayListingProductSettingsObj.newReturnPolicyTemplateCallback
        );">
            {$this->__('View')}&nbsp;/&nbsp;{$this->__('Edit')}
        </a>
        <span>{$this->__('or')}</span>
    </span>
    <a id="add_return_policy_template_link" href="javascript: void(0);"
        onclick="EbayListingProductSettingsObj.addNewTemplate(
        '{$this->getAddNewUrl($formData['marketplace_id'], TemplateManager::TEMPLATE_RETURN_POLICY)}',
        EbayListingProductSettingsObj.newReturnPolicyTemplateCallback
    );">{$this->__('Add New')}</a>
    <span id="specify_return_policy_template_link" class="specify-template-span">
        {$this->__('Please, specify a value suitable for all chosen Products.')}
    </span>
</span>
HTML
            ]
        );

        $fieldset = $form->addFieldset(
            'selling_settings',
            [
                'legend'      => $this->__('Selling'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'template_selling_format_messages',
            self::CUSTOM_CONTAINER,
            [
                'style'     => 'display: block;',
                'css_class' => 'm2epro-fieldset-table no-margin-bottom'
            ]
        );

        $sellingFormatTemplates = $this->getSellingFormatTemplates();
        $style = count($sellingFormatTemplates) === 0 ? 'display: none' : '';

        $templateSellingFormat = $this->elementFactory->create(
            'select',
            [
                'data' => [
                    'html_id'  => 'template_selling_format_id',
                    'name'     => 'template_selling_format_id',
                    'style'    => 'width: 50%;' . $style,
                    'no_span'  => true,
                    'values'   => $this->getTemplateValues($sellingFormatTemplates),
                    'value'    => $formData['template_selling_format_id'],
                    'class'    => 'template-switcher M2ePro-validate-ebay-template-switcher'
                        . ' listing-policy-template-switcher',
                ]
            ]
        );
        $templateSellingFormat->setForm($form);

        $style = count($sellingFormatTemplates) === 0 ? '' : 'display: none';
        $fieldset->addField(
            'template_selling_format_container',
            self::CUSTOM_CONTAINER,
            [
                'label'                  => $this->__('Selling Policy'),
                'style'                  => 'line-height: 34px;display: initial;',
                'field_extra_attributes' => 'style="margin-bottom: 5px"',
                'required'               => true,
                'text'                   => <<<HTML
    <span id="template_selling_format_label" style="{$style}">
        {$this->__('No Policies available.')}
    </span>
    {$templateSellingFormat->toHtml()}
HTML
                ,
                'after_element_html'     => <<<HTML
&nbsp;
<span style="line-height: 30px;">
    <span id="edit_selling_format_template_link" style="color:#41362f">
        <a href="javascript: void(0);" style="" onclick="EbayListingProductSettingsObj.editTemplate(
            '{$this->getEditUrl(TemplateManager::TEMPLATE_SELLING_FORMAT)}',
            $('template_selling_format_id').value,
            EbayListingProductSettingsObj.newSellingFormatTemplateCallback
        );">
            {$this->__('View')}&nbsp;/&nbsp;{$this->__('Edit')}
        </a>
        <span>{$this->__('or')}</span>
    </span>
    <a id="add_selling_format_template_link" href="javascript: void(0);"
        onclick="EbayListingProductSettingsObj.addNewTemplate(
        '{$this->getAddNewUrl($formData['marketplace_id'], TemplateManager::TEMPLATE_SELLING_FORMAT)}',
        EbayListingProductSettingsObj.newSellingFormatTemplateCallback
    );">{$this->__('Add New')}</a>
    <span id="specify_selling_template_link" class="specify-template-span">
        {$this->__('Please, specify a value suitable for all chosen Products.')}
    </span>
</span>
HTML
            ]
        );

        $descriptionTemplates = $this->getDescriptionTemplates();
        $style = count($descriptionTemplates) === 0 ? 'display: none' : '';

        $templateDescription = $this->elementFactory->create(
            'select',
            [
                'data' => [
                    'html_id'  => 'template_description_id',
                    'name'     => 'template_description_id',
                    'style'    => 'width: 50%;' . $style,
                    'no_span'  => true,
                    'values'   => $this->getTemplateValues($descriptionTemplates),
                    'value'    => $formData['template_description_id'],
                    'class'    => 'template-switcher M2ePro-validate-ebay-template-switcher'
                        . ' listing-policy-template-switcher',
                ]
            ]
        );
        $templateDescription->setForm($form);

        $style = count($descriptionTemplates) === 0 ? '' : 'display: none';
        $fieldset->addField(
            'template_description_container',
            self::CUSTOM_CONTAINER,
            [
                'label'                  => $this->__('Description Policy'),
                'style'                  => 'line-height: 34px;display: initial;',
                'field_extra_attributes' => 'style="margin-bottom: 5px"',
                'required'               => true,
                'text'                   => <<<HTML
    <span id="template_description_label" style="{$style}">
        {$this->__('No Policies available.')}
    </span>
    {$templateDescription->toHtml()}
HTML
                ,
                'after_element_html'     => <<<HTML
&nbsp;
<span style="line-height: 30px;">
    <span id="edit_description_template_link" style="color:#41362f">
        <a href="javascript: void(0);" onclick="EbayListingProductSettingsObj.editTemplate(
            '{$this->getEditUrl(TemplateManager::TEMPLATE_DESCRIPTION)}',
            $('template_description_id').value,
            EbayListingProductSettingsObj.newDescriptionTemplateCallback
        );">
            {$this->__('View')}&nbsp;/&nbsp;{$this->__('Edit')}
        </a>
        <span>{$this->__('or')}</span>
    </span>
    <a id="add_description_template_link" href="javascript: void(0);"
        onclick="EbayListingProductSettingsObj.addNewTemplate(
        '{$this->getAddNewUrl($formData['marketplace_id'], TemplateManager::TEMPLATE_DESCRIPTION)}',
        EbayListingProductSettingsObj.newDescriptionTemplateCallback
    );">{$this->__('Add New')}</a>
    <span id="specify_description_template_link" class="specify-template-span">
        {$this->__('Please, specify a value suitable for all chosen Products.')}
    </span>
</span>
HTML
            ]
        );

        $fieldset = $form->addFieldset(
            'synchronization_settings',
            [
                'legend'      => $this->__('Synchronization'),
                'collapsable' => false
            ]
        );

        $synchronizationTemplates = $this->getSynchronizationTemplates();
        $style = count($synchronizationTemplates) === 0 ? 'display: none' : '';

        $templateSynchronization = $this->elementFactory->create(
            'select',
            [
                'data' => [
                    'html_id'  => 'template_synchronization_id',
                    'name'     => 'template_synchronization_id',
                    'style'    => 'width: 50%;' . $style,
                    'no_span'  => true,
                    'values'   => $this->getTemplateValues($synchronizationTemplates),
                    'value'    => $formData['template_synchronization_id'],
                    'class'    => 'template-switcher M2ePro-validate-ebay-template-switcher'
                        . ' listing-policy-template-switcher',
                ]
            ]
        );
        $templateSynchronization->setForm($form);

        $style = count($synchronizationTemplates) === 0 ? '' : 'display: none';
        $fieldset->addField(
            'template_synchronization_container',
            self::CUSTOM_CONTAINER,
            [
                'label'                  => $this->__('Synchronization Policy'),
                'style'                  => 'line-height: 34px;display: initial;',
                'field_extra_attributes' => 'style="margin-bottom: 5px"',
                'required'               => true,
                'text'                   => <<<HTML
    <span id="template_synchronization_label" style="{$style}">
        {$this->__('No Policies available.')}
    </span>
    {$templateSynchronization->toHtml()}
HTML
                ,
                'after_element_html'     => <<<HTML
&nbsp;
<span style="line-height: 30px;">
    <span id="edit_synchronization_template_link" style="color:#41362f">
        <a href="javascript: void(0);" onclick="EbayListingProductSettingsObj.editTemplate(
            '{$this->getEditUrl(TemplateManager::TEMPLATE_SYNCHRONIZATION)}',
            $('template_synchronization_id').value,
            EbayListingProductSettingsObj.newSynchronizationTemplateCallback
        );">
            {$this->__('View')}&nbsp;/&nbsp;{$this->__('Edit')}
        </a>
        <span>{$this->__('or')}</span>
    </span>
    <a id="add_synchronization_template_link" href="javascript: void(0);"
        onclick="EbayListingProductSettingsObj.addNewTemplate(
        '{$this->getAddNewUrl($formData['marketplace_id'], TemplateManager::TEMPLATE_SYNCHRONIZATION)}',
        EbayListingProductSettingsObj.newSynchronizationTemplateCallback
    );">{$this->__('Add New')}</a>
    <span id="specify_synchronization_template_link" class="specify-template-span">
        {$this->__('Please, specify a value suitable for all chosen Products.')}
    </span>
</span>
HTML
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Template\Edit\Form|\Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \ReflectionException
     */
    protected function _prepareLayout()
    {
        $formData = $this->getListingProductsData();

        $marketplace = $this->helperDataGlobal->getValue('ebay_marketplace');
        $formData['marketplace_id'] = $marketplace->getId();

        $this->jsPhp->addConstants(
            $this->helperData->getClassConstants(\Ess\M2ePro\Helper\Component\Ebay::class)
        );

        $this->jsUrl->addUrls(
            [
                'templateCheckMessages' => $this->getUrl(
                    '*/template/checkMessages',
                    ['component_mode' => \Ess\M2ePro\Helper\Component\Ebay::NICK]
                ),
                'getShippingTemplates' => $this->getUrl(
                    '*/general/modelGetAll',
                    [
                        'model'              => 'Ebay_Template_Shipping',
                        'id_field'           => 'id',
                        'data_field'         => 'title',
                        'sort_field'         => 'title',
                        'sort_dir'           => 'ASC',
                        'marketplace_id'     => $formData['marketplace_id'],
                        'is_custom_template' => 0
                    ]
                ),
                'getReturnPolicyTemplates' => $this->getUrl(
                    '*/general/modelGetAll',
                    [
                        'model'              => 'Ebay_Template_ReturnPolicy',
                        'id_field'           => 'id',
                        'data_field'         => 'title',
                        'sort_field'         => 'title',
                        'sort_dir'           => 'ASC',
                        'marketplace_id'     => $formData['marketplace_id'],
                        'is_custom_template' => 0
                    ]
                ),
                'getSellingFormatTemplates' => $this->getUrl(
                    '*/general/modelGetAll',
                    [
                        'model'              => 'Template_SellingFormat',
                        'id_field'           => 'id',
                        'data_field'         => 'title',
                        'sort_field'         => 'title',
                        'sort_dir'           => 'ASC',
                        'component_mode'     => \Ess\M2ePro\Helper\Component\Ebay::NICK,
                        'is_custom_template' => 0
                    ]
                ),
                'getDescriptionTemplates' => $this->getUrl(
                    '*/general/modelGetAll',
                    [
                        'model'              => 'Template_Description',
                        'id_field'           => 'id',
                        'data_field'         => 'title',
                        'sort_field'         => 'title',
                        'sort_dir'           => 'ASC',
                        'component_mode'     => \Ess\M2ePro\Helper\Component\Ebay::NICK,
                        'is_custom_template' => 0
                    ]
                ),
                'getSynchronizationTemplates' => $this->getUrl(
                    '*/general/modelGetAll',
                    [
                        'model'              => 'Template_Synchronization',
                        'id_field'           => 'id',
                        'data_field'         => 'title',
                        'sort_field'         => 'title',
                        'sort_dir'           => 'ASC',
                        'component_mode'     => \Ess\M2ePro\Helper\Component\Ebay::NICK,
                        'is_custom_template' => 0
                    ]
                )
            ]
        );

        $this->js->add(
            <<<JS
    require([
        'M2ePro/TemplateManager',
        'M2ePro/Ebay/Listing/Product/Settings',
    ], function() {
        TemplateManagerObj = new TemplateManager();
        EbayListingProductSettingsObj = new EbayListingProductSettings();
        EbayListingProductSettingsObj.initObservers();
    });
JS
        );

        return parent::_prepareLayout();
    }

    /**
     * @param mixed $marketplaceId
     * @return mixed
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getShippingTemplates($marketplaceId)
    {
        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Shipping\Collection */
        $collection = $this->activeRecordFactory->getObject('Ebay_Template_Shipping')->getCollection();
        $collection->addFieldToFilter('marketplace_id', $marketplaceId);
        $collection->addFieldToFilter('is_custom_template', 0);
        $collection->setOrder('title', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS)->columns(
            [
                'value' => 'id',
                'label' => 'title'
            ]
        );

        $result = $collection->toArray();

        return $result['items'];
    }

    /**
     * @param mixed $marketplaceId
     * @return mixed
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getReturnPolicyTemplates($marketplaceId)
    {
        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Ebay\Template\ReturnPolicy\Collection */
        $collection = $this->activeRecordFactory->getObject('Ebay_Template_ReturnPolicy')->getCollection();
        $collection->addFieldToFilter('marketplace_id', $marketplaceId);
        $collection->addFieldToFilter('is_custom_template', 0);
        $collection->setOrder('title', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS)->columns(
            [
                'value' => 'id',
                'label' => 'title'
            ]
        );

        $result = $collection->toArray();

        return $result['items'];
    }

    /**
     * @return mixed
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getSellingFormatTemplates()
    {
        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Template\SellingFormat\Collection */
        $collection = $this->ebayFactory->getObject('Template_SellingFormat')->getCollection();
        $collection->addFieldToFilter('is_custom_template', 0);
        $collection->setOrder('title', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS)->columns(
            [
                'value' => 'id',
                'label' => 'title'
            ]
        );

        $result = $collection->toArray();

        return $result['items'];
    }

    /**
     * @return mixed
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getDescriptionTemplates()
    {
        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Template\Description\Collection */
        $collection = $this->ebayFactory->getObject('Template_Description')->getCollection();
        $collection->addFieldToFilter('is_custom_template', 0);
        $collection->setOrder('title', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS)->columns(
            [
                'value' => 'id',
                'label' => 'title'
            ]
        );

        $result = $collection->toArray();

        return $result['items'];
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getSynchronizationTemplates(): array
    {
        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Template\Synchronization\Collection */
        $collection = $this->ebayFactory->getObject('Template_Synchronization')->getCollection();
        $collection->addFieldToFilter('is_custom_template', 0);
        $collection->setOrder('title', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS)->columns(
            [
                'value' => 'id',
                'label' => 'title'
            ]
        );

        return $collection->getConnection()->fetchAssoc($collection->getSelect());
    }

    /**
     * @param mixed $marketplaceId
     * @param mixed $nick
     * @return string
     */
    private function getAddNewUrl($marketplaceId, $nick): string
    {
        return $this->getUrl(
            '*/ebay_template/newAction',
            [
                'marketplace_id' => $marketplaceId,
                'wizard'         => $this->getRequest()->getParam('wizard'),
                'nick'           => $nick,
                'close_on_save'  => 1
            ]
        );
    }

    /**
     * @param mixed $nick
     * @return string
     */
    private function getEditUrl($nick): string
    {
        return $this->getUrl(
            '*/ebay_template/edit',
            [
                'wizard'        => $this->getRequest()->getParam('wizard'),
                'nick'          => $nick,
                'close_on_save' => 1
            ]
        );
    }

    /**
     * @param mixed $template
     * @return array
     */
    private function getTemplateValues($template): array
    {
        return [
            [
                'value' => self::VALUE_DIFFERENT_TEMPLATES,
                'label' => '',
            ],
            [
                'value' => self::VALUE_USE_FROM_LISTING,
                'label' => 'Use From Listing Settings',
            ],
            [
                'value' => $template,
                'label' => 'Policies',
            ],
        ];
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getListingProductsData(): array
    {
        $templates = [
            'shipping',
            'return_policy',
            'selling_format',
            'description',
            'synchronization',
        ];

        $resultData = [];

        foreach ($templates as $templateName) {
            if (!$this->helperDataGlobal->getValue('ebay_template_force_parent_'.$templateName)) {
                $templateData = $this->helperDataGlobal->getValue('ebay_template_'.$templateName);
                $resultData['template_'.$templateName.'_id'] = $templateData->getId();
            } else {
                $resultData['template_'.$templateName.'_id'] = self::VALUE_DIFFERENT_TEMPLATES;
            }
        }

        return $resultData;
    }
}
