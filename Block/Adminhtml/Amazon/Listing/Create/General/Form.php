<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Create\General;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Block\Adminhtml\StoreSwitcher;

class Form extends AbstractForm
{
    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->amazonFactory = $amazonFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id'      => 'edit_form',
                    'method'  => 'post',
                    'action'  => 'javascript:void(0)',
                    'enctype' => 'multipart/form-data',
                    'class' => 'admin__scope-old'
                ]
            ]
        );

        $fieldset = $form->addFieldset(
            'general_fieldset',
            [
                'legend' => $this->__('General'),
                'collapsable' => false
            ]
        );

        $title = $this->amazonFactory->getObject('Listing')->getCollection()->getSize() == 0 ? 'Default' : '';
        $accountId = '';

        $storeId = '';
        // ---------------------------------------
        $sessionKey = 'amazon_listing_create';
        $sessionData = $this->getHelper('Data\Session')->getValue($sessionKey);

        isset($sessionData['title'])          && $title = $sessionData['title'];
        isset($sessionData['account_id'])     && $accountId = $sessionData['account_id'];
        isset($sessionData['store_id'])       && $storeId = $sessionData['store_id'];
        // ---------------------------------------

        $fieldset->addField(
            'title',
            'text',
            [
                'name' => 'title',
                'label' => $this->__('Title'),
                'value' => $title,
                'required' => true,
                'class' => 'M2ePro-listing-title',
                'tooltip' => $this->__('Create a descriptive and meaningful Title for your M2E Pro Listing. <br/>
                    This is used for reference within M2E Pro and will not appear on your Amazon Listings.')
            ]
        );

        $fieldset = $form->addFieldset(
            'amazon_settings_fieldset',
            [
                'legend' => $this->__('Amazon Settings'),
                'collapsable' => false
            ]
        );

        // ---------------------------------------
        $accountsCollection = $this->amazonFactory->getObject('Account')->getCollection()
            ->setOrder('title','ASC');

        $accountsCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS)
            ->columns([
                'value' => 'id',
                'label' => 'title'
            ]);
        // ---------------------------------------

        $accountSelectionDisabled = false;

        if($this->getRequest()->getParam('account_id')) {
            $accountId = $this->getRequest()->getParam('account_id');
            $fieldset->addField(
                'account_id_hidden',
                'hidden',
                [
                    'name' => 'account_id',
                    'value' => $accountId
                ]
            );
            $accountSelectionDisabled = true;
        }

        $accounts = $accountsCollection->getConnection()->fetchAssoc($accountsCollection->getSelect());
        $accountSelect = $this->elementFactory->create('select', [
            'data' => [
                'html_id' => 'account_id',
                'name' => 'account_id',
                'style' => 'width: 50%;',
                'value' => $accountId,
                'values' => $accounts,
                'required' => count($accounts) > 1,
                'disabled' => $accountSelectionDisabled
            ]
        ]);
        $accountSelect->setForm($form);

        $fieldset->addField(
            'account_container',
            self::CUSTOM_CONTAINER,
            [
                'label' => $this->__('Account'),
                'style' => 'line-height: 32px; display: initial;',
                'text' => <<<HTML
    <span id="account_label"></span>
    {$accountSelect->toHtml()}
HTML
                ,
                'after_element_html' => $this->createBlock('Magento\Button')->setData([
                    'id' => 'add_account_button',
                    'label' => $this->__('Add Another'),
                    'style' => 'margin-left: 5px;' .
                        ($this->getRequest()->getParam('wizard',false) ? 'display: none;' : ''),
                    'onclick' => '',
                    'class' => 'primary'
                ])->toHtml(),
                'tooltip' => $this->__('This is the user name of your Amazon Account.')
            ]
        );

        // ---------------------------------------
        $marketplacesCollection = $this->amazonFactory->getObject('Marketplace')->getCollection()
            ->setOrder('sorder','ASC')
            ->setOrder('title','ASC');

        $marketplacesCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS)
            ->columns([
                'value' => 'id',
                'label' => 'title',
                'url'   => 'url'
            ]);
        // ---------------------------------------

        $fieldset->addField(
            'marketplace_info',
            self::CUSTOM_CONTAINER,
            [
                'css_class' => 'no-margin-bottom',
                'label' => $this->__('Marketplace'),
                'field_extra_attributes' => 'id="marketplace_info" style="display: none; margin-top: 0px"',
                'text' => '<span id="marketplace_title"></span><p class="note" id="marketplace_url"></p>'
            ]
        );

        $fieldset->addField(
            'marketplace_id',
            'hidden',
            [
                'value' => ''
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_fieldset',
            [
                'legend' => $this->__('Magento Settings'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'store_switcher',
            self::STORE_SWITCHER,
            [
                'name' => 'store_id',
                'label' => $this->__('Magento Store View'),
                'value' => $storeId,
                'required' => true,
                'has_empty_option' => true,
                'display_default_store_mode' => StoreSwitcher::DISPLAY_DEFAULT_STORE_MODE_DOWN,
                'tooltip' => $this->__('Choose the Magento Store View you want to use for this M2E Pro Listing.')
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('General', [
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK
        ]));
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Amazon\Account'));
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Amazon\Marketplace'));
        $this->jsUrl->addUrls(
            $this->getHelper('Data')->getControllerActions('Amazon\Listing\Create', ['_current' => true])
        );

        $this->jsUrl->add($this->getUrl('*/amazon_account/newAction', [
            'close_on_save' => true,
            'wizard' => (bool)$this->getRequest()->getParam('wizard',false)
        ]), 'amazon_account/newAction');

        $this->jsTranslator->add(
            'The specified Title is already used for other Listing. Listing Title must be unique.',
            $this->__(
                'The specified Title is already used for other Listing. Listing Title must be unique.'
            )
        );
        $this->jsTranslator->add(
            'Account not found, please create it.', $this->__('Account not found, please create it.')
        );
        $this->jsTranslator->add('Add Another', $this->__('Add Another'));
        $this->jsTranslator->add(
            'Please wait while Synchronization is finished.',
            $this->__('Please wait while Synchronization is finished.')
        );
        $this->jsTranslator->add(
            'Preparing to start. Please wait ...', $this->__('Preparing to start. Please wait ...')
        );

        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Helper\Component\Amazon'));

        $this->js->add(<<<JS

    M2ePro.formData.wizard = {$this->getRequest()->getParam('wizard', 0)};

require([
    'M2ePro/Amazon/Listing/Settings',
    'M2ePro/Amazon/Listing/Create/General'
], function(){

    window.AmazonListingSettingsObj = new AmazonListingSettings();
    window.AmazonListingCreateGeneralObj = new AmazonListingCreateGeneral();

});
JS
        );

        return parent::_prepareForm();
    }
}