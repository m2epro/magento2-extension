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

        // Policies
        $fieldset = $form->addFieldset(
            'policies_settings',
            [
                'legend'      => $this->__('Policies Settings'),
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
                    'values'   => array_merge(
                        [
                            '' => ''
                        ],
                        $sellingFormatTemplates
                    ),
                    'value'    => $formData['template_selling_format_id'],
                    'required' => true
                ]
            ]
        );
        $templateSellingFormat->setForm($form);

        $editPolicyTooltip = $this->getTooltipHtml(
            $this->__(
                'You can edit the saved Policy any time you need. However, the changes you make will automatically
            affect all of the Products which are listed using this Policy.'
            )
        );

        $style = count($sellingFormatTemplates) === 0 ? '' : 'display: none';
        $fieldset->addField(
            'template_selling_format_container',
            self::CUSTOM_CONTAINER,
            [
                'label'              => $this->__('Selling Policy'),
                'style'              => 'line-height: 34px; display: initial;',
                'required'           => true,
                'text'               => <<<HTML
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
                    'values'   => array_merge(
                        [
                            '' => ''
                        ],
                        $descriptionTemplates
                    ),
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
                'label'              => $this->__('Description Policy'),
                'style'              => 'line-height: 34px;display: initial;',
                'required'           => true,
                'text'               => <<<HTML
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
                    'values'   => array_merge(
                        [
                            '' => ''
                        ],
                        $synchronizationTemplates
                    ),
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
    <span id="template_synchronization_label" style="padding-right: 25px; {$style}">
        {$this->__('No Policies available.')}
    </span>
    {$templateSynchronization->toHtml()}
HTML
                ,
                'after_element_html'     => <<<HTML
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
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->jsPhp->addConstants(
            $this->getHelper('Data')
                ->getClassConstants(\Ess\M2ePro\Helper\Component\Walmart::class)
        );

        $this->jsUrl->addUrls(
            [
                'templateCheckMessages'         => $this->getUrl(
                    '*/template/checkMessages',
                    [
                        'component_mode' => \Ess\M2ePro\Helper\Component\Walmart::NICK
                    ]
                ),
                'addNewSellingFormatTemplate'   => $this->getUrl(
                    '*/walmart_template_sellingFormat/new',
                    [
                        'close_on_save' => 1
                    ]
                ),
                'editSellingFormatTemplate'     => $this->getUrl(
                    '*/walmart_template_sellingFormat/edit',
                    [
                        'close_on_save' => 1
                    ]
                ),
                'getSellingFormatTemplates'     => $this->getUrl(
                    '*/general/modelGetAll',
                    [
                        'model'          => 'Template_SellingFormat',
                        'id_field'       => 'id',
                        'data_field'     => 'title',
                        'sort_field'     => 'title',
                        'sort_dir'       => 'ASC',
                        'component_mode' => \Ess\M2ePro\Helper\Component\Walmart::NICK
                    ]
                ),
                'addNewDescriptionTemplate'     => $this->getUrl(
                    '*/walmart_template_description/new',
                    [
                        'close_on_save' => 1
                    ]
                ),
                'editDescriptionTemplate'       => $this->getUrl(
                    '*/walmart_template_description/edit',
                    [
                        'close_on_save' => 1
                    ]
                ),
                'getDescriptionTemplates'       => $this->getUrl(
                    '*/general/modelGetAll',
                    [
                        'model'          => 'Template_Description',
                        'id_field'       => 'id',
                        'data_field'     => 'title',
                        'sort_field'     => 'title',
                        'sort_dir'       => 'ASC',
                        'component_mode' => \Ess\M2ePro\Helper\Component\Walmart::NICK
                    ]
                ),
                'addNewSynchronizationTemplate' => $this->getUrl(
                    '*/walmart_template_synchronization/new',
                    [
                        'close_on_save' => 1
                    ]
                ),
                'editSynchronizationTemplate'   => $this->getUrl(
                    '*/walmart_template_synchronization/edit',
                    [
                        'close_on_save' => 1
                    ]
                ),
                'getSynchronizationTemplates'   => $this->getUrl(
                    '*/general/modelGetAll',
                    [
                        'model'          => 'Template_Synchronization',
                        'id_field'       => 'id',
                        'data_field'     => 'title',
                        'sort_field'     => 'title',
                        'sort_dir'       => 'ASC',
                        'component_mode' => \Ess\M2ePro\Helper\Component\Walmart::NICK
                    ]
                )
            ]
        );

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

        $this->js->add(
            <<<JS
    require([
        'M2ePro/TemplateManager',
        'M2ePro/Walmart/Listing/Settings'
    ], function(){
        window.TemplateManagerObj = new TemplateManager();
    
        window.WalmartListingSettingsObj = new WalmartListingSettings();
        WalmartListingSettingsObj.initObservers();
    });
JS
        );

        return parent::_prepareLayout();
    }

    //########################################

    protected function getSellingFormatTemplates()
    {
        $collection = $this->walmartFactory->getObject('Template\SellingFormat')->getCollection();
        $collection->setOrder('title', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);
        $collection->addFieldToFilter('marketplace_id', $this->listing->getMarketplaceId());
        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS)->columns(
            [
                'value' => 'id',
                'label' => 'title'
            ]
        );

        return $collection->getConnection()->fetchAssoc($collection->getSelect());
    }

    protected function getDescriptionTemplates()
    {
        $collection = $this->walmartFactory->getObject('Template\Description')->getCollection();
        $collection->setOrder('title', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS)->columns(
            [
                'value' => 'id',
                'label' => 'title'
            ]
        );

        return $collection->getConnection()->fetchAssoc($collection->getSelect());
    }

    protected function getSynchronizationTemplates()
    {
        $collection = $this->walmartFactory->getObject('Template\Synchronization')->getCollection();
        $collection->setOrder('title', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS)->columns(
            [
                'value' => 'id',
                'label' => 'title'
            ]
        );

        return $collection->getConnection()->fetchAssoc($collection->getSelect());
    }

    //########################################

    protected function getListingData()
    {
        return array_merge($this->getListing()->getData(), $this->getListing()->getChildObject()->getData());
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
