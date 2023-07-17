<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\SellingFormat\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Button;
use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Block\Adminhtml\Walmart\Template\SellingFormat\Edit\Form\Promotions;
use Ess\M2ePro\Block\Adminhtml\Walmart\Template\SellingFormat\Edit\Form\ShippingOverrideRules;
use Ess\M2ePro\Model\Walmart\Template\SellingFormat;

class Form extends AbstractForm
{
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;

    public $templateModel;
    public $formData = [];

    public $allAttributesByInputTypes = [];
    public $allAttributes = [];

    /** @var \Ess\M2ePro\Helper\Magento\Attribute */
    protected $magentoAttributeHelper;

    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseHelper;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    /** @var \Ess\M2ePro\Helper\Component\Walmart */
    private $walmartHelper;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Component\Walmart $walmartHelper,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->magentoAttributeHelper = $magentoAttributeHelper;
        $this->supportHelper = $supportHelper;
        $this->databaseHelper = $databaseHelper;
        $this->dataHelper = $dataHelper;
        $this->globalDataHelper = $globalDataHelper;
        $this->walmartHelper = $walmartHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartTemplateSellingFormatEditForm');
        // ---------------------------------------

        $this->templateModel = $this->globalDataHelper->getValue('tmp_template');
        $this->formData = $this->getFormData();

        $this->allAttributes = $this->magentoAttributeHelper->getAll();
        $this->allAttributesByInputTypes = [
            'text' => $this->magentoAttributeHelper
                ->filterByInputTypes($this->allAttributes, ['text']),
            'text_select' => $this->magentoAttributeHelper
                ->filterByInputTypes($this->allAttributes, ['text', 'select']),
            'text_price' => $this->magentoAttributeHelper
                ->filterByInputTypes($this->allAttributes, ['text', 'price']),
            'text_date' => $this->magentoAttributeHelper
                ->filterByInputTypes($this->allAttributes, ['text', 'date']),
            'text_weight' => $this->magentoAttributeHelper
                ->filterByInputTypes($this->allAttributes, ['text', 'weight']),
            'boolean' => $this->magentoAttributeHelper
                ->filterByInputTypes($this->allAttributes, ['boolean']),
        ];
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Walmart\Template\SellingFormat\Edit\Form
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm(): Form
    {
        $form = $this->_formFactory->create([
            'data' => [
                'id' => 'edit_form',
                'method' => 'post',
                'action' => $this->getUrl('*/*/save'),
                'enctype' => 'multipart/form-data',
            ],
        ]);

        $form->addField(
            'walmart_template_selling_format_help',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    <<<HTML
                    <p>Using Selling Policy, you should define conditions for selling your products on Walmart,
                    such as Price, Quantity, Shipping settings, etc.</p><br>

                    <p>Selling Policy is created per marketplace and cannot be changed once the Policy is assigned
                    to M2E Pro Listing.</p><br>

                    <p>Head over to <a href="%url%" target="_blank" class="external-link">docs</a>
                    for detailed information.</p><br>
HTML
                    ,
                    $this->supportHelper->getDocumentationArticleUrl('selling-policy')
                ),
            ]
        );

        // ---------------------------------------

        $fieldset = $form->addFieldset(
            'general_fieldset',
            ['legend' => __('General'), 'collapsable' => false]
        );

        // ---------------------------------------

        $fieldset->addField(
            'title',
            'text',
            [
                'name' => 'title',
                'label' => __('Title'),
                'title' => __('Title'),
                'value' => $this->formData['title'],
                'class' => 'input-text M2ePro-price-tpl-title',
                'required' => true,
                'tooltip' => __('Policy Title for your internal use.'),
            ]
        );

        // ---------------------------------------

        $isLockedMarketplace = !empty($this->formData['marketplace_id']);

        $fieldset->addField(
            'marketplace_id',
            'select',
            [
                'name' => 'marketplace_id',
                'label' => __('Marketplace'),
                'title' => __('Marketplace'),
                'values' => $this->getMarketplaceDataToOptions(),
                'value' => $this->formData['marketplace_id'],
                'required' => true,
                'disabled' => $isLockedMarketplace,
            ]
        );

        if ($isLockedMarketplace) {
            $fieldset->addField(
                'marketplace_id_hidden',
                'hidden',
                [
                    'name' => 'marketplace_id',
                    'value' => $this->formData['marketplace_id'],
                ]
            );
        }

        // ---------------------------------------

        $fieldset = $form->addFieldset(
            'magento_block_walmart_template_selling_format_qty',
            [
                'legend' => __('Quantity'),
                'class' => 'm2epro-marketplace-depended-block',
                'collapsable' => false,
            ]
        );

        // ---------------------------------------

        $defaultValue = '';
        if (
            in_array(
                $this->formData['qty_mode'],
                [
                    \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT,
                    \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_NUMBER,
                ]
            )
        ) {
            $defaultValue = $this->formData['qty_mode'];
        }

        $fieldset->addField(
            'qty_mode',
            self::SELECT,
            [
                'name' => 'qty_mode',
                'container_id' => 'qty_mode_tr',
                'label' => __('Quantity'),
                'values' => $this->getQtyOptions(),
                'value' => $defaultValue,
                'create_magento_attribute' => true,
                'tooltip' => __('Item Quantity available on Walmart.'),
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField(
            'qty_custom_attribute',
            'hidden',
            [
                'name' => 'qty_custom_attribute',
                'value' => $this->formData['qty_custom_attribute'],
            ]
        );

        $fieldset->addField(
            'qty_custom_value',
            'text',
            [
                'name' => 'qty_custom_value',
                'container_id' => 'qty_custom_value_tr',
                'label' => __('Quantity Value'),
                'value' => $this->formData['qty_custom_value'],
                'class' => 'validate-digits M2ePro-required-when-visible',
                'required' => true,
                'field_extra_attributes' => 'style="display: none;"',
            ]
        );

        // ---------------------------------------

        $preparedAttributes = [];
        for ($i = 100; $i >= 5; $i -= 5) {
            $preparedAttributes[] = [
                'value' => $i,
                'label' => $i . ' %',
            ];
        }

        $fieldset->addField(
            'qty_percentage',
            self::SELECT,
            [
                'name' => 'qty_percentage',
                'container_id' => 'qty_percentage_tr',
                'label' => __('Quantity Percentage'),
                'values' => $preparedAttributes,
                'value' => $this->formData['qty_percentage'],
                'tooltip' => __(
                    'Specify what percentage of Magento Product Quantity has to be submitted to Walmart.<br>
                    <strong>For example</strong>, if QTY Percentage is set to 10% and
                    Magento Product Quantity is 100,<br>
                    the Item Quantity available on Walmart will be calculated as 100 * 10% = 10.'
                ),
            ]
        );

        // ---------------------------------------

        $fieldset->addField(
            'qty_modification_mode',
            self::SELECT,
            [
                'name' => 'qty_modification_mode',
                'container_id' => 'qty_modification_mode_tr',
                'label' => __('Conditional Quantity'),
                'values' => [
                    SellingFormat::QTY_MODIFICATION_MODE_OFF => __('Disabled'),
                    SellingFormat::QTY_MODIFICATION_MODE_ON => __('Enabled'),
                ],
                'value' => $this->formData['qty_modification_mode'],
                'tooltip' => __(
                    'Enable to set the minimum and maximum Item Quantity
                                        values that will be submitted to Walmart.'
                ),
            ]
        );

        $fieldset->addField(
            'qty_min_posted_value',
            'text',
            [
                'name' => 'qty_min_posted_value',
                'container_id' => 'qty_min_posted_value_tr',
                'label' => __('Minimum Quantity to Be Listed'),
                'value' => $this->formData['qty_min_posted_value'],
                'class' => 'M2ePro-validate-qty',
                'required' => true,
                'field_extra_attributes' => 'style="display: none;"',
                'tooltip' => __(
                    'If Magento Product QTY is 2 and you set the Minimum Quantity to Be Listed of 5,
                    the Item will not be listed on Walmart.<br>
                    To be submitted, Magento Product QTY has to be equal or more than the value set
                    for Minimum Quantity to Be Listed.'
                ),
            ]
        );

        $fieldset->addField(
            'qty_max_posted_value',
            'text',
            [
                'name' => 'qty_max_posted_value',
                'container_id' => 'qty_max_posted_value_tr',
                'label' => __('Maximum Quantity to Be Listed'),
                'value' => $this->formData['qty_max_posted_value'],
                'class' => 'M2ePro-validate-qty',
                'required' => true,
                'field_extra_attributes' => 'style="display: none;"',
                'tooltip' => __(
                    'If Magento Product QTY is 5 and you set the Maximum Quantity to Be Listed of 2,
                     the listed Item will have maximum Quantity of 2.<br>
                     If Magento Product QTY is 1 and you set the Maximum Quantity to Be Listed of 3,
                     the listed Item will have maximum Quantity of 1.'
                ),
            ]
        );

        // ---------------------------------------

        $fieldset = $form->addFieldset(
            'magento_block_walmart_template_selling_format_prices',
            [
                'legend' => __('Price'),
                'class' => 'm2epro-marketplace-depended-block',
                'collapsable' => false,
            ]
        );

        // ---------------------------------------

        $defaultValue = '';
        if (
            in_array(
                $this->formData['price_mode'],
                [
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_NONE,
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT,
                    \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL,
                ]
            )
        ) {
            $defaultValue = $this->formData['price_mode'];
        }

        $tooltipRegularPriceMode = $this->getTooltipHtml(
            __('Item Price displayed on Walmart.')
        );

        $fieldset->addField(
            'price_mode',
            self::SELECT,
            [
                'name' => 'price_mode',
                'label' => __('Price'),
                'values' => $this->getPriceOptions(),
                'value' => $defaultValue,
                'class' => 'select-main',
                'create_magento_attribute' => true,
                'after_element_html' => $tooltipRegularPriceMode,
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,price');

        $fieldset->addField(
            'price_custom_attribute',
            'hidden',
            [
                'name' => 'price_custom_attribute',
                'value' => $this->formData['price_custom_attribute'],
            ]
        );

        $this->appendPriceChangeElements(
            $fieldset,
            $this->formData['price_modifier']
        );

        // ---------------------------------------

        $fieldset->addField(
            'price_variation_mode',
            self::SELECT,
            [
                'name' => 'price_variation_mode',
                'label' => __('Variation Price Source'),
                'class' => 'select-main',
                'values' => [
                    SellingFormat::PRICE_VARIATION_MODE_PARENT => __('Main Product'),
                    SellingFormat::PRICE_VARIATION_MODE_CHILDREN => __('Associated Products'),
                ],
                'value' => $this->formData['price_variation_mode'],
                'tooltip' => __('Choose the source of the price value for Bundle Products variations.'),
            ]
        );

        // ---------------------------------------

        $fieldset->addField(
            'promotions_mode',
            self::SELECT,
            [
                'name' => 'promotions_mode',
                'label' => __('Promotions'),
                'class' => 'select-main',
                'values' => [
                    SellingFormat::PROMOTIONS_MODE_NO => __('Disabled'),
                    SellingFormat::PROMOTIONS_MODE_YES => __('Enabled'),
                ],
                'value' => $this->formData['promotions_mode'],
                'required' => true,
                'tooltip' => __(
                    'Enable to add up to 10 Promotion rules. Price your Items at
                    reduced values during the specified period.<br>
                    Comparison Price is used to calculate savings on Items. Remain the current
                    Item Prices or submit a new values.<br>
                    <strong>Note:</strong> Make sure that your Promotion dates do not overlap.'
                ),
            ]
        );

        $fieldset->addField(
            'promotions_container',
            self::CUSTOM_CONTAINER,
            [
                'text' => $this->getPromotionsHtml($form),
                'css_class' => 'm2epro-fieldset-table',
                'field_extra_attributes' => 'id="promotions_tr"',
            ]
        );

        // ---------------------------------------

        $fieldset->addField(
            'price_increase_vat_percent',
            self::SELECT,
            [
                'name' => 'price_increase_vat_percent',
                'label' => __('Add VAT Percentage'),
                'values' => [
                    0 => __('No'),
                    1 => __('Yes'),
                ],
                'required' => true,
                'value' => (int)($this->formData['price_vat_percent'] > 0),
                'tooltip' => __(
                    <<<HTML
Enable this option to add a specified VAT percent value to the Price when a Product is listed on Walmart.
<br/>
<br/>
The final product price on Walmart will be calculated according to the following formula:
<br/>
<br/>
(Product Price + Price Change) + VAT Rate
<br/>
<br/>
<strong>Note:</strong> Walmart considers the VAT rate value sent by M2E Pro as an additional price increase,
not as a proper VAT rate.
HTML
                ),
            ]
        );

        $fieldset->addField(
            'price_vat_percent',
            'text',
            [
                'name' => 'price_vat_percent',
                'container_id' => 'price_vat_percent_tr',
                'label' => __('VAT Rate, %'),
                'value' => $this->formData['price_vat_percent'],
                'class' => 'M2ePro-validate-vat-percent input-text',
                'required' => true,
                'field_extra_attributes' => 'style="display: none;"',
            ]
        );

        // ---------------------------------------

        $fieldset = $form->addFieldset(
            'magento_block_walmart_template_selling_format_details',
            [
                'legend' => __('Details'),
                'class' => 'm2epro-marketplace-depended-block',
                'collapsable' => false,
            ]
        );

        // ---------------------------------------

        $fieldset->addField(
            'lag_time_custom_attribute',
            'hidden',
            [
                'name' => 'lag_time_custom_attribute',
                'value' => $this->formData['lag_time_custom_attribute'],
            ]
        );

        $fieldset->addField(
            'lag_time_value',
            'hidden',
            [
                'name' => 'lag_time_value',
                'value' => $this->formData['lag_time_value'],
            ]
        );

        $fieldset->addField(
            'lag_time_mode',
            self::SELECT,
            [
                'name' => 'lag_time_mode',
                'label' => __('Lag Time'),
                'values' => $this->getLagTimeOptions(),
                'value' => '',
                'required' => true,
                'tooltip' => __(
                    '
                    The number of days it takes to prepare the Item for shipment.<br><br>

                    <strong>Note:</strong> Sellers are required to provide up to 1 day Lag Time. Otherwise,
                    the Lag Time Exception must be requested.<br>
                    The details can be found
                    <a href="https://sellerhelp.walmart.com/seller/s/guide?article=000005986" target="_blank">here</a>.
                    <br><br>

                    <strong>Note:</strong> any changes to the Lag Time value may take up to 24 hours to be reflected on
                    the Channel due to the Walmart limitations.'
                ),
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        // ---------------------------------------

        $fieldset = $form->addFieldset(
            'magento_block_walmart_template_selling_format_shipping',
            [
                'legend' => __('Shipping'),
                'class' => 'm2epro-marketplace-depended-block',
                'collapsable' => false,
            ]
        );

        // ---------------------------------------

        $fieldset->addField(
            'item_weight_custom_attribute',
            'hidden',
            [
                'name' => 'item_weight_custom_attribute',
                'value' => $this->formData['item_weight_custom_attribute'],
            ]
        );

        $defaultValue = '';
        if ($this->formData['item_weight_mode'] == SellingFormat::WEIGHT_MODE_CUSTOM_VALUE) {
            $defaultValue = $this->formData['item_weight_mode'];
        }

        $fieldset->addField(
            'item_weight_mode',
            self::SELECT,
            [
                'name' => 'item_weight_mode',
                'label' => __('Weight'),
                'title' => __('Weight'),
                'values' => $this->getItemWeightModeOptions(),
                'value' => $defaultValue,
                'class' => 'select',
                'create_magento_attribute' => true,
                'tooltip' => __(
                    'The weight of the Item when it is packaged to ship.<br>
                                <strong>Note:</strong> The Shipping Weight is set in pounds by default.'
                ),
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField(
            'item_weight_custom_value',
            'text',
            [
                'name' => 'item_weight_custom_value',
                'label' => __('Weight Value'),
                'title' => __('Weight Value'),
                'value' => $this->formData['item_weight_custom_value'],
                'class' => 'input-text M2ePro-required-when-visible',
                'required' => true,
                'field_extra_attributes' => 'id="item_weight_custom_value_tr" style="display: none;"',
            ]
        );

        // ---------------------------------------

        $fieldset->addField(
            'must_ship_alone_custom_attribute',
            'hidden',
            [
                'name' => 'must_ship_alone_custom_attribute',
                'value' => $this->formData['must_ship_alone_custom_attribute'],
            ]
        );

        $defaultValue = '';
        if ($this->formData['must_ship_alone_mode'] == SellingFormat::MUST_SHIP_ALONE_MODE_NONE) {
            $defaultValue = $this->formData['must_ship_alone_mode'];
        }

        $fieldset->addField(
            'must_ship_alone_mode',
            self::SELECT,
            [
                'name' => 'must_ship_alone_mode',
                'label' => __('Must Ship Alone'),
                'title' => __('Must Ship Alone'),
                'values' => $this->getMustShipAloneOptions(),
                'value' => $defaultValue,
                'class' => 'select',
                'create_magento_attribute' => true,
                'tooltip' => __(
                    'Specify whether the Item must be shipped alone or not. <br><br>
                     <strong>Note:</strong> If the option is enabled, Walmart order will be created for each
                     ordered item. It is because Walmart will recognize each item as a different shipment. For example,
                     if a buyer orders one product with a quantity of 3,
                     there will be 3 separate Walmart orders for each item.
                    '
                ),
            ]
        )->addCustomAttribute('allowed_attribute_types', 'boolean');

        // ---------------------------------------

        $fieldset->addField(
            'ships_in_original_packaging_custom_attribute',
            'hidden',
            [
                'name' => 'ships_in_original_packaging_custom_attribute',
                'value' => $this->formData['ships_in_original_packaging_custom_attribute'],
            ]
        );

        $defaultValue = '';
        if (
            $this->formData['ships_in_original_packaging_mode'] == SellingFormat::SHIPS_IN_ORIGINAL_PACKAGING_MODE_NONE
        ) {
            $defaultValue = $this->formData['ships_in_original_packaging_mode'];
        }

        $fieldset->addField(
            'ships_in_original_packaging_mode',
            self::SELECT,
            [
                'name' => 'ships_in_original_packaging_mode',
                'label' => __('Ships In Original Packaging'),
                'title' => __('Ships In Original Packaging'),
                'values' => $this->getShipsInOriginalPackagingOptions(),
                'value' => $defaultValue,
                'class' => 'select',
                'create_magento_attribute' => true,
                'tooltip' => __(
                    'Specify whether the Item can be shipped in original
                packaging without being put in an outer box or not.'
                ),
            ]
        )->addCustomAttribute('allowed_attribute_types', 'boolean');

        // ---------------------------------------

        $fieldset->addField(
            'shipping_override_rule_mode',
            self::SELECT,
            [
                'name' => 'shipping_override_rule_mode',
                'label' => __('Override Mode'),
                'class' => 'select-main',
                'values' => [
                    SellingFormat::SHIPPING_OVERRIDE_RULE_MODE_NO => __('Disabled'),
                    SellingFormat::SHIPPING_OVERRIDE_RULE_MODE_YES => __('Enabled'),
                ],
                'value' => $this->formData['shipping_override_rule_mode'],
                'tooltip' => __(
                    'Enable to add Shipping Overrides.<br>
                    <strong>Note:</strong> When you set an override for one shipping method,
                    the other shipping methods will<br>
                    still be taken from the global shipping settings in your Seller Center.'
                ),
            ]
        );

        $fieldset->addField(
            'shipping_override_rule_container',
            self::CUSTOM_CONTAINER,
            [
                'text' => $this->getShippingOverrideRuleHtml($form),
                'css_class' => 'm2epro-fieldset-table',
                'field_extra_attributes' => 'id="shipping_override_rule_tr"',
            ]
        );

        // ---------------------------------------

        $fieldset = $form->addFieldset(
            'magento_block_walmart_template_selling_format_sale_time',
            [
                'legend' => __('Offer Start/End Date'),
                'class' => 'm2epro-marketplace-depended-block',
                'collapsable' => false,
                'tooltip' => $this->__(
                    '
                        It is highly recommended to keep the default Offer Start/End Date values, i.e.
                        Immediate and Endless. Your offer will become visible on
                        Walmart as soon as it is in stock.<br><br>

                        Defining an offer start/end date may only be reasonable if you plan
                        to sell an Item during a certain period, e.g. start selling on
                        20th November and stop selling on 30th November. <br><br>

                        Read more details <a href="%url%" target="_blank">here</a>.',
                    $this->supportHelper->getDocumentationArticleUrl('selling-policy')
                ),
            ]
        );

        // ---------------------------------------

        $fieldset->addField(
            'sale_time_start_date_custom_attribute',
            'hidden',
            [
                'name' => 'sale_time_start_date_custom_attribute',
                'value' => $this->formData['sale_time_start_date_custom_attribute'],
            ]
        );

        $defaultValue = '';
        if (
            $this->formData['sale_time_start_date_mode'] == SellingFormat::DATE_NONE
            || $this->formData['sale_time_start_date_mode'] == SellingFormat::DATE_VALUE
        ) {
            $defaultValue = $this->formData['sale_time_start_date_mode'];
        }

        $fieldset->addField(
            'sale_time_start_date_mode',
            self::SELECT,
            [
                'name' => 'sale_time_start_date_mode',
                'label' => __('Start Date'),
                'title' => __('Start Date'),
                'values' => $this->getSaleTimeStartDateOptions(),
                'value' => $defaultValue,
                'class' => 'select',
                'create_magento_attribute' => true,
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,date');

        $fieldset->addField(
            'sale_time_start_date_value',
            'date',
            [
                'name' => 'sale_time_start_date_value',
                'container_id' => 'sale_time_start_date_value_tr',
                'label' => __('Start Date Value'),
                'value' => $this->formData['sale_time_start_date_value'],
                'date_format' => $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT),
                'field_extra_attributes' => 'style="display: none;"',
            ]
        );

        // ---------------------------------------

        $fieldset->addField(
            'sale_time_end_date_custom_attribute',
            'hidden',
            [
                'name' => 'sale_time_end_date_custom_attribute',
                'value' => $this->formData['sale_time_end_date_custom_attribute'],
            ]
        );

        $defaultValue = '';
        if (
            $this->formData['sale_time_end_date_mode'] == SellingFormat::DATE_NONE
            || $this->formData['sale_time_end_date_mode'] == SellingFormat::DATE_VALUE
        ) {
            $defaultValue = $this->formData['sale_time_end_date_mode'];
        }

        $fieldset->addField(
            'sale_time_end_date_mode',
            self::SELECT,
            [
                'name' => 'sale_time_end_date_mode',
                'label' => __('End Date'),
                'title' => __('End Date'),
                'values' => $this->getSaleTimeEndDateOptions(),
                'value' => $defaultValue,
                'class' => 'select',
                'create_magento_attribute' => true,
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,date');

        $fieldset->addField(
            'sale_time_end_date_value',
            'date',
            [
                'name' => 'sale_time_end_date_value',
                'container_id' => 'sale_time_end_date_value_tr',
                'label' => __('End Date Value'),
                'value' => $this->formData['sale_time_end_date_value'],
                'date_format' => $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT),
                'field_extra_attributes' => 'style="display: none;"',
            ]
        );

        // ---------------------------------------

        $fieldset = $form->addFieldset(
            'magento_block_walmart_template_selling_format_attributes',
            [
                'legend' => __('Attributes'),
                'class' => 'm2epro-marketplace-depended-block',
                'collapsable' => false,
            ]
        );

        // ---------------------------------------

        $fieldset->addField(
            'attributes_mode',
            self::SELECT,
            [
                'name' => 'attributes_mode',
                'label' => __('Attributes'),
                'title' => __('Attributes'),
                'values' => [
                    ['value' => SellingFormat::ATTRIBUTES_MODE_NONE, 'label' => __('None')],
                    ['value' => SellingFormat::ATTRIBUTES_MODE_CUSTOM, 'label' => __('Custom Value')],
                ],
                'value' => $this->formData['attributes_mode'],
                'tooltip' => __('Specify up to 5 additional features that describe your Item.'),
            ]
        );

        $this->appendAttributesFields($fieldset, 5, 'attributes');

        // ---------------------------------------

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    // ---------------------------------------

    public function getMarketplaceDataToOptions()
    {
        $optionsResult = [
            ['value' => '', 'label' => '', 'style' => 'display: none;'],
        ];

        foreach ($this->walmartHelper->getMarketplacesAvailableForApiCreation() as $marketplace) {
            $optionsResult[] = [
                'value' => $marketplace->getId(),
                'label' => $this->escapeHtml($marketplace->getTitle()),
            ];
        }

        return $optionsResult;
    }

    public function getQtyOptions()
    {
        $optionsResult = [
            [
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT,
                'label' => __('Product Quantity'),
            ],
            [
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_NUMBER,
                'label' => __('Custom Value'),
            ],
        ];

        $attributeOptions = $this->getAttributeOptions(
            \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE,
            'qty_custom_attribute',
            'text'
        );

        $tmpOption = [
            'value' => \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT_FIXED,
            'label' => __('QTY'),
        ];

        if ($this->formData['qty_mode'] == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT_FIXED) {
            $tmpOption['attrs']['selected'] = 'selected';
        }

        array_unshift($attributeOptions[count($attributeOptions) - 1]['value'], $tmpOption);

        return array_merge($optionsResult, $attributeOptions);
    }

    public function getPriceOptions()
    {
        $optionsResult = [
            [
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT,
                'label' => __('Product Price'),
            ],
            [
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL,
                'label' => __('Special Price'),
            ],
        ];

        return array_merge(
            $optionsResult,
            $this->getAttributeOptions(
                \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE,
                'price_custom_attribute',
                'text_price'
            )
        );
    }

    public function getLagTimeOptions()
    {
        $optionsResult = [
            [
                'value' => SellingFormat::LAG_TIME_MODE_RECOMMENDED,
                'label' => __('Same Day'),
                'attrs' => ['attribute_code' => 0],
            ],
        ];

        $recommendedOptions = [
            'value' => [],
            'label' => 'Recommended Value',
        ];
        for ($i = 1; $i <= 30; $i++) {
            $option = [
                'value' => SellingFormat::LAG_TIME_MODE_RECOMMENDED,
                'label' => __($i . ' day(s)'),
                'attrs' => ['attribute_code' => $i],
            ];

            if ($this->formData['lag_time_value'] == $i) {
                $option['attrs']['selected'] = 'selected';
            }

            $recommendedOptions['value'][] = $option;
        }

        $optionsResult[] = $recommendedOptions;

        return array_merge(
            $optionsResult,
            $this->getAttributeOptions(
                SellingFormat::LAG_TIME_MODE_CUSTOM_ATTRIBUTE,
                'lag_time_custom_attribute',
                'text_select'
            )
        );
    }

    public function getItemWeightModeOptions()
    {
        $optionsResult = [
            [
                'value' => SellingFormat::WEIGHT_MODE_CUSTOM_VALUE,
                'label' => __('Custom Value'),
            ],
        ];

        return array_merge(
            $optionsResult,
            $this->getAttributeOptions(
                SellingFormat::WEIGHT_MODE_CUSTOM_ATTRIBUTE,
                'item_weight_custom_attribute',
                'text_weight'
            )
        );
    }

    public function getMustShipAloneOptions()
    {
        $options = [
            [
                'value' => SellingFormat::MUST_SHIP_ALONE_MODE_YES,
                'label' => __('Yes'),
            ],
            [
                'value' => SellingFormat::MUST_SHIP_ALONE_MODE_NO,
                'label' => __('No'),
            ],
        ];

        foreach ($options as $option) {
            if ($this->formData['must_ship_alone_mode'] == $option['value']) {
                $option['attrs']['selected'] = 'selected';
            }
        }

        $optionsResult = [
            [
                'value' => SellingFormat::MUST_SHIP_ALONE_MODE_NONE,
                'label' => __('None'),
            ],
            [
                'value' => $options,
                'label' => 'Custom Value',
            ],
        ];

        return array_merge(
            $optionsResult,
            $this->getAttributeOptions(
                SellingFormat::MUST_SHIP_ALONE_MODE_CUSTOM_ATTRIBUTE,
                'must_ship_alone_custom_attribute',
                'boolean'
            )
        );
    }

    public function getShipsInOriginalPackagingOptions()
    {
        $options = [
            [
                'value' => SellingFormat::SHIPS_IN_ORIGINAL_PACKAGING_MODE_NO,
                'label' => __('Yes'),
            ],
            [
                'value' => SellingFormat::MUST_SHIP_ALONE_MODE_NO,
                'label' => __('No'),
            ],
        ];

        foreach ($options as $option) {
            if ($this->formData['ships_in_original_packaging_mode'] == $option['value']) {
                $option['attrs']['selected'] = 'selected';
            }
        }

        $optionsResult = [
            [
                'value' => SellingFormat::SHIPS_IN_ORIGINAL_PACKAGING_MODE_NONE,
                'label' => __('None'),
            ],
            [
                'value' => $options,
                'label' => 'Custom Value',
            ],
        ];

        return array_merge(
            $optionsResult,
            $this->getAttributeOptions(
                SellingFormat::SHIPS_IN_ORIGINAL_PACKAGING_MODE_CUSTOM_ATTRIBUTE,
                'ships_in_original_packaging_custom_attribute',
                'boolean'
            )
        );
    }

    public function getSaleTimeStartDateOptions()
    {
        $optionsResult = [
            [
                'value' => SellingFormat::DATE_NONE,
                'label' => __('Immediate'),
            ],
            [
                'value' => SellingFormat::DATE_VALUE,
                'label' => __('Custom Value'),
            ],
        ];

        return array_merge(
            $optionsResult,
            $this->getAttributeOptions(
                SellingFormat::DATE_ATTRIBUTE,
                'sale_time_start_date_custom_attribute',
                'text_date'
            )
        );
    }

    public function getSaleTimeEndDateOptions()
    {
        $optionsResult = [
            [
                'value' => SellingFormat::DATE_NONE,
                'label' => __('Endless'),
            ],
            [
                'value' => SellingFormat::DATE_VALUE,
                'label' => __('Custom Value'),
            ],
        ];

        return array_merge(
            $optionsResult,
            $this->getAttributeOptions(
                SellingFormat::DATE_ATTRIBUTE,
                'sale_time_end_date_custom_attribute',
                'text_date'
            )
        );
    }

    public function getPromotionsHtml($form)
    {
        return $this->getLayout()->createBlock(Promotions::class)
                    ->setParentForm($form)
                    ->setAttributesByInputType('text_date', $this->allAttributesByInputTypes['text_date'])
                    ->setAttributesByInputType('text_price', $this->allAttributesByInputTypes['text_price'])
                    ->toHtml();
    }

    public function getShippingOverrideRuleHtml($form)
    {
        return $this->getLayout()->createBlock(ShippingOverrideRules::class)
                    ->setParentForm($form)
                    ->setAllAttributes($this->allAttributes)
                    ->toHtml();
    }

    public function appendAttributesFields(
        \Magento\Framework\Data\Form\Element\Fieldset $fieldSet,
        $fieldCount,
        $name
    ) {
        $helper = $this->dataHelper;
        for ($i = 0; $i < $fieldCount; $i++) {
            $value = '';
            if (!empty($this->formData[$name][$i]['name'])) {
                $value = $helper->escapeHtml($this->formData[$name][$i]['name']);
            }

            $nameBlock = $this->elementFactory->create(
                'text',
                [
                    'data' => [
                        'name' => $name . '_name[]',
                        'value' => $value,
                        'onkeyup' => 'WalmartTemplateSellingFormatObj.multi_element_keyup(\'' . $name . '\',this)',
                        'class' => 'M2ePro-required-when-visible',
                        'css_class' => $name . '_tr no-margin-bottom',
                        'after_element_html' => ' /',
                    ],
                ]
            );
            $nameBlock->setId($name . '_name_' . $i);
            $nameBlock->setForm($fieldSet->getForm());

            $value = '';
            if (!empty($this->formData[$name][$i]['value'])) {
                $value = $helper->escapeHtml($this->formData[$name][$i]['value']);
            }

            $valueBlock = $this->elementFactory->create(
                'text',
                [
                    'data' => [
                        'name' => $name . '_value[]',
                        'value' => $value,
                        'onkeyup' => 'WalmartTemplateSellingFormatObj.multi_element_keyup(\'' . $name . '\',this)',
                        'class' => 'M2ePro-required-when-visible',
                        'css_class' => $name . '_tr no-margin-bottom',
                        'tooltip' => __('Max. 100 characters.'),
                    ],
                ]
            );
            $valueBlock->setId($name . '_value_' . $i);
            $valueBlock->setForm($fieldSet->getForm());

            $button = $this->getLayout()
                           ->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button\MagentoAttribute::class)
                           ->addData([
                               'label' => __('Insert'),
                               'destination_id' => $name . '_value_' . $i,
                               'magento_attributes' => $this->getClearAttributesByInputTypesOptions(),
                               'on_click_callback' => "WalmartTemplateSellingFormatObj.multi_element_keyup
                                        ('{$name}',$('{$name}_value_{$i}'));",
                               'class' => 'primary attributes-container-td',
                               'style' => 'display: inline-block; margin-left: 5px;',
                           ]);

            $selectAttrBlock = $this->elementFactory->create(self::SELECT, [
                'data' => [
                    'values' => $this->getClearAttributesByInputTypesOptions('text_select'),
                    'class' => 'M2ePro-required-when-visible magento-attribute-custom-input',
                    'create_magento_attribute' => true,
                ],
            ])->addCustomAttribute('allowed_attribute_types', 'text,select')
                                                    ->addCustomAttribute('apply_to_all_attribute_sets', 'false');

            $selectAttrBlock->setId('selectAttr_' . $name . '_value_' . $i);
            $selectAttrBlock->setForm($fieldSet->getForm());

            $fieldSet->addField(
                'attributes_container_' . $i,
                self::CUSTOM_CONTAINER,
                [
                    'container_id' => 'custom_title_tr',
                    'label' => $this->__('Attributes (name / value) #%number%', $i + 1),
                    'title' => $this->__('Attributes (name / value) #%number%', $i + 1),
                    'style' => 'display: inline-block;',
                    'text' => $nameBlock->toHtml() . $valueBlock->toHtml(),
                    'after_element_html' => $selectAttrBlock->toHtml() . $button->toHtml(),
                    'css_class' => 'attributes_tr',
                    'field_extra_attributes' => 'style="display: none;"',
                ]
            );
        }

        $fieldSet->addField(
            $name . '_actions',
            self::CUSTOM_CONTAINER,
            [
                'label' => '',
                'text' => <<<HTML
                <a id="show_{$name}_action"
                   href="javascript: void(0);"
                   onclick="WalmartTemplateSellingFormatObj.showElement('{$name}');">
                   {$this->__('Add New')}
                </a>
                        /
                <a id="hide_{$name}_action"
                   href="javascript: void(0);"
                   onclick="WalmartTemplateSellingFormatObj.hideElement('{$name}');">
                   {$this->__('Remove')}
                </a>
HTML
                ,
                'field_extra_attributes' => 'id="' . $name . '_actions_tr" style="display: none;"',
            ]
        );
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \ReflectionException
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function _beforeToHtml()
    {
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Template\SellingFormat::class)
        );

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Walmart\Template\SellingFormat::class)
        );

        $this->jsPhp->addConstants(
            $this->dataHelper
                ->getClassConstants(\Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion::class)
        );

        $this->jsPhp->addConstants(
            $this->dataHelper
                ->getClassConstants(\Ess\M2ePro\Model\Walmart\Template\SellingFormat\ShippingOverride::class)
        );

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Helper\Component\Walmart::class)
        );

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Walmart_Template_SellingFormat'));
        $this->jsUrl->addUrls([
            'formSubmit' => $this->getUrl(
                '*/walmart_template_sellingFormat/save',
                ['_current' => true]
            ),
            'formSubmitNew' => $this->getUrl('*/walmart_template_sellingFormat/save'),
            'deleteAction' => $this->getUrl(
                '*/walmart_template_sellingFormat/delete',
                ['_current' => true]
            ),

            'm2epro_skin_url' => $this->getViewFileUrl('Ess_M2ePro'),
        ]);

        $this->jsTranslator->addTranslations([
            'QTY' => __('QTY'),
            'Price' => __('Price'),

            'The Price, at which you want to sell your Product(s) at specific time.' => __(
                'The Price, at which you want to sell your Product(s) at specific time.'
            ),
            'The Price, at which you want to sell your Product(s) at specific time.<br/>
            <b>Note:</b> The Final Price is only used for Simple Products.' => __(
                'The Price, at which you want to sell your Product(s) at specific time.<br/>
                <b>Note:</b> The Final Price is only used for Simple Products.'
            ),

            'Add Selling Policy' => __('Add Selling Policy'),
            'The specified Title is already used for other Policy. Policy Title must be unique.' => __(
                'The specified Title is already used for other Policy. Policy Title must be unique.'
            ),
            'You should select Attribute Sets first and press Confirm Button.' => __(
                'You should select Attribute Sets first and press Confirm Button.'
            ),
            'Coefficient is not valid.' => __('Coefficient is not valid.'),
            'Date range is not valid.' => __('Incorrect Promotion Dates.'),

            'Wrong value. Only integer numbers.' => __('Wrong value. Only integer numbers.'),
            'wrong_value_more_than_30' => __(
                'Wrong value. Must be no more than 30. Max applicable length is 6 characters,
                including the decimal (e.g., 12.345).'
            ),
            'At least one Selling Type should be enabled.' => __('At least one Selling Type should be enabled.'),
            'The Quantity value should be unique.' => __('The Quantity value should be unique.'),
            'You should specify a unique pair of Magento Attribute and Price Change value for each Discount Rule.' =>
                __(
                    'You should specify a unique pair of Magento Attribute
                    and Price Change value for each Discount Rule.'
                ),
            'You should add at least one Discount Rule.' => __('You should add at least one Discount Rule.'),
            'Any' => __('Any'),
            'Add Shipping Override Policy.' => __('Add Shipping Override Policy.'),
            'You should specify at least one Promotion.' => __('You should specify at least one Promotion.'),
            'You should specify at least one Override Rule.' => __(
                'You should specify at least one Override Rule.'
            ),
            'Price Change is not valid.' => __('Price Change is not valid.'),
        ]);

        $formData = \Ess\M2ePro\Helper\Json::encode($this->formData);
        $isEdit = $this->templateModel->getId() ? 'true' : 'false';
        $allAttributes = \Ess\M2ePro\Helper\Json::encode($this->magentoAttributeHelper->getAll());

        $promotions = \Ess\M2ePro\Helper\Json::encode($this->formData['promotions']);
        $shippingOverride = \Ess\M2ePro\Helper\Json::encode($this->formData['shipping_override_rule']);

        $injectPriceChangeJs = $this->getPriceChangeInjectorJs($this->formData);

        $this->js->addRequireJs(
            [
                'jQuery' => 'jquery',
                'attr' => 'M2ePro/Attribute',
                'attribute_button' => 'M2ePro/Plugin/Magento/Attribute/Button',
                'sellingFormat' => 'M2ePro/Walmart/Template/SellingFormat',
            ],
            <<<JS

        M2ePro.formData = {$formData};

        M2ePro.customData.is_edit = {$isEdit};

        if (typeof AttributeObj === 'undefined') {
            window.AttributeObj = new Attribute();
        }
        window.AttributeObj.setAvailableAttributes({$allAttributes});

        window.WalmartTemplateSellingFormatObj = new WalmartTemplateSellingFormat();
        window.MagentoAttributeButtonObj = new MagentoAttributeButton();

        jQuery(function() {
            WalmartTemplateSellingFormatObj.initObservers();

            if ({$isEdit}) {
                WalmartTemplateSellingFormatObj.renderPromotions({$promotions});
                WalmartTemplateSellingFormatObj.renderRules({$shippingOverride});
                {$injectPriceChangeJs}
            }
        });
JS
        );

        return parent::_beforeToHtml();
    }

    protected function _toHtml()
    {
        return '<div id="modal_dialog_message"></div>' . parent::_toHtml();
    }

    public function getFormData()
    {
        $default = array_merge(
            $this->modelFactory->getObject('Walmart_Template_SellingFormat_Builder')->getDefaultData(),
            [
                'marketplace_id' => $this->getRequest()->getParam('marketplace_id', ''),
            ]
        );

        if (!$this->templateModel || !$this->templateModel->getId()) {
            return $default;
        }

        $data = array_merge(
            $this->templateModel->getData(),
            $this->templateModel->getChildObject()->getData()
        );

        $data['shipping_override_rule'] = $this->templateModel->getChildObject()->getShippingOverrides();
        $data['promotions'] = $this->templateModel->getChildObject()->getPromotions();
        $data['attributes'] = \Ess\M2ePro\Helper\Json::decode($data['attributes']);

        return array_merge($default, $data);
    }

    protected function getAttributeOptions($attributeMode, $attributeName, $attributeType)
    {
        $optionsResult = [];

        $forceAddedAttributeOption = $this->getForceAddedAttributeOption(
            $this->formData[$attributeName],
            $this->allAttributesByInputTypes[$attributeType],
            $attributeMode
        );

        if ($forceAddedAttributeOption) {
            $optionsResult[] = $forceAddedAttributeOption;
        }

        $optionsResult[] = [
            'value' => $this->getAttributesByInputTypesOptions(
                $attributeMode,
                $attributeType,
                function ($attribute) use ($attributeName) {
                    return $attribute['code'] == $this->formData[$attributeName];
                }
            ),
            'label' => 'Magento Attribute',
            'attrs' => ['is_magento_attribute' => true],
        ];

        return $optionsResult;
    }

    public function getForceAddedAttributeOption($attributeCode, $availableValues, $value = null)
    {
        if (
            empty($attributeCode)
            || $this->magentoAttributeHelper->isExistInAttributesArray($attributeCode, $availableValues)
        ) {
            return '';
        }

        $attributeLabel = $this->dataHelper
            ->escapeHtml($this->magentoAttributeHelper->getAttributeLabel($attributeCode));

        $result = ['value' => $value, 'label' => $attributeLabel];

        if ($value === null) {
            return $result;
        }

        $result['attrs'] = ['attrbiute_code' => $attributeCode];

        return $result;
    }

    public function getAttributesByInputTypesOptions($value, $attributeType, $conditionCallback = false)
    {
        if (!isset($this->allAttributesByInputTypes[$attributeType])) {
            return [];
        }

        $optionsResult = [];
        $helper = $this->dataHelper;

        foreach ($this->allAttributesByInputTypes[$attributeType] as $attribute) {
            $tmpOption = [
                'value' => $value,
                'label' => $helper->escapeHtml($attribute['label']),
                'attrs' => ['attribute_code' => $attribute['code']],
            ];

            if (is_callable($conditionCallback) && $conditionCallback($attribute)) {
                $tmpOption['attrs']['selected'] = 'selected';
            }

            $optionsResult[] = $tmpOption;
        }

        return $optionsResult;
    }

    public function getClearAttributesByInputTypesOptions()
    {
        $optionsResult = [];
        $helper = $this->dataHelper;

        foreach ($this->allAttributesByInputTypes['text_select'] as $attribute) {
            $optionsResult[] = [
                'value' => $attribute['code'],
                'label' => $helper->escapeHtml($attribute['label']),
            ];
        }

        return $optionsResult;
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\Fieldset $fieldset
     * @param string $priceModifier
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function appendPriceChangeElements(
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset,
        string $priceModifier
    ) {
        $block = $this->getLayout()
                      ->createBlock(\Ess\M2ePro\Block\Adminhtml\Template\SellingFormat\PriceChange::class)
                      ->addData([
                          'price_type' => 'price',
                          'price_modifier' => $priceModifier,
                      ]);

        $fieldset->addField(
            'price_change_placement',
            'label',
            [
                'container_id' => 'price_change_placement_tr',
                'label' => '',
                'after_element_html' => $block->toHtml(),
            ]
        );
    }

    /**
     * @param array $formData
     *
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getPriceChangeInjectorJs(array $formData): string
    {
        $result = [];

        $key = 'price_modifier';
        if (!empty($formData[$key])) {
            // ensure that data always have a valid json format
            $json = \Ess\M2ePro\Helper\Json::encode(
                \Ess\M2ePro\Helper\Json::decode($formData[$key]) ?: []
            );

            $result[] = <<<JS
    WalmartTemplateSellingFormatObj.priceChangeHelper.renderPriceChangeRows(
        'price',
        {$json}
    );
JS;
        }

        return implode("\n", $result);
    }
}
