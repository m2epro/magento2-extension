<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\InstallationEbay\Installation\Account;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Wizard\InstallationEbay\Installation\Account\Content
 */
class Content extends AbstractForm
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('wizardInstallationWizardTutorial');
        // ---------------------------------------
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->getLayout()->getBlock('wizard.help.block')->setContent($this->__(<<<HTML
On this step, you should link your eBay Account with your M2E Pro.<br/><br/>
You can proceed with both Live and Sandbox eBay Environments. Live environment is set by default.
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
            'accounts',
            [
            ]
        );

        $url = 'https://scgi.ebay.com/ws/eBayISAPI.dll?RegisterEnterInfo&bizflow=2';
        $fieldset->addField(
            'message',
            self::MESSAGES,
            [
                'messages' => [
                    [
                        'type' => \Magento\Framework\Message\MessageInterface::TYPE_NOTICE,
                        'content' => $this->__(
                            'If you do not have an existing account, you can click
                            <a href="%url%" target="_blank" class="external-link">here</a> to register one.',
                            $url
                        )
                    ]
                ]
            ]
        );

        $fieldset->addField(
            'mode',
            'radios',
            [
                'label' => $this->__('What the Type of Account do You Want to Onboard?'),
                'css_class' => 'account-mode-choose',
                'name' => 'mode',
                'values' => [
                    [
                        'value' => 'production',
                        'label' => $this->__('Live Account')
                    ],
                    [
                        'value' => 'sandbox',
                        'label' => $this->__('Sandbox Account')
                    ],
                    [
                        'value' => '',
                        'label' => ''
                    ],
                ],
                'value' => 'production'
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _beforeToHtml()
    {
        $this->jsTranslator->add(
            'An error during of account creation.',
            $this->__('The eBay token obtaining is currently unavailable. Please try again later.')
        );

        return parent::_beforeToHtml();
    }

    //########################################
}
