<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */
namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\SourceMode;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\SourceMode\Form
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
            'source_help_block',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    '<p>After an M2E Pro listing is successfully configured and created, Magento Products should
                    be added into it. <br> The Products you add to the Listing will further be Listed on eBay.</p><br>
                    <p>There are several different options of how Magento products can be found/selected
                    and added to the Listing.</p>'
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'source_mode',
            []
        );

        $defaultSource = $this->getRequest()
            ->getParam('source', \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\SourceMode::MODE_PRODUCT);

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
                        'value' => \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\SourceMode::MODE_PRODUCT,
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
                        'value' => \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\SourceMode::MODE_CATEGORY,
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
