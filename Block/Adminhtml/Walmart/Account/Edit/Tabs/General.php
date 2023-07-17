<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Helper\Component\Walmart;
use Ess\M2ePro\Model\Walmart\Account as WalmartAccount;

class General extends AbstractForm
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory */
    protected $walmartFactory;

    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    /** @var \Ess\M2ePro\Helper\Component\Walmart */
    private $walmartHelper;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $WalmartFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Component\Walmart $walmartHelper,
        array $data = []
    ) {
        $this->walmartFactory = $WalmartFactory;
        $this->supportHelper = $supportHelper;
        $this->dataHelper = $dataHelper;
        $this->globalDataHelper = $globalDataHelper;
        $this->walmartHelper = $walmartHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->jsTranslator->add(
            'confirmation_account_delete',
            __(
                <<<HTML
<p>You are about to delete your eBay/Amazon/Walmart seller account from M2E Pro. This will remove the
account-related Listings and Products from the extension and disconnect the synchronization.
Your listings on the channel will <b>not</b> be affected.</p>
<p>Please confirm if you would like to delete the account.</p>
<p>Note: once the account is no longer connected to your M2E Pro, please remember to delete it from
<a href="%1">M2E Accounts</a></p>
HTML
                ,
                $this->supportHelper->getAccountsUrl()
            )
        );
    }

    protected function _prepareForm()
    {
        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->globalDataHelper->getValue('edit_account');
        $formData = $account !== null ? array_merge($account->getData(), $account->getChildObject()->getData()) : [];

        if (isset($formData['other_listings_mapping_settings'])) {
            $formData['other_listings_mapping_settings'] = (array)\Ess\M2ePro\Helper\Json::decode(
                $formData['other_listings_mapping_settings'],
                true
            );
        }

        $defaults = $this->modelFactory->getObject('Walmart_Account_Builder')->getDefaultData();

        $formData = array_merge($defaults, $formData);

        $isEdit = !!$this->getRequest()->getParam('id');

        $marketplacesCollection = $this->walmartHelper->getMarketplacesAvailableForApiCreation();
        $marketplaces = [];
        foreach ($marketplacesCollection->getItems() as $item) {
            $marketplaces[] = array_merge($item->getData(), $item->getChildObject()->getData());
        }

        $form = $this->_formFactory->create();

        if ($isEdit) {
            $content = $this->__(
                <<<HTML
<div>
    Under this section, you can link your Walmart account to M2E Pro.
    Read how to <a href="%url%" target="_blank">get the API credentials</a>.
</div>
HTML
                ,
                $this->supportHelper->getDocumentationArticleUrl('help/m2/walmart-integration/account-configurations')
            );
        } else {
            $content = $this->__(
                <<<HTML
<div>
    Under this section, you can link your Walmart account to M2E Pro.
    Read how to <a href="%url%" target="_blank">get the API credentials</a> or register on
    <a href="https://marketplace-apply.walmart.com/apply?id=00161000012XSxe" target="_blank">Walmart US</a> /
    <a href="https://marketplace.walmart.ca/apply?q=ca" target="_blank">Walmart CA</a>.
</div>
HTML
                ,
                $this->supportHelper->getDocumentationArticleUrl('help/m2/walmart-integration/account-configurations')
            );
        }

        $form->addField(
            'walmart_accounts_general',
            self::HELP_BLOCK,
            [
                'content' => $content,
            ]
        );

        $fieldset = $form->addFieldset(
            'general',
            [
                'legend' => __('General'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'title',
            'text',
            [
                'name' => 'title',
                'class' => 'M2ePro-account-title',
                'label' => __('Title'),
                'required' => true,
                'value' => $formData['title'],
                'tooltip' => __('Title or Identifier of Walmart Account for your internal use.'),
            ]
        );

        $fieldset = $form->addFieldset(
            'access_details',
            [
                'legend' => __('Access Details'),
                'collapsable' => false,
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
                    'style' => 'display: none;',
                ],
            ];
        }
        foreach ($marketplaces as $marketplace) {
            $marketplacesValues[$marketplace['id']] = $marketplace['title'];
        }

        $fieldset->addField(
            'marketplace_id',
            self::SELECT,
            [
                'name' => 'marketplace_id',
                'label' => __('Marketplace'),
                'values' => $marketplacesValues,
                'value' => $formData['marketplace_id'],
                'disabled' => $isEdit,
                'required' => true,
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
            'marketplaces_register_url_ca',
            'link',
            [
                'label' => '',
                'href' => $this->walmartHelper->getRegisterUrl($marketplaceCA),
                'target' => '_blank',
                'value' => __('Get Access Data'),
                'class' => "external-link",
                'css_class' => "marketplace-required-field marketplace-required-field-id{$marketplaceCA}",
            ]
        );
        $fieldset->addField(
            'marketplaces_register_url_us',
            'link',
            [
                'label' => '',
                'href' => $this->walmartHelper->getRegisterUrl($marketplaceUS),
                'target' => '_blank',
                'value' => __('Get Access Data'),
                'class' => "external-link",
                'css_class' => "marketplace-required-field marketplace-required-field-id{$marketplaceUS}",
            ]
        );

        $fieldset->addField(
            'consumer_id',
            'text',
            [
                'container_id' => 'marketplaces_consumer_id_container',
                'name' => 'consumer_id',
                'label' => __('Consumer ID'),
                'css_class' => "marketplace-required-field marketplace-required-field-id{$marketplaceCA}",
                'required' => true,
                'value' => $formData['consumer_id'],
                'disabled' => $isEdit,
                'tooltip' => __('A unique seller identifier on the website.'),
            ]
        );

        $fieldset->addField(
            'private_key',
            'textarea',
            [
                'container_id' => 'marketplaces_private_key_container',
                'name' => 'private_key',
                'label' => __('Private Key'),
                'class' => "M2ePro-marketplace-merchant",
                'css_class' => "marketplace-required-field marketplace-required-field-id{$marketplaceCA}",
                'required' => true,
                'value' => $formData['private_key'],
                'tooltip' => __('Walmart Private Key generated from your Seller Center Account.'),
            ]
        );

        $fieldset->addField(
            'client_id',
            'text',
            [
                'container_id' => 'marketplaces_client_id_container',
                'name' => 'client_id',
                'label' => __('Client ID'),
                'class' => '',
                'css_class' => "marketplace-required-field marketplace-required-field-id{$marketplaceUS}",
                'required' => true,
                'value' => $formData['client_id'],
                'tooltip' => __('A Client ID retrieved to get an access token.'),
            ]
        );

        $fieldset->addField(
            'client_secret',
            'textarea',
            [
                'container_id' => 'marketplaces_client_secret_container',
                'name' => 'client_secret',
                'label' => __('Client Secret'),
                'class' => 'M2ePro-marketplace-merchant',
                'css_class' => "marketplace-required-field marketplace-required-field-id{$marketplaceUS}",
                'required' => true,
                'value' => $formData['client_secret'],
                'tooltip' => __('A Client Secret key retrieved to get an access token.'),
            ]
        );

        $id = $this->getRequest()->getParam('id');
        $title = $this->dataHelper->escapeJs($this->dataHelper->escapeHtml($formData['title']));

        $this->js->add("M2ePro.formData.id = '$id';");
        $this->js->add("M2ePro.formData.title = '$title';");

        $this->jsPhp->addConstants($this->dataHelper->getClassConstants(WalmartAccount::class));
        $this->jsPhp->addConstants($this->dataHelper->getClassConstants(Walmart::class));

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Walmart\Account', ['_current' => true]));
        $this->jsUrl->addUrls(
            [
                'formSubmit' => $this->getUrl(
                    '*/walmart_account/save',
                    ['_current' => true, 'id' => $this->getRequest()->getParam('id')]
                ),
                'deleteAction' => $this->getUrl(
                    '*/walmart_account/delete',
                    ['id' => $this->getRequest()->getParam('id')]
                ),
            ]
        );

        $this->jsTranslator->add(
            'Be attentive! By Deleting Account you delete all information on it from M2E Pro Server. '
            . 'This will cause inappropriate work of all Accounts\' copies.',
            __(
                'Be attentive! By Deleting Account you delete all information on it from M2E Pro Server. '
                . 'This will cause inappropriate work of all Accounts\' copies.'
            )
        );

        $this->jsTranslator->addTranslations(
            [
                'Coefficient is not valid.' => __(
                    'Coefficient is not valid.'
                ),
                'The specified Title is already used for other Account. Account Title must be unique.' => __(
                    'The specified Title is already used for other Account. Account Title must be unique.'
                ),
                'M2E Pro was not able to get access to the Walmart Account' => __(
                    'M2E Pro could not get access to your Walmart account. <br>
                 For Walmart CA, please check if you entered valid Consumer ID and Private Key. <br>
                 For Walmart US, please ensure to provide M2E Pro with full access permissions
                 to all API sections and enter valid Consumer ID, Client ID, and Client Secret.'
                ),
                'M2E Pro was not able to get access to the Walmart Account. Reason: %error_message%' => __(
                    'M2E Pro was not able to get access to the Walmart Account. Reason: %error_message%'
                ),
            ]
        );

        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _prepareLayout()
    {
        $this->js->add(
            <<<JS
    require([
        'M2ePro/Walmart/Account',
    ], function() {
        WalmartAccountObj.initValidation();
        WalmartAccountObj.initTokenValidation();
        WalmartAccountObj.initObservers();
    });
JS
            ,
            2
        );

        return parent::_prepareLayout();
    }
}
