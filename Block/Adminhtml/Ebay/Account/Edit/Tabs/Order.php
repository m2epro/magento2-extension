<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Ebay\Account;
use Magento\Framework\Message\MessageInterface;

class Order extends AbstractForm
{
    protected $orderConfig;
    protected $customerGroup;
    protected $taxClass;

    public function __construct(
        \Magento\Tax\Model\ClassModel $taxClass,
        \Magento\Customer\Model\Group $customerGroup,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->orderConfig = $orderConfig;
        $this->customerGroup = $customerGroup;
        $this->taxClass = $taxClass;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $account = $this->getHelper('Data\GlobalData')->getValue('edit_account');

        $websites = $this->getHelper('Magento\Store\Website')->getWebsites(true);

        // ---------------------------------------

        // ---------------------------------------
        $temp = $this->customerGroup->getCollection()->toArray();
        $groups = $temp['items'];
        // ---------------------------------------

        // ---------------------------------------
        $productTaxClasses = $this->taxClass->getCollection()
            ->addFieldToFilter('class_type', \Magento\Tax\Model\ClassModel::TAX_CLASS_TYPE_PRODUCT)
            ->toOptionArray();
        $none = array('value' => \Ess\M2ePro\Model\Magento\Product::TAX_CLASS_ID_NONE, 'label' => $this->__('None'));
        array_unshift($productTaxClasses, $none);

        // ---------------------------------------

        $formData = !is_null($account) ? array_merge($account->getData(), $account->getChildObject()->getData()) : [];
        $formData['magento_orders_settings'] = !empty($formData['magento_orders_settings'])
            ? $this->getHelper('Data')->jsonDecode($formData['magento_orders_settings']) : array();

        $defaults = array(
            'magento_orders_settings' => array(
                'listing' => array(
                    'mode' => Account::MAGENTO_ORDERS_LISTINGS_MODE_YES,
                    'store_mode' => Account::MAGENTO_ORDERS_LISTINGS_STORE_MODE_DEFAULT,
                    'store_id' => NULL
                ),
                'listing_other' => array(
                    'mode' => Account::MAGENTO_ORDERS_LISTINGS_OTHER_MODE_YES,
                    'product_mode' => Account::MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IMPORT,
                    'product_tax_class_id' => \Ess\M2ePro\Model\Magento\Product::TAX_CLASS_ID_NONE,
                    'store_id' => $this->getHelper('Magento\Store')->getDefaultStoreId(),
                ),
                'number' => array(
                    'source' => Account::MAGENTO_ORDERS_NUMBER_SOURCE_MAGENTO,
                    'prefix' => array(
                        'mode'   => Account::MAGENTO_ORDERS_NUMBER_PREFIX_MODE_NO,
                        'prefix' => '',
                    ),
                ),
                'customer' => array(
                    'mode' => Account::MAGENTO_ORDERS_CUSTOMER_MODE_GUEST,
                    'id' => NULL,
                    'website_id' => NULL,
                    'group_id' => NULL,
//                'subscription_mode' => Account::MAGENTO_ORDERS_CUSTOMER_NEW_SUBSCRIPTION_MODE_NO,
                    'notifications' => array(
//                    'customer_created' => false,
                        'invoice_created' => false,
                        'order_created' => false
                    )
                ),
                'creation' => array(
                    'mode' => Account::MAGENTO_ORDERS_CREATE_CHECKOUT_AND_PAID,
                    'reservation_days' => 0
                ),
                'tax' => array(
                    'mode' => Account::MAGENTO_ORDERS_TAX_MODE_MIXED
                ),
                'in_store_pickup_statuses' => array(
                    'mode' => 0,
                    'ready_for_pickup' => '',
                    'picked_up' => '',
                ),
                'status_mapping' => array(
                    'mode' => Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT,
                    'new' => Account::MAGENTO_ORDERS_STATUS_MAPPING_NEW,
                    'paid' => Account::MAGENTO_ORDERS_STATUS_MAPPING_PAID,
                    'shipped' => Account::MAGENTO_ORDERS_STATUS_MAPPING_SHIPPED
                ),
                'qty_reservation' => array(
                    'days' => 1
                ),
                'invoice_mode' => Account::MAGENTO_ORDERS_INVOICE_MODE_YES,
                'shipment_mode' => Account::MAGENTO_ORDERS_SHIPMENT_MODE_YES
            )
        );

        $formData = array_replace_recursive($defaults, $formData);

        $form = $this->_formFactory->create();

        $form->addField('ebay_accounts_orders',
            self::HELP_BLOCK,
            [
                'content' => $this->__(<<<HTML
<p>Specify how M2E Pro should manage the Orders imported from eBay.</p><br/>
<p>You are able to configure the different rules of <strong>Magento Order Creation</strong> considering whether the
Item was listed via M2E Pro or by some other software.</p><br/>
<p>Once eBay Order is imported, the <strong>Reserve Quantity</strong> feature will hold the Stock if Magento Order
could not be created immediately in accordance with provided settings.</p><br/>
<p>Besides, you can configure the <strong>Tax, Order Number</strong> and <strong>Order Status Mapping</strong> Settings
for your Magento Orders as well as specify the automatic creation of invoices and shipment notifications.</p><br/>
<p>More detailed information you can find <a href="%url%" target="_blank" class="external-link">here</a>.</p>
HTML
                    ,
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/LgItAQ'))
            ]
        );

        $fieldset = $form->addFieldset('listed_by_m2e',
            [
                'legend' => $this->__('Product Is Listed By M2E Pro'),
                'collapsable' => false
            ]
        );

        $fieldset->addField('magento_orders_listings_mode',
            'select',
            [
                'name' => 'magento_orders_settings[listing][mode]',
                'label' => $this->__('Create Order in Magento'),
                'values' => [
                    Account::MAGENTO_ORDERS_LISTINGS_MODE_YES => $this->__('Yes'),
                    Account::MAGENTO_ORDERS_LISTINGS_MODE_NO => $this->__('No'),
                ],
                'value' => $formData['magento_orders_settings']['listing']['mode'],
                'tooltip' => $this->__(
                    'Choose whether a Magento Order should be created if an eBay Order is received for
                    an eBay Item Listed using M2E Pro.'
                )
            ]
        );

        $fieldset->addField('magento_orders_listings_store_mode',
            'select',
            [
                'container_id' => 'magento_orders_listings_store_mode_container',
                'name' => 'magento_orders_settings[listing][store_mode]',
                'label' => $this->__('Magento Store View Source'),
                'values' => [
                    Account::MAGENTO_ORDERS_LISTINGS_STORE_MODE_DEFAULT => $this->__('Use Store View from Listing'),
                    Account::MAGENTO_ORDERS_LISTINGS_STORE_MODE_CUSTOM => $this->__('Choose Store View Manually'),
                ],
                'value' => $formData['magento_orders_settings']['listing']['store_mode'],
                'tooltip' => $this->__(
                    'Choose to specify the Magento Store View here or to keep the Magento
                    Store View used in the M2E Pro Listing.'
                )
            ]
        );

        $fieldset->addField('magento_orders_listings_store_id',
            self::STORE_SWITCHER,
            [
                'container_id' => 'magento_orders_listings_store_id_container',
                'name' => 'magento_orders_settings[listing][store_id]',
                'label' => $this->__('Magento Store View'),
                'required' => true,
                'value' => $formData['magento_orders_settings']['listing']['store_id'],
                'has_empty_option' => true,
                'has_default_option' => false,
                'tooltip' => $this->__('The Magento Store View that Orders will be placed in.')
            ]
        );

        $fieldset = $form->addFieldset('listed_by_other',
            [
                'legend' => $this->__('Product Is Listed By Any Other Software'),
                'collapsable' => false
            ]
        );

        $fieldset->addField('magento_orders_listings_other_mode',
            'select',
            [
                'name' => 'magento_orders_settings[listing_other][mode]',
                'label' => $this->__('Create Order in Magento'),
                'values' => [
                    Account::MAGENTO_ORDERS_LISTINGS_OTHER_MODE_NO => $this->__('No'),
                    Account::MAGENTO_ORDERS_LISTINGS_OTHER_MODE_YES => $this->__('Yes'),
                ],
                'value' => $formData['magento_orders_settings']['listing_other']['mode'],
                'tooltip' => $this->__(
                    'Choose whether a Magento Order should be created if an eBay Order is received for
                    an eBay Item <b>not</b> Listed using M2E Pro.'
                )
            ]
        );

        $fieldset->addField('magento_orders_listings_other_store_id',
            self::STORE_SWITCHER,
            [
                'container_id' => 'magento_orders_listings_other_store_id_container',
                'name' => 'magento_orders_settings[listing_other][store_id]',
                'label' => $this->__('Magento Store View'),
                'required' => true,
                'value' => $formData['magento_orders_settings']['listing_other']['store_id'],
                'has_empty_option' => true,
                'has_default_option' => false,
                'tooltip' => $this->__('The Magento Store View that Orders will be placed in.')
            ]
        );

        $fieldset->addField('magento_orders_listings_other_product_mode',
            'select',
            [
                'container_id' => 'magento_orders_listings_other_product_mode_container',
                'name' => 'magento_orders_settings[listing_other][product_mode]',
                'label' => $this->__('Product Not Found'),
                'values' => [
                    Account::MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IGNORE => $this->__('Do Not Create Order'),
                    Account::MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IMPORT => $this->__('Create Product and Order'),
                ],
                'value' => $formData['magento_orders_settings']['listing_other']['product_mode'],
                'tooltip' => $this->__(
                        'Choose what should happen if an Order is received for a Product that
                         does not exist in your Magento Inventory.')
                        . '<span id="magento_orders_listings_other_product_mode_note">'
                        . $this->__(
                            '<br/><b>Note:</b> Only Simple Products without Variations can be created in Magento.
                            If there is a Product with Variations on eBay, M2E Pro creates different
                            Simple Products for each Variation.'
                        )
                        . '</span>'
            ]
        );

        $values = [];
        foreach ($productTaxClasses as $taxClass) {
            $values[$taxClass['value']] = $taxClass['label'];
        }

        $fieldset->addField('magento_orders_listings_other_product_tax_class_id',
            'select',
            [
                'container_id' => 'magento_orders_listings_other_product_tax_class_id_container',
                'name' => 'magento_orders_settings[listing_other][product_tax_class_id]',
                'label' => $this->__('Product Tax Class'),
                'values' => $values,
                'value' => $formData['magento_orders_settings']['listing_other']['product_tax_class_id'],
                'tooltip' => $this->__('Tax Class which will be used for Products created by M2E Pro.')
            ]
        );

        $fieldset = $form->addFieldset('magento_block_ebay_accounts_magento_orders_number',
            [
                'legend' => $this->__('Magento Order Number'),
                'collapsable' => true
            ]
        );

        $fieldset->addField('magento_orders_number_source',
            'select',
            [
                'name' => 'magento_orders_settings[number][source]',
                'label' => $this->__('Source'),
                'values' => [
                    Account::MAGENTO_ORDERS_NUMBER_SOURCE_MAGENTO => $this->__('Magento'),
                    Account::MAGENTO_ORDERS_NUMBER_SOURCE_CHANNEL => $this->__('eBay'),
                ],
                'value' => $formData['magento_orders_settings']['number']['source'],
                'tooltip' => $this->__(
                    'If source is set to Magento, Magento Order numbers are created basing on your Magento Settings.
                    If source is set to eBay, Magento Order numbers are the same as eBay Order numbers.'
                )
            ]
        );

        $fieldset->addField('magento_orders_number_prefix_mode',
            'select',
            [
                'name' => 'magento_orders_settings[number][prefix][mode]',
                'label' => $this->__('Use Prefix'),
                'values' => [
                    Account::MAGENTO_ORDERS_NUMBER_PREFIX_MODE_NO => $this->__('No'),
                    Account::MAGENTO_ORDERS_NUMBER_PREFIX_MODE_YES => $this->__('Yes'),
                ],
                'value' => $formData['magento_orders_settings']['number']['prefix']['mode'],
                'tooltip' => $this->__('Choose to set prefix before Magento Order number.')
            ]
        );

        $fieldset->addField('magento_orders_number_prefix_prefix',
            'text',
            [
                'container_id' => 'magento_orders_number_prefix_container',
                'class' => 'M2ePro-account-order-number-prefix',
                'name' => 'magento_orders_settings[number][prefix][prefix]',
                'label' => $this->__('Prefix'),
                'value' => $formData['magento_orders_settings']['number']['prefix']['prefix'],
                'required' => true,
                'maxlength' => 5
            ]
        );

        $fieldset->addField('sample_magento_order_id',
            'hidden',
            [
                'value' => $this->getHelper('Magento')->getNextMagentoOrderId()
            ]
        );

        $fieldset->addField('sample_ebay_order_id',
            'hidden',
            [
                'value' => '110194096334-27192305001'
            ]
        );

        $fieldset->addField('order_number_example',
            'label',
            [
                'label' => '',
                'note' => $this->__('e.g.') . ' <span id="order_number_example_container"></span>'
            ]
        );

        $fieldset = $form->addFieldset('magento_block_ebay_accounts_magento_orders_customer',
            [
                'legend' => $this->__('Customer Settings'),
                'collapsable' => true
            ]
        );

        $fieldset->addField('magento_orders_customer_mode',
            'select',
            [
                'name' => 'magento_orders_settings[customer][mode]',
                'label' => $this->__('Customer'),
                'values' => [
                    Account::MAGENTO_ORDERS_CUSTOMER_MODE_GUEST => $this->__('Guest Account'),
                    Account::MAGENTO_ORDERS_CUSTOMER_MODE_PREDEFINED => $this->__('Predefined Customer'),
                    Account::MAGENTO_ORDERS_CUSTOMER_MODE_NEW => $this->__('Create New'),
                ],
                'value' => $formData['magento_orders_settings']['customer']['mode'],
                'tooltip' => $this->__(
                    '<b>Guest Account:</b> Magento Guest Checkout Option must be enabled to use this Option.
                    Use the default Guest Account. Do not create a Customer Account.<br/><br/>
                    <b>Predefined Customer:</b> Use a specific Customer for all Orders.
                    You should specify the Magento Customer ID to use.<br/><br/>
                    <b>Create New:</b> Create a new Customer in Magento for the Order.
                    If an existing Magento Customer has the same email address as the email address used for the
                    eBay Order, the Order will be assigned to that Customer instead.'
                )
            ]
        );

        $fieldset->addField('magento_orders_customer_id',
            'text',
            [
                'container_id' => 'magento_orders_customer_id_container',
                'class' => 'validate-digits M2ePro-account-customer-id',
                'name' => 'magento_orders_settings[customer][id]',
                'label' => $this->__('Customer ID'),
                'value' => $formData['magento_orders_settings']['customer']['id'],
                'required' => true
            ]
        );

        $values = [];
        foreach ($websites as $website) {
            $values[$website['website_id']] = $website['name'];
        }

        $fieldset->addField('magento_orders_customer_new_website_id',
            'select',
            [
                'container_id' => 'magento_orders_customer_new_website_id_container',
                'name' => 'magento_orders_settings[customer][website_id]',
                'label' => $this->__('Associate to Website'),
                'values' => $values,
                'value' => $formData['magento_orders_settings']['customer']['website_id'],
                'required' => true
            ]
        );

        $values = [];
        foreach ($groups as $group) {
            $values[$group['customer_group_id']] = $group['customer_group_code'];
        }

        $fieldset->addField('magento_orders_customer_new_group_id',
            'select',
            [
                'container_id' => 'magento_orders_customer_new_group_id_container',
                'name' => 'magento_orders_settings[customer][group_id]',
                'label' => $this->__('Customer Group'),
                'values' => $values,
                'value' => $formData['magento_orders_settings']['customer']['group_id'],
                'required' => true
            ]
        );

        $value = [];
        $formData['magento_orders_settings']['customer']['notifications']['order_created']
            && $value[] = 'order_created';
        $formData['magento_orders_settings']['customer']['notifications']['invoice_created']
            && $value[] = 'invoice_created';

        $fieldset->addField('magento_orders_customer_new_notifications',
            'multiselect',
            [
                'container_id' => 'magento_orders_customer_new_notifications_container',
                'name' => 'magento_orders_settings[customer][notifications][]',
                'label' => $this->__('Send Emails When The Following Is Created'),
                'values' => [
                    ['label' => $this->__('Magento Order'), 'value' => 'order_created'],
                    ['label' => $this->__('Invoice'), 'value' => 'invoice_created'],
                ],
                'value' => $value,
                'tooltip' => $this->__(
                    '<p>Necessary emails will be sent according to Magento Settings in
                    Stores > Configuration > Sales > Sales Emails.</p>
                    <p>Hold Ctrl Button to choose more than one Option.</p>'
                )
            ]
        );

        $fieldset = $form->addFieldset('magento_block_ebay_accounts_magento_orders_rules',
            [
                'legend' => $this->__('Order Creation Rules'),
                'collapsable' => true,
            ]
        );

        $fieldset->addField('magento_orders_creation_mode_immediately_warning',
            self::MESSAGES,
            [
                'messages' => [
                    [
                        'type' => MessageInterface::TYPE_WARNING,
                        'content' => $this->__(
                            'Please note that Immediate Magento order creation sets certain limits to the update of
                            the later order changes. If the shipping or tax details are modified after checkout is
                            completed, these changes will not be reflected in Magento order.'
                        )
                    ],
                ],
                'style' => 'display: none'
            ]
        );

        $fieldset->addField('magento_orders_creation_mode',
            'select',
            [
                'name' => 'magento_orders_settings[creation][mode]',
                'label' => $this->__('Create Magento Order When'),
                'values' => [
                    Account::MAGENTO_ORDERS_CREATE_IMMEDIATELY => $this->__('Immediately'),
                    Account::MAGENTO_ORDERS_CREATE_CHECKOUT => $this->__('Checkout Is Completed'),
                    Account::MAGENTO_ORDERS_CREATE_PAID => $this->__('Payment Is Received'),
                    Account::MAGENTO_ORDERS_CREATE_CHECKOUT_AND_PAID => $this->__(
                        'Checkout Is Completed & Payment Received'
                    ),
                ],
                'value' => $formData['magento_orders_settings']['creation']['mode'],
                'tooltip' => $this->__(
                    'Choose the stage of the eBay Order process at which the Magento Order should be created.
                     You can also choose for how long to reserve Stock as soon as an eBay Order is received,
                     to ensure you can fulfil the eBay Order in case there is a delay between the Order being made
                     and the Magento Order being created.'
                )
            ]
        );

        $values = [
            0 => $this->__('Never')
        ];
        for ($day = 7; $day <= 10; $day++) {
            $values[$day] = $this->__('In %d% days', $day);
        }

        $fieldset->addField('magento_orders_creation_reservation_days',
            'select',
            [
                'container_id' => 'magento_orders_creation_reservation_days_container',
                'name' => 'magento_orders_settings[creation][reservation_days]',
                'label' => $this->__('Automatic Cancellation'),
                'values' => $values,
                'value' => $formData['magento_orders_settings']['creation']['reservation_days'],
                'tooltip' => $this->__(
                    'Magento Orders, which were not paid in a definite time interval,
                    will be cancelled. Unpaid Item Process will be launched for such Orders on eBay.'
                )
            ]
        );

        $values = [];
        for ($day = 1; $day <= 14; $day++) {
            if ($day == 1) {
                $values[$day] = $this->__('For %number% day', $day);
            } else {
                $values[$day] = $this->__('For %number% days', $day);
            }
        }

        $fieldset->addField('magento_orders_qty_reservation_days',
            'select',
            [
                'container_id' => 'magento_orders_qty_reservation_days_container',
                'name' => 'magento_orders_settings[qty_reservation][days]',
                'label' => $this->__('Reserve Quantity'),
                'values' => $values,
                'value' => $formData['magento_orders_settings']['qty_reservation']['days'],
                'tooltip' => $this->__(
                    'Choose how long to set Stock aside after an eBay Order is made,
                    to allow time for the eBay process to reach the point at which a Magento Order is created.'
                )
            ]
        );

        $fieldset = $form->addFieldset('magento_block_ebay_accounts_magento_orders_tax',
            [
                'legend' => $this->__('Order Tax Settings'),
                'collapsable' => true
            ]
        );

        $fieldset->addField('magento_orders_tax_mode',
            'select',
            [
                'name' => 'magento_orders_settings[tax][mode]',
                'label' => $this->__('Tax Source'),
                'values' => [
                    Account::MAGENTO_ORDERS_TAX_MODE_NONE => $this->__('None'),
                    Account::MAGENTO_ORDERS_TAX_MODE_CHANNEL => $this->__('eBay'),
                    Account::MAGENTO_ORDERS_TAX_MODE_MAGENTO => $this->__('Magento'),
                    Account::MAGENTO_ORDERS_TAX_MODE_MIXED => $this->__('eBay & Magento'),
                ],
                'value' => $formData['magento_orders_settings']['tax']['mode'],
                'tooltip' => $this->__(
                    'Choose the Tax Settings for your Magento Order:
                    <br/>
                    <br/><b>eBay</b> - Magento Orders use Tax Settings from the eBay Listing.
                    <br/><b>Magento</b> - Magento Orders use Magento Tax Settings.
                    <br/><b>eBay & Magento</b> - Magento Orders use Tax Settings from the eBay Listing.
                    If there are no Tax Settings set in eBay, Magento Tax Settings are used.
                    <br/><b>None</b> - No Tax Settings are set.'
                )
            ]
        );

        $fieldset = $form->addFieldset('magento_block_ebay_accounts_magento_orders_status_mapping',
            [
                'legend' => $this->__('Order Status Mapping'),
                'collapsable' => true
            ]
        );

        $fieldset->addField('magento_orders_status_mapping_mode',
            'select',
            [
                'name' => 'magento_orders_settings[status_mapping][mode]',
                'label' => $this->__('Status Mapping'),
                'values' => [
                    Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT => $this->__('Default Order Statuses'),
                    Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_CUSTOM => $this->__('Custom Order Statuses'),
                ],
                'value' => $formData['magento_orders_settings']['status_mapping']['mode'],
                'tooltip' => $this->__(
                    'Match stages in the eBay Order process to Order Statuses in Magento.
                     You can also choose whether to create invoices and shipment notifications automatically.'
                )
            ]
        );

        $isDisabledStatus = (
            $formData['magento_orders_settings']['status_mapping']['mode']
                    == Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT
        );

        if ($formData['magento_orders_settings']['status_mapping']['mode']
                        == Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT) {
            $formData['magento_orders_settings']['status_mapping']['new'] = Account::MAGENTO_ORDERS_STATUS_MAPPING_NEW;
            $formData['magento_orders_settings']['status_mapping']['paid']
                = Account::MAGENTO_ORDERS_STATUS_MAPPING_PAID;
            $formData['magento_orders_settings']['status_mapping']['shipped']
                = Account::MAGENTO_ORDERS_STATUS_MAPPING_SHIPPED;

            $formData['magento_orders_settings']['invoice_mode'] = Account::MAGENTO_ORDERS_INVOICE_MODE_YES;
            $formData['magento_orders_settings']['shipment_mode'] = Account::MAGENTO_ORDERS_SHIPMENT_MODE_YES;
        }

        $statusList = $this->orderConfig->getStatuses();

        $fieldset->addField('magento_orders_status_mapping_new',
            'select',
            [
                'name' => 'magento_orders_settings[status_mapping][new]',
                'label' => $this->__('Checkout Is Completed'),
                'values' => $statusList,
                'value' => $formData['magento_orders_settings']['status_mapping']['new'],
                'disabled' => $isDisabledStatus
            ]
        );

        $invoiceModeDisabled = $isDisabledStatus ? 'disabled="disabled"' : '';
        $invoiceModeChecked = $formData['magento_orders_settings']['status_mapping']['mode']
                == Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT
        || $formData['magento_orders_settings']['invoice_mode'] == Account::MAGENTO_ORDERS_INVOICE_MODE_YES
            ? 'checked="checked"' : '';

        $fieldset->addField('magento_orders_status_mapping_paid',
            'select',
            [
                'container_id' => 'magento_orders_status_mapping_paid_container',
                'name' => 'magento_orders_settings[status_mapping][paid]',
                'label' => $this->__('Payment Is Completed'),
                'values' => $statusList,
                'value' => $formData['magento_orders_settings']['status_mapping']['paid'],
                'disabled' => $isDisabledStatus
            ]
        )->setAfterElementHtml(<<<HTML
<label for="magento_orders_invoice_mode">
<input id="magento_orders_invoice_mode"
       name="magento_orders_settings[invoice_mode]"
       type="checkbox" $invoiceModeChecked $invoiceModeDisabled> {$this->__('Automatic Invoice Creation')}</label>
HTML
);

        $shipmentModeDisabled = $isDisabledStatus ? 'disabled="disabled"' : '';
        $shipmentModeChecked = $formData['magento_orders_settings']['status_mapping']['mode']
                == Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT
        || $formData['magento_orders_settings']['shipment_mode'] == Account::MAGENTO_ORDERS_SHIPMENT_MODE_YES
            ? 'checked="checked"' : '';

        $fieldset->addField('magento_orders_status_mapping_shipped',
            'select',
            [
                'container_id' => 'magento_orders_status_mapping_shipped_container',
                'name' => 'magento_orders_settings[status_mapping][shipped]',
                'label' => $this->__('Shipping Is Completed'),
                'values' => $statusList,
                'value' => $formData['magento_orders_settings']['status_mapping']['shipped'],
                'disabled' => $isDisabledStatus
            ]
        )->setAfterElementHtml(<<<HTML
<label for="magento_orders_shipment_mode">
<input id="magento_orders_shipment_mode"
       name="magento_orders_settings[shipment_mode]"
       type="checkbox" $shipmentModeChecked $shipmentModeDisabled> {$this->__('Automatic Shipment Creation')}</label>
HTML
);

        $this->setForm($form);

        $formData['magento_orders_settings']['listing']['mode'] = $this->getHelper('Data')->escapeJs(
            $formData['magento_orders_settings']['listing']['mode']
        );
        $formData['magento_orders_settings']['listing']['store_mode'] = $this->getHelper('Data')->escapeJs(
            $formData['magento_orders_settings']['listing']['store_mode']
        );
        $formData['magento_orders_settings']['listing']['store_id'] = $this->getHelper('Data')->escapeJs(
            $formData['magento_orders_settings']['listing']['store_id']
        );

        $formData['magento_orders_settings']['listing_other']['mode'] = $this->getHelper('Data')->escapeJs(
            $formData['magento_orders_settings']['listing_other']['mode']
        );
        $formData['magento_orders_settings']['listing_other']['store_id'] = $this->getHelper('Data')->escapeJs(
            $formData['magento_orders_settings']['listing_other']['store_id']
        );
        $formData['magento_orders_settings']['listing_other']['product_mode'] = $this->getHelper('Data')->escapeJs(
            $formData['magento_orders_settings']['listing_other']['product_mode']
        );

        $formData['magento_orders_settings']['customer']['mode'] = $this->getHelper('Data')->escapeJs(
            $formData['magento_orders_settings']['customer']['mode']
        );
        $formData['magento_orders_settings']['customer']['id'] = $this->getHelper('Data')->escapeJs(
            $formData['magento_orders_settings']['customer']['id']
        );
        $formData['magento_orders_settings']['customer']['website_id'] = $this->getHelper('Data')->escapeJs(
            $formData['magento_orders_settings']['customer']['website_id']
        );
        $formData['magento_orders_settings']['customer']['group_id'] = $this->getHelper('Data')->escapeJs(
            $formData['magento_orders_settings']['customer']['group_id']
        );

        $this->js->add(<<<JS

    M2ePro.formData.magento_orders_listings_mode = "{$formData['magento_orders_settings']['listing']['mode']}";
    M2ePro.formData.magento_orders_listings_store_mode
        = "{$formData['magento_orders_settings']['listing']['store_mode']}";
    M2ePro.formData.magento_orders_listings_store_id
        = "{$formData['magento_orders_settings']['listing']['store_id']}";

    M2ePro.formData.magento_orders_listings_other_mode
        = "{$formData['magento_orders_settings']['listing_other']['mode']}";
    M2ePro.formData.magento_orders_listings_other_store_id
        = "{$formData['magento_orders_settings']['listing_other']['store_id']}";
    M2ePro.formData.magento_orders_listings_other_product_mode
        = "{$formData['magento_orders_settings']['listing_other']['product_mode']}";

    M2ePro.formData.magento_orders_customer_mode = "{$formData['magento_orders_settings']['customer']['mode']}";
    M2ePro.formData.magento_orders_customer_id = "{$formData['magento_orders_settings']['customer']['id']}";
    M2ePro.formData.magento_orders_customer_new_website_id
        = "{$formData['magento_orders_settings']['customer']['website_id']}";
    M2ePro.formData.magento_orders_customer_new_group_id
        = "{$formData['magento_orders_settings']['customer']['group_id']}";

JS
);

        return parent::_prepareForm();
    }
}