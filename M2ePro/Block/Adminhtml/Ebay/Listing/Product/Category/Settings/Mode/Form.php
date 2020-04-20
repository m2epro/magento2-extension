<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode\Form
 */
class Form extends AbstractForm
{
    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            ['data' => [
                'id' => 'categories_mode_form',
                'method' => 'post'
            ]]
        );

        $fieldset = $form->addFieldset(
            'categories_mode',
            [
            ]
        );

        $fieldset->addField(
            'block-title',
            'label',
            [
                'value' => $this->__('You need to choose eBay Categories for Products in order to list them on eBay.'),
                'field_extra_attributes' =>
                    'id="categories_mode_block_title" style="font-weight: bold;font-size:18px;margin-bottom:0px"'
            ]
        );
        $this->css->add(
            <<<CSS
    #categories_mode_block_title .admin__field-control{
        width: 90%;
    }
CSS
        );

        $fieldset->addField(
            'block-notice',
            'label',
            [
                'value' => $this->__('Choose one of the Options below.'),
                'field_extra_attributes' => 'style="margin-bottom: 0;"',
            ]
        );

        $fieldset->addField(
            'mode1',
            'radios',
            [
                'name' => 'mode',
                'field_extra_attributes' => 'style="margin: 4px 0 0 0; font-weight: bold"',
                'values' => [
                    [
                        'value' => 'same',
                        'label' => 'All Products same Category'
                    ]
                ],
                'note' => '<div style="padding-top: 3px; padding-left: 26px; font-weight: normal">'.
                    $this->__('Products will be Listed using the same eBay Category.').'</div>'
            ]
        );

        $fieldset->addField(
            'mode_same_remember_checkbox',
            'checkbox',
            [
                'name' => 'mode_same_remember_checkbox',
                'after_element_html'=>
                    '&nbsp;&nbsp; <span style="color: #808080; font-size: 1.2rem; vertical-align: top;">' .
                    $this->__('Remember my choice and skip this step in the future.') . '</span>',
                'value' => 1,
                'checked' => false,
                'disabled' => true,
                'field_extra_attributes' => 'style="margin-top: 2px; margin-bottom: 0; padding-left: 56px;"'
            ]
        );

        $fieldset->addField(
            'mode2',
            'radios',
            [
                'name' => 'mode',
                'field_extra_attributes' => 'style="margin: 4px 0 0 0; font-weight: bold"',
                'values' => [
                    [
                        'value' => 'category',
                        'label' => 'Based on Magento Categories'
                    ]
                ],
                'note' => '<div style="padding-top: 3px; padding-left: 26px; font-weight: normal">'.
                    $this->__('Products will have eBay Categories set according to the Magento Categories.').'</div>'
            ]
        );

        $fieldset->addField(
            'mode3',
            'radios',
            [
                'name' => 'mode',
                'field_extra_attributes' => 'style="margin: 4px 0 0 0; font-weight: bold"',
                'values' => [
                    [
                        'value' => 'product',
                        'label' => 'Get suggested Categories'
                    ]
                ],
                'note' => '<div style="padding-top: 3px; padding-left: 26px; font-weight: normal">'.
                    $this->__(
                        'Get eBay to suggest Categories for your Products based on the Title and Magento Attribute set.'
                    ).'</div>'
            ]
        );

        $fieldset->addField(
            'mode4',
            'radios',
            [
                'name' => 'mode',
                'field_extra_attributes' => 'style="margin: 4px 0 0 0; font-weight: bold"',
                'values' => [
                    [
                        'value' => 'manually',
                        'label' => 'Set Manually for each Product'
                    ]
                ],
                'note' => '<div style="padding-top: 3px; padding-left: 26px; font-weight: normal">'.
                    $this->__('Set eBay Categories for each Product (or a group of Products) manually.').'</div>'
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
