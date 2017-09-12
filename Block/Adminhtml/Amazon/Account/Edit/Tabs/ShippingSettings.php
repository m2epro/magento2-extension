<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Amazon\Account;

class ShippingSettings extends AbstractForm
{
    //########################################

    protected function _prepareForm()
    {
        /** @var $account \Ess\M2ePro\Model\Account */
        $account = $this->getHelper('Data\GlobalData')->getValue('edit_account');
        $formData = !is_null($account) ? array_merge($account->getData(), $account->getChildObject()->getData()) : [];

        $defaults = array(
            'shipping_mode' => Account::SHIPPING_MODE_TEMPLATE
        );

        $formData = array_merge($defaults, $formData);

        $form = $this->_formFactory->create();

        $form->addField('amazon_accounts_shipping_settings_help_block',
            self::HELP_BLOCK,
            [
                'content' => $this->__(<<<HTML
                There are several modes available for working with Shipping settings on Amazon:<br/><br/>
                Global settings with the ability to override the configurations for the particular Product(s)
                (Shipping Override Policy);<br />
                Shipping Templates which allow identifying settings for the particular Product(s).<br/><br/>
                M2E Pro supports both of the Amazon Shipping configurations. So, on this page you should select
                the Mode which is enabled in your Amazon Account configurations.
                More detailed information about how to work with this Page you can find
                <a href="%url%" target="_blank">here</a>.
HTML
                    ,$this->getHelper('Module\Support')->getDocumentationArticleUrl("x/yQA9AQ")
                )
            ]
        );

        $fieldset = $form->addFieldset('amazon_accounts_shipping_settings_general',
            [
                'legend' => $this->__('General'),
                'collapsable' => false
            ]
        );

        $fieldset->addField('shipping_mode',
            'select',
            [
                'name' => 'shipping_mode',
                'label' => $this->__('Mode'),
                'values' => [
                    Account::SHIPPING_MODE_TEMPLATE => $this->__('Based on Templates'),
                    Account::SHIPPING_MODE_OVERRIDE => $this->__('Based on Overrides'),
                ],
                'value' => $formData['shipping_mode'],
                'tooltip' => $this->__(
                    'The Mode should be selected according to the configurations of your Amazon Account. Otherwise,
                    Amazon might return you errors.'
                )
            ]
        );

        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################
}