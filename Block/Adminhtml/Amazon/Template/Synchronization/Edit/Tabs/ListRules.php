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
            'list_qty_calculated_value_max' => '10'
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
                    <p><strong>List Action</strong> - this Action can be executed for each Item in M2E Pro
                    Listings which has Not Listed Status and which Settings meet the List Condition. 
                    If an Item was not initially Listed for some reason, automatic synchronization will attempt 
                    to list it again only if there is a change of Product Status, Stock Availability or Quantity 
                    in Magento.</p><br>
                    <p><strong>Note:</strong> M2E Pro Listings Synchronization must be enabled in 
                    Synchronization <strong>(Amazon Integration > Configuration > Settings > Synchronization)</strong>.
                    Otherwise, Synchronization Policy Rules will not take effect.</p><br>
                    <p>More detailed information about how to work with this Page you can find 
                    <a href="%url%" target="_blank">here</a>.</p>
HTML
                ,
                $this->getHelper('Module\Support')->getDocumentationUrl(NULL, NULL, 'x/RQItAQ')
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

        $fieldset->addField(
            'list_mode',
            'select',
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

        $fieldset->addField(
            'list_status_enabled',
            'select',
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

        $fieldset->addField(
            'list_is_in_stock',
            'select',
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

        $fieldset->addField(
            'list_qty_magento',
            'select',
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
                'name' => 'list_qty_magento_value',
                'label' => $this->__('Max Quantity'),
                'value' => $formData['list_qty_magento_value_max'],
                'class' => 'validate-digits M2ePro-validate-conditions-between',
                'required' => true
            ]
        );

        $fieldset->addField(
            'list_qty_calculated',
            'select',
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