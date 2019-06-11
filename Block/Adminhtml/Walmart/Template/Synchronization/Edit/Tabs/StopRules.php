<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\Synchronization\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Walmart\Template\Synchronization;

class StopRules extends AbstractForm
{
    protected function _prepareForm()
    {
        $template = $this->getHelper('Data\GlobalData')->getValue('tmp_template');
        $formData = !is_null($template)
            ? array_merge($template->getData(), $template->getChildObject()->getData()) : [];

        $defaults = array(
            'stop_mode' => Synchronization::STOP_MODE_YES,

            'stop_status_disabled' => Synchronization::STOP_STATUS_DISABLED_YES,
            'stop_out_off_stock'   => Synchronization::STOP_OUT_OFF_STOCK_YES,

            'stop_qty_magento'           => Synchronization::STOP_QTY_NONE,
            'stop_qty_magento_value'     => '0',
            'stop_qty_magento_value_max' => '10',

            'stop_qty_calculated'           => Synchronization::STOP_QTY_NONE,
            'stop_qty_calculated_value'     => '0',
            'stop_qty_calculated_value_max' => '10'
        );
        $formData = array_merge($defaults, $formData);

        $form = $this->_formFactory->create();

        $form->addField(
            'walmart_template_synchronization_stop',
            self::HELP_BLOCK,
            [
                'content' => $this->__('
<p>Enable the Stop Action and define the Stop Conditions based on which M2E Pro will
automatically stop your Items on Walmart.</p>
<p>If at least one specified Condition is met, the Item(s) will be automatically stopped on Walmart.</p><br>
<p><strong>Note:</strong> If none of Stop Conditions is enabled, the Stop Action will
not be applied to your Item(s) on Walmart.</p>
<p>The detailed information can be found
    <a href="%url%" target="_blank" class="external-link">here</a>.</p>',
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/UABhAQ')
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

        $fieldset->addField('stop_mode',
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
                    'Automatically stops Item(s) if its status has been changed to \'Disabled\' in Magento.'
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

        $fieldset->addField('stop_status_disabled',
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
                    'Automatically stops Item(s) if its status has been changed to \'Disabled\' in Magento.'
                )
            ]
        );

        $fieldset->addField('stop_out_off_stock',
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
                    'Automatically stops Item(s) if its Stock availability has been changed to \'Out of Stock\'
                    in Magento.'
                )
            ]
        );

        $fieldset->addField('stop_qty_magento',
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
                    'Automatically stops Item(s) if Magento Quantity has been changed and meets the Conditions.'
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

        $fieldset->addField('stop_qty_calculated',
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
                    'Automatically stops Item(s) if Calculated Quantity according to the Selling
                    Policy has been changed and meets the Conditions.'
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

        $jsFormData = [
            'stop_mode',

            'stop_status_disabled',
            'stop_out_off_stock',

            'stop_qty_magento',
            'stop_qty_magento_value',
            'stop_qty_magento_value_max',

            'stop_qty_calculated',
            'stop_qty_calculated_value',
            'stop_qty_calculated_value_max'
        ];

        foreach ($jsFormData as $item) {
            $this->js->add("M2ePro.formData.$item = '{$this->getHelper('Data')->escapeJs($formData[$item])}';");
        }

        $this->setForm($form);

        return parent::_prepareForm();
    }
}