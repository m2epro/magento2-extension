<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\PickupStore\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use \Ess\M2ePro\Model\Ebay\Account\PickupStore;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Account\PickupStore\Edit\Tabs\StockSettings
 */
class StockSettings extends AbstractForm
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayAccountPickupStoreEditTabsStockSettings');
        // ---------------------------------------
    }

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();
        $formData = $this->getFormData();

        /** @var \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper */
        $magentoAttributeHelper = $this->getHelper('Magento\Attribute');

        $attributes = $magentoAttributeHelper->getGeneralFromAttributeSets(
            $this->getHelper('Magento\AttributeSet')->getAll(\Ess\M2ePro\Helper\Magento\AbstractHelper::RETURN_TYPE_IDS)
        );

        $attributesByInputTypes = ['text' => $magentoAttributeHelper->filterByInputTypes($attributes, ['text']),];

        $form->addField(
            'block_notice_ebay_accounts_pickup_store_stock_settings',
            self::HELP_BLOCK,
            [
                'content' => $this->__('
                You should select the Quantity source for the Products in this Store. There are 2 Options to
                choose from:<br/>
                <ul class="list">
                    <li><strong>Selling Policy</strong> — uses the quantity settings provided in
                    the Selling Policy for the Product;</li>
                    <li><strong>Custom Settings</strong> —
                    allows you to select the Quantity source as well as specify
                    the Percentage and the Conditional Quantity Options.</li>
                </ul>
                Note: in case Custom Settings are used and there are a lot of Stores with multiple Products,
                 it can affect the Performace of your Magento system.
            ')
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_account_pickup_store_form_data_quantity_general',
            [
                'legend' => $this->__('General'), 'collapsable' => false
            ]
        );

        $fieldset->addField(
            'default_mode',
            self::SELECT,
            [
                'name' => 'default_mode',
                'label' => $this->__('Use From'),
                'values' => [
                    ['label' => $this->__('Selling Policy'), 'value' => 0],
                    ['label' => $this->__('Custom Settings'), 'value' => 1],
                ],
                'value' => (int)$formData['qty_mode'] != PickupStore::QTY_MODE_SELLING_FORMAT_TEMPLATE,
                'tooltip' => $this->__(
                    'Select the Quantity source for the Products in this Store. If you select the
                    Custom Settings with different Quantity sources and there are a lot of Products in
                    your Store, the Performance of your Magento can be affected.'
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_account_pickup_store_form_data_quantity_custom_settings',
            [
                'legend' => $this->__('Custom Settings'), 'collapsable' => false
            ]
        );

        $fieldset->addField(
            'performance_error_message_block',
            self::MESSAGES,
            [
                'messages' => [[
                    'type' => \Magento\Framework\Message\MessageInterface::TYPE_ERROR,
                    'content' => $this->__(
                        'Application of Custom Settings can have negative impact on the Performance of your Magento
                        if there are a lot of Stores and Products in these Stores.<br/>
                        Please, be <strong>very attentive</strong> when selecting Settings in this Block. If you
                        select the different Quantity sources and there are a lot of Products in your Store,
                        the <strong>Performance</strong> of your Magento <strong>can be affected</strong>.'
                    )
                ]]
            ]
        );

        $magentoAttributesValues = [
            ['label' => $this->__('QTY'), 'value' => PickupStore::QTY_MODE_PRODUCT_FIXED]
        ];

        if ($formData['qty_mode'] == PickupStore::QTY_MODE_ATTRIBUTE) {
            $magentoAttributesValues[] = [
                'label' => $magentoAttributeHelper->getAttributeLabel($formData['qty_custom_attribute']),
                'value' => PickupStore::QTY_MODE_ATTRIBUTE,
                'attrs' => [
                    'attribute_code' => $formData['qty_custom_attribute'],
                    'selected' => 'selected'
                ]
            ];
        }

        foreach ($attributesByInputTypes['text'] as $attribute) {
            $tmp = [
                'label' => $attribute['label'],
                'value' => PickupStore::QTY_MODE_ATTRIBUTE,
                'attrs' => [
                    'attribute_code' => $attribute['code'],
                ]
            ];

            if ($formData['qty_mode'] == PickupStore::QTY_MODE_ATTRIBUTE
                && $attribute['code'] == $formData['qty_custom_attribute']) {
                $tmp['attrs']['selected'] = 'selected';
            }

            $magentoAttributesValues[] = $tmp;
        }

        $values = [
            ['label' => $this->__('Product Quantity'), 'value' => PickupStore::QTY_MODE_PRODUCT],
            ['label' => $this->__('Single Item'), 'value' => PickupStore::QTY_MODE_SINGLE],
            ['label' => $this->__('Custom Value'), 'value' => PickupStore::QTY_MODE_NUMBER],
            [
                'label' => $this->__('Magento Attribute'),
                'value' => $magentoAttributesValues,
                'attrs' => [
                    'new_option_value' => PickupStore::QTY_MODE_ATTRIBUTE,
                    'is_magento_attribute' => true
                ]
            ]
        ];

        $fieldset->addField(
            'qty_mode',
            self::SELECT,
            [
                'name' => 'qty_mode',
                'label' => $this->__('Quantity'),
                'values' => $values,
                'value' => $formData['qty_mode'] != PickupStore::QTY_MODE_ATTRIBUTE
                    ? $formData['qty_mode'] : '',
                'create_magento_attribute' => true,
                'tooltip' => $this->__(
                    'The number of Items you want to sell on eBay.<br/><br/>
                    <b>Product Quantity:</b> the number of Items on eBay will be the same as in Magento.<br/>
                    <b>Single Item:</b> only one Item will be available on eBay.<br/>
                    <b>Custom Value:</b> set a Quantity in the Policy here.<br/>
                    <b>Magento Attribute:</b> takes the number from the Attribute you specify.'
                )
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField('qty_custom_attribute', 'hidden', ['name' => 'qty_custom_attribute']);

        $fieldset->addField(
            'qty_custom_value',
            'text',
            [
                'name' => 'qty_custom_value',
                'label' => $this->__('Quantity Value'),
                'value' => $formData['qty_custom_value'],
                'class' => 'input-text validate-digits',
                'field_extra_attributes' => 'id="qty_mode_cv_tr" style="display:none;"'
            ]
        );

        $values = [];
        for ($i = 100; $i >= 5; $i -= 5) {
            $values[] = [
                'label' => $i . ' %',
                'value' => $i
            ];
        }

        $fieldset->addField(
            'qty_percentage',
            self::SELECT,
            [
                'name' => 'qty_percentage',
                'label' => $this->__('Quantity Percentage'),
                'values' => $values,
                'value' => $formData['qty_percentage'],
                'required' => true,
                'class' => 'input-text validate-digits',
                'field_extra_attributes' => 'id="qty_percentage_tr"',
                'tooltip' => $this->__(
                    'Sets the percentage for calculation of Items number to be Listed on
                    eBay basing on Product Quantity or Magento Attribute. E.g., if Quantity Percentage is set
                    to 10% and Product Quantity is 100, the Quantity to be Listed on eBay will be calculated as <br/>
                    100 *10%  = 10.<br/>'
                )
            ]
        );

        $fieldset->addField(
            'qty_modification_mode',
            self::SELECT,
            [
                'name' => 'qty_modification_mode',
                'label' => $this->__('Conditional Quantity'),
                'values' => [
                    ['value' => 0, 'label' => $this->__('Disabled')],
                    ['value' => 1, 'label' => $this->__('Enabled')],
                ],
                'value' => $formData['qty_modification_mode'],
                'required' => true,
                'field_extra_attributes' => 'id="qty_modification_mode_tr"',
                'tooltip' => $this->__(
                    'Choose whether to limit the amount of Stock you list on eBay, eg because you want to set
                    some Stock aside for sales off eBay.<br/><br/>
                    If this Setting is <b>Enabled</b> you can specify the maximum Quantity to be Listed.
                    If this Setting is <b>Disabled</b> all Stock for the Product will be Listed as available on eBay.'
                )
            ]
        );

        $fieldset->addField(
            'qty_min_posted_value',
            'text',
            [
                'name' => 'qty_min_posted_value',
                'label' => $this->__('Minimum Quantity to Be Listed'),
                'value' => $formData['qty_min_posted_value'],
                'class' => 'validate-qty',
                'field_extra_attributes' => 'id="qty_min_posted_value_tr"',
                'tooltip' => $this->__(
                    'If you have 2 pieces in Stock but set a Minimum Quantity to Be Listed of 5,
                    Item will not be Listed on eBay.<br/>
                    Otherwise, the Item will be Listed with Quantity according to the Settings in the Selling Policy'
                )
            ]
        );

        $fieldset->addField(
            'qty_max_posted_value',
            'text',
            [
                'name' => 'qty_max_posted_value',
                'label' => $this->__('Maximum Quantity to Be Listed'),
                'value' => $formData['qty_max_posted_value'],
                'class' => 'validate-qty',
                'field_extra_attributes' => 'id="qty_max_posted_value_tr"',
                'tooltip' => $this->__(
                    'Set a maximum number to sell on eBay, e.g. if you have 10 Items in Stock but want to
                    keep 2 Items back, set a Maximum Quantity of 8.'
                )
            ]
        );

        $this->setForm($form);

        $this->js->add(<<<JS
            M2ePro.formData.qty_mode = {$formData['qty_mode']};
            M2ePro.formData.qty_modification_mode = {$formData['qty_modification_mode']};
JS
        );

        return parent::_prepareForm();
    }

    //########################################

    public function getFormData()
    {
        $default = [
            'qty_mode' => PickupStore::QTY_MODE_SELLING_FORMAT_TEMPLATE,
            'qty_custom_value' => 1,
            'qty_custom_attribute' => '',
            'qty_percentage' => 100,
            'qty_modification_mode' => 0,
            'qty_min_posted_value' => 1,
            'qty_max_posted_value' => 100
        ];

        $model = $this->getHelper('Data\GlobalData')->getValue('temp_data');
        if ($model === null) {
            return $default;
        }

        return array_merge($default, $model->toArray());
    }

    //########################################
}
