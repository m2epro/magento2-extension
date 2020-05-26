<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs\VCSUploadInvoices
 */
class VCSUploadInvoices extends AbstractForm
{
    //########################################

    protected function _prepareForm()
    {
        /** @var $account \Ess\M2ePro\Model\Account */
        $account = $this->getHelper('Data\GlobalData')->getValue('edit_account');
        $formData = $account !== null ? array_merge($account->getData(), $account->getChildObject()->getData()) : [];

        $defaults = [
            'auto_invoicing' => 0,
            'is_magento_invoice_creation_disabled' => 0,
        ];

        $formData = array_merge($defaults, $formData);

        $form = $this->_formFactory->create();

        $form->addField(
            'amazon_accounts_vat_calculation_service',
            self::HELP_BLOCK,
            [
                'content' => $this->__(<<<HTML
'<strong>Upload Magento Invoices</strong> - M2E Pro will automatically send Magento Invoices/Credit Memos to 
Amazon once they are created in Magento Order.<br /><br/>
<strong>Use VAT Calculation Service</strong> - Amazon will automatically generate and send Invoices to the buyers. 
Switch <i>Disable Magento Invoice Creation</i> option to “Yes” to prevent Invoice duplicates.<br/><br/>
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

        $options = [
            \Ess\M2ePro\Model\Amazon\Account::MAGENTO_ORDERS_AUTO_INVOICING_DISABLED => $this->__('Disabled')
        ];
        if ($account->getChildObject()->getMarketplace()->getChildObject()->isUploadInvoicesAvailable()) {
            $options[\Ess\M2ePro\Model\Amazon\Account::MAGENTO_ORDERS_AUTO_INVOICING_UPLOAD_MAGENTO_INVOICES] =
                $this->__('Upload Magento Invoices');
        }
        $options[\Ess\M2ePro\Model\Amazon\Account::MAGENTO_ORDERS_AUTO_INVOICING_VAT_CALCULATION_SERVICE] =
            $this->__('Use VAT Calculation Service');

        $fieldset->addField(
            'auto_invoicing',
            'select',
            [
                'name' => 'auto_invoicing',
                'label' => $this->__('Auto-Invoicing'),
                'values' => $options,
                'value' => $formData['auto_invoicing'],
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
