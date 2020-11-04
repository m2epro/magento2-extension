<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Settings\Tabs;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Settings\Tabs\MagentoInventory
 */
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

        $form->addField(
            'magento_inventory_help',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    <<<HTML
                    <p>In this section, you can provide the global settings for Product Quantity and Price management.
                    Click <strong>Save</strong> after the changes are made.</p>
HTML
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_configuration_settings_variational_products_settings',
            [
                'legend'      => $this->__('Variational Product Settings'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'grouped_product_mode',
            self::SELECT,
            [
                'name' => 'grouped_product_mode',
                'label' => $this->__('List Grouped Product as'),
                'values' => [
                    1 => $this->__('Product Set'),
                    0 => $this->__('Variations')
                ],
                'value' => $this->getHelper('Module_Configuration')->getGroupedProductMode(),
                'tooltip' => $this->__(
                    <<<HTML
<b>Product Set</b> - a group of products will be listed as a Set (Individual Item).
Customers can purchase products only as a set. Read the <a href="%url%" target="_blank">article</a> for details.
<b>Variations</b> - a group of products will be listed as a Variational Item.
Customers can purchase each option of Variational Product separately.
HTML
                    ,
                    $this->getHelper('Module_Support')->getSupportUrl(
                        'knowledgebase/1585305-listing-magento-grouped-product-as-product-set'
                    )
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'configuration_settings_magento_inventory_quantity',
            [
                'legend'      => $this->__('Quantity'),
                'collapsable' => false,
                'tooltip'     => $this->__(
                    'In this section, you can provide the global settings for Inventory management.'
                )
            ]
        );

        $fieldset->addField(
            'product_force_qty_mode',
            self::SELECT,
            [
                'name' => 'product_force_qty_mode',
                'label' => $this->__('Manage Stock "No", Backorders'),
                'values' => [
                    0 => $this->__('Disallow'),
                    1 => $this->__('Allow')
                ],
                'value' => $this->getHelper('Module_Configuration')->isEnableProductForceQtyMode(),
                'tooltip' => $this->__(
                    'Choose whether M2E Pro is allowed to List Products with unlimited stock or that are
                    temporarily out of stock.<br>
                    <b>Disallow</b> is the recommended setting for eBay Integration.'
                )
            ]
        );

        $fieldset->addField(
            'product_force_qty_value',
            'text',
            [
                'name' => 'product_force_qty_value',
                'label' => $this->__('Quantity To Be Listed'),
                'value' => $this->getHelper('Module_Configuration')->getProductForceQtyValue(),
                'tooltip' => $this->__(
                    'Set a number to List, e.g. if you have Manage Stock "No" in Magento Product and set this Value
                    to 10, 10 will be sent as available Quantity to the Channel.'
                ),
                'field_extra_attributes' => 'id="product_force_qty_value_tr"',
                'class' => 'validate-greater-than-zero',
                'required' => true
            ]
        );

        $fieldset = $form->addFieldset(
            'configuration_settings_magento_inventory_price',
            [
                'legend'      => $this->__('Price'),
                'collapsable' => false,
                'tooltip'     => $this->__(
                    'In this section, you can provide the global settings for Product Price management.'
                )
            ]
        );

        $fieldset->addField(
            'magento_attribute_price_type_converting_mode',
            self::SELECT,
            [
                'name' => 'magento_attribute_price_type_converting_mode',
                'label' => $this->__('Convert Magento Price Attribute'),
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes')
                ],
                'value' => $this->getHelper('Module_Configuration')->getMagentoAttributePriceTypeConvertingMode(),
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

        $('product_force_qty_mode').observe('change', function() {
            if($('product_force_qty_mode').value == 1) {
                $('product_force_qty_value_tr').show();
            } else {
                $('product_force_qty_value_tr').hide();
            }
        }).simulate('change');
JS
        );

        return parent::_beforeToHtml();
    }

    //########################################
}
