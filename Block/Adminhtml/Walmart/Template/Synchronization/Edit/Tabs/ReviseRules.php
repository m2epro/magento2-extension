<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\Synchronization\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Walmart\Template\Synchronization;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Synchronization\Edit\Tabs\ReviseRules
 */
class ReviseRules extends AbstractForm
{
    protected function _prepareForm()
    {
        $template = $this->getHelper('Data\GlobalData')->getValue('tmp_template');
        $formData = $template !== null
            ? array_merge($template->getData(), $template->getChildObject()->getData()) : [];

        $defaults = [
            'revise_update_qty'                              => 1,
            'revise_update_qty_max_applied_value_mode'       => 1,
            'revise_update_qty_max_applied_value'            => 5,
            'revise_update_price'                            => 1,
            'revise_update_price_max_allowed_deviation_mode' => 1,
            'revise_update_price_max_allowed_deviation'      => 3,
            'revise_update_promotions'                       => 0,
            'revise_update_details'                          => 0,
        ];

        $formData = array_merge($defaults, $formData);

        $form = $this->_formFactory->create();

        $form->addField(
            'walmart_template_synchronization_revise',
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
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/UABhAQ')
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_walmart_template_synchronization_form_data_revise_products',
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
                    'Automatically revises Item Quantity and Lag Time on Walmart when any changes are made
                    to the Selling Policy settings that define these Item properties or Magento Attribute
                    values used for these Item properties in the Selling Policy.'
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
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Enable to narrow the conditions under which the Item Quantity should be revised.
                    This allows optimizing the sync process.'
                )
            ]
        );

        $fieldset->addField(
            'revise_update_qty_max_applied_value_',
            'text',
            [
                'container_id' => 'revise_update_qty_max_applied_value_tr',
                'name' => 'revise_update_qty_max_applied_value',
                'label' => $this->__('Revise When Less or Equal to'),
                'value' => $formData['revise_update_qty_max_applied_value'],
                'class' => 'M2ePro-validate-qty',
                'required' => true,
                'tooltip' => $this->__(
                    'Set the Item Quantity limit at which the Revise Action should be triggered.
                    It is recommended to keep this value relatively low, between 10 and 20 Items.'
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
                'name' => 'revise_update_price',
                'label' => $this->__('Price'),
                'value' => $formData['revise_update_price'],
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically revises Item Price on Walmart when any changes are made to the
                    Selling Policy settings that define this Item property or Magento Attribute values
                    used for this Item property in the Selling Policy.'
                )
            ]
        );

        $fieldset->addField(
            'revise_update_price_max_allowed_deviation_mode',
            self::SELECT,
            [
                'container_id' => 'revise_update_price_max_allowed_deviation_mode_tr',
                'name' => 'revise_update_price_max_allowed_deviation_mode',
                'label' => $this->__('Conditional Revise'),
                'value' => $formData['revise_update_price_max_allowed_deviation_mode'],
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Enable to narrow the conditions under which the Item Price should be revised.
                    This allows optimizing the sync process.'
                )
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
                'name' => 'revise_update_price_max_allowed_deviation',
                'label' => $this->__('Revise When Deviation More or Equal than'),
                'value' => $formData['revise_update_price_max_allowed_deviation'],
                'values' => $preparedValues,
                'tooltip' => $this->__(
                    'Specify the percentage value of maximum possible deviation between Item Price
                    in Selling Policy and on Walmart that can be ignored. <br><br>

                    For example, your Magento Product Price is 23.25$. According to Selling Policy,
                    the Item Price is equal to Magento Product Price. The Revise When Deviation More or Equal
                    than option is set to 1% which equals to 0.23$. <br>
                    - If Magento Product Price is increased to 23.26$, i.e. by 0.1$, the Price value will not
                    be revised on Walmart as this Price change is within the allowable deviation, i.e. 0.23$.
                    <br>
                    - If Magento Product Price is increased to 23.5$, i.e. by 0.25$, the Price value will be
                    revised on Walmart as this Price change exceeds the allowable deviation, i.e. 0.23$.
                    <br>
                    After Walmart Item Price is successfully revised, the allowable deviation will be calculated
                    based on the new Price value which equals to 23.5$ in our example.
                    '
                )
            ]
        );

        $fieldset->addField(
            'revise_update_price_line',
            self::SEPARATOR,
            []
        );

        $fieldset->addField(
            'revise_update_promotions',
            self::SELECT,
            [
                'name' => 'revise_update_promotions',
                'label' => $this->__('Promotions'),
                'value' => $formData['revise_update_promotions'],
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically revises Promotions on Walmart when any changes are made to
                    the Selling Policy settings that define Promotion properties or Magento Attribute
                    values used for Promotion properties in the Selling Policy.'
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
                    'Data will be automatically revised on Walmart Listing(s) if changes are made to the
                    Magento Attributes related to Image, Description, or Selling Settings.'
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
