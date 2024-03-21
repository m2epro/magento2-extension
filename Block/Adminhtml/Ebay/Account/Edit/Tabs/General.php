<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs;

class General extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Component\Ebay */
    private $ebayHelper;
    /** @var \Ess\M2ePro\Model\Account */
    private $account;
    /** @var \Ess\M2ePro\Model\Ebay\Account\BuilderFactory */
    private $ebayAccountBuilderFactory;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Account\BuilderFactory $ebayAccountBuilderFactory,
        \Ess\M2ePro\Model\Account $account,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Component\Ebay $ebayHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        array $data = []
    ) {
        $this->ebayAccountBuilderFactory = $ebayAccountBuilderFactory;
        $this->account = $account;
        $this->supportHelper = $supportHelper;
        $this->dataHelper = $dataHelper;
        $this->ebayHelper = $ebayHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $formData = array_merge($this->account->getData(), $this->account->getChildObject()->getData());

        $defaults = $this->ebayAccountBuilderFactory->create()->getDefaultData();
        $formData = array_merge($defaults, $formData);

        $form = $this->_formFactory->create();

        $content = $this->__(
            <<<HTML
This Page shows the Environment for your eBay Account and details of the authorisation for M2E Pro to connect
to your eBay Account.<br/><br/>
If your token has expired or is not activated, click <b>Get Token</b>.<br/><br/>
More detailed information about ability to work with this Page you can find
<a href="%url%" target="_blank" class="external-link">here</a>.
HTML
            ,
            $this->supportHelper->getDocumentationArticleUrl('ebay-account-activation')
        );

        $form->addField(
            'ebay_accounts_general',
            self::HELP_BLOCK,
            [
                'content' => $content,
            ]
        );

        $fieldset = $form->addFieldset(
            'general',
            [
                'legend' => $this->__('General'),
                'collapsable' => false,
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
                'tooltip' => $this->__('Title or Identifier of eBay Account for your internal use.'),
            ]
        );

        $fieldset = $form->addFieldset(
            'access_detaails',
            [
                'legend' => $this->__('Access Details'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'ebay_user_id',
            'link',
            [
                'label' => $this->__('eBay User ID'),
                'value' => $formData['user_id'],
                'href' => $this->ebayHelper->getMemberUrl(
                    $formData['user_id'],
                    $formData['mode']
                ),
                'class' => 'control-value external-link',
                'target' => '_blank',
                'style' => 'text-decoration: underline;',
            ]
        );

        $mode = $formData['mode'] == \Ess\M2ePro\Model\Ebay\Account::MODE_PRODUCTION ? __('Production (Live)') : __('Sandbox (Test)');
        $fieldset->addField(
            'mode',
            'text',
            [
                'label' => $this->__('Environment'),
                'name' => 'mode',
                'value' => $mode,
                'disabled' => true,
            ]
        );

        $url = $this->getUrl(
            '*/ebay_account/beforeGetSellApiToken',
            ['id' => $this->account->getId(),
             'mode' => $this->account->getChildObject()->getMode()
            ]
        );

        $fieldset->addField(
            'grant_access_sell_api',
            'button',
            [
                'label' => $this->__('Grant Access'),
                'value' => $this->__('Get Token'),
                'class' => 'action-primary',
                'onclick' => 'setLocation(\'' . $url . '\');',
                'note' => $this->__(
                    'You need to finish the token process within 5 minutes.<br/>
                    If not, just click <b>Get Token</b> and try again.'
                ),
            ]
        );

        if ($formData['sell_api_token_expired_date'] != '') {
            $fieldset->addField(
                'expiration_date_sell_api',
                'label',
                [
                    'label' => $this->__('Expiration Date'),
                    'value' => $formData['sell_api_token_expired_date'],
                ]
            );
        }

        $this->setForm($form);

        $id = $this->getRequest()->getParam('id');
        $this->js->add("M2ePro.formData.id = '$id';");

        $this->js->add(
            <<<JS
    require([
        'M2ePro/Ebay/Account',
    ], function(){
        window.EbayAccountObj = new EbayAccount('{$id}');
        EbayAccountObj.initObservers();
    });
JS
        );

        return parent::_prepareForm();
    }
}
