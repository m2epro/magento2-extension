<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Synchronization\Edit\Form\Tabs;

use Ess\M2ePro\Model\Ebay\Template\Synchronization;

class ReviseRules extends AbstractTab
{
    protected function _prepareForm()
    {
        $default = $this->activeRecordFactory->getObject('Ebay\Template\Synchronization')->getReviseDefaultSettings();
        $formData = $this->getFormData();

        $formData = array_merge($default, $formData);

        $form = $this->_formFactory->create();

        $form->addField('ebay_template_synchronization_form_data_revise',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    'Set the Conditions when M2E Pro should Revise Items on eBay.<br/><br/>
                    <b>Partial Revise</b> - if Conditions here are met, eBay Items will be updated with just the
                    specific data that has changed.<br/>
                    <b>Full Revise</b> - Conditions here relate to changes that are made to the
                    Policies made in the M2E Pro Listing.
                    If Conditions here are met, eBay Items will be updated in full, with all data being sent to eBay.
                    <br/><br/>
                    More detailed information about ability to work with this Page you can find
                    <a href="%url%" target="_blank" class="external-link">here</a>.',
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/PwItAQ')
                )
            ]
        );

        $fieldset = $form->addFieldset('magento_block_ebay_template_synchronization_form_data_revise_products',
            [
                'legend' => $this->__('Partial Revise'),
                'collapsable' => true
            ]
        );

        $fieldset->addField('revise_update_qty',
            self::SELECT,
            [
                'name' => 'synchronization[revise_update_qty]',
                'label' => $this->__('Quantity'),
                'value' => $formData['revise_update_qty'],
                'values' => [
                    Synchronization::REVISE_UPDATE_QTY_NONE => $this->__('No'),
                    Synchronization::REVISE_UPDATE_QTY_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically Revise the eBay Quantity if there is a change to the Quantity
                    (or the Attribute used for Quantity) in Magento.'
                )
            ]
        );

        $fieldset->addField('revise_update_qty_max_applied_value_mode',
            self::SELECT,
            [
                'container_id' => 'revise_update_qty_max_applied_value_mode_tr',
                'name' => 'synchronization[revise_update_qty_max_applied_value_mode]',
                'label' => $this->__('Conditional Revise'),
                'value' => $formData['revise_update_qty_max_applied_value_mode'],
                'values' => [
                    Synchronization::REVISE_MAX_AFFECTED_QTY_MODE_OFF => $this->__('No'),
                    Synchronization::REVISE_MAX_AFFECTED_QTY_MODE_ON => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Choose if you want to Revise Quantities on eBay only when certain Conditions are met.'
                )
            ]
        );

        $fieldset->addField('revise_update_qty_max_applied_value',
            'text',
            [
                'container_id' => 'revise_update_qty_max_applied_value_tr',
                'name' => 'synchronization[revise_update_qty_max_applied_value]',
                'label' => $this->__('Revise When Less or Equal to'),
                'value' => $formData['revise_update_qty_max_applied_value'],
                'class' => 'M2ePro-validate-qty',
                'required' => true,
                'tooltip' => $this->__(
                    'Set the Quantity of Stock when Revise Rules should be triggered.
                    We recommend keeping this value relatively low, with an initial value of anything from 10 to 20.'
                )
            ]
        );

        $fieldset->addField('revise_update_qty_max_applied_value_line_tr',
            self::SEPARATOR,
            []
        );

        $fieldset->addField('revise_update_price',
            self::SELECT,
            [
                'name' => 'synchronization[revise_update_price]',
                'label' => $this->__('Price'),
                'value' => $formData['revise_update_price'],
                'values' => [
                    Synchronization::REVISE_UPDATE_PRICE_NONE => $this->__('No'),
                    Synchronization::REVISE_UPDATE_PRICE_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically Revise the eBay Item Price if there is a change to the Price
                    (or the Attribute used for Price) in Magento.'
                )
            ]
        );

        $fieldset->addField('revise_update_price_max_allowed_deviation_mode',
            self::SELECT,
            [
                'container_id' => 'revise_update_price_max_allowed_deviation_mode_tr',
                'name' => 'synchronization[revise_update_price_max_allowed_deviation_mode]',
                'label' => $this->__('Conditional Revise'),
                'value' => $formData['revise_update_price_max_allowed_deviation_mode'],
                'values' => [
                    Synchronization::REVISE_MAX_ALLOWED_PRICE_DEVIATION_MODE_OFF => $this->__('No'),
                    Synchronization::REVISE_MAX_ALLOWED_PRICE_DEVIATION_MODE_ON => $this->__('Yes'),
                ],
                'tooltip' => $this->__('Updates eBay Price only when the Condition you set below is met.')
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
                'name' => 'synchronization[revise_update_price_max_allowed_deviation]',
                'label' => $this->__('Revise When Deviation More or Equal than'),
                'value' => $formData['revise_update_price_max_allowed_deviation'],
                'values' => $preparedValues,
                'tooltip' => $this->__('
                    It is a Percent Value of maximum possible Deviation between Magento Price
                    (Price, Quantity and Format Policy Settings) and eBay Item Price, that can be ignored.<br/><br/>
                    <strong>For example</strong>, your Magento Price is 23.25$. According to
                    Price, Quantity and Format Policy Settings Item Price is equal to Magento Price.
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

        $fieldset->addField('revise_update_price_line',
            self::SEPARATOR,
            []
        );

        $fieldset->addField('revise_update_title',
            self::SELECT,
            [
                'name' => 'synchronization[revise_update_title]',
                'label' => $this->__('Title'),
                'value' => $formData['revise_update_title'],
                'values' => [
                    Synchronization::REVISE_UPDATE_TITLE_NONE => $this->__('No'),
                    Synchronization::REVISE_UPDATE_TITLE_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically Revise the eBay Item Title if there is a change to the Title
                    (or the Attribute used for Title) in Magento.'
                )
            ]
        );

        $fieldset->addField('revise_update_sub_title',
            self::SELECT,
            [
                'name' => 'synchronization[revise_update_sub_title]',
                'label' => $this->__('Subtitle'),
                'value' => $formData['revise_update_sub_title'],
                'values' => [
                    Synchronization::REVISE_UPDATE_SUB_TITLE_NONE => $this->__('No'),
                    Synchronization::REVISE_UPDATE_SUB_TITLE_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically Revise the eBay Item Subtitle if there is a change to the Subtitle
                    (or the Attribute used for Subtitle) in Magento.'
                )
            ]
        );

        $fieldset->addField('revise_update_description',
            self::SELECT,
            [
                'name' => 'synchronization[revise_update_description]',
                'label' => $this->__('Description'),
                'value' => $formData['revise_update_description'],
                'values' => [
                    Synchronization::REVISE_UPDATE_DESCRIPTION_NONE => $this->__('No'),
                    Synchronization::REVISE_UPDATE_DESCRIPTION_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically Revise the eBay Item Description if there is a change to the Description
                    (or the Attribute used for Description) in Magento.'
                )
            ]
        );

        $fieldset->addField('revise_update_images',
            self::SELECT,
            [
                'name' => 'synchronization[revise_update_images]',
                'label' => $this->__('Images'),
                'value' => $formData['revise_update_images'],
                'values' => [
                    Synchronization::REVISE_UPDATE_IMAGES_NONE => $this->__('No'),
                    Synchronization::REVISE_UPDATE_IMAGES_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically Revise the eBay Item Gallery if there is a change to the Gallery
                    (or the Attribute used for gallery) in Magento.'
                )
            ]
        );

        $fieldset->addField('revise_update_specifics',
            self::SELECT,
            [
                'name' => 'synchronization[revise_update_specifics]',
                'label' => $this->__('Specifics'),
                'value' => $formData['revise_update_specifics'],
                'values' => [
                    Synchronization::REVISE_UPDATE_SPECIFICS_NONE => $this->__('No'),
                    Synchronization::REVISE_UPDATE_SPECIFICS_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically Revise the eBay Item Specifics if there was a change to the Attribute value
                    which is used in Specifics configurations.'
                )
            ]
        );

        $fieldset->addField('revise_update_shipping_services',
            self::SELECT,
            [
                'name' => 'synchronization[revise_update_shipping_services]',
                'label' => $this->__('Shipping Services'),
                'value' => $formData['revise_update_shipping_services'],
                'values' => [
                    Synchronization::REVISE_UPDATE_SHIPPING_SERVICES_NONE => $this->__('No'),
                    Synchronization::REVISE_UPDATE_SHIPPING_SERVICES_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically Revise the Shipping Services data if there was a change to the Attribute value used
                    for Cost, Additional Cost and Surcharge options configurations.'
                )
            ]
        );

        $fieldset = $form->addFieldset('magento_block_ebay_template_synchronization_form_data_revise_templates',
            [
                'legend' => $this->__('Full Revise'),
                'collapsable' => true
            ]
        );

        $fieldset->addField('revise_change_selling_format_template',
            self::SELECT,
            [
                'name' => 'synchronization[revise_change_selling_format_template]',
                'label' => $this->__('Price, Quantity and Format'),
                'value' => $formData['revise_change_selling_format_template'],
                'values' => [
                    \Ess\M2ePro\Model\Template\Synchronization::REVISE_CHANGE_SELLING_FORMAT_TEMPLATE_NONE
                        => $this->__('No'),
                    \Ess\M2ePro\Model\Template\Synchronization::REVISE_CHANGE_SELLING_FORMAT_TEMPLATE_YES
                        => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically Revise the eBay Item if there is a change to the Price,
                    Quantity and Format Policy in this M2E Pro Listing.'
                )
            ]
        );

        $fieldset->addField('revise_change_description_template',
            self::SELECT,
            [
                'name' => 'synchronization[revise_change_description_template]',
                'label' => $this->__('Description'),
                'value' => $formData['revise_change_description_template'],
                'values' => [
                    Synchronization::REVISE_CHANGE_DESCRIPTION_TEMPLATE_NONE => $this->__('No'),
                    Synchronization::REVISE_CHANGE_DESCRIPTION_TEMPLATE_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically Revise the eBay Item if there is a change to the
                    Description Policy in this M2E Pro Listing.'
                )
            ]
        );

        $fieldset->addField('revise_change_category_template',
            self::SELECT,
            [
                'name' => 'synchronization[revise_change_category_template]',
                'label' => $this->__('Category/Specifics'),
                'value' => $formData['revise_change_category_template'],
                'values' => [
                    Synchronization::REVISE_CHANGE_CATEGORY_TEMPLATE_NONE => $this->__('No'),
                    Synchronization::REVISE_CHANGE_CATEGORY_TEMPLATE_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically Revise the eBay Item if there is a change to the
                    Category/specifics made in Magento or M2E Pro.'
                )
            ]
        );

        $fieldset->addField('revise_change_payment_template',
            self::SELECT,
            [
                'name' => 'synchronization[revise_change_payment_template]',
                'label' => $this->__('Payment'),
                'value' => $formData['revise_change_payment_template'],
                'values' => [
                    Synchronization::REVISE_CHANGE_PAYMENT_TEMPLATE_NONE => $this->__('No'),
                    Synchronization::REVISE_CHANGE_PAYMENT_TEMPLATE_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically Revise the eBay Item if there
                    is a change to the Payment Policy in this M2E Pro Listing.'
                )
            ]
        );

        $fieldset->addField('revise_change_shipping_template',
            self::SELECT,
            [
                'name' => 'synchronization[revise_change_shipping_template]',
                'label' => $this->__('Shipping'),
                'value' => $formData['revise_change_shipping_template'],
                'values' => [
                    Synchronization::REVISE_CHANGE_SHIPPING_TEMPLATE_NONE => $this->__('No'),
                    Synchronization::REVISE_CHANGE_SHIPPING_TEMPLATE_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically Revise the eBay Item if there is a change to
                    the Shipping Policy in this M2E Pro Listing.'
                )
            ]
        );

        $fieldset->addField('revise_change_return_policy_template',
            self::SELECT,
            [
                'name' => 'synchronization[revise_change_return_policy_template]',
                'label' => $this->__('Return'),
                'value' => $formData['revise_change_return_policy_template'],
                'values' => [
                    Synchronization::REVISE_CHANGE_RETURN_TEMPLATE_NONE => $this->__('No'),
                    Synchronization::REVISE_CHANGE_RETURN_TEMPLATE_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically Revise the eBay
                    Item if there is a change to the Return Policy in this M2E Pro Listing.'
                )
            ]
        );

        $form->addField('revise_qty_max_applied_value_confirmation_popup_template',
            self::CUSTOM_CONTAINER,
            [
                'text' => $this->__('
                    <br/>It is necessary to understand that Disabling this Option can <strong>negatively</strong>
                    influence on <strong>M2E Pro Performance</strong>.<br/><br/>
                    In case this Option is <strong>Disabled</strong>, M2E Pro will Revise the smallest changes
                    for high Quantity Values (e.g. from 515 to 514), that most likely has no practical effect.
                    It can be time-consuming and more important changes (e.g. from 1 to 0) for another Product can
                    be <strong>stayed in queue</strong> instead of immediate update. Also it can cause increase of
                    Order Import passivity up to 12 hours.<br/>
                    If you <strong>Enable</strong> "Conditional Revise" Option and "Revise When Less or Equal to"
                    Option is set to 5, M2E Pro will Revise your Products in realtime format only when Magento
                    Quantity will be less or equal 5. Revise will not be run until the Quantity Value is more than 5.
                    <br/><br/>
                    So M2E Pro <strong>does not recommend</strong> to Disable this Option and suggests to specify
                    for "Revise When Less or Equal to" Option Value 5 (The less value = less Unuseful Calls +
                    more Performance of M2E Pro).<br/>
                    You can always change this Option Value according to your needs.<br/><br/>
                    <strong>Note</strong>: For Sellers who synchronize Magento Inventory with Suppliers Inventory
                    by any Automatic Software this Option is <strong>critically required</strong>,
                    as usually Supplier\'s Quantity has Big Values and it is changed very often.
                '),
                'style' => 'display: none;'
            ]
        );

        $form->addField('revise_price_max_max_allowed_deviation_confirmation_popup_template',
            self::CUSTOM_CONTAINER,
            [
                'text' => $this->__('
                    <br/>It is necessary to understand that Disabling this Option can <strong>negatively</strong>
                    influence on <strong>M2E Pro Performance</strong>.<br/><br/>
                    In case this Option is <strong>Disabled</strong>, M2E Pro will Revise the smallest changes for
                    Price Values (e.g. from 25.25$ to 25.26$), that most likely has no practical effect.
                    It can be time-consuming and more important changes (e.g. from 1$ to 50$) for another Product
                    can be <strong>stayed in queue</strong> instead of immediate update.
                    Also it can cause increase of Order Import passivity up to 12 hours.<br/>
                    If you <strong>Enable</strong> "Conditional Revise" Option and "Revise When Deviation More or
                    Equal than" set to 3%, M2E Pro will Revise your Products in realtime format only when
                    Price change will be more than 3% from Starting Price.<br/><br/>
                    So M2E Pro <strong>does not recommend</strong> to Disable this Option (The more value = less
                    Unusefull Calls + more Performance of M2E Pro).<br/>
                    You can always change this Option Value according to your needs.<br/><br/>
                    <strong>Note</strong>: For Sellers who synchronize Magento Inventory with Suppliers
                    Inventory by any Automatic Software this Option is <strong>critically required</strong>,
                    as Supplier\'s Price Values are changed very often.
                '),
                'style' => 'display: none;'
            ]
        );

        $this->setForm($form);

        return parent::_prepareForm();
    }
}