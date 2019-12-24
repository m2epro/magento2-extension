<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\Synchronization\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Walmart\Template\Synchronization;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Synchronization\Edit\Tabs\StopRules
 */
class StopRules extends AbstractForm
{
    protected function _prepareForm()
    {
        $template = $this->getHelper('Data\GlobalData')->getValue('tmp_template');
        $formData = $template !== null
            ? array_merge($template->getData(), $template->getChildObject()->getData()) : [];

        $defaults = [
            'stop_mode' => Synchronization::STOP_MODE_YES,

            'stop_status_disabled' => Synchronization::STOP_STATUS_DISABLED_YES,
            'stop_out_off_stock'   => Synchronization::STOP_OUT_OFF_STOCK_YES,

            'stop_qty_magento'           => Synchronization::STOP_QTY_NONE,
            'stop_qty_magento_value'     => '0',
            'stop_qty_magento_value_max' => '10',

            'stop_qty_calculated'           => Synchronization::STOP_QTY_NONE,
            'stop_qty_calculated_value'     => '0',
            'stop_qty_calculated_value_max' => '10',

            'stop_advanced_rules_mode'    => Synchronization::ADVANCED_RULES_MODE_NONE,
            'stop_advanced_rules_filters' => null
        ];
        $formData = array_merge($defaults, $formData);

        $form = $this->_formFactory->create();

        $form->addField(
            'walmart_template_synchronization_stop',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    '
<p>Enable the Stop Action and define the Stop Conditions based on which M2E Pro will automatically stop
your Items on Walmart. If at least one specified Condition is met, the Items will be
automatically stopped on Walmart.</p><br>
<p><strong>Note:</strong> If none of Stop Conditions is enabled, the Stop Action will
not be applied to your Items on Walmart.</p><br>
<p><strong>Note:</strong> M2E Pro Listing Synchronization must be enabled under
<i>Walmart Integration > Configuration > Settings > Synchronization</i>. Otherwise, Synchronization
Rules will not take effect.</p>'
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_walmart_template_synchronization_stop_filters',
            [
                'legend' => $this->__('General'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'stop_mode',
            self::SELECT,
            [
                'name' => 'stop_mode',
                'label' => $this->__('Stop Action'),
                'value' => $formData['stop_mode'],
                'values' => [
                    Synchronization::STOP_MODE_NONE => $this->__('No'),
                    Synchronization::STOP_MODE_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Enable to automatically stop the Item(s) when the Stop Conditions are met.'
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_walmart_template_synchronization_stop_rules',
            [
                'legend' => $this->__('Stop Conditions'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'stop_status_disabled',
            self::SELECT,
            [
                'name' => 'stop_status_disabled',
                'label' => $this->__('Stop When Status Disabled'),
                'value' => $formData['stop_status_disabled'],
                'values' => [
                    Synchronization::STOP_STATUS_DISABLED_NONE => $this->__('No'),
                    Synchronization::STOP_STATUS_DISABLED_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically stops the Items on Walmart when their Magento status is changed to Disabled.'
                )
            ]
        );

        $fieldset->addField(
            'stop_out_off_stock',
            self::SELECT,
            [
                'name' => 'stop_out_off_stock',
                'label' => $this->__('Stop When Out Of Stock'),
                'value' => $formData['stop_out_off_stock'],
                'values' => [
                    Synchronization::STOP_OUT_OFF_STOCK_NONE => $this->__('No'),
                    Synchronization::STOP_OUT_OFF_STOCK_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically stops the Items on Walmart when their Magento Stock Availability
                    is changed to Out Of Stock.'
                )
            ]
        );

        $fieldset->addField(
            'stop_qty_magento',
            self::SELECT,
            [
                'name' => 'stop_qty_magento',
                'label' => $this->__('Stop When Magento Quantity Is'),
                'value' => $formData['stop_qty_magento'],
                'values' => [
                    Synchronization::STOP_QTY_NONE => $this->__('No Action'),
                    Synchronization::STOP_QTY_LESS => $this->__('Less or Equal'),
                    Synchronization::STOP_QTY_BETWEEN => $this->__('Between'),
                ],
                'tooltip' => $this->__(
                    'Automatically stops the Items on Walmart when their Magento Quantity reaches the specified value.'
                )
            ]
        )->addCustomAttribute('qty_type', 'magento');

        $fieldset->addField(
            'stop_qty_magento_value',
            'text',
            [
                'container_id' => 'stop_qty_magento_value_container',
                'name' => 'stop_qty_magento_value',
                'label' => $this->__('Quantity'),
                'value' => $formData['stop_qty_magento_value'],
                'class' => 'validate-digits',
                'required' => true
            ]
        );

        $fieldset->addField(
            'stop_qty_magento_value_max',
            'text',
            [
                'container_id' => 'stop_qty_magento_value_max_container',
                'name' => 'stop_qty_magento_value_max',
                'label' => $this->__('Max Quantity'),
                'value' => $formData['stop_qty_magento_value_max'],
                'class' => 'validate-digits M2ePro-validate-conditions-between',
                'required' => true
            ]
        );

        $fieldset->addField(
            'stop_qty_calculated',
            self::SELECT,
            [
                'name' => 'stop_qty_calculated',
                'label' => $this->__('Stop When Calculated Quantity Is'),
                'value' => $formData['stop_qty_calculated'],
                'values' => [
                    Synchronization::STOP_QTY_NONE => $this->__('No Action'),
                    Synchronization::STOP_QTY_LESS => $this->__('Less or Equal'),
                    Synchronization::STOP_QTY_BETWEEN => $this->__('Between'),
                ],
                'tooltip' => $this->__(
                    'Automatically stops the Items on Walmart when their Quantity calculated based on
                    the Selling Policy settings reaches the specified value. <br><br>

                    <strong>Note:</strong> This option will be ignored for Magento Variational Product
                    listed as Walmart Variant Group.'
                )
            ]
        )->addCustomAttribute('qty_type', 'calculated');

        $fieldset->addField(
            'stop_qty_calculated_value',
            'text',
            [
                'container_id' => 'stop_qty_calculated_value_container',
                'name' => 'stop_qty_calculated_value',
                'label' => $this->__('Quantity'),
                'value' => $formData['stop_qty_calculated_value'],
                'class' => 'validate-digits',
                'required' => true
            ]
        );

        $fieldset->addField(
            'stop_qty_calculated_value_max',
            'text',
            [
                'container_id' => 'stop_qty_calculated_value_max_container',
                'name' => 'stop_qty_calculated_value_max',
                'label' => $this->__('Max Quantity'),
                'value' => $formData['stop_qty_calculated_value_max'],
                'class' => 'validate-digits M2ePro-validate-conditions-between',
                'required' => true
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_walmart_template_synchronization_stop_advanced_filters',
            [
                'legend' => $this->__('Advanced Conditions'),
                'collapsable' => false,
                'tooltip' => $this->__(
                    '<p>Define Magento Attribute value(s) based on which a product must be stopped on the Channel.<br>
                    Once at least one Stop or Advanced Condition is met, the product will be stopped.</p>'
                )
            ]
        );

        $fieldset->addField(
            'stop_advanced_rules_filters_warning',
            self::MESSAGES,
            [
                'messages' => [[
                    'type' => \Magento\Framework\Message\MessageInterface::TYPE_WARNING,
                    'content' => $this->__(
                        'Please be very thoughtful before enabling this option as this functionality can have
                        a negative impact on the Performance of your system.<br> It can decrease the speed of running
                        in case you have a lot of Products with the high number of changes made to them.'
                    )
                ]]
            ]
        );

        $fieldset->addField(
            'stop_advanced_rules_mode',
            self::SELECT,
            [
                'name' => 'stop_advanced_rules_mode',
                'label' => $this->__('Stop When Meet'),
                'value' => $formData['stop_advanced_rules_mode'],
                'values' => [
                    Synchronization::ADVANCED_RULES_MODE_NONE => $this->__('No'),
                    Synchronization::ADVANCED_RULES_MODE_YES  => $this->__('Yes'),
                ],
            ]
        );

        $ruleModel = $this->activeRecordFactory->getObject('Magento_Product_Rule')->setData(
            ['prefix' => Synchronization::STOP_ADVANCED_RULES_PREFIX]
        );

        if (!empty($formData['stop_advanced_rules_filters'])) {
            $ruleModel->loadFromSerialized($formData['stop_advanced_rules_filters']);
        }

        $ruleBlock = $this->createBlock('Magento_Product_Rule')->setData(['rule_model' => $ruleModel]);

        $fieldset->addField(
            'advanced_filter',
            self::CUSTOM_CONTAINER,
            [
                'container_id' => 'stop_advanced_rules_filters_container',
                'label'        => $this->__('Conditions'),
                'text'         => $ruleBlock->toHtml(),
            ]
        );

        $jsFormData = [
            'stop_mode',

            'stop_status_disabled',
            'stop_out_off_stock',

            'stop_qty_magento',
            'stop_qty_magento_value',
            'stop_qty_magento_value_max',

            'stop_qty_calculated',
            'stop_qty_calculated_value',
            'stop_qty_calculated_value_max',

            'stop_advanced_rules_mode',
            'stop_advanced_rules_filters',
        ];

        foreach ($jsFormData as $item) {
            $this->js->add("M2ePro.formData.$item = '{$this->getHelper('Data')->escapeJs($formData[$item])}';");
        }

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
