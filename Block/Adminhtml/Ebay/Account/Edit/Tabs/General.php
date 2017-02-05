<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Ebay\Account;

class General extends AbstractForm
{
    protected function _prepareForm()
    {
        $account = $this->getHelper('Data\GlobalData')->getValue('edit_account');
        $formData = !is_null($account) ? array_merge($account->getData(), $account->getChildObject()->getData()) : [];

        $ebayUserId = null;
        if (empty($formData['user_id']) && isset($formData['info']) &&
            $ebayInfo = $this->getHelper('Data')->jsonDecode($formData['info'])
        ) {
            !empty($ebayInfo['UserID']) && $formData['user_id'] = (string)$ebayInfo['UserID'];
        }

        $temp = $this->getHelper('Data\Session')->getValue('get_token_account_title', true);
        !is_null($temp) && $formData['title'] = $temp;

        $temp = $this->getHelper('Data\Session')->getValue('get_token_account_mode', true);
        !is_null($temp) && $formData['mode'] = $temp;

        $temp = $this->getHelper('Data\Session')->getValue('get_token_account_token_session', true);
        !is_null($temp) && $formData['token_session'] = $temp;

        $defaults = array(
            'title' => '',
            'user_id' => '',
            'mode' => Account::MODE_PRODUCTION,
            'token_session' => '',
            'token_expired_date' => '',
            'other_listings_synchronization' => Account::OTHER_LISTINGS_SYNCHRONIZATION_YES
        );
        $formData = array_merge($defaults, $formData);

        $isEdit = !!$this->getRequest()->getParam('id');

        $form = $this->_formFactory->create();

        if (!$isEdit) {
            $content = $this->__(<<<HTML
Add an eBay Account to M2E Pro by choosing the eBay Environment and granting access to your eBay Account.<br/><br/>
First choose the <b>Environment</b> of the eBay Account you want to work in.
If you want to add an eBay Account to list for real on Marketplaces,
choose <b>Production (Live)</b>. If you want to add an eBay Sandbox Account that\'s been set up for
test or development purposes,
choose <b>Sandbox (Test)</b>. Then click <b>Get Token</b> to sign in to eBay and
<b>Agree</b> to allow your eBay Account to connect to M2E Pro.<br/><br/>
Once you\'ve successfully authorised M2E Pro to access your Account, the <b>Activated</b>
status will change to \'Yes\' and you can click <b>Save and Continue Edit</b>.<br/><br/>
<b>Note:</b> A Production (Live) eBay Account only works on a live Marketplace.
A Sandbox (Test) Account only works on the eBay Sandbox test Environment.
To register for a Sandbox Account, register at
<a href="https://developer.ebay.com/join/" target="_blank" class="external-link">developer.ebay.com/join</a>.
HTML
            );
        } else {
            $content = $this->__(<<<HTML
This Page shows the Environment for your eBay Account and details of the authorisation for M2E Pro to connect
to your eBay Account.<br/><br/>
If your token has expired or is not activated, click <b>Get Token</b>.<br/><br/>
More detailed information about ability to work with this Page you can find
<a href="%url%" target="_blank" class="external-link">here</a>.
HTML
                , $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/KgItAQ'));
        }

        $form->addField(
            'ebay_accounts_general',
            self::HELP_BLOCK,
            [
                'content' => $content
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
                'tooltip' => $this->__('Title or Identifier of Ebay Account for your internal use.')
            ]
        );

        $fieldset = $form->addFieldset(
            'access_detaails',
            [
                'legend' => $this->__('Access Details'),
                'collapsable' => false
            ]
        );

        if ($isEdit) {
            if (!empty($formData['user_id'])) {
                $fieldset->addField(
                    'ebay_user_id',
                    'link',
                    [
                        'label' => $this->__('eBay User ID'),
                        'value' => $formData['user_id'],
                        'href' => $this->getHelper('Component\Ebay')->getMemberUrl(
                            $formData['user_id'], $formData['mode']
                        ),
                        'class' => 'control-value external-link',
                        'target' => '_blank',
                        'style' => 'text-decoration: underline;'
                    ]
                );
            } else {
                $fieldset->addField(
                    'ebay_user_id',
                    'label',
                    [
                        'label' => $this->__('eBay User ID'),
                        'value' => $formData['title']
                    ]
                );
            }
        }

        $fieldset->addField(
            'mode',
            'select',
            [
                'label' => $this->__('Environment'),
                'name' => 'mode',
                'values' => [
                    Account::MODE_PRODUCTION => $this->__('Production (Live)'),
                    Account::MODE_SANDBOX => $this->__('Sandbox (Test)'),
                ],
                'value' => $formData['mode'],
                'disabled' => $formData['token_session'] != '',
                'tooltip' => !$isEdit ? $this->__(
                    'Choose \'Production (Live)\' to use an eBay Account to list for real on Marketplaces.
                    <br/>Choose \'Sandbox (Test)\' to use an eBay Sandbox Account for testing purposes.')
                        : $this->__('<b>Production (Live):</b> an eBay Account Listing for real on Marketplaces.
                                    <br/><b>Sandbox (Test):</b> an eBay Sandbox Account for testing purposes.')
            ]
        );

        if ($formData['token_session'] != '') {
            $fieldset->addField(
                'mode_hidden',
                'hidden',
                [
                    'name' => 'mode',
                    'value' => $formData['mode']
                ]
            );
        }

        $fieldset->addField(
            'grant_access',
            'button',
            [
                'label' => $this->__('Grant Access'),
                'value' => $this->__('Get Token'),
                'class' => 'action-primary',
                'onclick' => 'EbayAccountObj.get_token();',
                'note' => $this->__(
                    'You need to finish the token process within 5 minutes.<br/>
                    If not, just click <b>Get Token</b> and try again.'
                )
            ]
        );

        $fieldset->addField(
            'activated',
            'label',
            [
                'label' => $this->__('Activated'),
                'value' =>  $formData['token_session'] != '' ? $this->__('Yes') : $this->__('No'),
                'css_class' =>  !$formData['token_session'] ||
                                !$formData['token_expired_date'] ? 'no-margin-bottom' : ''
            ]
        );

        if ($formData['token_session'] != '' && $formData['token_expired_date'] != '') {
            $fieldset->addField(
                'expiration_date',
                'label',
                [
                    'label' => $this->__('Expiration Date'),
                    'value' => $formData['token_expired_date']
                ]
            );
        }

        $fieldset->addField(
            'token_expired_date',
            'hidden',
            [
                'name' => 'token_expired_date',
                'value' => $formData['token_expired_date']
            ]
        );

        $fieldset->addField(
            'token_session',
            'text',
            [
                'label' => '',
                'name' => 'token_session',
                'value' => $formData['token_session'],
                'class' => 'M2ePro-account-token-session',
                'style' => 'visibility: hidden'
            ]
        );
        $this->css->add('label.mage-error[for="token_session"] { top: 0 !important; }');

        $this->setForm($form);

        $this->js->add("M2ePro.formData.mode = '" . $this->getHelper('Data')->escapeJs($formData['mode']) . "';");
        $this->js->add(
            "M2ePro.formData.token_session
             = '" . $this->getHelper('Data')->escapeJs($formData['token_session']) . "';"
        );
        $this->js->add(
            "M2ePro.formData.token_expired_date
            = '" . $this->getHelper('Data')->escapeJs($formData['token_expired_date']) . "';"
        );

        $id = $this->getRequest()->getParam('id');
        $this->js->add("M2ePro.formData.id = '$id';");

        $this->js->add( <<<JS
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