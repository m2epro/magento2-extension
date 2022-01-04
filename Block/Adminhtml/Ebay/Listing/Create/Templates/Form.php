<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Create\Templates;

use \Ess\M2ePro\Model\Ebay\Template\Manager as TemplateManager;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Create\Templates\Form
 */
class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing;

    protected $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->ebayFactory = $ebayFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id'     => 'edit_form',
                    'method' => 'post',
                    'action' => $this->getUrl('*/ebay_listing/save')
                ]
            ]
        );

        $formData = $this->getListingData();

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
                'legend'      => $this->__('Payment and Shipping'),
                'collapsable' => false
            ]
        );

        $paymentTemplates = $this->getPaymentTemplates($formData['marketplace_id']);
        $style = count($paymentTemplates) === 0 ? 'display: none' : '';

        $templatePayment = $this->elementFactory->create(
            'select',
            [
                'data' => [
                    'html_id'  => 'template_payment_id',
                    'name'     => 'template_payment_id',
                    'style'    => 'width: 50%;' . $style,
                    'no_span'  => true,
                    'values'   => array_merge(['' => ''], $paymentTemplates),
                    'value'    => $formData['template_payment_id'],
                    'required' => true
                ]
            ]
        );
        $templatePayment->setForm($form);

        $style = count($paymentTemplates) === 0 ? '' : 'display: none';
        $fieldset->addField(
            'template_payment_container',
            self::CUSTOM_CONTAINER,
            [
                'label'                  => $this->__('Payment Policy'),
                'style'                  => 'line-height: 34px;display: initial;',
                'field_extra_attributes' => 'style="margin-bottom: 5px"',
                'required'               => true,
                'text'                   => <<<HTML
    <span id="template_payment_label" style="{$style}">
        {$this->__('No Policies available.')}
    </span>
    {$templatePayment->toHtml()}
HTML
                ,
                'after_element_html'     => <<<HTML
&nbsp;
<span style="line-height: 30px;">
    <span id="edit_payment_template_link" style="color:#41362f">
        <a href="javascript: void(0);" onclick="EbayListingSettingsObj.editTemplate(
            '{$this->getEditUrl(TemplateManager::TEMPLATE_PAYMENT)}', 
            $('template_payment_id').value,
            EbayListingSettingsObj.newPaymentTemplateCallback
        );">{$this->__('View')}&nbsp;/&nbsp;{$this->__('Edit')}</a>
        <span>{$this->__('or')}</span>
    </span>
    <a id="add_payment_template_link" href="javascript: void(0);"
        onclick="EbayListingSettingsObj.addNewTemplate(
        '{$this->getAddNewUrl($formData['marketplace_id'], TemplateManager::TEMPLATE_PAYMENT)}',
        EbayListingSettingsObj.newPaymentTemplateCallback
    );">{$this->__('Add New')}</a>
</span>
HTML
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
                    'values'   => array_merge(['' => ''], $shippingTemplates),
                    'value'    => $formData['template_shipping_id'],
                    'required' => true
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
        <a href="javascript: void(0);" onclick="EbayListingSettingsObj.editTemplate(
            '{$this->getEditUrl(TemplateManager::TEMPLATE_SHIPPING)}', 
            $('template_shipping_id').value,
            EbayListingSettingsObj.newShippingTemplateCallback
        );">{$this->__('View')}&nbsp;/&nbsp;{$this->__('Edit')}</a>
        <span>{$this->__('or')}</span>
    </span>
    <a id="add_shipping_template_link" href="javascript: void(0);"
        onclick="EbayListingSettingsObj.addNewTemplate(
        '{$this->getAddNewUrl($formData['marketplace_id'], TemplateManager::TEMPLATE_SHIPPING)}',
        EbayListingSettingsObj.newShippingTemplateCallback
    );">{$this->__('Add New')}</a>
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
                    'values'   => array_merge(['' => ''], $returnPolicyTemplates),
                    'value'    => $formData['template_return_policy_id'],
                    'required' => true
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
        <a href="javascript: void(0);" onclick="EbayListingSettingsObj.editTemplate(
            '{$this->getEditUrl(TemplateManager::TEMPLATE_RETURN_POLICY)}', 
            $('template_return_policy_id').value,
            EbayListingSettingsObj.newReturnPolicyTemplateCallback
        );">
            {$this->__('View')}&nbsp;/&nbsp;{$this->__('Edit')}
        </a>
        <span>{$this->__('or')}</span>
    </span>
    <a id="add_return_policy_template_link" href="javascript: void(0);"
        onclick="EbayListingSettingsObj.addNewTemplate(
        '{$this->getAddNewUrl($formData['marketplace_id'], TemplateManager::TEMPLATE_RETURN_POLICY)}',
        EbayListingSettingsObj.newReturnPolicyTemplateCallback
    );">{$this->__('Add New')}</a>
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
                    'values'   => array_merge(['' => ''], $sellingFormatTemplates),
                    'value'    => $formData['template_selling_format_id'],
                    'required' => true
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
        <a href="javascript: void(0);" style="" onclick="EbayListingSettingsObj.editTemplate(
            '{$this->getEditUrl(TemplateManager::TEMPLATE_SELLING_FORMAT)}', 
            $('template_selling_format_id').value,
            EbayListingSettingsObj.newSellingFormatTemplateCallback
        );">
            {$this->__('View')}&nbsp;/&nbsp;{$this->__('Edit')}
        </a>
        <span>{$this->__('or')}</span>
    </span>
    <a id="add_selling_format_template_link" href="javascript: void(0);"
        onclick="EbayListingSettingsObj.addNewTemplate(
        '{$this->getAddNewUrl($formData['marketplace_id'], TemplateManager::TEMPLATE_SELLING_FORMAT)}',
        EbayListingSettingsObj.newSellingFormatTemplateCallback
    );">{$this->__('Add New')}</a>
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
                    'values'   => array_merge(['' => ''], $descriptionTemplates),
                    'value'    => $formData['template_description_id'],
                    'required' => true
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
        <a href="javascript: void(0);" onclick="EbayListingSettingsObj.editTemplate(
            '{$this->getEditUrl(TemplateManager::TEMPLATE_DESCRIPTION)}', 
            $('template_description_id').value,
            EbayListingSettingsObj.newDescriptionTemplateCallback
        );">
            {$this->__('View')}&nbsp;/&nbsp;{$this->__('Edit')}
        </a>
        <span>{$this->__('or')}</span>
    </span>
    <a id="add_description_template_link" href="javascript: void(0);"
        onclick="EbayListingSettingsObj.addNewTemplate(
        '{$this->getAddNewUrl($formData['marketplace_id'], TemplateManager::TEMPLATE_DESCRIPTION)}',
        EbayListingSettingsObj.newDescriptionTemplateCallback
    );">{$this->__('Add New')}</a>
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
                    'values'   => array_merge(['' => ''], $synchronizationTemplates),
                    'value'    => $formData['template_synchronization_id'],
                    'required' => true
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
        <a href="javascript: void(0);" onclick="EbayListingSettingsObj.editTemplate(
            '{$this->getEditUrl(TemplateManager::TEMPLATE_SYNCHRONIZATION)}', 
            $('template_synchronization_id').value,
            EbayListingSettingsObj.newSynchronizationTemplateCallback
        );">
            {$this->__('View')}&nbsp;/&nbsp;{$this->__('Edit')}
        </a>
        <span>{$this->__('or')}</span>
    </span>
    <a id="add_synchronization_template_link" href="javascript: void(0);"
        onclick="EbayListingSettingsObj.addNewTemplate(
        '{$this->getAddNewUrl($formData['marketplace_id'], TemplateManager::TEMPLATE_SYNCHRONIZATION)}',
        EbayListingSettingsObj.newSynchronizationTemplateCallback
    );">{$this->__('Add New')}</a>
</span>
HTML
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################

    protected function _prepareLayout()
    {
        $formData = $this->getListingData();

        $this->jsPhp->addConstants(
            $this->getHelper('Data')
                ->getClassConstants(\Ess\M2ePro\Helper\Component\Ebay::class)
        );

        $this->jsUrl->addUrls(
            [
                'templateCheckMessages'       => $this->getUrl(
                    '*/template/checkMessages',
                    [
                        'component_mode' => \Ess\M2ePro\Helper\Component\Ebay::NICK
                    ]
                ),
                'getPaymentTemplates'         => $this->getUrl(
                    '*/general/modelGetAll',
                    [
                        'model'              => 'Ebay_Template_Payment',
                        'id_field'           => 'id',
                        'data_field'         => 'title',
                        'sort_field'         => 'title',
                        'sort_dir'           => 'ASC',
                        'marketplace_id'     => $formData['marketplace_id'],
                        'is_custom_template' => 0
                    ]
                ),
                'getShippingTemplates'        => $this->getUrl(
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
                'getReturnPolicyTemplates'    => $this->getUrl(
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
                'getSellingFormatTemplates'   => $this->getUrl(
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
                'getDescriptionTemplates'     => $this->getUrl(
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

        $this->js->addOnReadyJs(
            <<<JS
    require([
        'M2ePro/TemplateManager',
        'M2ePro/Ebay/Listing/Settings'
    ], function(){
        TemplateManagerObj = new TemplateManager();
        EbayListingSettingsObj = new EbayListingSettings();
        EbayListingSettingsObj.initObservers();
    });
JS
        );

        return parent::_prepareLayout();
    }

    //########################################

    public function getDefaultFieldsValues()
    {
        return [
            'template_payment_id'         => '',
            'template_shipping_id'        => '',
            'template_return_policy_id'   => '',
            'template_selling_format_id'  => '',
            'template_description_id'     => '',
            'template_synchronization_id' => '',
        ];
    }

    //########################################

    protected function getListingData()
    {
        if ($this->getRequest()->getParam('id') !== null) {
            $data = array_merge($this->getListing()->getData(), $this->getListing()->getChildObject()->getData());
        } else {
            $data = $this->getHelper('Data_Session')->getValue(
                \Ess\M2ePro\Model\Ebay\Listing::CREATE_LISTING_SESSION_DATA
            );
            $data = array_merge($this->getDefaultFieldsValues(), $data);
        }

        return $data;
    }

    //########################################

    protected function getListing()
    {
        if ($this->listing === null && $this->getRequest()->getParam('id')) {
            $this->listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $this->getRequest()->getParam('id'));
        }

        return $this->listing;
    }

    //########################################

    protected function getPaymentTemplates($marketplaceId)
    {
        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Payment\Collection */
        $collection = $this->activeRecordFactory->getObject('Ebay_Template_Payment')->getCollection();
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

    protected function getShippingTemplates($marketplaceId)
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

    protected function getReturnPolicyTemplates($marketplaceId)
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

    protected function getSellingFormatTemplates()
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

    protected function getDescriptionTemplates()
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

    protected function getSynchronizationTemplates()
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

    //########################################

    protected function getAddNewUrl($marketplaceId, $nick)
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

    protected function getEditUrl($nick)
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

    //########################################
}
