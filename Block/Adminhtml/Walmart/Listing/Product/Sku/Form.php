<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Sku;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Sku\Form
 */
class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            ['data' => [
                'id'    => 'edit_form',
                'action' => 'javascript:void(0)',
                'method' => 'post'
            ]]
        );

        $fieldset = $form->addFieldset(
            'edit_sku_fieldset',
            [
                'legend' => $this->__('Edit SKU'),
                'collapsable' => false,
                'direction_class' => 'to-right',
                'tooltip' => $this->__(
                    'You may edit SKU of the already listed Item. Enter a new SKU value and click Submit. M2E Pro will
                    automatically submit the new Item SKU to Walmart.<br><br>
                    <strong>Note:</strong> a new SKU value must be unique.'
                )
            ]
        );

        $fieldset->addField(
            'new_sku_value',
            'text',
            [
                'name' => 'new_sku_value',
                'label' => $this->__('New SKU'),
                'required' => true,
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################
}
