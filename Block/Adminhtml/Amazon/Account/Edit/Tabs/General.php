<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

class General extends AbstractForm
{
    protected $amazonFactory;

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
        /** @var $account \Ess\M2ePro\Model\Account */
        $account = $this->getHelper('Data\GlobalData')->getValue('edit_account');
        $formData = !is_null($account) ? array_merge($account->getData(), $account->getChildObject()->getData()) : [];

        if (isset($formData['other_listings_mapping_settings'])) {
            $formData['other_listings_mapping_settings'] = (array)$this->getHelper('Data')->jsonDecode(
                $formData['other_listings_mapping_settings'],true
            );
        }

        $defaults = array(
            'title'          => '',
            'marketplace_id' => 0,
            'merchant_id'    => '',
            'token'          => ''
        );

        $formData = array_merge($defaults, $formData);

        $isEdit = !!$this->getRequest()->getParam('id');

        $marketplacesCollection = $this->getHelper('Component\Amazon')->getMarketplacesAvailableForApiCreation();
        $marketplaces = [];
        foreach ($marketplacesCollection->getItems() as $item) {
            $marketplaces[] = array_merge($item->getData(), $item->getChildObject()->getData());
        }

        $accountTitle = $this->getHelper('Data\Session')->getValue('account_title', true);
        $merchantId = $this->getHelper('Data\Session')->getValue('merchant_id', true);
        $mwsToken   = $this->getHelper('Data\Session')->getValue('mws_token', true);

        $isAuthMode = !empty($merchantId) && !empty($mwsToken);

        $authMarketplaceId = $this->getHelper('Data\Session')->getValue('marketplace_id', true);

        $form = $this->_formFactory->create();

        $form->addField(
            'amazon_accounts_general',
            self::HELP_BLOCK,
            [
                'content' => $this->__(<<<HTML
<p>Fill in the Title, choose the Marketplace you want to work with and click on the Get Access Data link.
You will be redirected to Amazon Website.</p><br>
<p><strong>Note:</strong> To be eligible to sell on Amazon, Sellers must have at least one of the following:
a non-individual <i>Selling on Amazon Account</i>, an <i>Amazon WebStore Account</i>, a
<i>Checkout by Amazon Account</i>, or an <i>Amazon Product Ads Account</i>. If you are an individual Seller you
have to upgrade to a Pro Merchant Seller Account from the Amazon Services Selling on Amazon Page.</p><br>
<p>Sign-in and complete the steps to obtain access to a specific Marketplace:</p>
<ul>
<li><p>Select - 'I want to use an application to access my Amazon Seller Account with MWS.'</p></li>
<li><p>Accept the Amazon MWS License Agreement.</p></li>
<li><p>The Merchant ID and MWS Auth Token will be automatically filled in.</p></li>
</ul><br>
<p>More detailed information about how to work with this
Page you can find <a href="%url%" target="_blank" class="external-link">here</a>.</p>
HTML
                , $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/MgItAQ'))
            ]
        );

        $fieldset = $form->addFieldset(
            'general',
            [
                'legend' => $this->__('General'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'title',
            'text',
            [
                'name' => 'title',
                'class' => 'M2ePro-account-title',
                'label' => $this->__('Title'),
                'required' => true,
                'value' => !empty($accountTitle) ? $accountTitle : $formData['title'],
                'tooltip' => $this->__('Title or Identifier of Amazon Account for your internal use.')
            ]
        );

        $fieldset = $form->addFieldset(
            'access_details',
            [
                'legend' => $this->__('Access Details'),
                'collapsable' => false
            ]
        );

        $preparedValues = [];
        if (!$isEdit) {
            $preparedValues[] = [
                'label' => '',
                'value' => '',
                'attrs' => [
                    'style' => 'display: none;'
                ]
            ];
        }
        foreach ($marketplaces as $marketplace) {
            $preparedValues[$marketplace['id']] = $marketplace['title'];
        }

        $fieldset->addField(
            'marketplace_id',
            self::SELECT,
            [
                'name' => 'marketplace_id',
                'label' => $this->__('Marketplace'),
                'values' => $preparedValues,
                'value' => $isAuthMode ? $authMarketplaceId : $formData['marketplace_id'],
                'disabled' => $isEdit,
                'required' => true,
                'onchange' => 'AmazonAccountObj.changeMarketplace(this.value);'
            ]
        );

        if ($isEdit) {
            $fieldset->addField(
                'marketplace_id_hidden',
                'hidden',
                [
                    'name' => 'marketplace_id',
                    'value' => $formData['marketplace_id'],
                ]
            );
            $fieldset->addField(
                'merchant_id_hidden',
                'hidden',
                [
                    'name' => 'merchant_id',
                    'value' => $formData['merchant_id'],
                ]
            );
            $fieldset->addField(
                'token_hidden',
                'hidden',
                [
                    'name' => 'token',
                    'value' => $formData['token'],
                ]
            );
        }

        $fieldset->addField(
            'marketplaces_application_name',
            'label',
            [
                'container_id' => 'marketplaces_application_name_container',
                'label' => $this->__('Application Name'),
                'value' => $this->getHelper('Component\Amazon')->getApplicationName(),
                'display' => false,
            ]
        )->setFieldExtraAttributes('style="display: none"');

        foreach ($marketplaces as $marketplace) {
            $fieldset->addField(
                'marketplaces_developer_key_' . $marketplace['id'],
                'label',
                [
                    'container_id' => 'marketplaces_developer_key_container_' . $marketplace['id'],
                    'label' => $this->__('Developer Account Number'),
                    'value' => $marketplace['developer_key'],
                ]
            )->setFieldExtraAttributes('style="display: none"');

            $fieldset->addField(
                'marketplaces_register_url_' . $marketplace['id'],
                'link',
                [
                    'container_id' => 'marketplaces_register_url_container_' . $marketplace['id'],
                    'label' => '',
                    'href' => '',
                    'onclick' => 'return AmazonAccountObj.getToken('.$marketplace['id'].')',
                    'target' => '_blank',
                    'value' => $this->__('Get Access Data'),
                    'class' => 'external-link',
                ]
            )->setFieldExtraAttributes('style="display: none"');
        }

        $field = $fieldset->addField(
            'merchant_id',
            'text',
            [
                'container_id' => 'marketplaces_merchant_id_container',
                'name' => 'merchant_id',
                'label' => $this->__('Merchant ID'),
                'class' => 'M2ePro-marketplace-merchant',
                'required' => true,
                'value' => $isAuthMode ? $merchantId : ($isEdit ? $formData['merchant_id'] : ''),
                'display' => $isEdit,
                'disabled' => $isEdit,
                'tooltip' => $this->__('Paste generated Merchant ID from Amazon. (It must look like: A15UFR7CZVW5YA).')
            ]
        );
        if (!$isEdit) {
            $field->setFieldExtraAttributes('style="display: none"');
        }

        $field = $fieldset->addField(
            'token',
            'text',
            [
                'container_id' => 'marketplaces_token_container',
                'name' => 'token',
                'label' => $this->__('MWS Auth Token'),
                'class' => 'M2ePro-marketplace-merchant',
                'required' => true,
                'value' => $isAuthMode ? $mwsToken : ($isEdit ? $formData['token'] : ''),
                'display' => $isEdit,
                'tooltip' => $this->__(
                    'Paste generated MWS Auth Token from Amazon.
                    (It must look like: amzn.mws.bna3f75c-a683-49c7-6da0-749y33313dft).')
            ]
        );
        if (!$isEdit) {
            $field->setFieldExtraAttributes('style="display: none"');
        }

        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Amazon\Account'));
        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Helper\Component\Amazon'));

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Amazon\Account', ['_current' => true]));
        $this->jsUrl->addUrls([
            'formSubmit' => $this->getUrl(
                '*/amazon_account/save', array('_current' => true, 'id' => $this->getRequest()->getParam('id'))
            ),
            'deleteAction' => $this->getUrl(
                '*/amazon_account/delete', array('id' => $this->getRequest()->getParam('id'))
            )
        ]);

        $this->jsTranslator->add('Please enter correct value.', $this->__('Please enter correct value.'));

        $this->jsTranslator->add(
            'Be attentive! By Deleting Account you delete all information on it from M2E Pro Server. '
            . 'This will cause inappropriate work of all Accounts\' copies.',
            $this->__(
                'Be attentive! By Deleting Account you delete all information on it from M2E Pro Server. '
                . 'This will cause inappropriate work of all Accounts\' copies.'));

        $id = $this->getRequest()->getParam('id');
        $this->js->add("M2ePro.formData.id = '$id';");

        $marketplaceJs = '';

        if ($isAuthMode) {
            $marketplaceJs = 'AmazonAccountObj.changeMarketplace('.$authMarketplaceId.');';
        } elseif ($isEdit) {
            $marketplaceJs = "AmazonAccountObj.showGetAccessData({$formData['marketplace_id']});";
        }

        $this->js->add(<<<JS
    require([
        'M2ePro/Amazon/Account',
    ], function(){
        window.AmazonAccountObj = new AmazonAccount();
        AmazonAccountObj.initObservers();
        jQuery(function(){
            {$marketplaceJs}
        });
    });
JS
        );

        $this->jsTranslator->addTranslations([
            'The specified Title is already used for other Account. Account Title must be unique.' => $this->__(
                'The specified Title is already used for other Account. Account Title must be unique.'
            ),
            'You must choose Marketplace first.' => $this->__('You must choose Marketplace first.'),
            'M2E Pro was not able to get access to the Amazon Account. Please, make sure, that you choose correct ' .
             'Option on MWS Authorization Page and enter correct Merchant ID.' => $this->__(
                'M2E Pro was not able to get access to the Amazon Account.' .
                ' Please, make sure, that you choose correct Option on MWS Authorization Page
                and enter correct Merchant ID / MWS Auth Token'),
            'M2E Pro was not able to get access to the Amazon Account. Reason: %error_message%' => $this->__(
                'M2E Pro was not able to get access to the Amazon Account. Reason: %error_message%'
            )
        ]);

        $title = $this->getHelper('Data')->escapeJs($this->getHelper('Data')->escapeHtml($formData['title']));
        $this->js->add("M2ePro.formData.title = '$title';");

        $this->setForm($form);

        return parent::_prepareForm();
    }
}