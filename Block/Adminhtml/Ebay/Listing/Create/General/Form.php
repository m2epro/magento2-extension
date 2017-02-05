<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Create\General;

use Ess\M2ePro\Block\Adminhtml\StoreSwitcher;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    protected $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->ebayFactory = $ebayFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingAccountMarketplace');
        // ---------------------------------------
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            ['data' => [
                'id' => 'edit_form',
                'action' => 'javascript:void(0)',
                'method' => 'post'
            ]]
        );

        $fieldset = $form->addFieldset(
            'general_fieldset',
            [
                'legend' => $this->__('General'),
                'collapsable' => false
            ]
        );

        $title = $this->ebayFactory->getObject('Listing')->getCollection()->getSize() == 0 ? 'Default' : '';
        $accountId = '';

        $account = $this->ebayFactory->getObject('Account')->getCollection()->getLastItem();
        $marketplaceId = $this->getRequest()->getParam('marketplace_id', '');
        $marketplaceSelectionDisabled = true;
        if (!$marketplaceId && $account->getId()) {
            $info = $this->getHelper('Data')->jsonDecode($account->getChildObject()->getInfo());
            $marketplaceId = $this->activeRecordFactory->getObject('Marketplace')->getIdByCode($info['Site']);
            $marketplaceSelectionDisabled = false;
        }

        $storeId = '';
        // ---------------------------------------
        $sessionKey = 'ebay_listing_create';
        $sessionData = $this->getHelper('Data\Session')->getValue($sessionKey);

        isset($sessionData['listing_title'])  && $title = $sessionData['listing_title'];
        isset($sessionData['account_id'])     && $accountId = $sessionData['account_id'];
        isset($sessionData['marketplace_id']) && $marketplaceId = $sessionData['marketplace_id'];
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
                    This is used for reference within M2E Pro and will not appear on your eBay Listings.')
            ]
        );

        $fieldset = $form->addFieldset(
            'ebay_settings_fieldset',
            [
                'legend' => $this->__('eBay Settings'),
                'collapsable' => false
            ]
        );

        // ---------------------------------------
        $accountsCollection = $this->ebayFactory->getObject('Account')->getCollection()
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
        $accountSelect = $this->elementFactory->create(self::SELECT, [
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
                'required' => count($accounts) > 1,
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
                ])->toHtml()
            ]
        );

        // ---------------------------------------
        $marketplacesCollection = $this->ebayFactory->getObject('Marketplace')->getCollection();
        $marketplacesCollection->getSelect()->order('main_table.sorder ASC')->order('main_table.title ASC');

        $marketplacesCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS)
            ->columns([
                'value' => 'id',
                'label' => 'title',
                'url'   => 'url'
            ]);
        // ---------------------------------------

        if ($this->getRequest()->getParam('marketplace_id', false) !== false) {
            $fieldset->addField('marketplace_id_hidden',
                'hidden',
                [
                    'name' => 'marketplace_id',
                    'value' => $marketplaceId
                ]
            );
        }

        $marketplaces = array_merge_recursive(
            [['label' => '', 'value' => '', 'attrs' => ['style' => 'display: none;']]],
            $marketplacesCollection->getConnection()->fetchAssoc($marketplacesCollection->getSelect())
        );

        $fieldset->addField(
            'marketplace_id',
            self::SELECT,
            [
                'name' => 'marketplace_id',
                'label' => $this->__('Marketplace'),
                'value' => $marketplaceId,
                'values' => $marketplaces,
                'tooltip' => $this->__(
                    'Choose the Marketplace you want to list on using this M2E Pro Listing.
                    Currency will be set automatically based on the Marketplace you choose.'
                ),
                'field_extra_attributes' => 'style="margin-bottom: 0px"',
                'disabled' => $marketplaceSelectionDisabled,
                'note' => '<span id="marketplace_url_note"></span>'
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
                'tooltip' => $this->__(
                    'Choose the Magento Store View you want to use for this M2E Pro Listing.
                     Please remember that Attribute values from the selected Store View will be used in the Listing.'
                )
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('General', [
            'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK
        ]));
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Ebay\Account'));
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Ebay\Marketplace'));
        $this->jsUrl->addUrls(
            $this->getHelper('Data')->getControllerActions('Ebay\Listing\Create', ['_current' => true])
        );

        $this->jsUrl->add($this->getUrl('*/ebay_account/newAction', [
            'close_on_save' => true,
            'wizard' => (bool)$this->getRequest()->getParam('wizard',false)
        ]), 'ebay_account/newAction');

        $this->jsTranslator->add(
            'The specified Title is already used for other Listing. Listing Title must be unique.',
            $this->__(
                'The specified Title is already used for other Listing. Listing Title must be unique.'
            )
        );
        $this->jsTranslator->add(
            'Account not found, please create it.', $this->__('Account not found, please create it.')
        );
        $this->jsTranslator->add(
            'Preparing to start. Please wait ...', $this->__('Preparing to start. Please wait ...')
        );
        $this->jsTranslator->add('Add Another', $this->__('Add Another'));
        $this->jsTranslator->add(
            'Please wait while Synchronization is finished.',
            $this->__('Please wait while Synchronization is finished.')
        );

        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Helper\Component\Ebay'));

        $marketplaces = $this->getHelper('Data')->jsonEncode($marketplaces);

        $this->js->add(<<<JS

    M2ePro.formData.wizard = {$this->getRequest()->getParam('wizard', 0)};

require([
    'M2ePro/Ebay/Listing/Create/General'
], function(){

    window.EbayListingCreateGeneralObj = new EbayListingCreateGeneral(
        {$marketplaces}
    );

});
JS
        );

        return parent::_prepareForm();
    }

    //########################################
}