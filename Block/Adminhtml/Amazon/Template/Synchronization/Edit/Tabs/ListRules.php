<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Synchronization\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Amazon\Template\Synchronization;

class ListRules extends AbstractForm
{
    protected function _prepareForm()
    {
        $template = $this->getHelper('Data\GlobalData')->getValue('tmp_template');
        $formData = !is_null($template)
            ? array_merge($template->getData(), $template->getChildObject()->getData()) : [];

        $defaults = array(
            'title'               => '',
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
        );
        $formData = array_merge($defaults, $formData);

        $isEdit = !!$this->getRequest()->getParam('id');

        $form = $this->_formFactory->create();

        $form->addField(
            'amazon_template_synchronization_general',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    <<<HTML
                    <p>If <strong>List Action</strong> is enabled, each new Item in M2E Pro Listing, that
                    has Not Listed status and the settings met specified List Conditions,
                    will be listed automatically</p><br/>
                    <p><strong>Note:</strong> M2E Pro Listings Synchronization must be enabled
                    <strong>(Amazon Integration > Configuration > Settings > Synchronization)</strong>.
                    Otherwise, Synchronization Policy Rules will not take effect.</p><br/>
                    <p>More detailed information about how to work with this Page you can find
                    <a href="%url%" target="_blank" class="external-link">here</a>.</p>
HTML
                ,
                $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/RQItAQ')
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_synchronization_general_list',
            [
                'legend' => $this->__('General'),
                'collapsable' => false
            ]
        );

        $fieldset->addField('list_mode',
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
                    'Enables / disables automatic Listing of <i>Not Listed</i> Items,
                    when they meet the List Conditions.'
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_synchronization_list_rules',
            [
                'legend' => $this->__('List Conditions'),
                'collapsable' => false
            ]
        );

        $fieldset->addField('list_status_enabled',
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
                    '<p><strong>Enabled:</strong> List Items on Amazon automatically if they have status
                    Enabled in Magento Product. (Recommended)</p>
                    <p><strong>Any:</strong> List Items with any Magento Product status on Amazon automatically.</p>'
                )
            ]
        );

        $fieldset->addField('list_is_in_stock',
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
                    '<p><strong>In Stock:</strong> List Items automatically if Products are
                    in Stock. (Recommended.)</p>
                    <p><strong>Any:</strong> List Items automatically, regardless of Stock availability.</p>'
                )
            ]
        );

        $fieldset->addField('list_qty_magento',
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
                    '<p><strong>Any:</strong> List Items automatically with any Quantity available.</p>
                    <p><strong>More or Equal:</strong> List Items automatically if the Quantity available in
                    Magento is at least equal to the number you set. (Recommended)</p>
                    <p><strong>Between:</strong> List Items automatically if the Quantity available in
                    Magento is between the minimum and maximum numbers you set.</p>'
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

        $fieldset->addField('list_qty_calculated',
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
                    '<p><strong>Any:</strong> List Items automatically with any Quantity available.</p>
                    <p><strong>More or Equal:</strong> List Items automatically if the calculated Quantity is at
                    least equal to the number you set, according to the Price, Quantity and Format Policy.
                    (Recommended)</p>
                    <p><strong>Between:</strong> List Items automatically if the Quantity is between the minimum
                    and maximum numbers you set, according to the Price, Quantity and Format Policy.</p>'
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

        $fieldset = $form->addFieldset('magento_block_amazon_template_synchronization_list_advanced_filters',
            [
                'legend' => $this->__('Advanced Conditions'),
                'collapsable' => false,
                'tooltip' => $this->__(
                    '<p>You can provide flexible Advanced Conditions to manage when the List action should be
                    run basing on the Attributes’ values of the Magento Product.<br> So, when all the Conditions
                    (both general List Conditions and Advanced Conditions) are met,
                    the Product will be listed on Channel.</p>'
                )
            ]
        );

        $fieldset->addField('list_advanced_rules_filters_warning',
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

        $fieldset->addField('list_advanced_rules_mode',
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

        $ruleModel = $this->activeRecordFactory->getObject('Magento\Product\Rule')->setData(
            ['prefix' => Synchronization::LIST_ADVANCED_RULES_PREFIX]
        );

        if (!empty($formData['list_advanced_rules_filters'])) {
            $ruleModel->loadFromSerialized($formData['list_advanced_rules_filters']);
        }

        $ruleBlock = $this->createBlock('Magento\Product\Rule')->setData(['rule_model' => $ruleModel]);

        $fieldset->addField('advanced_filter',
            self::CUSTOM_CONTAINER,
            [
                'container_id' => 'list_advanced_rules_filters_container',
                'label'        => $this->__('Conditions'),
                'text'         => $ruleBlock->toHtml(),
            ]
        );

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Amazon\Template\Synchronization')
        );
        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Helper\Component\Amazon'));

        $this->jsUrl->addUrls([
            'formSubmit'    => $this->getUrl(
                '*/amazon_template_synchronization/save', array('_current' => true)
            ),
            'formSubmitNew' => $this->getUrl('m2epro/amazon_template_synchronization/save'),
            'deleteAction'  => $this->getUrl(
                '*/amazon_template_synchronization/delete', array('_current' => true)
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
        $this->js->add(
            "M2ePro.formData.title
            = '{$this->getHelper('Data')->escapeJs($this->getHelper('Data')->escapeHtml($formData['title']))}';"
        );

        $this->js->add(<<<JS
    require([
        'M2ePro/Amazon/Template/Synchronization',
    ], function(){
        window.AmazonTemplateSynchronizationObj = new AmazonTemplateSynchronization();
        AmazonTemplateSynchronizationObj.initObservers();
    });
JS
        );

        $this->setForm($form);

        return parent::_prepareForm();
    }
}