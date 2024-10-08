<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\ProductSource;

use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\ProductSourceSelect as SourceMode;
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

        $form->addField(
            'source_help_block',
            self::HELP_BLOCK,
            [
                'content' => __(
                    '<p>After an M2E Ebay Shop listing is configured and created, Magento Products should
                    be added into it. <br> The Products you add to the Listing will further be
                    Listed on Ebay.</p><br>
                    <p>There are several different options of how Magento products can be found/selected
                    and added to the Listing.</p>'
                ),
            ]
        );

        $fieldset = $form->addFieldset(
            'source_mode',
            []
        );

        $defaultSource = $this->getRequest()
                              ->getParam(
                                  'source',
                                  SourceMode::MODE_PRODUCT
                              );

        $fieldset->addField(
            'block-title',
            'label',
            [
                'value' => __('Choose how you want to display Products for selection'),
                'field_extra_attributes' => 'style="font-weight: bold;font-size:18px;margin-bottom:0px"',
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
                        'value' => SourceMode::MODE_PRODUCT,
                        'label' => __('Products List'),
                    ],
                ],
                'value' => $defaultSource,
                'note' => '<div style="padding-top: 3px; padding-left: 26px; font-weight: normal">' .
                    __('Products displayed as a list without any grouping.') . '</div>',
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
                        'value' => SourceMode::MODE_CATEGORY,
                        'label' => __('Categories'),
                    ],
                ],
                'value' => $defaultSource,
                'note' => '<div style="padding-top: 3px; padding-left: 26px; font-weight: normal">' .
                    __('Products grouped by Magento Categories.') . '</div>',
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
