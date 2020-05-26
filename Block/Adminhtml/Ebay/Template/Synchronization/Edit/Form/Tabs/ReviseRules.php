<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Synchronization\Edit\Form\Tabs;

use Ess\M2ePro\Model\Ebay\Template\Synchronization;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Synchronization\Edit\Form\Tabs\ReviseRules
 */
class ReviseRules extends AbstractTab
{
    protected function _prepareForm()
    {
        $default = $this->activeRecordFactory->getObject('Ebay_Template_Synchronization')->getReviseDefaultSettings();
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
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Choose if you want to Revise Quantities on eBay only when certain Conditions are met.'
                )
            ]
        );

        $fieldset->addField(
            'revise_update_qty_max_applied_value',
            'text',
            [
                'container_id' => 'revise_update_qty_max_applied_value_tr',
                'name' => 'synchronization[revise_update_qty_max_applied_value]',
                'label' => $this->__('Revise When Less or Equal to'),
                'value' => $formData['revise_update_qty_max_applied_value'],
                'class' => 'M2ePro-validate-qty',
                'required' => true,
                'tooltip' => $this->__(
                    'Set the Quantity In Stock limit at which the Revise Action should be triggered. 
                    We recommend keeping this value relatively low, between 10 and 20 Items.'
                )
            ]
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
            'revise_update_price_max_allowed_deviation_mode',
            self::SELECT,
            [
                'container_id' => 'revise_update_price_max_allowed_deviation_mode_tr',
                'name' => 'synchronization[revise_update_price_max_allowed_deviation_mode]',
                'label' => $this->__('Conditional Revise'),
                'value' => $formData['revise_update_price_max_allowed_deviation_mode'],
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'tooltip' => $this->__('Set \'Yes\' to narrow the conditions under which the Item Price should be 
                revised. It allows optimizing the sync process.')
            ]
        );

        $preparedValues = [];
        $percentageStep = 0.5;
        for ($priceDeviationValue = 0.5; $priceDeviationValue <= 20; $priceDeviationValue += $percentageStep) {
            $preparedValues[] = [
                'label' => $priceDeviationValue . ' %',
                'value' => $priceDeviationValue
            ];
            $priceDeviationValue >= 5 && $percentageStep = 1;
        }

        $fieldset->addField(
            'revise_update_price_max_allowed_deviation',
            self::SELECT,
            [
                'container_id' => 'revise_update_price_max_allowed_deviation_tr',
                'name' => 'synchronization[revise_update_price_max_allowed_deviation]',
                'label' => $this->__('Revise When Deviation More or Equal than'),
                'value' => $formData['revise_update_price_max_allowed_deviation'],
                'values' => $preparedValues,
                'tooltip' => $this->__('
                    It is a Percent Value of maximum possible Deviation between Magento Price
                    (Selling Policy Settings) and eBay Item Price, that can be ignored.<br/><br/>
                    <strong>For example</strong>, your Magento Price is 23.25$. According to
                    Selling Policy Settings Item Price is equal to Magento Price.
                    The "Revise When Deviation More or Equal than" Option is specified to 1%.<br/>
                    1) If Magento Price was changed to 23.26$, possible Deviation Value (0.23$) is
                    <strong>more</strong> than Price change (0.1$), so the Price
                    <strong>will not be Revised</strong> on eBay.<br/>
                    2) If Magento Price was changed to 23.5$, possible Deviation Value (0.23$) is
                    <strong>less</strong> than Price change (0.25$), so the Price <strong>will be Revised</strong>
                    on eBay.<br/><br/>
                    After successful Revise new Magento Price (in this case is 23.5$)
                    will be used for further Deviation count.
                ')
            ]
        );

        $fieldset->addField(
            'revise_update_price_line',
            self::SEPARATOR,
            []
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

        $form->addField(
            'revise_price_max_max_allowed_deviation_confirmation_popup_template',
            self::CUSTOM_CONTAINER,
            [
                'text' => $this->__('
                    Disabling this option might affect synchronization performance.
                     Please read this <a href="%url%" target="_blank">article</a> before using the option.
                ', $this->getHelper('Module\Support')->getSupportUrl('knowledgebase/1587081/')),
                'style' => 'display: none;'
            ]
        );

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
