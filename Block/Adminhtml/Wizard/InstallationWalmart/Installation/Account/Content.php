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
    Read how to <a href="%url%" target="_blank">get the API credentials</a>.
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
        $form = $this->_formFactory->create([
            'data' => [
                'id' => 'edit_form',
            ]
        ]);

        $fieldset = $form->addFieldset(
            'account_details',
            [
            ]
        );

        $marketplacesCollection = $this->walmartFactory->getObject('Marketplace')->getCollection()
            ->addFieldToFilter('developer_key', ['notnull' => true])
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
                'label'        => '',
                'href'         => $this->getHelper('Component\Walmart')->getRegisterUrl($marketplaceCA),
                'target'       => '_blank',
                'value'        => $this->__('Get Access Data'),
                'class'        => "external-link",
                'css_class'    => "marketplace-required-field marketplace-required-field-id{$marketplaceCA}",
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
                'label'        => $this->__('Consumer ID / Partner ID'),
                'class'        => 'M2ePro-marketplace-consumer-id',
                'css_class'    => 'marketplace-required-field marketplace-required-field-id-not-null',
                'required'     => true,
                'tooltip'      => $this->__(
                    <<<HTML
<span class="marketplace-required-field marketplace-required-field-id{$marketplaceCA}">
    A unique seller identifier on the website.
</span>
<span class="marketplace-required-field marketplace-required-field-id{$marketplaceUS}">
    A unique seller identifier on the website.<br>
    <b>Note:</b> You can find the instruction on how to get the Consumer ID / Partner ID 
    <a target="_blank" href="%url%">here</a>.
</span>
HTML
                    ,
                    $this->getHelper('Module_Support')->getSupportUrl(
                        'how-to-guide/1570387-how-to-get-my-consumer-id-partner-id-to-auth-m2e-on-walmart-us'
                    )
                )
            ]
        );

        $fieldset->addField(
            'old_private_key',
            'textarea',
            [
                'container_id' => 'marketplaces_old_private_key_container',
                'name'         => 'old_private_key',
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

        $this->js->addOnReadyJs(<<<JS

        wait(function() {
            return typeof InstallationWalmartWizardObj != 'undefined';
        }, function() {
            
            $('marketplace_id').simulate('change');
            
            jQuery.validator.addMethod('M2ePro-validate-consumer-id', function(value, el) {

                if (InstallationWalmartWizardObj.isElementHiddenFromPage(el)) {
                    return true;
                }

                // Partner ID example: 10000004781
                // Consumer ID Example: c2cfff2c-57a9-4f0a-b5ab-00b000dfe000
                return /^[0-9]{11}$/.test(value) || /^[a-f0-9-]{36}$/.test(value);

            }, M2ePro.translator.translate('The specified Consumer ID / Partner ID is not valid'));
            
        }, 50);
JS
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _beforeToHtml()
    {
        $this->jsTranslator->add(
            'An error during of account creation.',
            $this->__('An error during of account creation.')
        );

        $this->jsTranslator->add(
            'Another Synchronization Is Already Running.',
            $this->__('Another Synchronization Is Already Running.')
        );

        $this->jsTranslator->add(
            'Getting information. Please wait ...',
            $this->__('Getting information. Please wait ...')
        );

        $this->jsTranslator->add(
            'Preparing to start. Please wait ...',
            $this->__('Preparing to start. Please wait ...')
        );

        $this->jsTranslator->add(
            'Please wait while Synchronization is finished.',
            $this->__('Please wait while Synchronization is finished.')
        );

        $this->jsTranslator->add('Consumer ID', 'Consumer ID');
        $this->jsTranslator->add('Consumer ID / Partner ID', 'Consumer ID / Partner ID');
        $this->jsTranslator->add(
            'The specified Consumer ID / Partner ID is not valid',
            $this->__(
                'The specified Consumer ID / Partner ID is not valid.
                Please find the instruction on how to get it <a target="_blank" href="%url%">here</a>.',
                $this->getHelper('Module_Support')->getSupportUrl(
                    'how-to-guide/1570387-how-to-get-my-consumer-id-partner-id-to-auth-m2e-on-walmart-us'
                )
            )
        );

        return parent::_beforeToHtml();
    }

    //########################################
}
