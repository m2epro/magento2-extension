<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  2011-2017 ESS-UA [M2E Pro]
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

class VatCalculationService extends AbstractForm
{
    //########################################

    protected function _prepareForm()
    {
        /** @var $account \Ess\M2ePro\Model\Account */
        $account = $this->getHelper('Data\GlobalData')->getValue('edit_account');
        $formData = !is_null($account) ? array_merge($account->getData(), $account->getChildObject()->getData()) : [];

        $defaults = array(
            'is_vat_calculation_service_enabled'   => 0,
            'is_magento_invoice_creation_disabled' => 0,
        );

        $formData = array_merge($defaults, $formData);
        $isEdit = !!$this->getRequest()->getParam('id');

        $form = $this->_formFactory->create();

        $form->addField(
            'amazon_accounts_vat_calculation_service',
            self::HELP_BLOCK,
            [
                'content' => $this->__(<<<HTML
'This section allows providing the settings for VAT Calculation Service.<br />
After you enable the <strong>VAT Calculation Service</strong> option,
an ability to disable Magento Invoice creation for your Channel Sales will become available.<br />
If the <strong>Disable Magento Invoice Creation</strong> option is set to <strong>Yes</strong>,
it will prevent the issue of duplicate invoices.<br /><br />
<strong>Note:</strong> You have to be enrolled in Amazon VAT Calculation Service.'
HTML
)
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
            'is_vat_calculation_service_enabled',
            'select',
            [
                'name' => 'is_vat_calculation_service_enabled',
                'label' => $this->__('VAT Calculation Service'),
                'values' => [
                    0 => $this->__('Disabled'),
                    1 => $this->__('Enabled'),
                ],
                'value' => $formData['is_vat_calculation_service_enabled'],
            ]
        );

        $fieldset->addField(
            'is_magento_invoice_creation_disabled',
            'select',
            [
                'container_id' => 'is_magento_invoice_creation_disabled_tr',
                'name' => 'is_magento_invoice_creation_disabled',
                'label' => $this->__('Disable Magento Invoice Creation'),
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value' => $formData['is_magento_invoice_creation_disabled'],
                'tooltip' => $this->__('
                    Set <strong>Yes</strong> to disable Magento Invoice creation for your Amazon Orders.<br />
                    It will prevent the issue of duplicate invoices when VAT Calculation Service is enabled
                    in your Seller Central Account.')
            ]
        );

        $this->setForm($form);
    }

    //########################################
}