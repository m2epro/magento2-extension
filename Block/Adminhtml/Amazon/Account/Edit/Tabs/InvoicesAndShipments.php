<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use \Ess\M2ePro\Model\Amazon\Account as AmazonAccount;

/**
 * Class Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs\InvoicesAndShipments
 */
class InvoicesAndShipments extends AbstractForm
{
    //########################################

    protected function _prepareForm()
    {
        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->getHelper('Data\GlobalData')->getValue('edit_account');

        $formData = $this->getFormData();

        $form = $this->_formFactory->create();

        $helpText = $this->__(
            <<<HTML
    <p>Under this tab, you can enable Magento <i>Invoice/Shipment Creation</i> if you want M2E Pro to automatically
    create invoices and shipments in your Magento.</p>
HTML
        );

        if ($account->getChildObject()->getMarketplace()->getChildObject()->isVatCalculationServiceAvailable()) {
            $helpText .= $this->__(
                <<<HTML
    <p>Also, you can set up an <i>Automatic Invoice Uploading</i> to Amazon. Read the <a href="%url%"
    target="_blank">article</a> for more details.</p>
HTML
                ,
                $this->getHelper('Module_Support')->getHowToGuideUrl('1586245')
            );
        }

        $form->addField(
            'invoices_and_shipments',
            self::HELP_BLOCK,
            [
                'content' => $helpText
            ]
        );

        $fieldset = $form->addFieldset(
            'invoices',
            [
                'legend'      => $this->__('Invoices'),
                'collapsable' => false
            ]
        );

        if ($account->getChildObject()->getMarketplace()->getChildObject()->isVatCalculationServiceAvailable()) {
            $fieldset->addField(
                'auto_invoicing',
                'select',
                [
                    'label'   => $this->__('Invoice Uploading to Amazon'),
                    'title'   => $this->__('Invoice Uploading to Amazon'),
                    'name'    => 'auto_invoicing',
                    'options' => [
                        AmazonAccount::AUTO_INVOICING_DISABLED                => $this->__('Disabled'),
                        AmazonAccount::AUTO_INVOICING_UPLOAD_MAGENTO_INVOICES =>
                            $this->__('Upload Magento Invoices'),
                        AmazonAccount::AUTO_INVOICING_VAT_CALCULATION_SERVICE =>
                            $this->__('Use VAT Calculation Service')
                    ],
                    'value'   => $formData['auto_invoicing']
                ]
            );

            $fieldset->addField(
                'invoice_generation',
                'select',
                [
                    'container_id' => 'invoice_generation_container',
                    'label'        => $this->__('VAT Invoice Creation'),
                    'title'        => $this->__('VAT Invoice Creation'),
                    'name'         => 'invoice_generation',
                    'class'        => 'M2ePro-required-when-visible M2ePro-is-ready-for-document-generation',
                    'required'     => true,
                    'values'       => [
                        ''                                             => '',
                        AmazonAccount::INVOICE_GENERATION_BY_AMAZON    =>
                            $this->__('I want Amazon to generate VAT Invoices'),
                        AmazonAccount::INVOICE_GENERATION_BY_EXTENSION =>
                            $this->__('I will upload my own Invoices'),
                    ],
                    'value'        => ''
                ]
            );

            $fieldset->addField(
                'invoicing_applied_value_line_tr',
                self::SEPARATOR,
                []
            );
        }

        $fieldset->addField(
            'create_magento_invoice',
            'select',
            [
                'label'   => $this->__('Magento Invoice Creation'),
                'title'   => $this->__('Magento Invoice Creation'),
                'name'    => 'create_magento_invoice',
                'options' => [
                    0 => $this->__('Disabled'),
                    1 => $this->__('Enabled'),
                ],
                'tooltip' => $this->__(
                    'Enable to automatically create Magento Invoices when order status is Unshipped/Partially Shipped.'
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'shipments',
            [
                'legend'      => $this->__('Shipments'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'create_magento_shipment',
            'select',
            [
                'label'   => $this->__('Magento Shipment Creation'),
                'title'   => $this->__('Magento Shipment Creation'),
                'name'    => 'create_magento_shipment',
                'options' => [
                    0 => $this->__('Disabled'),
                    1 => $this->__('Enabled'),
                ],
                'tooltip' => $this->__(
                    'Enable to automatically create shipment for the Magento order when the associated order
                    on Channel is shipped.'
                )
            ]
        );

        $form->setValues($formData);

        $form->setUseContainer(false);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################

    protected function _prepareLayout()
    {
        $formData = $this->getFormData();

        $this->js->add(
            <<<JS
    require([
        'M2ePro/Amazon/Account',
    ], function(){
        $('create_magento_invoice').value = {$formData['create_magento_invoice']};
    });
JS
        );

        return parent::_prepareLayout();
    }

    //########################################

    protected function getFormData()
    {
        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->getHelper('Data\GlobalData')->getValue('edit_account');

        $formData = $account ? array_merge($account->toArray(), $account->getChildObject()->toArray()) : [];
        $defaults = $this->modelFactory->getObject('Amazon_Account_Builder')->getDefaultData();

        return array_merge($defaults, $formData);
    }

    //########################################
}
