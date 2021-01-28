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
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Synchronization\Edit\Tabs\RelistRules
 */
class RelistRules extends AbstractForm
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
            'walmart_template_synchronization_relist',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    <<<HTML
                    <p>Enable the Relist Action and define the Relist Conditions based on which M2E Pro
                    will automatically relist your Items on Walmart.</p><br>
                    <p><strong>Note:</strong> M2E Pro Listing Synchronization must be enabled under
                    <strong>Walmart Integration > Configuration > Settings > Synchronization</strong>. Otherwise,
                    Synchronization Rules will not take effect.</p>
HTML
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_walmart_template_synchronization_relist_filters',
            [
                'legend'      => $this->__('General'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'relist_mode',
            self::SELECT,
            [
                'name'    => 'relist_mode',
                'label'   => $this->__('Relist Action'),
                'value'   => $formData['relist_mode'],
                'values'  => [
                    0 => $this->__('Disabled'),
                    1 => $this->__('Enabled'),
                ],
                'tooltip' => $this->__(
                    'Enable to automatically relist the Not Listed Item(s) when the Relist Conditions are met.'
                )
            ]
        );

        $fieldset->addField(
            'relist_filter_user_lock',
            self::SELECT,
            [
                'container_id' => 'relist_filter_user_lock_tr_container',
                'name'         => 'relist_filter_user_lock',
                'label'        => $this->__('Relist When Stopped Manually'),
                'value'        => $formData['relist_filter_user_lock'],
                'values'       => [
                    1 => $this->__('No'),
                    0 => $this->__('Yes'),
                ],
                'tooltip'      => $this->__(
                    'Enable if you want to relist the Items that were stopped manually.'
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_walmart_template_synchronization_relist_rules',
            [
                'legend'      => $this->__('Relist Conditions'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'relist_status_enabled',
            self::SELECT,
            [
                'name'    => 'relist_status_enabled',
                'label'   => $this->__('Product Status'),
                'value'   => $formData['relist_status_enabled'],
                'values'  => [
                    0 => $this->__('Any'),
                    1 => $this->__('Enabled'),
                ],
                'class'   => 'M2ePro-validate-stop-relist-conditions-product-status',
                'tooltip' => $this->__(
                    'Magento Product Status at which the Item(s) have to be relisted.'
                )
            ]
        );

        $fieldset->addField(
            'relist_is_in_stock',
            self::SELECT,
            [
                'name'    => 'relist_is_in_stock',
                'label'   => $this->__('Stock Availability'),
                'value'   => $formData['relist_is_in_stock'],
                'values'  => [
                    0 => $this->__('Any'),
                    1 => $this->__('In Stock'),
                ],
                'class'   => 'M2ePro-validate-stop-relist-conditions-stock-availability',
                'tooltip' => $this->__(
                    'Magento Stock Availability at which the Item(s) have to be relisted.'
                )
            ]
        );

        $form->addField(
            'relist_qty_calculated_confirmation_popup_template',
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
            'relist_qty_calculated',
            self::SELECT,
            [
                'name'    => 'relist_qty_calculated',
                'label'   => $this->__('Quantity'),
                'value'   => $formData['relist_qty_calculated'],
                'values'  => [
                    TemplateSynchronization::QTY_MODE_NONE => $this->__('Any'),
                    TemplateSynchronization::QTY_MODE_YES  => $this->__('More or Equal'),
                ],
                'class'   => 'M2ePro-validate-stop-relist-conditions-item-qty',
                'tooltip' => $this->__(
                    'Item Quantity calculated based on the Selling Policy settings at
                    which the Item(s) have to be relisted. <br><br>

                    <strong>Note:</strong> This option will be ignored for Magento Variational
                    Product listed as Walmart Variant Group.'
                )
            ]
        )->setAfterElementHtml(
            <<<HTML
<input name="relist_qty_calculated_value" id="relist_qty_calculated_value"
       value="{$formData['relist_qty_calculated_value']}" type="text"
       style="width: 72px; margin-left: 10px;"
       class="input-text admin__control-text required-entry validate-digits _required" />
HTML
        );

        $fieldset = $form->addFieldset(
            'magento_block_walmart_template_synchronization_relist_advanced_filters',
            [
                'legend'      => $this->__('Advanced Conditions'),
                'collapsable' => false,
                'tooltip'     => $this->__(
                    '<p>Define Magento Attribute value(s) based on which a product must be relisted on the Channel.<br>
                    Once both Relist Conditions and Advanced Conditions are met, the product will be relisted.</p>'
                )
            ]
        );

        $fieldset->addField(
            'relist_advanced_rules_filters_warning',
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
            'relist_advanced_rules_mode',
            self::SELECT,
            [
                'name'   => 'relist_advanced_rules_mode',
                'label'  => $this->__('Mode'),
                'value'  => $formData['relist_advanced_rules_mode'],
                'values' => [
                    0 => $this->__('Disabled'),
                    1 => $this->__('Enabled'),
                ],
            ]
        );

        $ruleModel = $this->activeRecordFactory->getObject('Magento_Product_Rule')->setData(
            ['prefix' => Synchronization::RELIST_ADVANCED_RULES_PREFIX]
        );

        if (!empty($formData['relist_advanced_rules_filters'])) {
            $ruleModel->loadFromSerialized($formData['relist_advanced_rules_filters']);
        }

        $ruleBlock = $this->createBlock('Magento_Product_Rule')->setData(['rule_model' => $ruleModel]);

        $fieldset->addField(
            'advanced_filter',
            self::CUSTOM_CONTAINER,
            [
                'container_id' => 'relist_advanced_rules_filters_container',
                'label'        => $this->__('Conditions'),
                'text'         => $ruleBlock->toHtml(),
            ]
        );

        $jsFormData = [
            'relist_mode',
            'relist_status_enabled',
            'relist_is_in_stock',

            'relist_qty_calculated',
            'relist_qty_calculated_value',

            'relist_advanced_rules_mode',
            'relist_advanced_rules_filters',
        ];

        foreach ($jsFormData as $item) {
            $this->js->add("M2ePro.formData.$item = '{$this->getHelper('Data')->escapeJs($formData[$item])}';");
        }

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
