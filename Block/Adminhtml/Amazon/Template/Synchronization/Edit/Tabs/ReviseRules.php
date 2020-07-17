<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Synchronization\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Synchronization\Edit\Tabs\ReviseRules
 */
class ReviseRules extends AbstractForm
{
    protected function _prepareForm()
    {
        $template = $this->getHelper('Data\GlobalData')->getValue('tmp_template');
        $formData = $template !== null
            ? array_merge($template->getData(), $template->getChildObject()->getData()) : [];

        $defaults = $this->modelFactory->getObject('Amazon_Template_Synchronization_Builder')->getDefaultData();

        $formData = array_merge($defaults, $formData);

        $form = $this->_formFactory->create();

        $form->addField(
            'amazon_template_synchronization_revise',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    <<<HTML
<p>Specify which Channel data should be automatically revised by M2E Pro.</p><br>

<p>Selected Item Properties will be automatically updated based on the changes in related Magento Attributes or
Policy Templates.</p><br>

<p>More detailed information on how to work with this Page can be found
<a href="%url%" target="_blank" class="external-link">here</a>.</p>
HTML
                    ,
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/RwItAQ')
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_synchronization_form_data_revise_products',
            [
                'legend' => $this->__('Revise Conditions'),
                'collapsable' => true
            ]
        );

        $fieldset->addField(
            'revise_update_qty',
            self::SELECT,
            [
                'name' => 'revise_update_qty',
                'label' => $this->__('Quantity'),
                'value' => $formData['revise_update_qty'],
                'values' => [
                    1 => $this->__('Yes'),
                ],
                'disabled' => true,
                'tooltip' => $this->__(
                    'Automatically revises Item Quantity, Production Time and Restock Date in Amazon Listing
                    when there are changes made in Magento to at least one mentioned parameter.'
                )
            ]
        );

        $fieldset->addField(
            'revise_update_qty_max_applied_value_mode',
            self::SELECT,
            [
                'container_id' => 'revise_update_qty_max_applied_value_mode_tr',
                'name' => 'revise_update_qty_max_applied_value_mode',
                'label' => $this->__('Conditional Revise'),
                'value' => $formData['revise_update_qty_max_applied_value_mode'],
                'values' => [
                    0 => $this->__('Disabled'),
                    1 => $this->__('Revise When Less or Equal to'),
                ],
                'tooltip' => $this->__(
                    'Set the Item Quantity limit at which the Revise Action should be triggered.
                    It is recommended to keep this value relatively low, between 10 and 20 Items.'
                )
            ]
        )->setAfterElementHtml(<<<HTML
<input name="revise_update_qty_max_applied_value" id="revise_update_qty_max_applied_value"
       value="{$formData['revise_update_qty_max_applied_value']}" type="text"
       style="width: 72px; margin-left: 10px;"
       class="input-text admin__control-text required-entry M2ePro-validate-qty _required" />
HTML
        );

        $fieldset->addField(
            'revise_update_qty_max_applied_value_line_tr',
            self::SEPARATOR,
            []
        );

        $fieldset->addField(
            'revise_update_price',
            self::SELECT,
            [
                'name' => 'revise_update_price',
                'label' => $this->__('Price'),
                'value' => $formData['revise_update_price'],
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically revises Item Price, Minimum Advertised Price, Sale Price and Business Price
                    in Amazon Listing when there are changes made in Magento to at least one mentioned parameter.'
                )
            ]
        );

        $fieldset->addField(
            'revise_update_details',
            self::SELECT,
            [
                'name' => 'revise_update_details',
                'label' => $this->__('Details'),
                'value' => $formData['revise_update_details'],
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically revises Condition Note, Gift Message, Gift Wrap settings,
                    data from Description Policy, Shipping Template Policy and Product Tax Code Policy
                    in Amazon Listing when there are changes made to Magento Attribute
                    of at least one mentioned parameter.'
                )
            ]
        );

        $fieldset->addField(
            'revise_update_images',
            self::SELECT,
            [
                'name' => 'revise_update_images',
                'label' => $this->__('Images'),
                'value' => $formData['revise_update_images'],
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically revises Item Image in Amazon Listing if Product Image or Magento
                    Attribute value used for Product Image is changed in Magento.'
                )
            ]
        );

        $form->addField(
            'revise_qty_max_applied_value_confirmation_popup_template',
            self::CUSTOM_CONTAINER,
            [
                'text' => $this->__(
                    '<br/>Disabling this option might affect synchronization performance. Please read
             <a href="%url%" target="_blank">this article</a> before using the option.',
                    $this->getHelper('Module_Support')->getSupportUrl('knowledgebase/1579746/')
                ),
                'style' => 'display: none;'
            ]
        );

        $form->addField(
            'revise_update_details_or_images_confirmation_popup_template',
            self::CUSTOM_CONTAINER,
            [
                'text' => $this->__(
                    '<br/>Enabling this option might affect synchronization performance. Please read
             <a href="%url%" target="_blank">this article</a> before using the option.',
                    $this->getHelper('Module_Support')->getSupportUrl('knowledgebase/1580145/')
                ),
                'style' => 'display: none;'
            ]
        );

        $this->jsTranslator->add('Wrong value. Only integer numbers.', $this->__('Wrong value. Only integer numbers.'));

        $jsFormData = [
            'revise_update_qty',
            'revise_update_price',
            'revise_update_qty_max_applied_value',
        ];

        foreach ($jsFormData as $item) {
            $this->js->add("M2ePro.formData.$item = '{$this->getHelper('Data')->escapeJs($formData[$item])}';");
        }

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
