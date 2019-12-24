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
            'revise_update_qty'                              =>
                Synchronization::REVISE_UPDATE_QTY_YES,
            'revise_update_qty_max_applied_value_mode'       =>
                Synchronization::REVISE_MAX_AFFECTED_QTY_MODE_ON,
            'revise_update_qty_max_applied_value'            =>
                Synchronization::REVISE_UPDATE_QTY_MAX_APPLIED_VALUE_DEFAULT,
            'revise_update_price'                            =>
                Synchronization::REVISE_UPDATE_PRICE_YES,
            'revise_update_price_max_allowed_deviation_mode' =>
                Synchronization::REVISE_MAX_ALLOWED_PRICE_DEVIATION_MODE_ON,
            'revise_update_price_max_allowed_deviation'      =>
                Synchronization::REVISE_UPDATE_PRICE_MAX_ALLOWED_DEVIATION_DEFAULT,
            'revise_update_promotions'                       =>
                Synchronization::REVISE_UPDATE_PROMOTIONS_NONE,
        ];

        $formData = array_merge($defaults, $formData);

        $form = $this->_formFactory->create();

        $form->addField(
            'walmart_template_synchronization_revise',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    <<<HTML
<p>
Define the Revise Conditions based on which M2E Pro will automatically revise your Items on Walmart.
</p><br>

<p>Selected Item properties will be automatically updated when any changes are made to the Policy
settings that define these Item properties or Magento Attribute values used for these Item properties
in the Policy template.</p><br>

<p><strong>Note:</strong> M2E Pro Listing Synchronization must be enabled under
<i>Walmart Integration > Configuration > Settings > Synchronization</i>. Otherwise,
Synchronization Rules will not take effect.
</p>
HTML
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
                    Synchronization::REVISE_UPDATE_QTY_NONE => $this->__('No'),
                    Synchronization::REVISE_UPDATE_QTY_YES => $this->__('Yes'),
                ],
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
                    Synchronization::REVISE_MAX_AFFECTED_QTY_MODE_OFF => $this->__('No'),
                    Synchronization::REVISE_MAX_AFFECTED_QTY_MODE_ON => $this->__('Yes'),
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
                    Synchronization::REVISE_UPDATE_PRICE_NONE => $this->__('No'),
                    Synchronization::REVISE_UPDATE_PRICE_YES => $this->__('Yes'),
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
                    Synchronization::REVISE_MAX_ALLOWED_PRICE_DEVIATION_MODE_OFF => $this->__('No'),
                    Synchronization::REVISE_MAX_ALLOWED_PRICE_DEVIATION_MODE_ON => $this->__('Yes'),
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
                    Synchronization::REVISE_UPDATE_PROMOTIONS_NONE => $this->__('No'),
                    Synchronization::REVISE_UPDATE_PROMOTIONS_YES => $this->__('Yes'),
                ],
                'tooltip' => $this->__(
                    'Automatically revises Promotions on Walmart when any changes are made to
                    the Selling Policy settings that define Promotion properties or Magento Attribute
                    values used for Promotion properties in the Selling Policy.'
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
            'revise_update_qty_max_applied_value',
        ];

        foreach ($jsFormData as $item) {
            $this->js->add("M2ePro.formData.$item = '{$this->getHelper('Data')->escapeJs($formData[$item])}';");
        }

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
