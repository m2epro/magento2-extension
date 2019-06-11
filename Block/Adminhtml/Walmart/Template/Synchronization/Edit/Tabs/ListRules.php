<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\Synchronization\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Walmart\Template\Synchronization;

class ListRules extends AbstractForm
{
    protected function _prepareForm()
    {
        $template = $this->getHelper('Data\GlobalData')->getValue('tmp_template');
        $formData = !is_null($template)
            ? array_merge($template->getData(), $template->getChildObject()->getData()) : [];

        $defaults = array(
            'list_mode'           => Synchronization::LIST_MODE_YES,
            'list_status_enabled' => Synchronization::LIST_STATUS_ENABLED_YES,
            'list_is_in_stock'    => Synchronization::LIST_IS_IN_STOCK_YES,

            'list_qty_magento'           => Synchronization::LIST_QTY_NONE,
            'list_qty_magento_value'     => '1',
            'list_qty_magento_value_max' => '10',

            'list_qty_calculated'           => Synchronization::LIST_QTY_NONE,
            'list_qty_calculated_value'     => '1',
            'list_qty_calculated_value_max' => '10'
        );
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

                    <p>Enable the List Action and define the List Conditions based on which M2E Pro will
                    automatically list the Not Listed Items on Walmart. If the initial list fails, the Module
                    will reattempt the Item listing after the Product Status, Stock Availability or
                    Quantity are changed.</p><br>

                    <p><strong>Note:</strong> Inventory Synchronization must be enabled under
                    Walmart > Configuration > Settings > Synchronization tab. Otherwise,
                    Synchronization Policy Rules will not take effect.</p><br>

                    <p><strong>Note:</strong> Synchronization Policy is required when you
                    create a new offer on Walmart.</p><br>

                    <p>The detailed information can be found
                    <a href="%url%" target="_blank" class="external-link">here</a>.</p>
HTML
                ,
                $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/UABhAQ')
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
            'magento_block_walmart_template_synchronization_list_rules',
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
                    '<p><strong>Enabled:</strong> List Items on Walmart automatically if they have status
                    Enabled in Magento Product. (Recommended)</p>
                    <p><strong>Any:</strong> List Items with any Magento Product status on Walmart automatically.</p>'
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
                    least equal to the number you set, according to the Selling Policy.
                    (Recommended)</p>
                    <p><strong>Between:</strong> List Items automatically if the Quantity is between the minimum
                    and maximum numbers you set, according to the Selling Policy.</p>'
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

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Walmart\Template\Synchronization')
        );
        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Helper\Component\Walmart'));

        $this->jsUrl->addUrls([
            'formSubmit'    => $this->getUrl(
                '*/walmart_template_synchronization/save', array('_current' => true)
            ),
            'formSubmitNew' => $this->getUrl('m2epro/walmart_template_synchronization/save'),
            'deleteAction'  => $this->getUrl(
                '*/walmart_template_synchronization/delete', array('_current' => true)
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