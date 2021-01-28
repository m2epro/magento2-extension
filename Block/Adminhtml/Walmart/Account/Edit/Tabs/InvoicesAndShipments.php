<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Account\Edit\Tabs\InvoicesAndShipments
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
                    'Enable to automatically create Shipment when shipping is completed.'
                )
            ]
        );

        $otherCarriers = empty($formData['other_carriers']) ? [] : $this->getHelper('Data')->jsonDecode(
            $formData['other_carriers']
        );
        for ($i = 0; $i < 5; $i++) {
            $code = $url = '';

            if (!empty($otherCarriers[$i])) {
                $code = $this->getHelper('Data')->escapeHtml($otherCarriers[$i]['code']);
                $url = $this->getHelper('Data')->escapeHtml($otherCarriers[$i]['url']);
            }

            $fieldset->addField('other_carrier_field_' . $i . '_separator', self::SEPARATOR, []);

            $fieldset->addField(
                'other_carrier_field_' . $i,
                self::CUSTOM_CONTAINER,
                [
                    'container_class'    => 'other_carrier',
                    'label'              => $this->__('Other Carrier #%number%:', $i + 1),
                    'title'              => $this->__('Other Carrier #%number%:', $i + 1),
                    'style'              => 'vertical-align: unset;',
                    'text'               => <<<HTML
<input id="other_carrier_{$i}"
       type="text"
       name="other_carrier[]"
       value="{$code}"
       style="width: 127.5px;"
       class="input-text"
       onkeyup="window.WalmartAccountObj.otherCarrierKeyup(this)"
       placeholder="{$this->__('Title')}"
/>
HTML
                    ,
                    'after_element_html' => <<<HTML
<input id="other_carrier_url_{$i}"
       type="text"
       name="other_carrier_url[]"
       value="{$url}"
       style="width: 127.5px; margin-left: 10px;"
       class="input-text"
       onkeyup="window.WalmartAccountObj.otherCarrierUrlKeyup(this)"
       placeholder="{$this->__('URL')}"
/>
HTML
                    ,
                    'tooltip'            => $this->__(
                        <<<TEXT
If you use Other Carrier option on Walmart,
enter a carrier code (unique identifier) and their website URL,
so that your buyers could track shipments.
TEXT
                    )
                ]
            );
        }

        $fieldset->addField(
            'other_carrier_actions',
            self::CUSTOM_CONTAINER,
            [
                'text' => <<<HTML
<a id="show_other_carrier_action"
   href="javascript: void(0);"
   onclick="window.WalmartAccountObj.showElement(this);">
   {$this->__('Add New')}
</a>
&nbsp;/&nbsp;
<a id="hide_other_carrier_action"
   href="javascript: void(0);"
   onclick="window.WalmartAccountObj.hideElement(this);">
   {$this->__('Remove')}
</a>
HTML
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
        $this->css->add(
            <<<CSS
#other_carrier_actions a.action-disabled {
    color: gray;
    pointer-events: none;
    text-decoration: none;
}

#other_carrier_actions a.action-disabled:hover {
    color: gray !important;
    pointer-events: none; !important;
    text-decoration: none !important;
}
CSS
        );

        $this->js->add(
            <<<JS
    require([
        'M2ePro/Walmart/Account',
    ], function() {
        WalmartAccountObj.otherCarrierInit(5);
    });
JS
            ,
            2
        );

        return parent::_prepareLayout();
    }

    //########################################

    protected function getFormData()
    {
        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->getHelper('Data\GlobalData')->getValue('edit_account');

        $formData = $account ? array_merge($account->getData(), $account->getChildObject()->getData()) : [];
        $defaults = $this->modelFactory->getObject('Walmart_Account_Builder')->getDefaultData();

        return array_merge($defaults, $formData);
    }

    //########################################
}
