<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode\Form
 */
class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            ['data' => [
                'id' => 'edit_form',
                'method' => 'post'
            ]]
        );

        $form->addField(
            'soruce_mode_help_block',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    'After you set up M2E Pro Listing, you can add Magento Products into it.
                    You are able to select the products either from the entire Magento catalog or a
                    certain Magento category. To proceed, choose your option below.'
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'source_mode',
            [
            ]
        );

        $defaultSource = $this->getRequest()
            ->getParam('source', \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode::MODE_PRODUCT);

        $fieldset->addField(
            'block-title',
            'label',
            [
                'value' => $this->__('Choose how you want to display Products for selection'),
                'field_extra_attributes' => 'style="font-weight: bold;font-size:18px;margin-bottom:0px"'

            ]
        );
        $fieldset->addField(
            'source1',
            'radios',
            [
                'name' => 'source',
                'field_extra_attributes' => 'style="margin: 4px 0 0 0; font-weight: bold"',
                'values' => [
                    [
                        'value' => \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode::MODE_PRODUCT,
                        'label' => 'Products List'
                    ]
                ],
                'value' => $defaultSource,
                'note' => '<div style="padding-top: 3px; padding-left: 26px; font-weight: normal">'.
                    $this->__('Products displayed as a list without any grouping.').'</div>'
            ]
        );

        $fieldset->addField(
            'source2',
            'radios',
            [
                'name' => 'source',
                'field_extra_attributes' => 'style="margin: 4px 0 0 0; font-weight: bold"',
                'values' => [
                    [
                        'value' => \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode::MODE_CATEGORY,
                        'label' => 'Categories'
                    ]
                ],
                'value' => $defaultSource,
                'note' => '<div style="padding-top: 3px; padding-left: 26px; font-weight: normal">'.
                    $this->__('Products grouped by Magento Categories.').'</div>'
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################
}
