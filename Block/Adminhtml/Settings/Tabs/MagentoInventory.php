<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Settings\Tabs;

class MagentoInventory extends \Ess\M2ePro\Block\Adminhtml\Settings\Tabs\AbstractTab
{
    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create([
            'data' => [
                'method' => 'post',
                'action' => $this->getUrl('*/*/save')
            ]
        ]);

        $fieldset = $form->addFieldset('configuration_settings_magento_inventory_quantity',
            [
                'legend'      => $this->__('Quantity'),
                'collapsable' => false,
                'tooltip'     => $this->__(
                    'In this section, you can provide the global settings for Inventory management.'
                )
            ]
        );

        $fieldset->addField('force_qty_mode',
            self::SELECT,
            [
                'name' => 'force_qty_mode',
                'label' => $this->__('Manage Stock "No", Backorders'),
                'values' => [
                    0 => $this->__('Disallow'),
                    1 => $this->__('Allow')
                ],
                'value' => (int)$this->getHelper('Module')->getConfig()->getGroupValue(
                    '/product/force_qty/','mode'
                ),
                'tooltip' => $this->__(
                    'Choose whether M2E Pro is allowed to List Products with unlimited stock or that are
                    temporarily out of stock.<br>
                    <b>Disallow</b> is the recommended setting for eBay Integration.'
                )
            ]
        );

        $fieldset->addField('force_qty_value',
            'text',
            [
                'name' => 'force_qty_value',
                'label' => $this->__('Quantity To Be Listed'),
                'value' => (int)$this->getHelper('Module')->getConfig()->getGroupValue(
                    '/product/force_qty/','value'
                ),
                'tooltip' => $this->__(
                    'Set a number to List, e.g. if you have Manage Stock "No" in Magento Product and set this Value
                    to 10, 10 will be sent as available Quantity to the Channel.'
                ),
                'field_extra_attributes' => 'id="force_qty_value_tr"',
                'class' => 'validate-greater-than-zero',
                'required' => true
            ]
        );

        $fieldset = $form->addFieldset('configuration_settings_magento_inventory_price',
            [
                'legend'      => $this->__('Price'),
                'collapsable' => false,
                'tooltip'     => $this->__(
                    'In this section, you can provide the global settings for Product Price management.'
                )
            ]
        );

        $fieldset->addField('price_type_converting_mode',
            self::SELECT,
            [
                'name' => 'price_type_converting_mode',
                'label' => $this->__('Convert Magento Price Attribute'),
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes')
                ],
                'value' => (int)$this->getHelper('Module')->getConfig()->getGroupValue(
                    '/magento/attribute/','price_type_converting'
                ),
                'tooltip' => $this->__(
                    '<p>Choose whether Magento Price Attribute values should be converted automatically.
                    With this option enabled, M2E Pro will provide currency conversion based on Magento
                    Currency Settings.</p>
                    <p><strong>For example</strong>, the Item Price is set to be taken from Magento Price
                    Attribute (e.g. 5 USD).<br>
                    If this Item is listed on Marketplace with a different Base Currency (e.g. GBP),
                    the currency conversion is performed automatically based on the set exchange rate
                    (e.g. 1 USD = 0.82 GBP).<br>
                    The Item will be available on Channel at the Price of 4.1 GBP.</p>'
                )
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->jsUrl->add(
            $this->getUrl('*/settings_magentoInventory/save'),
            \Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs::TAB_ID_MAGENTO_INVENTORY
        );

        $this->js->addRequireJs([], <<<JS

        $('force_qty_mode').observe('change', function() {
            if($('force_qty_mode').value == 1) {
                $('force_qty_value_tr').show();
            } else {
                $('force_qty_value_tr').hide();
            }
        }).simulate('change');
JS
        );

        return parent::_beforeToHtml();
    }

    //########################################
}