<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Ebay\Account;
use Magento\Framework\Message\MessageInterface;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs\Order
 */
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
    ) {
        $this->orderConfig = $orderConfig;
        $this->customerGroup = $customerGroup;
        $this->taxClass = $taxClass;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $account = $this->getHelper('Data\GlobalData')->getValue('edit_account');

        $websites = $this->getHelper('Magento_Store_Website')->getWebsites(true);

        // ---------------------------------------

        // ---------------------------------------
        $temp = $this->customerGroup->getCollection()->toArray();
        $groups = $temp['items'];
        // ---------------------------------------

        // ---------------------------------------
        $productTaxClasses = $this->taxClass->getCollection()
            ->addFieldToFilter('class_type', \Magento\Tax\Model\ClassModel::TAX_CLASS_TYPE_PRODUCT)
            ->toOptionArray();
        $none = ['value' => \Ess\M2ePro\Model\Magento\Product::TAX_CLASS_ID_NONE, 'label' => $this->__('None')];
        array_unshift($productTaxClasses, $none);

        // ---------------------------------------

        $formData = $account !== null ? array_merge($account->getData(), $account->getChildObject()->getData()) : [];
        $formData['magento_orders_settings'] = !empty($formData['magento_orders_settings'])
            ? $this->getHelper('Data')->jsonDecode($formData['magento_orders_settings']) : [];

        $defaults = $this->modelFactory->getObject('Ebay_Account_Builder')->getDefaultData();

        $formData = array_replace_recursive($defaults, $formData);

        $form = $this->_formFactory->create();

        $form->addField(
            'ebay_accounts_orders',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    <<<HTML
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
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/LgItAQ')
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'listed_by_m2e',
            [
                'legend'      => $this->__('Product Is Listed By M2E Pro'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'magento_orders_listings_mode',
            'select',
            [
                'name'    => 'magento_orders_settings[listing][mode]',
                'label'   => $this->__('Create Order in Magento'),
                'values'  => [
                    1 => $this->__('Yes'),
                    0 => $this->__('No'),
                ],
                'value'   => $formData['magento_orders_settings']['listing']['mode'],
                'tooltip' => $this->__(
                    'Choose whether a Magento Order should be created if an eBay Order is received for
                    an eBay Item Listed using M2E Pro.'
                )
            ]
        );

        $fieldset->addField(
            'magento_orders_listings_store_mode',
            'select',
            [
                'container_id' => 'magento_orders_listings_store_mode_container',
                'name'         => 'magento_orders_settings[listing][store_mode]',
                'label'        => $this->__('Magento Store View Source'),
                'values'       => [
                    Account::MAGENTO_ORDERS_LISTINGS_STORE_MODE_DEFAULT => $this->__('Use Store View from Listing'),
                    Account::MAGENTO_ORDERS_LISTINGS_STORE_MODE_CUSTOM  => $this->__('Choose Store View Manually'),
                ],
                'value'        => $formData['magento_orders_settings']['listing']['store_mode'],
                'tooltip'      => $this->__(
                    'Choose to specify the Magento Store View here or to keep the Magento
                    Store View used in the M2E Pro Listing.'
                )
            ]
        );

        $fieldset->addField(
            'magento_orders_listings_store_id',
            self::STORE_SWITCHER,
            [
                'container_id'       => 'magento_orders_listings_store_id_container',
                'name'               => 'magento_orders_settings[listing][store_id]',
                'label'              => $this->__('Magento Store View'),
                'required'           => true,
                'value'              => $formData['magento_orders_settings']['listing']['store_id'],
                'has_empty_option'   => true,
                'has_default_option' => false,
                'tooltip'            => $this->__('The Magento Store View that Orders will be placed in.')
            ]
        );

        $fieldset = $form->addFieldset(
            'listed_by_other',
            [
                'legend'      => $this->__('Product Is Listed By Any Other Software'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'magento_orders_listings_other_mode',
            'select',
            [
                'name'    => 'magento_orders_settings[listing_other][mode]',
                'label'   => $this->__('Create Order in Magento'),
                'values'  => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value'   => $formData['magento_orders_settings']['listing_other']['mode'],
                'tooltip' => $this->__(
                    'Choose whether a Magento Order should be created if an eBay Order is received for an item
                    that does <b>not</b> belong to the M2E Pro Listing.'
                )
            ]
        );

        $fieldset->addField(
            'magento_orders_listings_other_store_id',
            self::STORE_SWITCHER,
            [
                'container_id'       => 'magento_orders_listings_other_store_id_container',
                'name'               => 'magento_orders_settings[listing_other][store_id]',
                'label'              => $this->__('Magento Store View'),
                'required'           => true,
                'value'              => $formData['magento_orders_settings']['listing_other']['store_id'],
                'has_empty_option'   => true,
                'has_default_option' => false,
                'tooltip'            => $this->__('The Magento Store View that Orders will be placed in.')
            ]
        );

        $fieldset->addField(
            'magento_orders_listings_other_product_mode',
            'select',
            [
                'container_id' => 'magento_orders_listings_other_product_mode_container',
                'name'         => 'magento_orders_settings[listing_other][product_mode]',
                'label'        => $this->__('Product Not Found'),
                'values'       => [
                    Account::MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IGNORE => $this->__('Do Not Create Order'),
                    Account::MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IMPORT => $this->__('Create Product and Order'),
                ],
                'value'        => $formData['magento_orders_settings']['listing_other']['product_mode'],
                'tooltip'      => $this->__(
                        'Choose what should happen if an Order is received for a Product that
                         does not exist in your Magento Inventory.'
                    )
                    . '<span id="magento_orders_listings_other_product_mode_note">'
                    . $this->__(
                        '<br/><b>Note:</b> Only Simple Products without Variations can be created in Magento.
                            If there is a Product with Variations on eBay, M2E Pro creates different
                            Simple Products for each Variation.'
                    )
                    . '</span>'
            ]
        );

        $fieldset->addField(
            'magento_orders_listings_other_product_mode_warning',
            self::MESSAGES,
            [
                'messages' => [
                    [
                        'type'    => \Magento\Framework\Message\MessageInterface::TYPE_NOTICE,
                        'content' => $this->__(
                            'Please note that a new Magento Product will be created
                            if the corresponding SKU is not found in your Catalog.'
                        )
                    ]
                ],
                'style'    => 'max-width:450px; margin-left:20%'
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
                'name'         => 'magento_orders_settings[listing_other][product_tax_class_id]',
                'label'        => $this->__('Product Tax Class'),
                'values'       => $values,
                'value'        => $formData['magento_orders_settings']['listing_other']['product_tax_class_id'],
                'tooltip'      => $this->__('Tax Class which will be used for Products created by M2E Pro.')
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_accounts_magento_orders_number',
            [
                'legend'      => $this->__('Magento Order Number'),
                'collapsable' => true
            ]
        );

        $fieldset->addField(
            'magento_orders_number_source',
            'select',
            [
                'name'    => 'magento_orders_settings[number][source]',
                'label'   => $this->__('Source'),
                'values'  => [
                    Account::MAGENTO_ORDERS_NUMBER_SOURCE_MAGENTO => $this->__('Magento'),
                    Account::MAGENTO_ORDERS_NUMBER_SOURCE_CHANNEL => $this->__('eBay'),
                ],
                'value'   => $formData['magento_orders_settings']['number']['source'],
                'tooltip' => $this->__(
                    'If source is set to Magento, Magento Order numbers are created basing on your Magento Settings.
                    If source is set to eBay, Magento Order numbers are the same as eBay Order numbers.'
                )
            ]
        );

        $fieldset->addField(
            'magento_orders_number_prefix_prefix',
            'text',
            [
                'container_id' => 'magento_orders_number_prefix_container',
                'name'         => 'magento_orders_settings[number][prefix][prefix]',
                'label'        => $this->__('General Prefix'),
                'value'        => $formData['magento_orders_settings']['number']['prefix']['prefix'],
                'maxlength'    => 5
            ]
        );

        $fieldset->addField(
            'magento_orders_number_prefix_use_marketplace_prefix',
            'select',
            [
                'name'   => 'magento_orders_settings[number][prefix][use_marketplace_prefix]',
                'label'  => $this->__('Use Marketplace ID as a prefix'),
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value'  => $formData['magento_orders_settings']['number']['prefix']['use_marketplace_prefix']
            ]
        );

        $fieldset->addField(
            'sample_magento_order_id',
            'hidden',
            [
                'value' => $this->getHelper('Magento')->getNextMagentoOrderId()
            ]
        );

        $fieldset->addField(
            'sample_ebay_order_id',
            'hidden',
            [
                'value' => '07-02610-01994'
            ]
        );

        $fieldset->addField(
            'sample_marketplace_prefix',
            'hidden',
            [
                'value' => 'GB'
            ]
        );

        $fieldset->addField(
            'order_number_example',
            'label',
            [
                'label' => '',
                'note'  => $this->__('e.g.') . ' <span id="order_number_example_container"></span>'
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_accounts_magento_orders_customer',
            [
                'legend'      => $this->__('Customer Settings'),
                'collapsable' => true
            ]
        );

        $fieldset->addField(
            'magento_orders_customer_mode',
            'select',
            [
                'name'    => 'magento_orders_settings[customer][mode]',
                'label'   => $this->__('Customer'),
                'values'  => [
                    Account::MAGENTO_ORDERS_CUSTOMER_MODE_GUEST      => $this->__('Guest Account'),
                    Account::MAGENTO_ORDERS_CUSTOMER_MODE_PREDEFINED => $this->__('Predefined Customer'),
                    Account::MAGENTO_ORDERS_CUSTOMER_MODE_NEW        => $this->__('Create New'),
                ],
                'value'   => $formData['magento_orders_settings']['customer']['mode'],
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

        $fieldset->addField(
            'magento_orders_customer_id',
            'text',
            [
                'container_id' => 'magento_orders_customer_id_container',
                'class'        => 'validate-digits M2ePro-account-customer-id',
                'name'         => 'magento_orders_settings[customer][id]',
                'label'        => $this->__('Customer ID'),
                'value'        => $formData['magento_orders_settings']['customer']['id'],
                'required'     => true
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
                'name'         => 'magento_orders_settings[customer][website_id]',
                'label'        => $this->__('Associate to Website'),
                'values'       => $values,
                'value'        => $formData['magento_orders_settings']['customer']['website_id'],
                'required'     => true
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
                'name'         => 'magento_orders_settings[customer][group_id]',
                'label'        => $this->__('Customer Group'),
                'values'       => $values,
                'value'        => $formData['magento_orders_settings']['customer']['group_id'],
                'required'     => true
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
                'name'         => 'magento_orders_settings[customer][notifications][]',
                'label'        => $this->__('Send Emails When The Following Is Created'),
                'values'       => [
                    ['label' => $this->__('Magento Order'), 'value' => 'order_created'],
                    ['label' => $this->__('Invoice'), 'value' => 'invoice_created'],
                ],
                'value'        => $value,
                'tooltip'      => $this->__(
                    '<p>Necessary emails will be sent according to Magento Settings in
                    Stores > Configuration > Sales > Sales Emails.</p>
                    <p>Hold Ctrl Button to choose more than one Option.</p>'
                )
            ]
        );

        $fieldset->addField(
            'magento_orders_customer_billing_address_mode',
            'select',
            [
                'name'    => 'magento_orders_settings[customer][billing_address_mode]',
                'label'   => $this->__('Billing Address Usage'),
                'values'  => [
                    Account::USE_SHIPPING_ADDRESS_AS_BILLING_ALWAYS                         => $this->__(
                        'Always'
                    ),
                    Account::USE_SHIPPING_ADDRESS_AS_BILLING_IF_SAME_CUSTOMER_AND_RECIPIENT => $this->__(
                        'Buyer & Recipient have the same name'
                    ),
                ],
                'value'   => $formData['magento_orders_settings']['customer']['billing_address_mode'],
                'note'    => $this->__('When to use shipping address as billing.'),
                'tooltip' => $this->__(
                    'The eBay does not supply the complete billing Buyer information,
                     only the Buyer\'s name and email address. The only way to fill in billing address in the
                     Customer\'s invoice is to use information from shipping address.<br/><br/>
                     You should select the appropriate Option how to handle billing address for imported Customer:<br/>
                     <br/>
                     <strong>Always</strong> - the shipping address is always used as billing address. <br/>
                     <strong>Buyer & Recipient have the same name</strong> - the shipping address is used as billing
                     address, only when Buyer\'s name and Recipient\'s name are the same. Otherwise,
                     billing address fields will be empty and next message will appear in the city field:
                     "eBay does not supply the complete billing Buyer information". <br/>'
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_accounts_magento_orders_rules',
            [
                'legend'      => $this->__('Order Creation Rules'),
                'collapsable' => true,
            ]
        );

        $fieldset->addField(
            'magento_orders_creation_mode_immediately_warning',
            self::MESSAGES,
            [
                'messages' => [
                    [
                        'type'    => MessageInterface::TYPE_WARNING,
                        'content' => $this->__(
                            'Please note that Immediate Magento order creation sets certain limits to the update of
                            the later order changes. If the shipping or tax details are modified after checkout is
                            completed, these changes will not be reflected in Magento order.'
                        )
                    ],
                ],
                'style'    => 'display: none'
            ]
        );

        $fieldset->addField(
            'magento_orders_creation_mode',
            'select',
            [
                'name'    => 'magento_orders_settings[creation][mode]',
                'label'   => $this->__('Create Magento Order When'),
                'values'  => [
                    Account::MAGENTO_ORDERS_CREATE_CHECKOUT          => $this->__('Checkout Is Completed'),
                    Account::MAGENTO_ORDERS_CREATE_CHECKOUT_AND_PAID => $this->__('Payment Is Received'),
                ],
                'value'   => $formData['magento_orders_settings']['creation']['mode'],
                'tooltip' => $this->__(
                    'Choose at which stage of eBay Order processing a Magento Order should be created.'
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

        $fieldset->addField(
            'magento_orders_qty_reservation_days',
            'select',
            [
                'container_id' => 'magento_orders_qty_reservation_days_container',
                'name'         => 'magento_orders_settings[qty_reservation][days]',
                'label'        => $this->__('Reserve Quantity'),
                'values'       => $values,
                'value'        => $formData['magento_orders_settings']['qty_reservation']['days'],
                'tooltip'      => $this->__(
                    'Choose for how long M2E Pro should reserve Magento Product quantity per eBay Order until
                    Magento Order is created.'
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_accounts_magento_orders_refund_and_cancellation',
            [
                'legend'      => $this->__('Refund & Cancellation') . ' [Beta]',
                'collapsable' => true
            ]
        );

        $fieldset->addField(
            'magento_orders_refund',
            'select',
            [
                'container_id' => 'magento_orders_refund_container',
                'name'         => 'magento_orders_settings[refund_and_cancellation][refund_mode]',
                'label'        => $this->__('Cancel/Refund eBay orders'),
                'values'       => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value'        => $formData['magento_orders_settings']['refund_and_cancellation']['refund_mode'],
                'tooltip'      => $this->__(
                    'Enable to cancel or refund eBay orders and automatically update their statuses on the Channel.
                    Find more details <a href="%url%" target="_blank">here</a>.',
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/aAL9AQ')
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_accounts_magento_orders_tax',
            [
                'legend'      => $this->__('Order Tax Settings'),
                'collapsable' => true
            ]
        );

        $fieldset->addField(
            'magento_orders_tax_mode',
            'select',
            [
                'name'    => 'magento_orders_settings[tax][mode]',
                'label'   => $this->__('Tax Source'),
                'values'  => [
                    Account::MAGENTO_ORDERS_TAX_MODE_NONE    => $this->__('None'),
                    Account::MAGENTO_ORDERS_TAX_MODE_CHANNEL => $this->__('eBay'),
                    Account::MAGENTO_ORDERS_TAX_MODE_MAGENTO => $this->__('Magento'),
                    Account::MAGENTO_ORDERS_TAX_MODE_MIXED   => $this->__('eBay & Magento'),
                ],
                'value'   => $formData['magento_orders_settings']['tax']['mode'],
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

        $fieldset = $form->addFieldset(
            'magento_block_ebay_accounts_magento_orders_status_mapping',
            [
                'legend'      => $this->__('Order Status Mapping'),
                'collapsable' => true
            ]
        );

        $fieldset->addField(
            'magento_orders_status_mapping_mode',
            'select',
            [
                'name'    => 'magento_orders_settings[status_mapping][mode]',
                'label'   => $this->__('Status Mapping'),
                'values'  => [
                    Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT => $this->__('Default Order Statuses'),
                    Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_CUSTOM  => $this->__('Custom Order Statuses'),
                ],
                'value'   => $formData['magento_orders_settings']['status_mapping']['mode'],
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

            $formData['magento_orders_settings']['invoice_mode'] = 1;
            $formData['magento_orders_settings']['shipment_mode'] = 1;
        }

        $statusList = $this->orderConfig->getStatuses();

        $fieldset->addField(
            'magento_orders_status_mapping_new',
            'select',
            [
                'name'     => 'magento_orders_settings[status_mapping][new]',
                'label'    => $this->__('Checkout Is Completed'),
                'values'   => $statusList,
                'value'    => $formData['magento_orders_settings']['status_mapping']['new'],
                'disabled' => $isDisabledStatus
            ]
        );

        $fieldset->addField(
            'magento_orders_status_mapping_paid',
            'select',
            [
                'container_id' => 'magento_orders_status_mapping_paid_container',
                'name'         => 'magento_orders_settings[status_mapping][paid]',
                'label'        => $this->__('Payment Is Completed'),
                'values'       => $statusList,
                'value'        => $formData['magento_orders_settings']['status_mapping']['paid'],
                'disabled'     => $isDisabledStatus
            ]
        );

        $fieldset->addField(
            'magento_orders_status_mapping_shipped',
            'select',
            [
                'container_id' => 'magento_orders_status_mapping_shipped_container',
                'name'         => 'magento_orders_settings[status_mapping][shipped]',
                'label'        => $this->__('Shipping Is Completed'),
                'values'       => $statusList,
                'value'        => $formData['magento_orders_settings']['status_mapping']['shipped'],
                'disabled'     => $isDisabledStatus
            ]
        );

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
