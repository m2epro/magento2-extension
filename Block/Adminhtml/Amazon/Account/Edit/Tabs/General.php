<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs;

class General extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory */
    protected $amazonFactory;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $sessionHelper;
    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    private $amazonHelper;
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    /** @var \Ess\M2ePro\Model\Amazon\Account\Builder */
    private $accountBuilder;

    /**
     * @param \Ess\M2ePro\Model\Amazon\Account\Builder $accountBuilder
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param \Ess\M2ePro\Helper\Data\Session $sessionHelper
     * @param \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper
     * @param \Ess\M2ePro\Helper\Component\Amazon $amazonHelper
     * @param \Ess\M2ePro\Helper\Module\Support $supportHelper
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Model\Amazon\Account\Builder $accountBuilder,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\Session $sessionHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Component\Amazon $amazonHelper,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->amazonFactory = $amazonFactory;
        $this->globalDataHelper = $globalDataHelper;
        $this->dataHelper = $dataHelper;
        $this->sessionHelper = $sessionHelper;
        $this->amazonHelper = $amazonHelper;
        $this->supportHelper = $supportHelper;
        $this->accountBuilder = $accountBuilder;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->globalDataHelper->getValue('edit_account');
        $accountData = $account !== null ? array_merge($account->getData(), $account->getChildObject()->getData()) : [];

        if (isset($accountData['other_listings_mapping_settings'])) {
            $accountData['other_listings_mapping_settings'] = (array)\Ess\M2ePro\Helper\Json::decode(
                $accountData['other_listings_mapping_settings'],
                true
            );
        }

        $formData = array_merge(
            $this->accountBuilder->getDefaultData(),
            $accountData
        );

        $isEdit = !empty($this->getRequest()->getParam('id'));

        $marketplacesCollection = $this->amazonHelper->getMarketplacesAvailableForApiCreation();
        $marketplaces = [];
        foreach ($marketplacesCollection->getItems() as $item) {
            $marketplaces[] = array_merge($item->getData(), $item->getChildObject()->getData());
        }

        $accountTitle = $this->sessionHelper->getValue('account_title', true);
        $merchantId = $this->sessionHelper->getValue('merchant_id', true);
        $mwsToken = $this->sessionHelper->getValue('mws_token', true);

        $isAuthMode = !empty($merchantId) && !empty($mwsToken);

        $authMarketplaceId = $this->sessionHelper->getValue('marketplace_id', true);

        $form = $this->_formFactory->create();

        $form->addField(
            'amazon_accounts_general',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    <<<HTML
<p><strong>Important note:</strong> to be eligible to sell on Amazon, seller must have at least one of the following
Amazon accounts: <br>
Non-individual Amazon seller, Amazon Payments, Amazon Fresh, Amazon Business, Amazon Prime Now, Amazon Webstore, Amazon
Product Ads, Amazon Supply.</p>
<p>Individual sellers may upgrade to a Pro Merchant seller account.</p> <br>
<p>Specify an Account Title, select an Amazon Marketplace, click <strong>Get Access Data</strong> and proceed with the
following steps to grant M2E Pro access to your Amazon data:</p>
<ul>
<li>sign in to your Seller Central account;</li>
<li>confirm you allow M2E Pro to access your Amazon selling account;</li>
</ul>
<p>Amazon Authorization Token will be generated automatically. After you get back to M2E Pro Amazon Account
Configuration page, click <strong>Save</strong> to apply the changes.</p><br>
<p>More detailed information on how to work with this page can be found <a href="%url%" target="_blank">here</a>.</p>
HTML
                    ,
                    $this->supportHelper->getDocumentationArticleUrl('x/Wv8UB')
                ),
            ]
        );

        $fieldset = $form->addFieldset(
            'general',
            [
                'legend'      => $this->__('General'),
                'collapsable' => false,
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
                'value'    => !empty($accountTitle) ? $accountTitle : $formData['title'],
                'tooltip'  => $this->__('Title or Identifier of Amazon Account for your internal use.'),
            ]
        );

        $fieldset = $form->addFieldset(
            'access_details',
            [
                'legend'      => $this->__('Access Details'),
                'collapsable' => false,
            ]
        );

        $preparedValues = [];
        if (!$isEdit) {
            $preparedValues[] = [
                'label' => '',
                'value' => '',
                'attrs' => [
                    'style' => 'display: none;',
                ],
            ];
        }
        foreach ($marketplaces as $marketplace) {
            $preparedValues[$marketplace['id']] = $marketplace['title'];
        }

        $fieldset->addField(
            'marketplace_id',
            self::SELECT,
            [
                'name'     => 'marketplace_id',
                'label'    => $this->__('Marketplace'),
                'values'   => $preparedValues,
                'value'    => $isAuthMode ? $authMarketplaceId : $formData['marketplace_id'],
                'disabled' => $isEdit,
                'required' => true,
                'onchange' => 'AmazonAccountObj.changeMarketplace(this.value);',
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
                'merchant_id_hidden',
                'hidden',
                [
                    'name'  => 'merchant_id',
                    'value' => $formData['merchant_id'],
                ]
            );
        }

        $fieldset->addField(
            'marketplaces_application_name',
            'label',
            [
                'container_id' => 'marketplaces_application_name_container',
                'label'        => $this->__('Application Name'),
                'value'        => $this->amazonHelper->getApplicationName(),
                'display'      => false,
            ]
        )->setFieldExtraAttributes('style="display: none"');

        foreach ($marketplaces as $marketplace) {
            $fieldset->addField(
                'marketplaces_developer_key_' . $marketplace['id'],
                'label',
                [
                    'container_id' => 'marketplaces_developer_key_container_' . $marketplace['id'],
                    'label'        => $this->__('Developer Account Number'),
                    'value'        => $marketplace['developer_key'],
                ]
            )->setFieldExtraAttributes('style="display: none"');

            $fieldset->addField(
                'marketplaces_register_url_' . $marketplace['id'],
                'link',
                [
                    'container_id' => 'marketplaces_register_url_container_' . $marketplace['id'],
                    'label'        => '',
                    'target'       => '_blank',
                    'value'        => $this->__('Get Access Data'),
                    'onclick'      => 'return AmazonAccountObj.getToken(' . $marketplace['id'] . ')',
                    'class'        => 'external-link',
                ]
            )->setFieldExtraAttributes('style="display: none"');
        }

        if ($isEdit) {
            $button = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)->addData(
                [
                    'label'   => $this->__('Check Token Validity'),
                    'onclick' => 'AmazonAccountObj.checkClick()',
                    'class'   => 'check M2ePro_check_button primary',
                    'id'      => 'check_token_validity',
                ]
            );
            $fieldset->addField(
                'check_token_validity_container',
                'label',
                [
                    'label'              => '',
                    'after_element_html' => $button->toHtml(),
                ]
            );
        }

        $field = $fieldset->addField(
            'merchant_id',
            'text',
            [
                'container_id' => 'marketplaces_merchant_id_container',
                'name'         => 'merchant_id',
                'label'        => $this->__('Merchant ID'),
                'class'        => 'M2ePro-marketplace-merchant',
                'required'     => true,
                'value'        => $isAuthMode ? $merchantId : ($isEdit ? $formData['merchant_id'] : ''),
                'display'      => $isEdit,
                'disabled'     => $isEdit,
                'tooltip'      => $this->__('Your Amazon Seller ID.'),
            ]
        );
        if (!$isEdit) {
            $field->setFieldExtraAttributes('style="display: none"');
        }

        if (!$isEdit) {
            $field = $fieldset->addField(
                'token',
                'text',
                [
                    'container_id' => 'marketplaces_token_container',
                    'name'         => 'token',
                    'label'        => $this->__('MWS Auth Token'),
                    'class'        => 'M2ePro-marketplace-merchant',
                    'required'     => true,
                    'value'        => $isAuthMode ? $mwsToken : '',
                    'display'      => false,
                    'tooltip'      => $this->__('An obtained Amazon Authorization Token.'),
                ]
            );

            $field->setFieldExtraAttributes('style="display: none"');
        }

        $this->jsPhp->addConstants($this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Amazon\Account::class));
        $this->jsPhp->addConstants($this->dataHelper->getClassConstants(\Ess\M2ePro\Helper\Component\Amazon::class));

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Amazon\Account', ['_current' => true]));
        $this->jsUrl->addUrls([
            'formSubmit'   => $this->getUrl(
                '*/amazon_account/save',
                ['_current' => true, 'id' => $this->getRequest()->getParam('id')]
            ),
            'checkAction'  => $this->getUrl(
                '*/amazon_account/check',
                ['_current' => true, 'id' => $this->getRequest()->getParam('id')]
            ),
            'deleteAction' => $this->getUrl(
                '*/amazon_account/delete',
                ['id' => $this->getRequest()->getParam('id')]
            ),
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

        $id = $this->getRequest()->getParam('id');
        $this->js->add("M2ePro.formData.id = '$id';");

        $marketplaceJs = '';

        if ($isAuthMode) {
            $marketplaceJs = 'AmazonAccountObj.changeMarketplace(' . $authMarketplaceId . ');';
        } elseif ($isEdit) {
            $marketplaceJs = "AmazonAccountObj.showGetAccessData({$formData['marketplace_id']});";
        }

        $this->js->add(
            <<<JS
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
            'M2E Pro was not able to get access to the Amazon Account. Please, make sure, that you choose correct ' .
            'Option on MWS Authorization Page and enter correct Merchant ID.'                      => $this->__(
                'M2E Pro was not able to get access to the Amazon Account.' .
                ' Please, make sure, that you choose correct Option on MWS Authorization Page
                and enter correct Merchant ID / MWS Auth Token'
            ),
            'M2E Pro was not able to get access to the Amazon Account. Reason: %error_message%'    => $this->__(
                'M2E Pro was not able to get access to the Amazon Account. Reason: %error_message%'
            ),
        ]);

        $title = $this->dataHelper->escapeJs($this->dataHelper->escapeHtml($formData['title']));
        $this->js->add("M2ePro.formData.title = '$title';");

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
