<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Edit\Form
 */
class Form extends AbstractForm
{
    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing;

    protected $walmartFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->walmartFactory = $walmartFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $formData = $this->getListingData();

        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id'      => 'edit_form',
                    'method'  => 'post',
                    'action'  => $this->getUrl('*/walmart_listing/save'),
                    'enctype' => 'multipart/form-data'
                ]
            ]
        );

        $form->addField(
            'store_id',
            'hidden',
            [
                'value' => $formData['store_id']
            ]
        );

        $form->addField(
            'marketplace_id',
            'hidden',
            [
                'value' => $formData['marketplace_id']
            ]
        );

        // Policies
        $fieldset = $form->addFieldset(
            'policies_settings',
            [
                'legend' => $this->__('Policies Settings'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'template_selling_format_messages',
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
                'label' => $this->__('Selling Policy'),
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
        <a href="javascript: void(0);" style="" onclick="WalmartListingSettingsObj.openWindow(
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
    <a href="javascript: void(0);" onclick="WalmartListingSettingsObj.addNewTemplate(
        M2ePro.url.get(
                'addNewSellingFormatTemplate',
                {close_on_save: 1}),
                WalmartListingSettingsObj.newSellingFormatTemplateCallback
    );">{$this->__('Add New')}</a>
</span>
HTML
            ]
        );

        $descriptionTemplates = $this->getDescriptionTemplates();
        $style = count($descriptionTemplates) === 0 ? 'display: none' : '';

        $templateDescription = $this->elementFactory->create('select', [
            'data' => [
                'html_id' => 'template_description_id',
                'name' => 'template_description_id',
                'style' => 'width: 50%;' . $style,
                'no_span' => true,
                'values' => array_merge([
                    '' => ''
                ], $descriptionTemplates),
                'value' => $formData['template_description_id'],
                'required' => true
            ]
        ]);
        $templateDescription->setForm($form);

        $style = count($descriptionTemplates) === 0 ? '' : 'display: none';
        $fieldset->addField(
            'template_description_container',
            self::CUSTOM_CONTAINER,
            [
                'label' => $this->__('Description Policy'),
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
<span style="line-height: 20px;">
    <span id="edit_description_template_link" style="color:#41362f">
        <a href="javascript: void(0);" onclick="WalmartListingSettingsObj.openWindow(
            M2ePro.url.get(
                    'editDescriptionTemplate',
                    {id: $('template_description_id').value,
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
    <a href="javascript: void(0);" onclick="WalmartListingSettingsObj.addNewTemplate(
        M2ePro.url.get(
                'addNewDescriptionTemplate',
                {close_on_save: 1}),
                WalmartListingSettingsObj.newDescriptionTemplateCallback
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
        <a href="javascript: void(0);" onclick="WalmartListingSettingsObj.openWindow(
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
    <a href="javascript: void(0);" onclick="WalmartListingSettingsObj.addNewTemplate(
        M2ePro.url.get(
                'addNewSynchronizationTemplate',
                {close_on_save: 1}),
                WalmartListingSettingsObj.newSynchronizationTemplateCallback
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

    protected function _toHtml()
    {
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('General', [
            'component' => \Ess\M2ePro\Helper\Component\Walmart::NICK
        ]));
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Walmart\Account'));
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Walmart\Marketplace'));
        $this->jsUrl->addUrls(
            $this->getHelper('Data')->getControllerActions('Walmart_Listing_Create', ['_current' => true])
        );

        $this->jsUrl->add($this->getUrl('*/walmart_account/newAction', [
            'close_on_save' => true,
            'wizard' => (bool)$this->getRequest()->getParam('wizard', false)
        ]), 'walmart_account/newAction');

        $this->jsUrl->add($this->getUrl(
            '*/template/checkMessages',
            ['component_mode' => \Ess\M2ePro\Helper\Component\Walmart::NICK]
        ), 'templateCheckMessages');
        $this->jsUrl->add($this->getUrl(
            '*/walmart_template_sellingFormat/new',
            ['wizard' => $this->getRequest()->getParam('wizard')]
        ), 'addNewSellingFormatTemplate');
        $this->jsUrl->add($this->getUrl(
            '*/walmart_template_description/new',
            ['wizard' => $this->getRequest()->getParam('wizard')]
        ), 'addNewDescriptionTemplate');
        $this->jsUrl->add($this->getUrl(
            '*/walmart_template_synchronization/new',
            ['wizard' => $this->getRequest()->getParam('wizard')]
        ), 'addNewSynchronizationTemplate');
        $this->jsUrl->add($this->getUrl(
            '*/walmart_template_sellingFormat/edit',
            ['wizard' => $this->getRequest()->getParam('wizard')]
        ), 'editSellingFormatTemplate');
        $this->jsUrl->add($this->getUrl(
            '*/walmart_template_description/edit',
            ['wizard' => $this->getRequest()->getParam('wizard')]
        ), 'editDescriptionTemplate');
        $this->jsUrl->add($this->getUrl(
            '*/walmart_template_synchronization/edit',
            ['wizard' => $this->getRequest()->getParam('wizard')]
        ), 'editSynchronizationTemplate');
        $this->jsUrl->add($this->getUrl('*/general/modelGetAll', [
            'model'=>'Template_SellingFormat',
            'id_field'=>'id',
            'data_field'=>'title',
            'sort_field'=>'title',
            'sort_dir'=>'ASC',
            'component_mode' => \Ess\M2ePro\Helper\Component\Walmart::NICK
        ]), 'getSellingFormatTemplates');
        $this->jsUrl->add($this->getUrl('*/general/modelGetAll', [
            'model'=>'Template_Description',
            'id_field'=>'id',
            'data_field'=>'title',
            'sort_field'=>'title',
            'sort_dir'=>'ASC',
            'component_mode' => \Ess\M2ePro\Helper\Component\Walmart::NICK
        ]), 'getDescriptionTemplates');
        $this->jsUrl->add($this->getUrl('*/general/modelGetAll', [
            'model'=>'Template_Synchronization',
            'id_field'=>'id',
            'data_field'=>'title',
            'sort_field'=>'title',
            'sort_dir'=>'ASC',
            'component_mode' => \Ess\M2ePro\Helper\Component\Walmart::NICK
        ]), 'getSynchronizationTemplates');

        $this->jsTranslator->add(
            'The specified Title is already used for other Listing. Listing Title must be unique.',
            $this->__(
                'The specified Title is already used for other Listing. Listing Title must be unique.'
            )
        );
        $this->jsTranslator->add(
            'Account not found, please create it.',
            $this->__('Account not found, please create it.')
        );
        $this->jsTranslator->add('Add Another', $this->__('Add Another'));
        $this->jsTranslator->add(
            'Please wait while Synchronization is finished.',
            $this->__('Please wait while Synchronization is finished.')
        );
        $this->jsTranslator->add(
            'Preparing to start. Please wait ...',
            $this->__('Preparing to start. Please wait ...')
        );
        $this->jsTranslator->add(
            'Another Synchronization Is Already Running.',
            $this->__('Another Synchronization Is Already Running.')
        );
        $this->jsTranslator->add(
            'Getting information. Please wait ...',
            $this->__('Getting information. Please wait ...')
        );

        $this->jsPhp->addConstants($this->getHelper('Data')
            ->getClassConstants(\Ess\M2ePro\Helper\Component\Walmart::class));

        $this->js->add(<<<JS

    M2ePro.formData.wizard = {$this->getRequest()->getParam('wizard', 0)};

require([
    'M2ePro/TemplateHandler',
    'M2ePro/Walmart/Listing/Settings'
], function(){

    window.TemplateHandlerObj = new TemplateHandler();

    window.WalmartListingSettingsObj = new WalmartListingSettings();

    $('template_selling_format_id').observe('change', function() {
        if ($('template_selling_format_id').value) {
            $('edit_selling_format_template_link').show();
        } else {
            $('edit_selling_format_template_link').hide();
        }
    });
    $('template_selling_format_id').simulate('change');

    $('template_selling_format_id').observe('change', WalmartListingSettingsObj.selling_format_template_id_change)
    if ($('template_selling_format_id').value) {
        $('template_selling_format_id').simulate('change');
    }

    $('template_description_id').observe('change', function() {
        if ($('template_description_id').value) {
            $('edit_description_template_link').show();
        } else {
            $('edit_description_template_link').hide();
        }
    });
    $('template_description_id').simulate('change');

    $('template_description_id').observe('change', WalmartListingSettingsObj.description_template_id_change)
    if ($('template_description_id').value) {
        $('template_description_id').simulate('change');
    }

    $('template_synchronization_id').observe('change', function() {
        if ($('template_synchronization_id').value) {
            $('edit_synchronization_template_link').show();
        } else {
            $('edit_synchronization_template_link').hide();
        }
    });
    $('template_synchronization_id').simulate('change');

    $('template_synchronization_id').observe('change', WalmartListingSettingsObj.synchronization_template_id_change)
    if ($('template_synchronization_id').value) {
        $('template_synchronization_id').simulate('change');
    }

});
JS
        );

        return parent::_toHtml();
    }

    //########################################

    protected function getSellingFormatTemplates()
    {
        $collection = $this->walmartFactory->getObject('Template\SellingFormat')->getCollection();
        $collection->setOrder('title', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);
        $collection->addFieldToFilter('marketplace_id', $this->listing->getMarketplaceId());
        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS)->columns([
            'value' => 'id',
            'label' => 'title'
        ]);

        return $collection->getConnection()->fetchAssoc($collection->getSelect());
    }

    protected function getDescriptionTemplates()
    {
        $collection = $this->walmartFactory->getObject('Template\Description')->getCollection();
        $collection->setOrder('title', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS)->columns([
            'value' => 'id',
            'label' => 'title'
        ]);

        return $collection->getConnection()->fetchAssoc($collection->getSelect());
    }

    protected function getSynchronizationTemplates()
    {
        $collection = $this->walmartFactory->getObject('Template\Synchronization')->getCollection();
        $collection->setOrder('title', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS)->columns([
            'value' => 'id',
            'label' => 'title'
        ]);

        return $collection->getConnection()->fetchAssoc($collection->getSelect());
    }

    //########################################

    public function getDefaultFieldsValues()
    {
        return [
            'title' => $this->walmartFactory->getObject('Listing')->getCollection()->getSize() == 0 ? 'Default' : '',
            'account_id' => '',
            'store_id' => '',
            'template_selling_format_id' => '',
            'template_description_id' => '',
            'template_synchronization_id' => '',
        ];
    }

    //########################################

    protected function getListingData()
    {
        if ($this->getRequest()->getParam('id') !== null) {
            $data = array_merge($this->getListing()->getData(), $this->getListing()->getChildObject()->getData());
        } else {
            $data = $this->getDefaultFieldsValues();
        }

        return $data;
    }

    //########################################

    protected function getListing()
    {
        if (!$listingId = $this->getRequest()->getParam('id')) {
            throw new \Ess\M2ePro\Model\Exception('Listing is not defined');
        }

        if ($this->listing === null) {
            $this->listing = $this->walmartFactory->getCachedObjectLoaded('Listing', $listingId);
        }

        return $this->listing;
    }

    //########################################
}
