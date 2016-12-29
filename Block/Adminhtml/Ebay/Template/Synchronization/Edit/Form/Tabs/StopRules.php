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
                    <a href="%url%" target="_blank" class="external-link">here</a>.',
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/QwItAQ')
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
            self::SELECT,
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
            self::SELECT,
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
            self::SELECT,
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
            self::SELECT,
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

        $fieldset = $form->addFieldset('magento_block_ebay_template_synchronization_stop_advanced_filters',
            [
                'legend' => $this->__('Advanced Conditions'),
                'collapsable' => false,
                'tooltip' => $this->__(
                    '<p>You can provide flexible Advanced Conditions to manage when the Stop action should
                    be run basing on the Attributes’ values of the Magento Product.<br> So, when at least
                    one of the Conditions (both general List Conditions and Advanced Conditions) is met,
                    the Product will be stopped on Channel.</p>'
                )
            ]
        );

        $fieldset->addField('stop_advanced_rules_filters_warning',
            self::MESSAGES,
            [
                'messages' => [[
                    'type' => \Magento\Framework\Message\MessageInterface::TYPE_WARNING,
                    'content' => $this->__(
                        'Please be very thoughtful before enabling this option as this functionality
                        can have a negative impact on the Performance of your system.<br> It can decrease the speed
                        of running in case you have a lot of Products with the high number of changes made to them.'
                    )
                ]]
            ]
        );

        $fieldset->addField('stop_advanced_rules_mode',
            self::SELECT,
            [
                'name' => 'synchronization[stop_advanced_rules_mode]',
                'label' => $this->__('Stop When Meet'),
                'value' => $formData['stop_advanced_rules_mode'],
                'values' => [
                    Synchronization::ADVANCED_RULES_MODE_NONE => $this->__('No'),
                    Synchronization::ADVANCED_RULES_MODE_YES  => $this->__('Yes'),
                ],
            ]
        );

        $ruleModel = $this->activeRecordFactory->getObject('Magento\Product\Rule')->setData(
            ['prefix' => Synchronization::STOP_ADVANCED_RULES_PREFIX]
        );

        if (!empty($formData['stop_advanced_rules_filters'])) {
            $ruleModel->loadFromSerialized($formData['stop_advanced_rules_filters']);
        }

        $ruleBlock = $this->createBlock('Magento\Product\Rule')->setData(['rule_model' => $ruleModel]);

        $fieldset->addField('advanced_filter',
            self::CUSTOM_CONTAINER,
            [
                'container_id' => 'stop_advanced_rules_filters_container',
                'label'        => $this->__('Conditions'),
                'text'         => $ruleBlock->toHtml(),
            ]
        );

        $this->setForm($form);

        return parent::_prepareForm();
    }
}