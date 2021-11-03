<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Synchronization\Edit\Form\Tabs;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Synchronization\Edit\Form\Tabs\ReviseRules
 */
class ReviseRules extends AbstractTab
{
    protected function _prepareForm()
    {
        $default = $this->modelFactory->getObject('Ebay_Template_Synchronization_Builder')->getDefaultData();
        $formData = $this->getFormData();

        $formData = array_merge($default, $formData);

        $form = $this->_formFactory->create();

        $form->addField(
            'ebay_template_synchronization_form_data_revise',
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
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/PwItAQ')
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_template_synchronization_form_data_revise_products',
            [
                'legend' => $this->__('Revise Conditions'),
                'collapsable' => true
            ]
        );

        $fieldset->addField(
            'revise_update_qty',
            self::SELECT,
            [
                'name' => 'synchronization[revise_update_qty]',
                'label' => $this->__('Quantity'),
                'value' => $formData['revise_update_qty'],
                'values' => [
                    1 => $this->__('Yes'),
                ],
                'disabled' => true,
                'tooltip' => $this->__(
                    'Automatically revises Item Quantity on eBay when Product Quantity, Magento Attribute
                    used for Item Quantity or Custom Quantity value are modified in Magento or Policy Template.
                    The Quantity management is the basic functionality the Magento-to-eBay integration is based on
                    and it cannot be disabled.'
                )
            ]
        );

        $fieldset->addField(
            'revise_update_qty_max_applied_value_mode',
            self::SELECT,
            [
                'container_id' => 'revise_update_qty_max_applied_value_mode_tr',
                'name' => 'synchronization[revise_update_qty_max_applied_value_mode]',
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
<input name="synchronization[revise_update_qty_max_applied_value]" id="revise_update_qty_max_applied_value"
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
                'name' => 'synchronization[revise_update_price]',
                'label' => $this->__('Price'),
                'value' => $formData['revise_update_price'],
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically revises Item Price on eBay when Product Price, Special Price or Magento Attribute
                    used for Item Price are modified in Magento or Policy Template.'
                )
            ]
        );

        $fieldset->addField(
            'revise_update_title',
            self::SELECT,
            [
                'name' => 'synchronization[revise_update_title]',
                'label' => $this->__('Title'),
                'value' => $formData['revise_update_title'],
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically revises Item Title on eBay when Product Name, Magento Attribute used for Item Title
                    or Custom Title value are modified in Magento or Policy Template.'
                )
            ]
        );

        $fieldset->addField(
            'revise_update_sub_title',
            self::SELECT,
            [
                'name' => 'synchronization[revise_update_sub_title]',
                'label' => $this->__('Subtitle'),
                'value' => $formData['revise_update_sub_title'],
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically revises Item Subtitle on eBay when Magento Attribute used for Item Subtitle or
                    Custom Subtitle value are modified in Magento or Policy Template.'
                )
            ]
        );

        $fieldset->addField(
            'revise_update_description',
            self::SELECT,
            [
                'name' => 'synchronization[revise_update_description]',
                'label' => $this->__('Description'),
                'value' => $formData['revise_update_description'],
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically revises Item Description on eBay when Product Description, Product Short
                    Description or Custom Description value are modified in Magento or Policy Template.'
                )
            ]
        );

        $fieldset->addField(
            'revise_update_images',
            self::SELECT,
            [
                'name' => 'synchronization[revise_update_images]',
                'label' => $this->__('Images'),
                'value' => $formData['revise_update_images'],
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically revises Item Image(s) on eBay when Product Image(s) or Magento Attribute used for
                    Product Image(s) are modified in Magento or Policy Template.'
                )
            ]
        );

        $fieldset->addField(
            'revise_update_categories',
            self::SELECT,
            [
                'name' => 'synchronization[revise_update_categories]',
                'label' => $this->__('Categories / Specifics'),
                'value' => $formData['revise_update_categories'],
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically revises Item Categories/Specifics on eBay when Categories/Specifics data or Magento
                    Attributes used for Categories/Specifics are modified.'
                )
            ]
        );

        $fieldset->addField(
            'revise_update_parts',
            self::SELECT,
            [
                'name' => 'synchronization[revise_update_parts]',
                'label' => $this->__('eBay Parts Compatibility'),
                'value' => $formData['revise_update_parts'],
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically revises Parts Compatibility data on eBay once the related data is modified.'
                )
            ]
        );

        $fieldset->addField(
            'revise_update_shipping',
            self::SELECT,
            [
                'name' => 'synchronization[revise_update_shipping]',
                'label' => $this->__('Shipping'),
                'value' => $formData['revise_update_shipping'],
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically revises Item Shipping information on eBay when the Shipping Policy Template or
                    Magento Attributes used in Shipping Policy Template are modified.'
                )
            ]
        );

        $fieldset->addField(
            'revise_update_payment',
            self::SELECT,
            [
                'name' => 'synchronization[revise_update_payment]',
                'label' => $this->__('Payment'),
                'value' => $formData['revise_update_payment'],
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically revises Item Payment information on eBay when Payment Policy Template is modified.'
                )
            ]
        );

        $fieldset->addField(
            'revise_update_return',
            self::SELECT,
            [
                'name' => 'synchronization[revise_update_return]',
                'label' => $this->__('Return'),
                'value' => $formData['revise_update_return'],
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically revises Item Return information on eBay when Return Policy Template is modified.'
                )
            ]
        );

        $fieldset->addField(
            'revise_update_other',
            self::SELECT,
            [
                'name' => 'synchronization[revise_update_other]',
                'label' => $this->__('Other'),
                'value' => $formData['revise_update_other'],
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically revises Item Condition, Condition Note, Lot Size, Taxation, Best Offer, and Charity
                    information on eBay when the related data is modified in Policy Templates.'
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

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
