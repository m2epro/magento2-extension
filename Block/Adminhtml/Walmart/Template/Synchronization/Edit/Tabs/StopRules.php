<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\Synchronization\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Template\Synchronization as TemplateSynchronization;
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

        $defaults = $this->modelFactory->getObject('Walmart_Template_Synchronization_Builder')->getDefaultData();
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
                'legend'      => $this->__('General'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'stop_mode',
            self::SELECT,
            [
                'name'    => 'stop_mode',
                'label'   => $this->__('Stop Action'),
                'value'   => $formData['stop_mode'],
                'values'  => [
                    0 => $this->__('Disabled'),
                    1 => $this->__('Enabled'),
                ],
                'tooltip' => $this->__(
                    'Enable to automatically stop the Item(s) when the Stop Conditions are met.'
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_walmart_template_synchronization_stop_rules',
            [
                'legend'      => $this->__('Stop Conditions'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'stop_status_disabled',
            self::SELECT,
            [
                'name'    => 'stop_status_disabled',
                'label'   => $this->__('Stop When Status Disabled'),
                'value'   => $formData['stop_status_disabled'],
                'values'  => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
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
                'name'    => 'stop_out_off_stock',
                'label'   => $this->__('Stop When Out Of Stock'),
                'value'   => $formData['stop_out_off_stock'],
                'values'  => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically stops the Items on Walmart when their Magento Stock Availability
                    is changed to Out Of Stock.'
                )
            ]
        );

        $form->addField(
            'stop_qty_calculated_confirmation_popup_template',
            self::CUSTOM_CONTAINER,
            [
                'text'  => $this->__(
                    <<<HTML
Disabling this option might affect actual product data updates.
Please read <a href="%url%" target="_blank">this article</a> before disabling the option.
HTML
                    ,
                    $this->getHelper('Module_Support')->getKnowledgebaseUrl('1606824')
                ),
                'style' => 'display: none;'
            ]
        );

        $fieldset->addField(
            'stop_qty_calculated',
            self::SELECT,
            [
                'name'    => 'stop_qty_calculated',
                'label'   => $this->__('Stop When Quantity Is'),
                'value'   => $formData['stop_qty_calculated'],
                'values'  => [
                    TemplateSynchronization::QTY_MODE_NONE => $this->__('No Action'),
                    TemplateSynchronization::QTY_MODE_YES  => $this->__('Less or Equal'),
                ],
                'tooltip' => $this->__(
                    'Automatically stops the Items on Walmart when their Quantity calculated based on
                    the Selling Policy settings reaches the specified value. <br><br>

                    <strong>Note:</strong> This option will be ignored for Magento Variational Product
                    listed as Walmart Variant Group.'
                )
            ]
        )->setAfterElementHtml(
            <<<HTML
<input name="stop_qty_calculated_value" id="stop_qty_calculated_value"
       value="{$formData['stop_qty_calculated_value']}" type="text"
       style="width: 72px; margin-left: 10px;"
       class="input-text admin__control-text required-entry validate-digits _required" />
HTML
        );

        $fieldset = $form->addFieldset(
            'magento_block_walmart_template_synchronization_stop_advanced_filters',
            [
                'legend'      => $this->__('Advanced Conditions'),
                'collapsable' => false,
                'tooltip'     => $this->__(
                    '<p>Define Magento Attribute value(s) based on which a product must be stopped on the Channel.<br>
                    Once at least one Stop or Advanced Condition is met, the product will be stopped.</p>'
                )
            ]
        );

        $fieldset->addField(
            'stop_advanced_rules_filters_warning',
            self::MESSAGES,
            [
                'messages' => [
                    [
                        'type'    => \Magento\Framework\Message\MessageInterface::TYPE_WARNING,
                        'content' => $this->__(
                            'Please be very thoughtful before enabling this option as this functionality can have
                        a negative impact on the Performance of your system.<br> It can decrease the speed of running
                        in case you have a lot of Products with the high number of changes made to them.'
                        )
                    ]
                ]
            ]
        );

        $fieldset->addField(
            'stop_advanced_rules_mode',
            self::SELECT,
            [
                'name'   => 'stop_advanced_rules_mode',
                'label'  => $this->__('Stop When Meet'),
                'value'  => $formData['stop_advanced_rules_mode'],
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
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

            'stop_qty_calculated',
            'stop_qty_calculated_value',

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
