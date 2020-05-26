<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Synchronization\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Amazon\Template\Synchronization;

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

        $defaults = [
            'revise_update_qty'                              => 1,
            'revise_update_qty_max_applied_value_mode'       => 1,
            'revise_update_qty_max_applied_value'            => 5,
            'revise_update_price'                            => 1,
            'revise_update_price_max_allowed_deviation_mode' => 1,
            'revise_update_price_max_allowed_deviation'      => 3,
            'revise_update_details'                          => 0,
            'revise_update_images'                           => 0,
        ];

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
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Updates Amazon QTY only when the Condition you set below is met.
                    <br/><br/><b>Note:</b> By using this Option you can significantly increase Synchronization
                    performance.'
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
                    'The value should not be too high (i.e. 100). Recommended value is in range 10 - 20.'
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
                    'Automatically revises Item Price, Minimum Advertised Price, Sale Price and Business Price
                    in Amazon Listing when there are changes made in Magento to at least one mentioned parameter.'
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
                'tooltip' => $this->__('Updates Amazon Price only when the Condition you set below is met.')
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
                'tooltip' => $this->__('
                    It is a Percent Value of Maximum possible Deviation between Magento Price
                    (Selling Policy settings) and Amazon Item Price, that can be ignored.<br/><br/>
                    <strong>For example</strong>, your Magento Price is 23.25$. According to
                    Selling Policy Settings Item Price is equal to Magento Price.
                    The "Revise When Deviation More or Equal than" Option is specified to 1%.<br/>
                    1) If Magento Price was changed to 23.26$, possible Deviation Value (0.23$) is
                    <strong>more</strong> than Price change (0.1$), so the Price <strong>will not be Revised</strong>
                    on Amazon.<br/>
                    2) If Magento Price was changed to 23.5$, possible Deviation Value (0.23$) is
                    <strong>less</strong> than Price change (0.25$), so the Price
                    <strong>will be Revised</strong> on Amazon.<br/><br/>
                    After Successful Revise new Magento Price (in this case is 23.5$)
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
