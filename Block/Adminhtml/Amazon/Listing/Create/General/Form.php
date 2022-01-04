<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Create\General;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Block\Adminhtml\StoreSwitcher;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Create\General\Form
 */
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
    ) {
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
                    'class'   => 'admin__scope-old'
                ]
            ]
        );

        $fieldset = $form->addFieldset(
            'general_fieldset',
            [
                'legend'      => $this->__('General'),
                'collapsable' => false
            ]
        );

        $title = $this->amazonFactory->getObject('Listing')->getCollection()->getSize() == 0 ? 'Default' : '';
        $accountId = '';
        $marketplaceId = '';
        $storeId = '';

        $sessionData = $this->getHelper('Data_Session')->getValue(
            \Ess\M2ePro\Model\Amazon\Listing::CREATE_LISTING_SESSION_DATA
        );
        isset($sessionData['title']) && $title = $sessionData['title'];
        isset($sessionData['account_id']) && $accountId = $sessionData['account_id'];
        isset($sessionData['marketplace_id']) && $marketplaceId = $sessionData['marketplace_id'];
        isset($sessionData['store_id']) && $storeId = $sessionData['store_id'];

        $fieldset->addField(
            'title',
            'text',
            [
                'name'     => 'title',
                'label'    => $this->__('Title'),
                'value'    => $title,
                'required' => true,
                'class'    => 'M2ePro-listing-title',
                'tooltip'  => $this->__(
                    'Create a descriptive and meaningful Title for your M2E Pro Listing. <br/>
                    This is used for reference within M2E Pro and will not appear on your Amazon Listings.'
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'amazon_settings_fieldset',
            [
                'legend'      => $this->__('Amazon Settings'),
                'collapsable' => false
            ]
        );

        $accountsCollection = $this->amazonFactory->getObject('Account')->getCollection()
            ->setOrder('title', 'ASC');

        $accountsCollection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns(
                [
                    'value' => 'id',
                    'label' => 'title'
                ]
            );

        $accountSelectionDisabled = false;

        if ($this->getRequest()->getParam('account_id')) {
            $accountId = $this->getRequest()->getParam('account_id');
            $fieldset->addField(
                'account_id_hidden',
                'hidden',
                [
                    'name'  => 'account_id',
                    'value' => $accountId
                ]
            );
            $accountSelectionDisabled = true;
        }

        $accounts = $accountsCollection->getConnection()->fetchAssoc($accountsCollection->getSelect());
        $accountSelect = $this->elementFactory->create(
            'select',
            [
                'data' => [
                    'html_id'  => 'account_id',
                    'name'     => 'account_id',
                    'style'    => 'width: 50%;',
                    'value'    => $accountId,
                    'values'   => $accounts,
                    'required' => count($accounts) > 1,
                    'disabled' => $accountSelectionDisabled
                ]
            ]
        );
        $accountSelect->setForm($form);

        $isAddAccountButtonHidden = $this->getRequest()->getParam('wizard', false) ? ' display: none;' : '';

        $fieldset->addField(
            'account_container',
            self::CUSTOM_CONTAINER,
            [
                'label'              => $this->__('Account'),
                'style'              => 'line-height: 32px; display: initial;',
                'text'               => <<<HTML
    <span id="account_label"></span>
    {$accountSelect->toHtml()}
HTML
                ,
                'after_element_html' => $this->createBlock('Magento\Button')->setData(
                    [
                        'id'      => 'add_account_button',
                        'label'   => $this->__('Add Another'),
                        'style'   => 'margin-left: 5px;' . $isAddAccountButtonHidden,
                        'onclick' => '',
                        'class'   => 'primary'
                    ]
                )->toHtml(),
                'tooltip'            => $this->__('This is the user name of your Amazon Account.')
            ]
        );

        $fieldset->addField(
            'marketplace_info',
            self::CUSTOM_CONTAINER,
            [
                'css_class' => 'no-margin-bottom',
                'label'     => $this->__('Marketplace'),
                'text'      => '<span id="marketplace_title"></span><p class="note" id="marketplace_url"></p>',

                'field_extra_attributes' => 'id="marketplace_info" style="display: none; margin-top: 0px"'
            ]
        );

        $fieldset->addField(
            'marketplace_id',
            'hidden',
            [
                'name'  => 'marketplace_id',
                'value' => $marketplaceId
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_fieldset',
            [
                'legend'      => $this->__('Magento Settings'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'store_id',
            self::STORE_SWITCHER,
            [
                'name'                       => 'store_id',
                'label'                      => $this->__('Magento Store View'),
                'value'                      => $storeId,
                'required'                   => true,
                'has_empty_option'           => true,
                'display_default_store_mode' => StoreSwitcher::DISPLAY_DEFAULT_STORE_MODE_DOWN,
                'tooltip'                    => $this->__(
                    'Choose the Magento Store View you want to use for this M2E Pro Listing.'
                )
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
                ->getClassConstants(\Ess\M2ePro\Helper\Component\Amazon::class)
        );

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Amazon\Account'));
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Amazon\Marketplace'));

        $this->jsUrl->addUrls(
            $this->getHelper('Data')->getControllerActions('Amazon_Listing_Create', ['_current' => true])
        );

        $this->jsUrl->add(
            $this->getUrl(
                '*/amazon_account/newAction',
                [
                    'close_on_save' => true,
                    'wizard'        => (bool)$this->getRequest()->getParam('wizard', false)
                ]
            ),
            'amazon_account/newAction'
        );

        $this->jsUrl->add(
            $this->getUrl(
                '*/amazon_synchronization_log/index',
                [
                    'wizard' => (bool)$this->getRequest()->getParam('wizard', false)
                ]
            ),
            'logViewUrl'
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
        'M2ePro/Amazon/Listing/Settings',
        'M2ePro/Amazon/Listing/Create/General'
    ], function(){
        M2ePro.formData.wizard = {$this->getRequest()->getParam('wizard', 0)};

        window.AmazonListingCreateGeneralObj = new AmazonListingCreateGeneral();
    });
JS
        );

        return parent::_prepareLayout();
    }

    //########################################
}
