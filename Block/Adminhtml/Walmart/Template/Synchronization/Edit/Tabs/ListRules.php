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
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Synchronization\Edit\Tabs\ListRules
 */
class ListRules extends AbstractForm
{
    protected function _prepareForm()
    {
        $template = $this->getHelper('Data\GlobalData')->getValue('tmp_template');
        $formData = $template !== null
            ? array_merge($template->getData(), $template->getChildObject()->getData()) : [];

        $defaults = [
            'list_mode'           => Synchronization::LIST_MODE_YES,
            'list_status_enabled' => Synchronization::LIST_STATUS_ENABLED_YES,
            'list_is_in_stock'    => Synchronization::LIST_IS_IN_STOCK_YES,

            'list_qty_magento'           => Synchronization::LIST_QTY_NONE,
            'list_qty_magento_value'     => '1',
            'list_qty_magento_value_max' => '10',

            'list_qty_calculated'           => Synchronization::LIST_QTY_NONE,
            'list_qty_calculated_value'     => '1',
            'list_qty_calculated_value_max' => '10',

            'list_advanced_rules_mode'    => Synchronization::ADVANCED_RULES_MODE_NONE,
            'list_advanced_rules_filters' => null
        ];
        $formData = array_merge($defaults, $formData);

        $form = $this->_formFactory->create();

        $form->addField(
            'walmart_template_synchronization_general',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    <<<HTML
                    <p>Synchronization Policy includes rules and conditions based on which M2E Pro
                    automatically transfers your Magento data to the Channel. You may configure the List,
                    Revise, Relist and Stop Rules.</p><br/>

                    <p><strong>Note:</strong> Synchronization Policy is required when you
                    create a new offer on Walmart.</p><br>

                    <p>Enable the List Action and define the List Conditions based on which M2E Pro will
                    automatically list the Not Listed Items on Walmart. If the initial list fails,
                    the Module will reattempt the Item listing after the Product Status, Stock Availability
                    or Quantity are changed.</p><br>

                    <p><strong>Note:</strong> M2E Pro Listing Synchronization must be enabled under
                    <i>Walmart Integration > Configuration > Settings > Synchronization</i>. Otherwise,
                    Synchronization Rules will not take effect.</p><br>
HTML
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_walmart_template_synchronization_general_list',
            [
                'legend' => $this->__('General'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'list_mode',
            self::SELECT,
            [
                'name' => 'list_mode',
                'label' => $this->__('List Action'),
                'value' => $formData['list_mode'],
                'values' => [
                    Synchronization::LIST_MODE_NONE => $this->__('Disabled'),
                    Synchronization::LIST_MODE_YES => $this->__('Enabled'),
                ],
                'tooltip' => $this->__(
                    'Enable to automatically list the Not Listed Item(s) when the List Conditions are met.'
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_walmart_template_synchronization_list_rules',
            [
                'legend' => $this->__('List Conditions'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'list_status_enabled',
            self::SELECT,
            [
                'name' => 'list_status_enabled',
                'label' => $this->__('Product Status'),
                'value' => $formData['list_status_enabled'],
                'values' => [
                    Synchronization::LIST_STATUS_ENABLED_NONE => $this->__('Any'),
                    Synchronization::LIST_STATUS_ENABLED_YES => $this->__('Enabled'),
                ],
                'tooltip' => $this->__(
                    'Magento Product Status at which the Item(s) have to be listed.'
                )
            ]
        );

        $fieldset->addField(
            'list_is_in_stock',
            self::SELECT,
            [
                'name' => 'list_is_in_stock',
                'label' => $this->__('Stock Availability'),
                'value' => $formData['list_is_in_stock'],
                'values' => [
                    Synchronization::LIST_IS_IN_STOCK_NONE => $this->__('Any'),
                    Synchronization::LIST_IS_IN_STOCK_YES => $this->__('In Stock'),
                ],
                'tooltip' => $this->__(
                    'Magento Stock Availability at which the Item(s) have to be listed'
                )
            ]
        );

        $fieldset->addField(
            'list_qty_magento',
            self::SELECT,
            [
                'name' => 'list_qty_magento',
                'label' => $this->__('Magento Quantity'),
                'value' => $formData['list_qty_magento'],
                'values' => [
                    Synchronization::LIST_QTY_NONE => $this->__('Any'),
                    Synchronization::LIST_QTY_MORE => $this->__('More or Equal'),
                    Synchronization::LIST_QTY_BETWEEN => $this->__('Between'),
                ],
                'tooltip' => $this->__(
                    'Magento Product Quantity at which the Item(s) have to be listed.'
                )
            ]
        )->addCustomAttribute('qty_type', 'magento');

        $fieldset->addField(
            'list_qty_magento_value',
            'text',
            [
                'container_id' => 'list_qty_magento_value_container',
                'name' => 'list_qty_magento_value',
                'label' => $this->__('Quantity'),
                'value' => $formData['list_qty_magento_value'],
                'class' => 'validate-digits',
                'required' => true
            ]
        );

        $fieldset->addField(
            'list_qty_magento_value_max',
            'text',
            [
                'container_id' => 'list_qty_magento_value_max_container',
                'name' => 'list_qty_magento_value_max',
                'label' => $this->__('Max Quantity'),
                'value' => $formData['list_qty_magento_value_max'],
                'class' => 'validate-digits M2ePro-validate-conditions-between',
                'required' => true
            ]
        );

        $fieldset->addField(
            'list_qty_calculated',
            self::SELECT,
            [
                'name' => 'list_qty_calculated',
                'label' => $this->__('Calculated Quantity'),
                'value' => $formData['list_qty_calculated'],
                'values' => [
                    Synchronization::LIST_QTY_NONE => $this->__('Any'),
                    Synchronization::LIST_QTY_MORE => $this->__('More or Equal'),
                    Synchronization::LIST_QTY_BETWEEN => $this->__('Between'),
                ],
                'tooltip' => $this->__(
                    '<p>Item Quantity calculated based on the Selling Policy settings at which
                    the Item(s) have to be listed.</p>
                    <p><strong>Note:</strong> This option will be ignored for
                    Magento Variational Product listed as Walmart Variant Group</p>'
                )
            ]
        )->addCustomAttribute('qty_type', 'calculated');

        $fieldset->addField(
            'list_qty_calculated_value',
            'text',
            [
                'container_id' => 'list_qty_calculated_value_container',
                'name' => 'list_qty_calculated_value',
                'label' => $this->__('Quantity'),
                'value' => $formData['list_qty_calculated_value'],
                'class' => 'validate-digits',
                'required' => true
            ]
        );

        $fieldset->addField(
            'list_qty_calculated_value_max',
            'text',
            [
                'container_id' => 'list_qty_calculated_value_max_container',
                'name' => 'list_qty_calculated_value_max',
                'label' => $this->__('Max Quantity'),
                'value' => $formData['list_qty_calculated_value_max'],
                'class' => 'validate-digits M2ePro-validate-conditions-between  ',
                'required' => true
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_walmart_template_synchronization_list_advanced_filters',
            [
                'legend' => $this->__('Advanced Conditions'),
                'collapsable' => false,
                'tooltip' => $this->__(
                    '<p>Define Magento Attribute value(s) based on which a product must be listed on the Channel.<br>
                    Once both List Conditions and Advanced Conditions are met, the product will be listed.</p>'
                )
            ]
        );

        $fieldset->addField(
            'list_advanced_rules_filters_warning',
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
            'list_advanced_rules_mode',
            self::SELECT,
            [
                'name' => 'list_advanced_rules_mode',
                'label' => $this->__('Mode'),
                'value' => $formData['list_advanced_rules_mode'],
                'values' => [
                    Synchronization::ADVANCED_RULES_MODE_NONE => $this->__('Disabled'),
                    Synchronization::ADVANCED_RULES_MODE_YES  => $this->__('Enabled'),
                ],
            ]
        );

        $ruleModel = $this->activeRecordFactory->getObject('Magento_Product_Rule')->setData(
            ['prefix' => Synchronization::LIST_ADVANCED_RULES_PREFIX]
        );

        if (!empty($formData['list_advanced_rules_filters'])) {
            $ruleModel->loadFromSerialized($formData['list_advanced_rules_filters']);
        }

        $ruleBlock = $this->createBlock('Magento_Product_Rule')->setData(['rule_model' => $ruleModel]);

        $fieldset->addField(
            'advanced_filter',
            self::CUSTOM_CONTAINER,
            [
                'container_id' => 'list_advanced_rules_filters_container',
                'label'        => $this->__('Conditions'),
                'text'         => $ruleBlock->toHtml(),
            ]
        );

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Model\Walmart\Template\Synchronization::class)
        );
        $this->jsPhp->addConstants($this->getHelper('Data')
            ->getClassConstants(\Ess\M2ePro\Helper\Component\Walmart::class));

        $this->jsUrl->addUrls([
            'formSubmit'    => $this->getUrl(
                '*/walmart_template_synchronization/save',
                ['_current' => true]
            ),
            'formSubmitNew' => $this->getUrl('m2epro/walmart_template_synchronization/save'),
            'deleteAction'  => $this->getUrl(
                '*/walmart_template_synchronization/delete',
                ['_current' => true]
            )
        ]);

        $this->jsTranslator->addTranslations([
            'Add Synchronization Policy' => $this->__('Add Synchronization Policy'),
            'Wrong time format string.' => $this->__('Wrong time format string.'),

            'Must be greater than "Min".' => $this->__('Must be greater than "Min".'),
            'Inconsistent Settings in Relist and Stop Rules.' => $this->__(
                'Inconsistent Settings in Relist and Stop Rules.'
            ),

            'The specified Title is already used for other Policy. Policy Title must be unique.' => $this->__(
                'The specified Title is already used for other Policy. Policy Title must be unique.'
            ),

            'Quantity' => $this->__('Quantity'),
            'Min Quantity' => $this->__('Min Quantity'),
        ]);

        $this->js->add("M2ePro.formData.id = '{$this->getRequest()->getParam('id')}';");

        $this->js->add(<<<JS
    require([
        'M2ePro/Walmart/Template/Synchronization',
    ], function(){
        window.WalmartTemplateSynchronizationObj = new WalmartTemplateSynchronization();
        WalmartTemplateSynchronizationObj.initObservers();
    });
JS
        );

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
