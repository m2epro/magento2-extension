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

        $urlComponents = $this->getHelper('Component')->getEnabledComponents();
        $componentForUrl = count($urlComponents) == 1
                           ? array_shift($urlComponents)
                           : \Ess\M2ePro\Helper\Component\Ebay::NICK;

        $fieldset = $form->addFieldset('configuration_settings_magento_inventory',
            [
                'legend' => '', 'collapsable' => false,
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
                    'Choose whether M2E Pro should List Products on external Channels when Manage Stock is \'No\'
                    or Backorders is enabled.
                    <b>Disallow</b> is the recommended Setting if you are selling on eBay.'
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