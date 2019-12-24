<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Helper\Component\Walmart;
use Ess\M2ePro\Model\Walmart\Account as WalmartAccount;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Account\Edit\Tabs\General
 */
class General extends AbstractForm
{
    protected $walmartFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $WalmartFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->walmartFactory = $WalmartFactory;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    //########################################

    protected function _prepareForm()
    {
        /** @var $account \Ess\M2ePro\Model\Account */
        $account = $this->getHelper('Data\GlobalData')->getValue('edit_account');
        $formData = $account !== null ? array_merge($account->getData(), $account->getChildObject()->getData()) : [];

        if (isset($formData['other_listings_mapping_settings'])) {
            $formData['other_listings_mapping_settings'] = (array)$this->getHelper('Data')->jsonDecode(
                $formData['other_listings_mapping_settings'],
                true
            );
        }

        $defaults = [
            'title'           => '',
            'marketplace_id'  => 0,
            'consumer_id'     => '',
            'old_private_key' => '',
            'client_id'       => '',
            'client_secret'   => ''
        ];

        $formData = array_merge($defaults, $formData);

        $isEdit = !!$this->getRequest()->getParam('id');

        $marketplacesCollection = $this->getHelper('Component\Walmart')->getMarketplacesAvailableForApiCreation();
        $marketplaces = [];
        foreach ($marketplacesCollection->getItems() as $item) {
            $marketplaces[] = array_merge($item->getData(), $item->getChildObject()->getData());
        }

        $form = $this->_formFactory->create();

        $form->addField(
            'walmart_accounts_general',
            self::HELP_BLOCK,
            [
                'content' =>  $this->__(
                    <<<HTML
<div>
    Under this section, you can link your Walmart account to M2E Pro.
    Read how to <a href="%url%" target="_blank">get the API credentials</a>.
</div>
HTML
                    ,
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/XgBhAQ')
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'general',
            [
                'legend'      => $this->__('General'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'title',
            'text',
            [
                'name'     => 'title',
                'class'    => 'M2ePro-account-title',
                'label'    => $this->__('Title'),
                'required' => true,
                'value'    => $formData['title'],
                'tooltip'  => $this->__('Title or Identifier of Walmart Account for your internal use.')
            ]
        );

        $fieldset = $form->addFieldset(
            'access_details',
            [
                'legend'      => $this->__('Access Details'),
                'collapsable' => false
            ]
        );

        $marketplaceUS = Walmart::MARKETPLACE_US;
        $marketplaceCA = Walmart::MARKETPLACE_CA;

        $marketplacesValues = [];
        if (!$isEdit) {
            $marketplacesValues[] = [
                'label' => '',
                'value' => '',
                'attrs' => [
                    'style' => 'display: none;'
                ]
            ];
        }
        foreach ($marketplaces as $marketplace) {
            $marketplacesValues[$marketplace['id']] = $marketplace['title'];
        }

        $fieldset->addField(
            'marketplace_id',
            self::SELECT,
            [
                'name'     => 'marketplace_id',
                'label'    => $this->__('Marketplace'),
                'values'   => $marketplacesValues,
                'value'    => $formData['marketplace_id'],
                'disabled' => $isEdit,
                'required' => true
            ]
        );

        if ($isEdit) {
            $fieldset->addField(
                'marketplace_id_hidden',
                'hidden',
                [
                    'name'  => 'marketplace_id',
                    'value' => $formData['marketplace_id'],
                ]
            );
            $fieldset->addField(
                'consumer_id_hidden',
                'hidden',
                [
                    'name'  => 'consumer_id',
                    'value' => $formData['consumer_id'],
                ]
            );
        }

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
                'value'        => $formData['consumer_id'],
                'disabled'     => $isEdit,
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
                'value'        => $formData['old_private_key'],
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
                'value'        => $formData['client_id'],
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
                'value'        => $formData['client_secret'],
                'tooltip'      => $this->__('A Client Secret key retrieved to get an access token.')
            ]
        );

        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants(WalmartAccount::class));
        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants(Walmart::class));

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Walmart\Account', ['_current' => true]));
        $this->jsUrl->addUrls([
            'formSubmit' => $this->getUrl(
                '*/walmart_account/save',
                ['_current' => true, 'id' => $this->getRequest()->getParam('id')]
            ),
            'deleteAction' => $this->getUrl(
                '*/walmart_account/delete',
                ['id' => $this->getRequest()->getParam('id')]
            )
        ]);

        $this->jsTranslator->add('Please enter correct value.', $this->__('Please enter correct value.'));

        $this->jsTranslator->add(
            'Be attentive! By Deleting Account you delete all information on it from M2E Pro Server. '
            . 'This will cause inappropriate work of all Accounts\' copies.',
            $this->__(
                'Be attentive! By Deleting Account you delete all information on it from M2E Pro Server. '
                . 'This will cause inappropriate work of all Accounts\' copies.'
            )
        );

        $this->jsTranslator->addTranslations([
            'Coefficient is not valid.' => $this->__('Coefficient is not valid.'),
            'The specified Title is already used for other Account. Account Title must be unique.' => $this->__(
                'The specified Title is already used for other Account. Account Title must be unique.'
            ),
            'M2E Pro was not able to get access to the Walmart Account' => $this->__(
                'M2E Pro could not get access to your Walmart account. <br>
                 For Walmart CA, please check if you entered valid Consumer ID and Private Key. <br>
                 For Walmart US, please ensure to provide M2E Pro with full access permissions
                 to all API sections and enter valid Consumer ID, Client ID, and Client Secret.'
            ),
            'M2E Pro was not able to get access to the Walmart Account. Reason: %error_message%' => $this->__(
                'M2E Pro was not able to get access to the Walmart Account. Reason: %error_message%'
            ),
            'Consumer ID' => 'Consumer ID',
            'Consumer ID / Partner ID' => 'Consumer ID / Partner ID',
            'The specified Consumer ID / Partner ID is not valid' => $this->__(
                'The specified Consumer ID / Partner ID is not valid.
                Please find the instruction on how to get it <a target="_blank" href="%url%">here</a>.',
                $this->getHelper('Module_Support')->getSupportUrl(
                    'how-to-guide/1570387-how-to-get-my-consumer-id-partner-id-to-auth-m2e-on-walmart-us'
                )
            )
        ]);

        $this->js->add(<<<JS
    require([
        'M2ePro/Walmart/Account'
    ], function(){

        window.WalmartAccountObj = new WalmartAccount();
        WalmartAccountObj.initObservers();
    });
JS
        );

        $id = $this->getRequest()->getParam('id');
        $title = $this->getHelper('Data')->escapeJs($this->getHelper('Data')->escapeHtml($formData['title']));

        $this->js->add("M2ePro.formData.id = '$id';");
        $this->js->add("M2ePro.formData.title = '$title';");

        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################
}
