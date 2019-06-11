<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

class General extends AbstractForm
{
    protected $WalmartFactory;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $WalmartFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->WalmartFactory = $WalmartFactory;

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
            'title'           => '',
            'marketplace_id'  => 0,
            'consumer_id'     => '',
            'old_private_key' => '',
            'client_id'       => '',
            'client_secret'   => ''
        );

        $formData = array_merge($defaults, $formData);

        $isEdit = !!$this->getRequest()->getParam('id');

        $marketplacesCollection = $this->getHelper('Component\Walmart')->getMarketplacesAvailableForApiCreation();
        $marketplaces = [];
        foreach ($marketplacesCollection->getItems() as $item) {
            $marketplaces[] = array_merge($item->getData(), $item->getChildObject()->getData());
        }

        $form = $this->_formFactory->create();

        $USMarketplace = \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_US;
        $CAMarketplace = \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_CA;

        $USHelpHtml = $this->__(<<<HTML
<div class="marketplace-required-field marketplace-required-field-id{$USMarketplace}">
Under this section, you need to connect M2E Pro with your Walmart account. Please complete the following steps:<br>
<ul class="list">
    <li>Select Walmart marketplace.</li>
    <li>Click <b>Get Access Data</b>. You will be redirected to the Walmart Developer Center.</li>
    <li>Log in using your Walmart Seller credentials.</li>
    <li>Under <i>Username > API Keys > Digital Signature</i>, copy your <i>Consumer ID</i> and paste it into the
    current M2E Pro page. </li>
    <li>Under <i>Username > Delegate Access</i>, provide M2E Pro with the full access permissions to all API sections.
    Click <b>API Keys</b> to generate <i>Client ID</i> and <i>Client Secret</i>.</li>
    <li>Copy your <i>Client ID</i> and <i>Client Secret</i> and paste the keys into the current M2E Pro page.</li>
    <li>Click <b>Save and Continue Edit</b>. Extension will be granted access to your Walmart account data.</li>
</ul>

<b>Important note:</b> Your <i>Consumer ID</i> must not be changed once it is obtain.
<i>Consumer ID</i> is unique seller
identifier M2E Pro requires to act on your behalf. <br>
If you need to reauthorize Extension, please generate new <i>Client ID</i> and <i>Client Secret</i> for M2E Pro under
<i>Username > Delegate Access</i> in Developer Center. <br><br>

The detailed information can be found <a href="%url%" target="_blank">here</a>.
</div>
HTML
            ,
            $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/XgBhAQ')
        );

        $CAHelpHtml = $this->__(<<<HTML
<div class="marketplace-required-field marketplace-required-field-id{$CAMarketplace}">
Under this section, you need to connect M2E Pro with your Walmart account. Please complete the following steps:<br/>
<ul class="list">
    <li>Click Get Access Data. You will be redirected to the Walmart website.</li>
    <li>Log in to your Seller Center Account.</li>
    <li>In admin panel, navigate to <i>Settings > API > Consumer IDs & Private Keys</i>.</li>
    <li>Copy the generated Consumer ID and Private Key to the corresponding fields on the current page.</li>
    <li>Click Save and Continue Edit. Extension will be granted access to your Walmart Account data.</li>
</ul>

<b>Note:</b> Make sure that you copy the valid API credentials, i.e. <b>Consumer ID</b> and <b>Private Key</b>.<br><br>

<b>Important note:</b> The Private Key is common for all applications you are using. Regeneration of the Key will
deactivate your previous Private Key.
This may cause the apps to no longer function properly.<br><br>

The detailed information can be found <a href="%url%" target="_blank">here</a>.
</div>
HTML
            ,
            $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/XgBhAQ')
        );

        $form->addField(
            'walmart_accounts_general',
            self::HELP_BLOCK,
            [
                'content' => $this->__(<<<HTML
                    {$USHelpHtml}

                    {$CAHelpHtml}
HTML
                ),
                'class' => 'marketplace-required-field marketplace-required-field-id-not-null'
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
                'value' => $formData['title'],
                'tooltip' => $this->__('Title or Identifier of Walmart Account for your internal use.')
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
                'value' => $formData['marketplace_id'],
                'disabled' => $isEdit,
                'required' => true
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
                'consumer_id_hidden',
                'hidden',
                [
                    'name' => 'consumer_id',
                    'value' => $formData['consumer_id'],
                ]
            );
        }

        $fieldset->addField(
            'marketplaces_register_url',
            'link',
            [
                'container_id' => 'marketplaces_register_url_container',
                'label'        => '',
                'href'         => 'javascript: void(0);',
                'target'       => '_blank',
                'value'        => $this->__('Get Access Data'),
                'class'        => 'external-link marketplaces_view_element',
                'css_class'    => 'marketplace-required-field'
            ]
        );

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
                'value' => $isEdit ? $formData['consumer_id'] : '',
                'display' => $isEdit,
                'disabled' => $isEdit,
                'tooltip' => $this->__('A unique seller identifier on the website.
                                        <br><b>Note:</b> Your <i>Consumer ID</i>
                                         must not be changed once it is obtain.')
            ]
        );

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
                'value' => $isEdit ? $formData['old_private_key'] : '',
                'display' => $isEdit,
                'tooltip' => $this->__('Walmart Private Key generated from your Seller Center Account.')
            ]
        );

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
                'value' => $isEdit ? $formData['client_id'] : '',
                'display' => $isEdit,
                'tooltip' => $this->__('A unique API key retrieved to get an access token.')
            ]
        );

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
                'value' => $isEdit ? $formData['client_secret'] : '',
                'display' => $isEdit,
                'tooltip' => $this->__('A unique API key retrieved to get an access token.')
            ]
        );

        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Walmart\Account'));
        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Helper\Component\Walmart'));

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Walmart\Account', ['_current' => true]));
        $this->jsUrl->addUrls([
            'formSubmit' => $this->getUrl(
                '*/walmart_account/save', array('_current' => true, 'id' => $this->getRequest()->getParam('id'))
            ),
            'deleteAction' => $this->getUrl(
                '*/walmart_account/delete', array('id' => $this->getRequest()->getParam('id'))
            )
        ]);

        $this->jsTranslator->add('Please enter correct value.', $this->__('Please enter correct value.'));

        $this->jsTranslator->add(
            'Be attentive! By Deleting Account you delete all information on it from M2E Pro Server. '
            . 'This will cause inappropriate work of all Accounts\' copies.',
            $this->__(
                'Be attentive! By Deleting Account you delete all information on it from M2E Pro Server. '
                . 'This will cause inappropriate work of all Accounts\' copies.'));

        $this->jsTranslator->addTranslations([
            'Coefficient is not valid.' => $this->__('Coefficient is not valid.'),
            'The specified Title is already used for other Account. Account Title must be unique.' => $this->__(
                'The specified Title is already used for other Account. Account Title must be unique.'
            ),
            'You must choose Marketplace first.' => $this->__('You must choose Marketplace first.'),
            'M2E Pro was not able to get access to the Walmart Account' => $this->__(
                'M2E Pro could not get access to your Walmart account. Please ensure that you provide M2E Pro with full
                access permissions to all API sections, enter the valid Consumer ID, Private Key (US), Client ID (CA)
                and Client Secret (CA).'
            ),
            'M2E Pro was not able to get access to the Walmart Account. Reason: %error_message%' => $this->__(
                'M2E Pro was not able to get access to the Walmart Account. Reason: %error_message%'
            )
        ]);

        $id = $this->getRequest()->getParam('id');
        $this->js->add("M2ePro.formData.id = '$id';");

        $marketplaceUS = \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_US;
        $marketplaceCA = \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_CA;

        $usUrl = $this->getHelper('Component\Walmart')->getRegisterUrl($marketplaceUS);
        $caUrl = $this->getHelper('Component\Walmart')->getRegisterUrl($marketplaceCA);

        $this->js->add(<<<JS
    require([
        'M2ePro/Walmart/Account',
    ], function(){

        M2ePro.customData['marketplace-{$marketplaceUS}-url'] = '$usUrl';
        M2ePro.customData['marketplace-{$marketplaceCA}-url'] = '$caUrl';

        window.WalmartAccountObj = new WalmartAccount();
        WalmartAccountObj.initObservers();
    });
JS
        );

        $title = $this->getHelper('Data')->escapeJs($this->getHelper('Data')->escapeHtml($formData['title']));
        $this->js->add("M2ePro.formData.title = '$title';");

        $this->setForm($form);

        return parent::_prepareForm();
    }
}