<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\InstallationWalmart\Installation\Account;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

/**
 * Class Content
 * @package Ess\M2ePro\Block\Adminhtml\Wizard\InstallationWalmart\Installation\Account
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

    protected function _prepareLayout()
    {
        $this->getLayout()->getBlock('wizard.help.block')->setContent(
            $this->__(<<<HTML
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
                'label' => $this->__('Marketplace'),
                'name' => 'marketplace_id',
                'required' => true,
                'values' => $marketplaces,
                'onchange' => 'InstallationWalmartWizardObj.changeMarketplace(this.value);'
            ]
        );

        $fieldset->addField(
            'marketplaces_register_url',
            'button',
            [
                'container_id' => 'marketplaces_register_url_container',
                'label' => '',
                'onclick' => 'InstallationWalmartWizardObj.getAccessDataUrl(this);',
                'value' => $this->__('Get Access Data'),
                'class' => 'action-primary',
                'css_class'    => 'marketplace-required-field'
            ]
        )->setFieldExtraAttributes('style="display: none;"');

        $fieldset->addField(
            'consumer_id',
            'text',
            [
                'container_id' => 'marketplaces_consumer_id_container',
                'name' => 'consumer_id',
                'label' => $this->__('Consumer ID'),
                'class' => 'M2ePro-marketplace-consumer-id M2ePro-required-when-visible',
                'css_class' => 'marketplace-required-field marketplace-required-field-id-not-null',
                'required' => true,
                'tooltip' => $this->__('A unique seller identifier on the website.
                                        <br><b>Note:</b> Your <i>Consumer ID</i>
                                         must not be changed once it is obtain.')
            ]
        )->setFieldExtraAttributes('style="display: none;"');

        $fieldset->addField(
            'old_private_key',
            'textarea',
            [
                'container_id' => 'marketplaces_old_private_key_container',
                'name' => 'old_private_key',
                'label' => $this->__('Private Key'),
                'class' => 'M2ePro-required-when-visible marketplace-required-input marketplace-required-input-text-id'.
                    \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_CA,
                'css_class' => 'marketplace-required-field marketplace-required-field-id' .
                    \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_CA,
                'required' => true,
                'tooltip' => $this->__('Walmart Private Key generated from your Seller Center Account.')
            ]
        )->setFieldExtraAttributes('style="display: none;"');

        $fieldset->addField(
            'client_id',
            'text',
            [
                'container_id' => 'marketplaces_client_id_container',
                'name' => 'client_id',
                'label' => $this->__('Client ID'),
                'class' => 'M2ePro-marketplace-client-id M2ePro-required-when-visible',
                'css_class' => 'marketplace-required-field marketplace-required-field-id' .
                    \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_US,
                'required' => true,
                'tooltip' => $this->__('A unique API key retrieved to get an access token.')
            ]
        )->setFieldExtraAttributes('style="display: none;"');

        $fieldset->addField(
            'client_secret',
            'textarea',
            [
                'container_id' => 'marketplaces_client_secret_container',
                'name' => 'client_secret',
                'label' => $this->__('Client Secret'),
                'class' => 'M2ePro-required-when-visible marketplace-required-input marketplace-required-input-text-id'.
                    \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_US,
                'css_class' => 'marketplace-required-field marketplace-required-field-id' .
                    \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_US,
                'required' => true,
                'tooltip' => $this->__('A unique API key retrieved to get an access token.')
            ]
        )->setFieldExtraAttributes('style="display: none;"');

        $this->jsPhp->addConstants($this->getHelper('Data')
            ->getClassConstants(\Ess\M2ePro\Model\Walmart\Account::class));
        $this->jsPhp->addConstants($this->getHelper('Data')
            ->getClassConstants(\Ess\M2ePro\Helper\Component\Walmart::class));

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Wizard\InstallationWalmart'));

        $marketplaceUS = \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_US;
        $marketplaceCA = \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_CA;

        $usUrl = $this->getHelper('Component\Walmart')->getRegisterUrl($marketplaceUS);
        $caUrl = $this->getHelper('Component\Walmart')->getRegisterUrl($marketplaceCA);

        $this->js->addOnReadyJs(<<<JS

        M2ePro.customData['marketplace-{$marketplaceUS}-url'] = '$usUrl';
        M2ePro.customData['marketplace-{$marketplaceCA}-url'] = '$caUrl';

        wait(function() {
            return typeof InstallationWalmartWizardObj != 'undefined';
        }, function() {
          $('marketplace_id').simulate('change');
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

        return parent::_beforeToHtml();
    }
}
