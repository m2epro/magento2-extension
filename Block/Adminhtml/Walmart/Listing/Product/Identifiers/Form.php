<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Identifiers;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Identifiers\Form
 */
class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => 'javascript:void(0)',
                    'method' => 'post',
                ],
            ]
        );

        $fieldset = $form->addFieldset(
            'edit_identifiers_fieldset',
            [
                'legend' => __('Edit Product ID'),
                'collapsable' => false,
                'direction_class' => 'to-right',
                'tooltip' => __(
                    'If you need to link your listed Item to a different product in Walmart catalog, you may edit its
                    Product ID.<br>
                    Select a Product ID Type, enter a new Product ID value and click Submit. M2E Pro will automatically
                    submit the new Product ID to Walmart.'
                ),
            ]
        );

        $fieldset->addField(
            'identifier',
            'select',
            [
                'name' => 'identifier',
                'label' => __('Product ID Type'),
                'values' => [
                    'gtin' => __('GTIN'),
                    'upc' => __('UPC'),
                    'ean' => __('EAN'),
                    'isbn' => __('ISBN'),
                ],
                'required' => true,
            ]
        );

        $fieldset->addField(
            'new_identifier_value',
            'text',
            [
                'name' => 'new_identifier_value',
                'label' => __('New Product ID'),
                'required' => true,
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################
}
