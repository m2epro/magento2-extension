<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Synchronization\Edit\Form\Tabs;

use Ess\M2ePro\Model\Ebay\Template\Synchronization;
use Magento\Framework\Message\MessageInterface;

class StopRules extends AbstractTab
{
    protected function _prepareForm()
    {
        $default = $this->activeRecordFactory->getObject('Ebay\Template\Synchronization')->getStopDefaultSettings();
        $formData = $this->getFormData();

        $formData = array_merge($default, $formData);

        $form = $this->_formFactory->create();

        $form->addField('ebay_template_synchronization_form_data_stop',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    'Set the Conditions when M2E Pro should stop Listings on eBay.<br/><br/>
                    If all Conditions are set to No or No Action then no eBay Items using this Synchronization
                    Policy will be Stopped.
                    If all Options are enabled, then an Item will be Stopped if at least one of the Stop
                    Conditions is met.<br/><br/>
                    More detailed information about ability to work with this Page you can find
                    <a href="%url%" target="_blank">here</a>.',
                    $this->getHelper('Module\Support')->getDocumentationUrl(NULL, NULL, 'x/QwItAQ')
                )
            ]
        );

        $fieldset = $form->addFieldset('magento_block_ebay_template_synchronization_form_data_stop_rules',
            [
                'legend' => $this->__('Stop Conditions'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField('stop_messages',
            self::MESSAGES,
            [
                'messages' => [
                    [
                        'type' => MessageInterface::TYPE_NOTICE,
                        'content' => $this->__('
                            If <strong>Out of Stock</strong> Control option is enabled, the
                            <strong>Good Till Cancelled</strong> Items
                            will be <strong>Revised instead of  being Stopped</strong> based
                            on the Stop Conditions specifed below.
                            Their Status will be updated to <strong>Listed (Hidden)</strong>.
                        ')
                    ],
                ]
            ]
        );

        $fieldset->addField('stop_status_disabled',
            'select',
            [
                'name' => 'synchronization[stop_status_disabled]',
                'label' => $this->__('Stop When Status Disabled'),
                'value' => $formData['stop_status_disabled'],
                'values' => [
                    Synchronization::STOP_STATUS_DISABLED_NONE => $this->__('No'),
                    Synchronization::STOP_STATUS_DISABLED_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically stops an Item that is on eBay if Status is changed to \'Disabled\' in Magento.'
                )
            ]
        );

        $fieldset->addField('stop_out_off_stock',
            'select',
            [
                'name' => 'synchronization[stop_out_off_stock]',
                'label' => $this->__('Stop When Out Of Stock'),
                'value' => $formData['stop_out_off_stock'],
                'values' => [
                    Synchronization::STOP_OUT_OFF_STOCK_NONE => $this->__('No'),
                    Synchronization::STOP_OUT_OFF_STOCK_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically stops an Item that is on eBay if Stock Availability is changed
                    to \'Out of Stock\' in Magento.'
                )
            ]
        );

        $fieldset->addField('stop_qty_magento',
            'select',
            [
                'name' => 'synchronization[stop_qty_magento]',
                'label' => $this->__('Stop When Magento Quantity Is'),
                'value' => $formData['stop_qty_magento'],
                'values' => [
                    Synchronization::STOP_QTY_NONE => $this->__('No Action'),
                    Synchronization::STOP_QTY_LESS => $this->__('Less or Equal'),
                    Synchronization::STOP_QTY_BETWEEN => $this->__('Between'),
                ],
                'tooltip' => $this->__(
                    'Automatically stops an Item on eBay if Magento Quantity is changed <b>and</b> it
                     meets the selected Conditions.'
                )
            ]
        )->addCustomAttribute('qty_type', 'magento');

        $fieldset->addField('stop_qty_magento_value',
            'text',
            [
                'container_id' => 'stop_qty_magento_value_container',
                'name' => 'synchronization[stop_qty_magento_value]',
                'label' => $this->__('Quantity'),
                'value' => $formData['stop_qty_magento_value'],
                'class' => 'validate-digits',
                'required' => true
            ]
        );

        $fieldset->addField('stop_qty_magento_value_max',
            'text',
            [
                'container_id' => 'stop_qty_magento_value_max_container',
                'name' => 'synchronization[stop_qty_magento_value_max]',
                'label' => $this->__('Max Quantity'),
                'value' => $formData['stop_qty_magento_value_max'],
                'class' => 'validate-digits M2ePro-validate-conditions-between',
                'required' => true
            ]
        );

        $fieldset->addField('stop_qty_calculated',
            'select',
            [
                'name' => 'synchronization[stop_qty_calculated]',
                'label' => $this->__('Stop When Calculated Quantity Is'),
                'value' => $formData['stop_qty_calculated'],
                'values' => [
                    Synchronization::STOP_QTY_NONE => $this->__('No Action'),
                    Synchronization::STOP_QTY_LESS => $this->__('Less or Equal'),
                    Synchronization::STOP_QTY_BETWEEN => $this->__('Between'),
                ],
                'tooltip' => $this->__(
                    'Automatically stops an Item on eBay if calculated Quantity according to the Price,
                     Quantity and Format Policy is changed <b>and</b> it meets the selected Conditions.'
                )
            ]
        )->addCustomAttribute('qty_type', 'calculated');

        $fieldset->addField('stop_qty_calculated_value',
            'text',
            [
                'container_id' => 'stop_qty_calculated_value_container',
                'name' => 'synchronization[stop_qty_calculated_value]',
                'label' => $this->__('Quantity'),
                'value' => $formData['stop_qty_calculated_value'],
                'class' => 'validate-digits',
                'required' => true
            ]
        );

        $fieldset->addField('stop_qty_calculated_value_max',
            'text',
            [
                'container_id' => 'stop_qty_calculated_value_max_container',
                'name' => 'synchronization[stop_qty_calculated_value_max]',
                'label' => $this->__('Max Quantity'),
                'value' => $formData['stop_qty_calculated_value_max'],
                'class' => 'validate-digits M2ePro-validate-conditions-between',
                'required' => true
            ]
        );

        $this->setForm($form);

        return parent::_prepareForm();
    }
}