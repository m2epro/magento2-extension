<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\InstallationWalmart\Installation\Account;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Helper\Component\Walmart;
use Ess\M2ePro\Model\Walmart\Account as WalmartAccount;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Wizard\InstallationWalmart\Installation\Account\Content
 */
class Content extends AbstractForm
{
    protected $walmartFactory;

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

    //########################################

    protected function _prepareLayout()
    {
        $this->getLayout()->getBlock('wizard.help.block')->setContent(
            $this->__(
                <<<HTML
<div>
    Under this section, you can link your Walmart account to M2E Pro.
    Read how to <a href="%url%" target="_blank">get the API credentials</a> or register on
    <a href="https://marketplace-apply.walmart.com/apply?id=00161000012XSxe" target="_blank">Walmart US</a> /
    <a href="https://marketplace.walmart.ca/apply?q=ca" target="_blank">Walmart CA</a>.
</div>
HTML
                ,
                $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/XgBhAQ')
            )
        );

        parent::_prepareLayout();
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                ]
            ]
        );

        $fieldset = $form->addFieldset(
            'account_details',
            [
            ]
        );

        $marketplacesCollection = $this->walmartFactory->getObject('Marketplace')->getCollection()
            ->addFieldToFilter('developer_key', ['notnull' => true])
            ->setOrder('sorder', 'ASC');

        $marketplaces = [
            [
                'value' => '',
                'label' => ''
            ]
        ];
        foreach ($marketplacesCollection->getItems() as $item) {
            $marketplace = array_merge($item->getData(), $item->getChildObject()->getData());
            $marketplaces[$marketplace['id']] = $marketplace['title'];
        }

        $fieldset->addField(
            'marketplace_id',
            'select',
            [
                'label'    => $this->__('Marketplace'),
                'name'     => 'marketplace_id',
                'required' => true,
                'values'   => $marketplaces,
                'onchange' => 'InstallationWalmartWizardObj.changeMarketplace(this.value);'
            ]
        );

        $marketplaceUS = Walmart::MARKETPLACE_US;
        $marketplaceCA = Walmart::MARKETPLACE_CA;

        $fieldset->addField(
            'marketplaces_register_url_ca',
            'link',
            [
                'label'     => '',
                'href'      => $this->getHelper('Component\Walmart')->getRegisterUrl($marketplaceCA),
                'target'    => '_blank',
                'value'     => $this->__('Get Access Data'),
                'class'     => "external-link",
                'css_class' => "marketplace-required-field marketplace-required-field-id{$marketplaceCA}",
            ]
        );
        $fieldset->addField(
            'marketplaces_register_url_us',
            'link',
            [
                'label'     => '',
                'href'      => $this->getHelper('Component\Walmart')->getRegisterUrl($marketplaceUS),
                'target'    => '_blank',
                'value'     => $this->__('Get Access Data'),
                'class'     => "external-link",
                'css_class' => "marketplace-required-field marketplace-required-field-id{$marketplaceUS}",
            ]
        );

        $fieldset->addField(
            'consumer_id',
            'text',
            [
                'container_id' => 'marketplaces_consumer_id_container',
                'name'         => 'consumer_id',
                'label'        => $this->__('Consumer ID'),
                'css_class'    => "marketplace-required-field marketplace-required-field-id{$marketplaceCA}",
                'required'     => true,
                'tooltip'      => $this->__('A unique seller identifier on the website.'),
            ]
        );

        $fieldset->addField(
            'private_key',
            'textarea',
            [
                'container_id' => 'marketplaces_private_key_container',
                'name'         => 'private_key',
                'label'        => $this->__('Private Key'),
                'class'        => "M2ePro-marketplace-merchant",
                'css_class'    => "marketplace-required-field marketplace-required-field-id{$marketplaceCA}",
                'required'     => true,
                'tooltip'      => $this->__('Walmart Private Key generated from your Seller Center Account.')
            ]
        );

        $fieldset->addField(
            'client_id',
            'text',
            [
                'container_id' => 'marketplaces_client_id_container',
                'name'         => 'client_id',
                'label'        => $this->__('Client ID'),
                'class'        => '',
                'css_class'    => "marketplace-required-field marketplace-required-field-id{$marketplaceUS}",
                'required'     => true,
                'tooltip'      => $this->__('A Client ID retrieved to get an access token.')
            ]
        );

        $fieldset->addField(
            'client_secret',
            'textarea',
            [
                'container_id' => 'marketplaces_client_secret_container',
                'name'         => 'client_secret',
                'label'        => $this->__('Client Secret'),
                'class'        => 'M2ePro-marketplace-merchant',
                'css_class'    => "marketplace-required-field marketplace-required-field-id{$marketplaceUS}",
                'required'     => true,
                'tooltip'      => $this->__('A Client Secret key retrieved to get an access token.')
            ]
        );

        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants(WalmartAccount::class));
        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants(Walmart::class));

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Wizard\InstallationWalmart'));
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Walmart\Account'));

        $this->js->addRequireJs(
            [
                'wa' => 'M2ePro/Walmart/Account'
            ],
            <<<JS
    WalmartAccountObj = new WalmartAccount();
    WalmartAccountObj.initTokenValidation();
JS
        );

        $this->js->addOnReadyJs(
            <<<JS
    wait(
        function() {
            return typeof InstallationWalmartWizardObj != 'undefined';
        },
        function() {
            $('marketplace_id').simulate('change');
        },
        50
    );
JS
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _beforeToHtml()
    {
        $this->jsTranslator->addTranslations(
            [
                'M2E Pro was not able to get access to the Walmart Account'                          => $this->__(
                    'M2E Pro could not get access to your Walmart account. <br>
                 For Walmart CA, please check if you entered valid Consumer ID and Private Key. <br>
                 For Walmart US, please ensure to provide M2E Pro with full access permissions
                 to all API sections and enter valid Consumer ID, Client ID, and Client Secret.'
                ),
                'M2E Pro was not able to get access to the Walmart Account. Reason: %error_message%' => $this->__(
                    'M2E Pro was not able to get access to the Walmart Account. Reason: %error_message%'
                ),
            ]
        );

        return parent::_beforeToHtml();
    }

    //########################################
}
