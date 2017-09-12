<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Synchronization\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Amazon\Template\Synchronization;

class ReviseRules extends AbstractForm
{
    protected function _prepareForm()
    {
        $template = $this->getHelper('Data\GlobalData')->getValue('tmp_template');
        $formData = !is_null($template)
            ? array_merge($template->getData(), $template->getChildObject()->getData()) : [];

        $defaults = array(
            'revise_update_qty'                              => Synchronization::REVISE_UPDATE_QTY_YES,
            'revise_update_qty_max_applied_value_mode'       => Synchronization::REVISE_MAX_AFFECTED_QTY_MODE_ON,
            'revise_update_qty_max_applied_value'
                    => Synchronization::REVISE_UPDATE_QTY_MAX_APPLIED_VALUE_DEFAULT,
            'revise_update_price'                            => Synchronization::REVISE_UPDATE_PRICE_YES,
            'revise_update_price_max_allowed_deviation_mode'
                    => Synchronization::REVISE_MAX_ALLOWED_PRICE_DEVIATION_MODE_ON,
            'revise_update_price_max_allowed_deviation'
                    => Synchronization::REVISE_UPDATE_PRICE_MAX_ALLOWED_DEVIATION_DEFAULT,
            'revise_update_details'                          => Synchronization::REVISE_UPDATE_DETAILS_NONE,
            'revise_update_images'                           => Synchronization::REVISE_UPDATE_IMAGES_NONE,
            'revise_change_selling_format_template'
                    => \Ess\M2ePro\Model\Template\Synchronization::REVISE_CHANGE_SELLING_FORMAT_TEMPLATE_YES,
            'revise_change_description_template'
                    => Synchronization::REVISE_CHANGE_DESCRIPTION_TEMPLATE_NONE,
            'revise_change_shipping_template'
                    => Synchronization::REVISE_CHANGE_SHIPPING_TEMPLATE_YES,
            'revise_change_product_tax_code_template'
                    => Synchronization::REVISE_CHANGE_PRODUCT_TAX_CODE_TEMPLATE_YES,
            'revise_change_listing'
                    => \Ess\M2ePro\Model\Template\Synchronization::REVISE_CHANGE_LISTING_YES
        );

        $formData = array_merge($defaults, $formData);

        $form = $this->_formFactory->create();

        $form->addField(
            'amazon_template_synchronization_revise',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    <<<HTML
<p>
Revise Rules are specific conditions that trigger an automatic full or partial Product data revise if met.
</p>

<ul>
    <li><p>
        <strong>Partial Revise</strong> - Amazon Item will be partially updated based on the changes made to
        certain Magento Product parameter you have enabled in Partial Revise settings.
    </p></li><br>
    <li><p>
        <strong>Full Revise</strong> - Amazon Item will be fully updated after the changes made to at least
        one parameter of M2E Pro settings you have specified for this Item and enabled in Full Revise
        settings (Listing Settings, Selling Format Policy, Description Policy, Shipping Policy,
        Product Tax Code Policy).
    </p></li>
</ul>

<p>More detailed information on how to work with this Page you can find
    <a href="%url%" target="_blank" class="external-link">here</a>.
</p>
HTML
                    ,
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/RwItAQ')
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_template_synchronization_form_data_revise_products',
            [
                'legend' => $this->__('Partial Revise'),
                'collapsable' => true
            ]
        );

        $fieldset->addField('revise_update_qty',
            self::SELECT,
            [
                'name' => 'revise_update_qty',
                'label' => $this->__('Quantity'),
                'value' => $formData['revise_update_qty'],
                'values' => [
                    Synchronization::REVISE_UPDATE_QTY_NONE => $this->__('No'),
                    Synchronization::REVISE_UPDATE_QTY_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically revises Item Quantity, Production Time and Restock Date in Amazon Listing
                    when there are changes made in Magento to at least one mentioned parameter.'
                )
            ]
        );

        $fieldset->addField('revise_update_qty_max_applied_value_mode',
            self::SELECT,
            [
                'container_id' => 'revise_update_qty_max_applied_value_mode_tr',
                'name' => 'revise_update_qty_max_applied_value_mode',
                'label' => $this->__('Conditional Revise'),
                'value' => $formData['revise_update_qty_max_applied_value_mode'],
                'values' => [
                    Synchronization::REVISE_MAX_AFFECTED_QTY_MODE_OFF => $this->__('No'),
                    Synchronization::REVISE_MAX_AFFECTED_QTY_MODE_ON => $this->__('Yes'),
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

        $fieldset->addField('revise_update_price',
            self::SELECT,
            [
                'name' => 'revise_update_price',
                'label' => $this->__('Price'),
                'value' => $formData['revise_update_price'],
                'values' => [
                    Synchronization::REVISE_UPDATE_PRICE_NONE => $this->__('No'),
                    Synchronization::REVISE_UPDATE_PRICE_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically revises Item Price, Minimum Advertised Price, Sale Price and Business Price
                    in Amazon Listing when there are changes made in Magento to at least one mentioned parameter.'
                )
            ]
        );

        $fieldset->addField('revise_update_price_max_allowed_deviation_mode',
            self::SELECT,
            [
                'container_id' => 'revise_update_price_max_allowed_deviation_mode_tr',
                'name' => 'revise_update_price_max_allowed_deviation_mode',
                'label' => $this->__('Conditional Revise'),
                'value' => $formData['revise_update_price_max_allowed_deviation_mode'],
                'values' => [
                    Synchronization::REVISE_MAX_ALLOWED_PRICE_DEVIATION_MODE_OFF => $this->__('No'),
                    Synchronization::REVISE_MAX_ALLOWED_PRICE_DEVIATION_MODE_ON => $this->__('Yes'),
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

        $fieldset->addField('revise_update_price_max_allowed_deviation',
            self::SELECT,
            [
                'container_id' => 'revise_update_price_max_allowed_deviation_tr',
                'name' => 'revise_update_price_max_allowed_deviation',
                'label' => $this->__('Revise When Deviation More or Equal than'),
                'value' => $formData['revise_update_price_max_allowed_deviation'],
                'values' => $preparedValues,
                'tooltip' => $this->__('
                    It is a Percent Value of Maximum possible Deviation between Magento Price
                    (Price, Quantity and Format Policy settings) and Amazon Item Price, that can be ignored.<br/><br/>
                    <strong>For example</strong>, your Magento Price is 23.25$. According to
                    Price, Quantity and Format Policy Settings Item Price is equal to Magento Price.
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

        $fieldset->addField('revise_update_details',
            self::SELECT,
            [
                'name' => 'revise_update_details',
                'label' => $this->__('Details'),
                'value' => $formData['revise_update_details'],
                'values' => [
                    Synchronization::REVISE_UPDATE_DETAILS_NONE => $this->__('No'),
                    Synchronization::REVISE_UPDATE_DETAILS_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically revises Condition Note, Gift Message, Gift Wrap settings,
                    data from Description Policy, Shipping Template Policy and Product Tax Code Policy
                    in Amazon Listing when there are changes made to Magento Attribute
                    of at least one mentioned parameter.'
                )
            ]
        );

        $fieldset->addField('revise_update_images',
            self::SELECT,
            [
                'name' => 'revise_update_images',
                'label' => $this->__('Images'),
                'value' => $formData['revise_update_images'],
                'values' => [
                    Synchronization::REVISE_UPDATE_IMAGES_NONE => $this->__('No'),
                    Synchronization::REVISE_UPDATE_IMAGES_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically revises Item Image in Amazon Listing if Product Image or Magento
                    Attribute value used for Product Image is changed in Magento.'
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_synchronization_revise_templates',
            [
                'legend' => $this->__('Full Revise'),
                'collapsable' => true
            ]
        );

        $fieldset->addField('revise_change_listing',
            self::SELECT,
            [
                'name' => 'revise_change_listing',
                'label' => $this->__('Listing Settings'),
                'value' => $formData['revise_change_listing'],
                'values' => [
                    \Ess\M2ePro\Model\Template\Synchronization::REVISE_CHANGE_LISTING_NONE => $this->__('No'),
                    \Ess\M2ePro\Model\Template\Synchronization::REVISE_CHANGE_LISTING_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__('Automatically revises Amazon Listing(s) if its Listing Settings were changed.')
            ]
        );

        $fieldset->addField('revise_change_selling_format_template',
            self::SELECT,
            [
                'name' => 'revise_change_selling_format_template',
                'label' => $this->__('Price, Quantity and Format Policy'),
                'value' => $formData['revise_change_selling_format_template'],
                'values' => [
                    \Ess\M2ePro\Model\Template\Synchronization::REVISE_CHANGE_SELLING_FORMAT_TEMPLATE_NONE
                                => $this->__('No'),
                    \Ess\M2ePro\Model\Template\Synchronization::REVISE_CHANGE_SELLING_FORMAT_TEMPLATE_YES
                                => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically revises Amazon Listing(s) if its Price, Quantity and Format Policy was changed.'
                )
            ]
        );

        $fieldset->addField('revise_change_description_template',
            self::SELECT,
            [
                'name' => 'revise_change_description_template',
                'label' => $this->__('Description Policy'),
                'value' => $formData['revise_change_description_template'],
                'values' => [
                    Synchronization::REVISE_CHANGE_DESCRIPTION_TEMPLATE_NONE => $this->__('No'),
                    Synchronization::REVISE_CHANGE_DESCRIPTION_TEMPLATE_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__('Automatically revises Amazon Listing(s) if its Description Policy was changed.')
            ]
        );

        $fieldset->addField(
            'revise_change_shipping_template',
            self::SELECT,
            [
                'name' => 'revise_change_shipping_template',
                'label' => $this->__('Shipping Policy'),
                'value' => $formData['revise_change_shipping_template'],
                'values' => [
                    Synchronization::REVISE_CHANGE_SHIPPING_TEMPLATE_NONE => $this->__('No'),
                    Synchronization::REVISE_CHANGE_SHIPPING_TEMPLATE_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically revises Amazon Listing(s) if its Shipping Policy was changed.'
                )
            ]
        );

        $fieldset->addField(
            'revise_change_product_tax_code_template',
            self::SELECT,
            [
                'name' => 'revise_change_product_tax_code_template',
                'label' => $this->__('Product Tax Code Policy'),
                'value' => $formData['revise_change_product_tax_code_template'],
                'values' => [
                    Synchronization::REVISE_CHANGE_PRODUCT_TAX_CODE_TEMPLATE_NONE => $this->__('No'),
                    Synchronization::REVISE_CHANGE_PRODUCT_TAX_CODE_TEMPLATE_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically revises Amazon Listing(s) if its Product Tax Code Policy was changed.'
                )
            ]
        );

        $form->addField(
            'revise_qty_max_applied_value_confirmation_popup_template',
            self::CUSTOM_CONTAINER,
            [
                'text' => $this->__('
                    <br/>It is necessary to understand that Disabling this Option can <strong>negatively</strong>
                    influence on <strong>M2E Pro Performance</strong>.<br/><br/>
                    In case this Option is <strong>Disabled</strong>, M2E Pro will Revise the smallest changes
                    for High Quantity Values (e.g. from 515 to 514), that most likely has no practical effect.
                    It can be time-consuming and more important changes (e.g. from 1 to 0)
                    for another Product can be <strong>stayed in queue</strong> instead of immediate update.
                    Also it can cause increase of Order Import passivity up to 12 hours.<br/>
                    If you <strong>Enable</strong> "Conditional Revise" Option and "Revise When Less or
                    Equal to" Option is set to 5, M2E Pro will Revise your Products in realtime
                    format only when Magento Quantity will be less or equal 5.
                    Revise will not be run until the Quantity Value is more than 5.<br/><br/>
                    So M2E Pro <strong>does not recommend</strong> to Disable this Option and suggests
                    to specify for "Revise When Less or Equal to" Option Value 5 (The less Value = less Unuseful
                    Calls + more Performance of M2E Pro).<br/>
                    You can always change this Option Value according to your needs.<br/><br/>
                    <strong>Note:</strong> For Sellers who synchronize Magento
                    Inventory with Suppliers Inventory by any Automatic Software this Option is
                    <strong>critically required</strong>, as usually Supplier\'s Quantity has
                    Big Values and it is changed very often.
                '),
                'style' => 'display: none;'
            ]
        );

        $form->addField(
            'revise_price_max_max_allowed_deviation_confirmation_popup_template',
            self::CUSTOM_CONTAINER,
            [
                'text' => $this->__('
                    <br/>It is necessary to understand that Disabling this Option can <strong>negatively</strong>
                    nfluence on <strong>M2E Pro Performance</strong>.<br/><br/>
                    In case this Option is <strong>Disabled</strong>, M2E Pro will Revise the smallest changes
                    for Price Values (e.g. from 25.25$ to 25.26$), that most likely has no practical effect.
                    It can be time-consuming and more important changes (e.g. from 1$ to 50$) for another
                    Product can be <strong>stayed in queue</strong> instead of immediate update.
                    Also it can cause increase of Order Import passivity up to 12 hours.<br/>
                    If you <strong>Enable</strong> "Conditional Revise" Option and "Revise When Deviation More or
                    Equal than" set to 3%, M2E Pro will Revise your Products in realtime format only when Price
                    change will be more than 3% from Starting Price.<br/><br/>
                    So M2E Pro <strong>does not recommend</strong> to Disable this Option (The more value =
                    less Unusefull Calls + more Performance of M2E Pro).<br/>
                    You can always change this Option Value according to your needs.<br/><br/>
                    <strong>Note:</strong> For Sellers who synchronize Magento Inventory with Suppliers
                    Inventory by any Automatic Software this Option is <strong>critically required</strong>,
                    as Supplier\s Price Values are changed very often.
                '),
                'style' => 'display: none;'
            ]
        );

        $this->jsTranslator->add('Wrong value. Only integer numbers.', $this->__('Wrong value. Only integer numbers.'));

        $jsFormData = [
            'revise_update_qty',
            'revise_update_price',
            'revise_change_selling_format_template',
            'revise_change_listing',
            'revise_update_qty_max_applied_value',
        ];

        foreach ($jsFormData as $item) {
            $this->js->add("M2ePro.formData.$item = '{$this->getHelper('Data')->escapeJs($formData[$item])}';");
        }

        $this->setForm($form);

        return parent::_prepareForm();
    }
}