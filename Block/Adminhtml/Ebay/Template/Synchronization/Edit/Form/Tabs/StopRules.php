<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Synchronization\Edit\Form\Tabs;

use Ess\M2ePro\Model\Ebay\Template\Synchronization;
use Ess\M2ePro\Model\Template\Synchronization as TemplateSynchronization;
use Magento\Framework\Message\MessageInterface;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Synchronization\Edit\Form\Tabs\StopRules
 */
class StopRules extends AbstractTab
{
    protected function _prepareForm()
    {
        $default = $this->modelFactory->getObject('Ebay_Template_Synchronization_Builder')->getDefaultData();
        $formData = $this->getFormData();

        $formData = array_merge($default, $formData);

        $form = $this->_formFactory->create();

        $form->addField(
            'ebay_template_synchronization_form_data_stop',
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

        $fieldset = $form->addFieldset(
            'magento_block_ebay_template_synchronization_form_data_stop_filters',
            [
                'legend'      => $this->__('General'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'stop_mode',
            self::SELECT,
            [
                'name'    => 'synchronization[stop_mode]',
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
            'magento_block_ebay_template_synchronization_stop_rules',
            [
                'legend'      => $this->__('Stop Conditions'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'stop_messages',
            self::MESSAGES,
            [
                'messages' => [
                    [
                        'type'    => MessageInterface::TYPE_NOTICE,
                        'content' => $this->__(
                            '
                            If <strong>Out of Stock</strong> Control option is enabled, the
                            <strong>Good Till Cancelled</strong> Items
                            will be <strong>Revised instead of  being Stopped</strong> based
                            on the Stop Conditions specifed below.
                            Their Status will be updated to <strong>Listed (Hidden)</strong>.
                        '
                        )
                    ],
                ]
            ]
        );

        $fieldset->addField(
            'stop_status_disabled',
            self::SELECT,
            [
                'name'    => 'synchronization[stop_status_disabled]',
                'label'   => $this->__('Stop When Status Disabled'),
                'value'   => $formData['stop_status_disabled'],
                'values'  => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically stops an Item that is on eBay if Status is changed to \'Disabled\' in Magento.'
                )
            ]
        );

        $fieldset->addField(
            'stop_out_off_stock',
            self::SELECT,
            [
                'name'    => 'synchronization[stop_out_off_stock]',
                'label'   => $this->__('Stop When Out Of Stock'),
                'value'   => $formData['stop_out_off_stock'],
                'values'  => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically stops an Item that is on eBay if Stock Availability is changed
                    to \'Out of Stock\' in Magento.'
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
                'name'    => 'synchronization[stop_qty_calculated]',
                'label'   => $this->__('Stop When Quantity Is'),
                'value'   => $formData['stop_qty_calculated'],
                'values'  => [
                    TemplateSynchronization::QTY_MODE_NONE => $this->__('No Action'),
                    TemplateSynchronization::QTY_MODE_YES  => $this->__('Less or Equal'),
                ],
                'tooltip' => $this->__(
                    'Automatically stops an Item on eBay if Quantity according to the
                     Selling Policy is changed <b>and</b> it meets the selected Conditions.'
                )
            ]
        )->setAfterElementHtml(
            <<<HTML
<input name="synchronization[stop_qty_calculated_value]" id="stop_qty_calculated_value"
       value="{$formData['stop_qty_calculated_value']}" type="text"
       style="width: 72px; margin-left: 10px;"
       class="input-text admin__control-text required-entry validate-digits _required" />
HTML
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_template_synchronization_stop_advanced_filters',
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
                            'Please be very thoughtful before enabling this option as this functionality
                        can have a negative impact on the Performance of your system.<br> It can decrease the speed
                        of running in case you have a lot of Products with the high number of changes made to them.'
                        )
                    ]
                ]
            ]
        );

        $fieldset->addField(
            'stop_advanced_rules_mode',
            self::SELECT,
            [
                'name'   => 'synchronization[stop_advanced_rules_mode]',
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

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
