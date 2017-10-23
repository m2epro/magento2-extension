<?php

namespace Ess\M2ePro\Block\Adminhtml\Wizard\InstallationAmazon\Installation\Account;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Magento\Framework\Message\MessageInterface;

class Content extends AbstractForm
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

    protected function _prepareLayout()
    {
        $this->getLayout()->getBlock('wizard.help.block')->setContent($this->__(<<<HTML
On this step, you should link your Amazon Account with your M2E Pro.<br/><br/>
Please, select the Marketplace you are going to sell on and click on Continue button.
HTML
));

        parent::_prepareLayout();
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create([
            'data' => [
                'id' => 'edit_form',
            ]
        ]);

        $fieldset = $form->addFieldset(
            'marketplaces',
            [
            ]
        );

        $marketplacesCollection = $this->amazonFactory->getObject('Marketplace')->getCollection()
            ->addFieldToFilter('developer_key', array('notnull' => true))
            ->setOrder('sorder', 'ASC');

        $marketplaces = [[
            'value' => '',
            'label' => ''
        ]];
        foreach ($marketplacesCollection->getItems() as $item) {
            $marketplace = array_merge($item->getData(), $item->getChildObject()->getData());
            $marketplaces[$marketplace['id']] = $marketplace['title'];
        }

        $fieldset->addField(
            'marketplace_id',
            'select',
            [
                'label' => $this->__('What the Marketplace do You Want to Onboard?'),
                'css_class' => 'account-mode-choose',
                'name' => 'marketplace_id',
                'values' => $marketplaces,
                'onchange' => 'InstallationAmazonWizardObj.marketplaceChange()'
            ]
        );

        $fieldset->addField(
            'amazon_wizard_installation_account_manual_authorization',
            self::MESSAGES,
            [
                'messages' => [
                    [
                        'content' => $this->__('
                            For providing access to Amazon Account click the "Get Access Data" link.
                            You will be redirected to the Amazon Website.<br /><br />
                            Sign-in and complete steps of getting access for M2E Pro:<br /><br />
                            <ul style="margin-left: 25px;">
                                <li>Select - \'I want to use an application to access my Amazon Seller Account
                                with MWS.\'</li>
                                <li>Fill in Application Name and Application\'s Developer Account Number,
                                which you can find in the Marketplaces Section on the current Page.</li>
                                <li>Accept the Amazon MWS License Agreement.</li>
                                <li>Copy generated "Merchant ID" / "MWS Auth Token" and paste it in the corresponding
                                fields of the current Page.</li>
                            </ul>
                        '),
                        'type'    => MessageInterface::TYPE_NOTICE,
                    ],
                ],
            ]
        )->setStyle('display: none')->setClass('manual-authorization');

        $fieldset = $form->addFieldset(
            'manual_authorization_marketplace',
            [
            ]
        );

        $fieldset->addField(
            'manual_authorization_marketplace_application_name',
            'label',
            [
                'container_id' => 'manual_authorization_marketplace_application_name_container',
                'label' => $this->__('Application Name'),
                'value' => $this->getHelper('Component\Amazon')->getApplicationName(),
                'css_class' => 'manual-authorization',
            ]
        )->setFieldExtraAttributes('style="display: none; line-height: 3.2rem"');

        foreach ($marketplacesCollection->getItems() as $item) {
            $marketplace = array_merge($item->getData(), $item->getChildObject()->getData());
            if ($marketplace['is_automatic_token_retrieving_available']) {
                continue;
            }

            $fieldset->addField(
                'manual_authorization_marketplace_developer_key_' . $marketplace['id'],
                'label',
                [
                    'container_id' => 'manual_authorization_marketplace_developer_key_container_' . $marketplace['id'],
                    'label' => $this->__('Developer Account Number'),
                    'value' => $marketplace['developer_key'],
                    'css_class' => 'manual-authorization',
                ]
            )->setFieldExtraAttributes('style="display: none; line-height: 3.2rem"');

            $fieldset->addField(
                'manual_authorization_marketplace_register_url_' . $marketplace['id'],
                'link',
                [
                    'container_id' => 'manual_authorization_marketplace_register_url_container_' . $marketplace['id'],
                    'label'        => '',
                    'href'         => $this->getHelper('Component\Amazon')->getRegisterUrl(
                        $marketplace['id']
                    ),
                    'onclick'      => '',
                    'target'       => '_blank',
                    'value'        => $this->__('Get Access Data'),
                    'class'        => 'external-link',
                    'css_class'    => 'manual-authorization',
                ]
            )->setFieldExtraAttributes('style="display: none"');

            $fieldset->addField(
                'manual_authorization_marketplace_merchant_id_'.$marketplace['id'],
                'text',
                [
                    'container_id' => 'manual_authorization_marketplace_merchant_id_container_' . $marketplace['id'],
                    'label'    => $this->__('Merchant ID'),
                    'name'     => 'manual_authorization_marketplace_merchant_id_'.$marketplace['id'],
                    'style'    => 'width: 50%',
                    'required' => true,
                    'css_class' => 'manual-authorization M2ePro-marketplace-merchant',
                    'tooltip' => $this->__(
                        'Paste generated Merchant ID from Amazon. (It must look like: A15UFR7CZVW5YA).'
                    )
                ]
            )->setFieldExtraAttributes('style="display: none"');

            $fieldset->addField(
                'manual_authorization_marketplace_token_'.$marketplace['id'],
                'text',
                [
                    'container_id' => 'manual_authorization_marketplace_token_container_' . $marketplace['id'],
                    'label'    => $this->__('MWS Auth Token'),
                    'name'     => 'manual_authorization_marketplace_token_'.$marketplace['id'],
                    'style'    => 'width: 50%',
                    'required' => true,
                    'css_class' => 'manual-authorization M2ePro-marketplace-merchant',
                    'tooltip' => $this->__(
                        'Paste generated MWS Auth Token from Amazon.
                        (It must look like: amzn.mws.bna3f75c-a683-49c7-6da0-749y33313dft).')
                ]
            )->setFieldExtraAttributes('style="display: none"');
        }

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
            ),
            'Please fill Merchant ID and MWS Auth Token fields.' => $this->__(
                'Please fill Merchant ID and MWS Auth Token fields.'
            ),
        ]);

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Wizard\InstallationAmazon'));
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Amazon\Account'));

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _beforeToHtml()
    {
        $this->jsTranslator->add(
            'Please select Marketplace first.',
            $this->__('Please select Marketplace first.')
        );

        $this->jsTranslator->add(
            'An error during of account creation.',
            $this->__('The Amazon token obtaining is currently unavailable. Please try again later.')
        );

        return parent::_beforeToHtml();
    }
}