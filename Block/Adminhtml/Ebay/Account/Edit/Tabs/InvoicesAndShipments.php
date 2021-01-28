<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

/**
 * Class  \Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs\InvoicesAndShipments
 */
class InvoicesAndShipments extends AbstractForm
{
    //########################################

    protected function _prepareForm()
    {
        $formData = $this->getFormData();

        $form = $this->_formFactory->create();

        $form->addField(
            'invoices_and_shipments',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    <<<HTML
    <p>Under this tab, you can set M2E Pro to automatically create invoices and shipments in your Magento.
     To do that, keep Magento <i>Invoice/Shipment Creation</i> options enabled.</p>
HTML
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'invoices',
            [
                'legend'      => $this->__('Invoices'),
                'collapsable' => false
            ]
        );

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
                    'Enable to automatically create Magento Invoices when payment is completed.'
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
                    'Enable to automatically create Shipment when shipping is completed.'
                )
            ]
        );

        $fieldset->addField(
            'skip_evtin',
            'select',
            [
                'label'   => $this->__('Skip eVTN'),
                'title'   => $this->__('Skip eVTN'),
                'name'    => 'skip_evtin',
                'options' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    <<<HTML
Set <b>Yes</b> if you want to exclude 
<a href="%url%" target="_blank">eVTN</a> from your Magento orders.
HTML
                    ,
                    $this->getHelper('Module\Support')->getKnowledgebaseUrl('1608273')
                )
            ]
        );

        $form->setValues($formData);

        $form->setUseContainer(false);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################

    protected function getFormData()
    {
        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->getHelper('Data\GlobalData')->getValue('edit_account');

        $formData = $account ? array_merge($account->getData(), $account->getChildObject()->getData()) : [];
        $defaults = $this->modelFactory->getObject('Ebay_Account_Builder')->getDefaultData();

        return array_merge($defaults, $formData);
    }

    //########################################
}
