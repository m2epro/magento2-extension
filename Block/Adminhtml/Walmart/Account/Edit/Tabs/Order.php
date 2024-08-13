<?php

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Walmart\Account;

class Order extends AbstractForm
{
    private \Magento\Sales\Model\Order\Config $orderConfig;
    private \Magento\Customer\Model\Group $customerGroup;
    private \Magento\Tax\Model\ClassModel $taxClass;
    private \Ess\M2ePro\Helper\Module\Support $supportHelper;
    private \Ess\M2ePro\Helper\Magento\Store\Website $storeWebsite;
    private \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper;
    private \Ess\M2ePro\Helper\Magento\Store $storeHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Magento\Store $storeHelper,
        \Magento\Tax\Model\ClassModel $taxClass,
        \Magento\Customer\Model\Group $customerGroup,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Magento\Store\Website $storeWebsite,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        array $data = []
    ) {
        $this->orderConfig = $orderConfig;
        $this->customerGroup = $customerGroup;
        $this->taxClass = $taxClass;
        $this->supportHelper = $supportHelper;
        $this->storeWebsite = $storeWebsite;
        $this->globalDataHelper = $globalDataHelper;
        $this->storeHelper = $storeHelper;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        /** @var \Ess\M2ePro\Model\Account|null $account */
        $account = $this->globalDataHelper->getValue('edit_account');
        /** @var \Ess\M2ePro\Model\Walmart\Account|null $walmartAccount */
        $walmartAccount = $account !== null ? $account->getChildObject() : null;

        $ordersSettings = $walmartAccount !== null ? $walmartAccount->getData('magento_orders_settings') : [];
        $ordersSettings = !empty($ordersSettings) ? \Ess\M2ePro\Helper\Json::decode($ordersSettings) : [];

        // ---------------------------------------
        $websites = $this->storeWebsite->getWebsites(true);
        // ---------------------------------------

        // ---------------------------------------
        $temp = $this->customerGroup->getCollection()->toArray();
        $groups = $temp['items'];
        // ---------------------------------------

        // ---------------------------------------

        $productTaxClasses = $this->taxClass->getCollection()
                                            ->addFieldToFilter(
                                                'class_type',
                                                \Magento\Tax\Model\ClassModel::TAX_CLASS_TYPE_PRODUCT
                                            )
                                            ->toOptionArray();
        $none = ['value' => \Ess\M2ePro\Model\Magento\Product::TAX_CLASS_ID_NONE, 'label' => __('None')];
        array_unshift($productTaxClasses, $none);

        $formData = $account !== null ? array_merge($account->getData(), $account->getChildObject()->getData()) : [];
        $formData['magento_orders_settings'] = !empty($formData['magento_orders_settings'])
            ? \Ess\M2ePro\Helper\Json::decode($formData['magento_orders_settings']) : [];

        $defaults = $this->modelFactory->getObject('Walmart_Account_Builder')->getDefaultData();

        $isEdit = !!$this->getRequest()->getParam('id');

        $isEdit && $defaults['magento_orders_settings']['refund_and_cancellation']['refund_mode'] = 0;

        $formData = array_replace_recursive($defaults, $formData);

        $form = $this->_formFactory->create();

        $form->addField(
            'walmart_accounts_orders',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    <<<HTML
Specify how M2E Pro should process your Walmart sales. You can enable an automatic Magento Order creation,
select tax settings to apply to an Order, activate an automatic invoice and shipment creation, etc.<br/><br/>

The detailed information can be found <a href="%url%" target="_blank">here</a>.
HTML
                    ,
                    $this->supportHelper->getDocumentationArticleUrl(
                        'help/m2/walmart-integration/account-configurations'
                    )
                ),
            ]
        );

        $fieldset = $form->addFieldset(
            'listed_by_m2e',
            [
                'legend' => __('Product Is Listed By M2E Pro'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'magento_orders_listings_mode',
            'select',
            [
                'name' => 'magento_orders_settings[listing][mode]',
                'label' => __('Create Order in Magento'),
                'values' => [
                    1 => __('Yes'),
                    0 => __('No'),
                ],
                'value' => $formData['magento_orders_settings']['listing']['mode'],
                'tooltip' => __(
                    'Enable to automatically create Magento Order if the Channel Order was placed for
                    the Item listed via M2E Pro.'
                ),
            ]
        );

        $fieldset->addField(
            'magento_orders_listings_create_from_date',
            'text',
            [
                'container_id' => 'magento_orders_listings_create_from_date_container',
                'name' => 'magento_orders_settings[listing][create_from_date]',
                'label' => __('Create From Date'),
                'tooltip' => __(
                    'Select the start date for channel orders to be created in Magento.'
                    . ' Orders purchased before this date will not be imported into Magento.'
                ),
                'value' => $this->getMagentoOrdersListingsCreateFromDate($walmartAccount)
                                ->format('Y-m-d H:i:s')
            ]
        );

        $fieldset->addField(
            'magento_orders_listings_store_mode',
            'select',
            [
                'container_id' => 'magento_orders_listings_store_mode_container',
                'name' => 'magento_orders_settings[listing][store_mode]',
                'label' => __('Magento Store View Source'),
                'values' => [
                    Account::MAGENTO_ORDERS_LISTINGS_STORE_MODE_DEFAULT => __('Use Store View from Listing'),
                    Account::MAGENTO_ORDERS_LISTINGS_STORE_MODE_CUSTOM => __('Choose Store View Manually'),
                ],
                'value' => $formData['magento_orders_settings']['listing']['store_mode'],
                'tooltip' => __(
                    'Specify whether Magento Store View should be determined automatically
                    based on M2E Pro Listing settings or selected manually.'
                ),
            ]
        );

        $fieldset->addField(
            'magento_orders_listings_store_id',
            self::STORE_SWITCHER,
            [
                'container_id' => 'magento_orders_listings_store_id_container',
                'name' => 'magento_orders_settings[listing][store_id]',
                'label' => __('Magento Store View'),
                'required' => true,
                'value' => !empty($ordersSettings['listing']['store_id'])
                    ? $ordersSettings['listing']['store_id'] : '',
                'has_empty_option' => true,
                'has_default_option' => false,
                'tooltip' => __('The Magento Store View that Orders will be placed in.'),
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_walmart_accounts_magento_orders_listings_other',
            [
                'legend' => __('Product Is Listed By Any Other Software'),
                'collapsable' => true,
            ]
        );

        $fieldset->addField(
            'magento_orders_listings_other_mode',
            'select',
            [
                'name' => 'magento_orders_settings[listing_other][mode]',
                'label' => __('Create Order in Magento'),
                'values' => [
                    0 => __('No'),
                    1 => __('Yes'),
                ],
                'value' => $formData['magento_orders_settings']['listing_other']['mode'],
                'tooltip' => __(
                    'Choose whether a Magento Order should be created if a Walmart Order is received for an item that
                    does <b>not</b> belong to the M2E Pro Listing.'
                ),
            ]
        );

        $fieldset->addField(
            'magento_orders_listings_other_create_from_date',
            'text',
            [
                'container_id' => 'magento_orders_listings_other_create_from_date_container',
                'name' => 'magento_orders_settings[listing_other][create_from_date]',
                'label' => __('Create From Date'),
                'tooltip' => __(
                    'Select the start date for channel orders to be created in Magento.'
                    . ' Orders purchased before this date will not be imported into Magento.'
                ),
                'value' => $this->getMagentoOrdersListingsOtherCreateFromDate($walmartAccount)
                                ->format('Y-m-d H:i:s'),
            ]
        );

        $fieldset->addField(
            'magento_orders_listings_other_store_id',
            self::STORE_SWITCHER,
            [
                'container_id' => 'magento_orders_listings_other_store_id_container',
                'name' => 'magento_orders_settings[listing_other][store_id]',
                'label' => __('Magento Store View'),
                'value' => !empty($ordersSettings['listing_other']['store_id'])
                    ? $ordersSettings['listing_other']['store_id'] : $this->storeHelper->getDefaultStoreId(),
                'required' => true,
                'has_empty_option' => true,
                'has_default_option' => false,
                'tooltip' => __(
                    'Select Magento Store View that will be associated with Magento Order.'
                ),
            ]
        );

        $fieldset->addField(
            'magento_orders_listings_other_product_mode',
            'select',
            [
                'container_id' => 'magento_orders_listings_other_product_mode_container',
                'name' => 'magento_orders_settings[listing_other][product_mode]',
                'label' => __('Product Not Found'),
                'values' => [
                    Account::MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IGNORE => __('Do Not Create Order'),
                    Account::MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IMPORT => __('Create Product and Order'),
                ],
                'value' => $formData['magento_orders_settings']['listing_other']['product_mode'],
                'tooltip' => __('What has to be done if a Listed Product does not exist in Magento.')
                    . '<span id="magento_orders_listings_other_product_mode_note">'
                    . __(
                        'Specify which action should be performed if the purchased Item does not have
                        the corresponding Product in Magento. <br><br>
                        <strong>Note:</strong> Only Simple Magento Products can be created based on these settings.
                        If a Variational Item was purchased, M2E Pro will automatically create different
                        Simple Products for each Variation.'
                    )
                    . '</span>',
            ]
        );

        $fieldset->addField(
            'magento_orders_listings_other_product_mode_warning',
            self::MESSAGES,
            [
                'messages' => [
                    [
                        'type' => \Magento\Framework\Message\MessageInterface::TYPE_NOTICE,
                        'content' => __(
                            'Please note that a new Magento Product will be created
                            if the corresponding SKU is not found in your Catalog.'
                        ),
                    ],
                ],
                'style' => 'max-width:450px; margin-left:20%',
            ]
        );

        $values = [];
        foreach ($productTaxClasses as $taxClass) {
            $values[$taxClass['value']] = $taxClass['label'];
        }

        $fieldset->addField(
            'magento_orders_listings_other_product_tax_class_id',
            'select',
            [
                'container_id' => 'magento_orders_listings_other_product_tax_class_id_container',
                'name' => 'magento_orders_settings[listing_other][product_tax_class_id]',
                'label' => __('Product Tax Class'),
                'values' => $values,
                'value' => $formData['magento_orders_settings']['listing_other']['product_tax_class_id'],
                'tooltip' => __('Select the Tax Class which will be used for Products created by M2E Pro.'),
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_walmart_accounts_magento_orders_wfs',
            [
                'legend' => __('WFS Orders Settings'),
                'collapsable' => true,
                'tooltip' => __(
                    'In this Block you can manage Stock Inventory of Products fulfilled by Walmart  (WFS Orders).<br/>
                <b>Yes</b> - after Magento Order Creation of WFS Order, Quantity of Product reduces in Magento.<br/>
                <b>No</b> - Magento Order Creation of WFS Order does not affect Quantity of Magento Product.'
                ),
            ]
        );

        $fieldset->addField(
            'magento_orders_wfs_mode',
            'select',
            [
                'name' => 'magento_orders_settings[wfs][mode]',
                'label' => __('Create Order in Magento'),
                'values' => [
                    0 => __('No'),
                    1 => __('Yes'),
                ],
                'value' => $formData['magento_orders_settings']['wfs']['mode'] ?? 0,
                'tooltip' => __(
                    'Whether an Order has to be created in Magento if a sold Product is fulfilled by Walmart.'
                ),
            ]
        );

        $fieldset->addField(
            'magento_orders_wfs_store_mode',
            'select',
            [
                'container_id' => 'magento_orders_wfs_store_mode_container',
                'name' => 'magento_orders_settings[wfs][store_mode]',
                'label' => __('Create in separate Store View'),
                'values' => [
                    0 => __('No'),
                    1 => __('Yes'),
                ],
                'value' => $formData['magento_orders_settings']['wfs']['store_mode'] ?? 0,
            ]
        );

        $fieldset->addField(
            'magento_orders_wfs_store_id',
            self::STORE_SWITCHER,
            [
                'container_id' => 'magento_orders_wfs_store_id_container',
                'name' => 'magento_orders_settings[wfs][store_id]',
                'label' => __('Magento Store View'),
                'value' => !empty($formData['magento_orders_settings']['wfs']['store_id'])
                    ? $formData['magento_orders_settings']['wfs']['store_id'] : '',
                //'required' => true,
                'has_empty_option' => true,
                'has_default_option' => false,
            ]
        );

        $fieldset->addField(
            'magento_orders_wfs_stock_mode',
            'select',
            [
                'container_id' => 'magento_orders_wfs_stock_mode_container',
                'name' => 'magento_orders_settings[wfs][stock_mode]',
                'label' => __('Manage Stock'),
                'values' => [
                    0 => __('No'),
                    1 => __('Yes'),
                ],
                'value' => $formData['magento_orders_settings']['wfs']['stock_mode'] ?? 0,
                'tooltip' => __(
                    'If <i>Yes</i>, after Magento Order Creation QTY of Magento Product reduces.'
                ),
            ]
        );

        $shippingInfoFieldset = $form->addFieldset(
            'magento_block_walmart_accounts_magento_orders_shipping_information',
            [
                'legend' => __('Shipping information'),
                'collapsable' => true,
            ]
        );

        $shippingInfoFieldset->addField(
            'magento_orders_ship_by_date_settings',
            'select',
            [
                'name' => 'magento_orders_settings[shipping_information][ship_by_date]',
                'label' => __('Import Ship by date to Magento order'),
                'values' => [
                    1 => __('Yes'),
                    0 => __('No'),
                ],
                'value' => $formData['magento_orders_settings']['shipping_information']['ship_by_date'] ?? 1,
            ]
        );

        $value = $formData['magento_orders_settings']['shipping_information']['shipping_address_region_override'] ?? 1;
        $shippingInfoFieldset->addField(
            'magento_orders_order_validation_shipping_address_region_override',
            'select',
            [
                'name' => 'magento_orders_settings[shipping_information][shipping_address_region_override]',
                'label' => __('Override invalid Region/State required value'),
                'values' => [
                    0 => __('No'),
                    1 => __('Yes'),
                ],
                'value' => $value,
                'tooltip' => __(
                    'When enabled, the invalid Region/State value will be replaced with an alternative one to create
                     an order in Magento.'
                ),
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_walmart_accounts_magento_orders_number',
            [
                'legend' => __('Magento Order Number'),
                'collapsable' => true,
            ]
        );

        $fieldset->addField(
            'magento_orders_number_source',
            'select',
            [
                'name' => 'magento_orders_settings[number][source]',
                'label' => __('Source'),
                'values' => [
                    Account::MAGENTO_ORDERS_NUMBER_SOURCE_MAGENTO => __('Magento'),
                    Account::MAGENTO_ORDERS_NUMBER_SOURCE_CHANNEL => __('Walmart'),
                ],
                'value' => $formData['magento_orders_settings']['number']['source'],
                'tooltip' => __(
                    'Select whether Magento Order number should be generated based on your
                    Magento settings or Walmart Order number.'
                ),
            ]
        );

        $fieldset->addField(
            'magento_orders_number_prefix_container',
            self::CUSTOM_CONTAINER,
            [
                'text' => $this->getLayout()
                               ->createBlock(
                                   \Ess\M2ePro\Block\Adminhtml\Walmart\Account\Edit\Tabs\Order\PrefixesTable::class
                               )
                               ->setFormData($formData)
                               ->toHtml(),
                'css_class' => 'm2epro-fieldset-table',
                'style' => 'padding: 0 !important;',
            ]
        );

        $fieldset->addField(
            'sample_walmart_order_id',
            'hidden',
            [
                'value' => '141-4423723-6495633',
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_orders_reservation_rules',
            [
                'legend' => __('Quantity Reservation'),
                'collapsable' => true,
                'tooltip' => __(
                    'Use Reserve Quantity Option to keep Items from being sold before an
                    Order is created in Magento.<br>
                    The Reserve Quantity Option ensures that Items are deducted from Magento stock immediately
                    upon an Order being imported from the channel. Reserved quantity will be used to create an Order
                    in Magento or will be released once the QTY reservation period expires.'
                ),
            ]
        );

        $values = [];
        for ($day = 1; $day <= 14; $day++) {
            if ($day == 1) {
                $values[$day] = __('For %number day', ['number' => $day]);
            } else {
                $values[$day] = __('For %number days', ['number' => $day]);
            }
        }

        $fieldset->addField(
            'magento_orders_qty_reservation_days',
            'select',
            [
                'container_id' => 'magento_orders_qty_reservation_days_container',
                'name' => 'magento_orders_settings[qty_reservation][days]',
                'label' => __('Reserve Quantity'),
                'values' => $values,
                'value' => $formData['magento_orders_settings']['qty_reservation']['days'],
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_walmart_accounts_magento_orders_refund_and_cancellation',
            [
                'legend' => __('Refund & Cancellation'),
                'collapsable' => true,
            ]
        );

        $fieldset->addField(
            'magento_orders_refund',
            'select',
            [
                'container_id' => 'magento_orders_refund_container',
                'name' => 'magento_orders_settings[refund_and_cancellation][refund_mode]',
                'label' => __('Cancel or Refund if Credit Memo is Created'),
                'values' => [
                    0 => __('No'),
                    1 => __('Yes'),
                ],
                'value' => $formData['magento_orders_settings']['refund_and_cancellation']['refund_mode'],
                'tooltip' => __(
                    'Enable the <i>Cancel or Refund if Credit Memo is Created</i> option to automatically cancel or
                     refund Walmart order after the Credit Memo is created for the associated Magento order. <br/><br/>
                     Walmart order will be <b>canceled</b> automatically if Credit Memo is created for all the items
                     in the associated Magento order. <br/><br/>
                     Walmart order can be <b>refunded</b> if it has <i>Shipped</i> or <i>Partially Shipped</i> status.
                     Refund is issued only for items indicated in the Credit Memo.'
                ),
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_walmart_accounts_magento_orders_customer',
            [
                'legend' => __('Customer Settings'),
                'collapsable' => true,
            ]
        );

        $fieldset->addField(
            'magento_orders_customer_mode',
            'select',
            [
                'name' => 'magento_orders_settings[customer][mode]',
                'label' => __('Customer'),
                'values' => [
                    Account::MAGENTO_ORDERS_CUSTOMER_MODE_GUEST => __('Guest Account'),
                    Account::MAGENTO_ORDERS_CUSTOMER_MODE_PREDEFINED => __('Predefined Customer'),
                    Account::MAGENTO_ORDERS_CUSTOMER_MODE_NEW => __('Create New'),
                ],
                'value' => $formData['magento_orders_settings']['customer']['mode'],
                'note' => __('Customer for which Magento Orders will be created.'),
                'tooltip' => __(
                    'Define Magento Customer for which Magento Order will be created: <br/><br/>

                     <b>Guest Account</b> - the system does not require a Customer Account to be created.
                     The default Guest Account will be defined as a Customer. <br/>
                     <b>Note:</b> Allow Guest Checkout Option must be enabled in your Magento:
                     <i>System > Configuration > Sales > Checkout</i>.<br/>

                     <b>Predefined Customer</b> - the system will use a single Magento Customer for all
                     Walmart Orders related to this Account. The related Customer ID must be provided. <br/>
                     <b>Note:</b> Magento Customer IDs can be found under the <i>Customers > Manage Customers</i>.<br/>

                     <b>Create New</b> - a new Customer will be created in Magento based on the
                     Buyer information in Walmart Order.<br/>
                     <b>Note:</b> Buyer email will be used as a unique Customer Identifier.
                     If this email already exists in Magento, the related Magento Customer will be
                     associated with Walmart Order. A new Customer will not be created.<br/>
                '
                ),
            ]
        );

        $fieldset->addField(
            'magento_orders_customer_id',
            'text',
            [
                'container_id' => 'magento_orders_customer_id_container',
                'class' => 'validate-digits M2ePro-account-customer-id',
                'name' => 'magento_orders_settings[customer][id]',
                'label' => __('Customer ID'),
                'value' => $formData['magento_orders_settings']['customer']['id'],
                'required' => true,
                'tooltip' => __('Enter Magento Customer ID.'),
            ]
        );

        $values = [];
        foreach ($websites as $website) {
            $values[$website['website_id']] = $website['name'];
        }

        $fieldset->addField(
            'magento_orders_customer_new_website_id',
            'select',
            [
                'container_id' => 'magento_orders_customer_new_website_id_container',
                'name' => 'magento_orders_settings[customer][website_id]',
                'label' => __('Associate to Website'),
                'values' => $values,
                'value' => $formData['magento_orders_settings']['customer']['website_id'],
                'required' => true,
                'tooltip' => __('Select Magento Website where a new Customer should be created.'),
            ]
        );

        $values = [];
        foreach ($groups as $group) {
            $values[$group['customer_group_id']] = $group['customer_group_code'];
        }

        $fieldset->addField(
            'magento_orders_customer_new_group_id',
            'select',
            [
                'container_id' => 'magento_orders_customer_new_group_id_container',
                'name' => 'magento_orders_settings[customer][group_id]',
                'label' => __('Customer Group'),
                'values' => $values,
                'value' => $formData['magento_orders_settings']['customer']['group_id'],
                'required' => true,
                'tooltip' => __('Select Magento Customer Group where a new Customer should be created.'),
            ]
        );

        $value = [];
        $formData['magento_orders_settings']['customer']['notifications']['order_created']
        && $value[] = 'order_created';
        $formData['magento_orders_settings']['customer']['notifications']['invoice_created']
        && $value[] = 'invoice_created';

        $fieldset->addField(
            'magento_orders_customer_new_notifications',
            'multiselect',
            [
                'container_id' => 'magento_orders_customer_new_notifications_container',
                'name' => 'magento_orders_settings[customer][notifications][]',
                'label' => __('Send Emails When The Following Is Created'),
                'values' => [
                    ['label' => __('Magento Order'), 'value' => 'order_created'],
                    ['label' => __('Invoice'), 'value' => 'invoice_created'],
                ],
                'value' => $value,
                'tooltip' => __(
                    '<p>Select certain conditions when the emails should be sent to Customer.
                    Hold Ctrl to select multiple options.</p>
                    <p><strong>Note:</strong> the related email type must be enabled in your Magento:
                    <i>System > Configuration > Sales Emails</i>.</p>'
                ),
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_walmart_accounts_magento_orders_tax',
            [
                'legend' => __('Order Tax Settings'),
                'collapsable' => true,
            ]
        );

        $fieldset->addField(
            'magento_orders_tax_mode',
            'select',
            [
                'name' => 'magento_orders_settings[tax][mode]',
                'label' => __('Tax Source'),
                'values' => [
                    Account::MAGENTO_ORDERS_TAX_MODE_NONE => __('None'),
                    Account::MAGENTO_ORDERS_TAX_MODE_CHANNEL => __('Walmart'),
                    Account::MAGENTO_ORDERS_TAX_MODE_MAGENTO => __('Magento'),
                    Account::MAGENTO_ORDERS_TAX_MODE_MIXED => __('Walmart & Magento'),
                ],
                'value' => $formData['magento_orders_settings']['tax']['mode'],
                'tooltip' => $this->__(
                    'Choose where the tax settings for your Magento Order will be taken from. See
                    <a href="%url%" target="_blank">this article</a> for more details.',
                    $this->supportHelper->getDocumentationArticleUrl(
                        'help/m2/walmart-integration/managing-sales/calculation-tax-settings#8d7b63463f81489184a4666c13c1d4d0'
                    )
                ),
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_walmart_accounts_magento_orders_status_mapping',
            [
                'legend' => __('Order Status Mapping'),
                'collapsable' => true,
            ]
        );

        $fieldset->addField(
            'magento_orders_status_mapping_mode',
            'select',
            [
                'name' => 'magento_orders_settings[status_mapping][mode]',
                'label' => __('Status Mapping'),
                'values' => [
                    Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT => __('Default Order Statuses'),
                    Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_CUSTOM => __('Custom Order Statuses'),
                ],
                'value' => $formData['magento_orders_settings']['status_mapping']['mode'],
                'tooltip' => __(
                    'Set the correspondence between Walmart and Magento order statuses.
                    The status of your Magento order will be updated based on these settings.'
                ),
            ]
        );

        $isDisabledStatusStyle = (
            $formData['magento_orders_settings']['status_mapping']['mode']
            == Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT
        );

        if (
            $formData['magento_orders_settings']['status_mapping']['mode']
            == Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT
        ) {
            $formData['magento_orders_settings']['status_mapping']['processing']
                = Account::MAGENTO_ORDERS_STATUS_MAPPING_PROCESSING;
            $formData['magento_orders_settings']['status_mapping']['shipped']
                = Account::MAGENTO_ORDERS_STATUS_MAPPING_SHIPPED;

            $formData['magento_orders_settings']['invoice_mode'] = 1;
            $formData['magento_orders_settings']['shipment_mode'] = 1;
        }

        $statusList = $this->orderConfig->getStatuses();

        $fieldset->addField(
            'magento_orders_status_mapping_processing',
            'select',
            [
                'container_id' => 'magento_orders_status_mapping_processing_container',
                'name' => 'magento_orders_settings[status_mapping][processing]',
                'label' => __('Order Status is Unshipped / Partially Shipped'),
                'values' => $statusList,
                'value' => $formData['magento_orders_settings']['status_mapping']['processing'],
                'disabled' => $isDisabledStatusStyle,
            ]
        );

        $fieldset->addField(
            'magento_orders_status_mapping_shipped',
            'select',
            [
                'container_id' => 'magento_orders_status_mapping_shipped_container',
                'name' => 'magento_orders_settings[status_mapping][shipped]',
                'label' => __('Shipping Is Completed'),
                'values' => $statusList,
                'value' => $formData['magento_orders_settings']['status_mapping']['shipped'],
                'disabled' => $isDisabledStatusStyle,
            ]
        );

        $this->setForm($form);

        $this->jsTranslator->addTranslations(
            [
                'No Customer entry is found for specified ID.' => __(
                    'No Customer entry is found for specified ID.'
                ),
            ]
        );

        return parent::_prepareForm();
    }

    private function getMagentoOrdersListingsCreateFromDate(
        ?\Ess\M2ePro\Model\Walmart\Account $walmartAccount
    ): \DateTime {
        if ($walmartAccount === null) {
            return \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
        }

        return $walmartAccount
            ->getMagentoOrdersListingsCreateFromDate()
            ->setTimezone(self::getDateTimeZone());
    }

    private function getMagentoOrdersListingsOtherCreateFromDate(
        ?\Ess\M2ePro\Model\Walmart\Account $walmartAccount
    ): \DateTime {
        if ($walmartAccount === null) {
            return \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
        }

        return $walmartAccount
            ->getMagentoOrdersListingsOtherCreateFromDate()
            ->setTimezone(self::getDateTimeZone());
    }

    public static function getDateTimeZone(): \DateTimeZone
    {
        return new \DateTimeZone(
            \Ess\M2ePro\Helper\Date::getTimezone()->getConfigTimezone()
        );
    }
}
