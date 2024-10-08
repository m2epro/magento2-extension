<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Category\ModeSame;

use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\SelectMode;
use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

class Form extends AbstractForm
{
    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'method' => 'post',
                ],
            ]
        );

        $fieldset = $form->addFieldset('categories_mode', []);

        $fieldset->addField(
            'block-title',
            'label',
            [
                'value' => __('You need to choose Ebay Categories for Products in order to list them on Ebay.'),
                'field_extra_attributes' =>
                    'id="categories_mode_block_title" style="font-weight: bold;font-size:18px;margin-bottom:0px"',
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
                'value' => __('Choose one of the Options below.'),
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
                        'value' => SelectMode::MODE_SAME,
                        'label' => __('All Products same Category'),
                    ],
                ],
                'value' => SelectMode::MODE_SAME,
                'note' => '<div style="padding-top: 3px; padding-left: 26px; font-weight: normal">' .
                    __('Products will be Listed using the same Ebay Category.') . '</div>',
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
                        'value' => SelectMode::MODE_MANUALLY,
                        'label' => __('Set Manually for each Product'),
                    ],
                ],
                'value' => SelectMode::MODE_SAME,
                'note' => '<div style="padding-top: 3px; padding-left: 26px; font-weight: normal">' .
                    __('Set Ebay Categories for each Product (or a group of Products) manually.') . '</div>',
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
                        'value' => SelectMode::MODE_CATEGORY,
                        'label' => __('Set eBay Categories (Based On Magento Categories)'),
                    ],
                ],
                'value' => SelectMode::MODE_SAME,
                'note' => '<div style="padding-top: 3px; padding-left: 26px; font-weight: normal">' .
                    __('Products will have eBay Categories set according to the Magento Categories.') . '</div>',
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
                        'value' => SelectMode::MODE_EBAY_SUGGESTED,
                        'label' => __('Get suggested Categories'),
                    ],
                ],
                'value' => SelectMode::MODE_SAME,
                'note' => '<div style="padding-top: 3px; padding-left: 26px; font-weight: normal">' .
                    __('Get eBay to suggest Categories for your Products based on the Title and Magento Attribute set.') . '</div>',
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
