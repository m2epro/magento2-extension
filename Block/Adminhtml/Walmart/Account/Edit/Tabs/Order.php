<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

use Ess\M2ePro\Model\Walmart\Account;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Account\Edit\Tabs\Order
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
        $ordersSettings = $account !== null ? $account->getChildObject()->getData('magento_orders_settings') : [];
        $ordersSettings = !empty($ordersSettings) ? $this->getHelper('Data')->jsonDecode($ordersSettings) : [];

        // ---------------------------------------
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

        $formData = $account !== null ? array_merge($account->getData(), $account->getChildObject()->getData()) : [];
        $formData['magento_orders_settings'] = !empty($formData['magento_orders_settings'])
            ? $this->getHelper('Data')->jsonDecode($formData['magento_orders_settings']) : [];

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
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/XgBhAQ')
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
                    'Enable to automatically create Magento Order if the Channel Order was placed for
                    the Item listed via M2E Pro.'
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
                    'Specify whether Magento Store View should be determined automatically
                    based on M2E Pro Listing settings or selected manually.'
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
                'value'              => !empty($ordersSettings['listing']['store_id'])
                    ? $ordersSettings['listing']['store_id'] : '',
                'has_empty_option'   => true,
                'has_default_option' => false,
                'tooltip'            => $this->__('The Magento Store View that Orders will be placed in.')
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_walmart_accounts_magento_orders_listings_other',
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
                    'Choose whether a Magento Order should be created if a Walmart Order is received for an item that 
                    does <b>not</b> belong to the M2E Pro Listing.'
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
                'value'              => !empty($ordersSettings['listing_other']['store_id'])
                    ? $ordersSettings['listing_other']['store_id'] : '',
                'required'           => true,
                'has_empty_option'   => true,
                'has_default_option' => false,
                'tooltip'            => $this->__(
                    'Select Magento Store View that will be associated with Magento Order.'
                )
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
                'tooltip'      => $this->__('What has to be done if a Listed Product does not exist in Magento.')
                    . '<span id="magento_orders_listings_other_product_mode_note">'
                    . $this->__(
                        'Specify which action should be performed if the purchased Item does not have
                        the corresponding Product in Magento. <br><br>
                        <strong>Note:</strong> Only Simple Magento Products can be created based on these settings.
                        If a Variational Item was purchased, M2E Pro will automatically create different
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
                'tooltip'      => $this->__('Select the Tax Class which will be used for Products created by M2E Pro.')
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_walmart_accounts_magento_orders_number',
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
                    Account::MAGENTO_ORDERS_NUMBER_SOURCE_CHANNEL => $this->__('Walmart'),
                ],
                'value'   => $formData['magento_orders_settings']['number']['source'],
                'tooltip' => $this->__(
                    'Select whether Magento Order number should be generated based on your
                    Magento settings or Walmart Order number.'
                )
            ]
        );

        $fieldset->addField(
            'magento_orders_number_prefix_prefix',
            'text',
            [
                'container_id' => 'magento_orders_number_prefix_container',
                'name'         => 'magento_orders_settings[number][prefix][prefix]',
                'label'        => $this->__('Prefix'),
                'value'        => $formData['magento_orders_settings']['number']['prefix']['prefix'],
                'maxlength'    => 5
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
            'sample_walmart_order_id',
            'hidden',
            [
                'value' => '141-4423723-6495633'
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
            'magento_block_walmart_accounts_magento_orders_refund_and_cancellation',
            [
                'legend'      => $this->__('Refund & Cancellation'),
                'collapsable' => true,
            ]
        );

        $fieldset->addField(
            'magento_orders_refund',
            'select',
            [
                'container_id' => 'magento_orders_refund_container',
                'name'         => 'magento_orders_settings[refund_and_cancellation][refund_mode]',
                'label'        => $this->__('Cancel or Refund if Credit Memo is Created'),
                'values'       => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value'        => $formData['magento_orders_settings']['refund_and_cancellation']['refund_mode'],
                'tooltip'      => $this->__(
                    'Enable the <i>Cancel or Refund if Credit Memo is Created</i> option to automatically cancel or
                     refund Walmart order after the Credit Memo is created for the associated Magento order. <br/><br/>
                     Walmart order will be <b>canceled</b> automatically if Credit Memo is created for all the items
                     in the associated Magento order. <br/><br/>
                     Walmart order can be <b>refunded</b> if it has <i>Shipped</i> or <i>Partially Shipped</i> status.
                     Refund is issued only for items indicated in the Credit Memo.'
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_walmart_accounts_magento_orders_customer',
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
                'note'    => $this->__('Customer for which Magento Orders will be created.'),
                'tooltip' => $this->__(
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
                'required'     => true,
                'tooltip'      => $this->__('Enter Magento Customer ID.')
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
                'required'     => true,
                'tooltip'      => $this->__('Select Magento Website where a new Customer should be created.')
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
                'required'     => true,
                'tooltip'      => $this->__('Select Magento Customer Group where a new Customer should be created.')
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
                    '<p>Select certain conditions when the emails should be sent to Customer.
                    Hold Ctrl to select multiple options.</p>
                    <p><strong>Note:</strong> the related email type must be enabled in your Magento:
                    <i>System > Configuration > Sales Emails</i>.</p>'
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_walmart_accounts_magento_orders_tax',
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
                    Account::MAGENTO_ORDERS_TAX_MODE_CHANNEL => $this->__('Walmart'),
                    Account::MAGENTO_ORDERS_TAX_MODE_MAGENTO => $this->__('Magento'),
                    Account::MAGENTO_ORDERS_TAX_MODE_MIXED   => $this->__('Walmart & Magento'),
                ],
                'value'   => $formData['magento_orders_settings']['tax']['mode'],
                'tooltip' => $this->__(
                    'Select the Tax settings that should be applied to Magento Order:
                    <ul class="list">
                        <li>
                        <b>Walmart</b> - the Tax settings configured in your Walmart Seller Center will be used.
                        </li>
                        <li><b>Magento</b> - the Tax settings configured in your Magento will be used.</li>
                        <li><b>Walmart & Magento</b> - Walmart Tax settings will be applied if specified.
                        Otherwise, Magento Tax settings will be used.</li>
                        <li><b>None</b> - no Taxes will be applied.</li>
                    </ul>'
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_walmart_accounts_magento_orders_status_mapping',
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
                    'Set the correspondence between Walmart and Magento Order statuses.
                    M2E Pro supports an automatic generation of Invoices and Shipments.
                    Enable the options next to the related Order statuses.<br/>'
                )
            ]
        );

        $isDisabledStatusStyle = (
            $formData['magento_orders_settings']['status_mapping']['mode']
            == Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT
        );

        if ($formData['magento_orders_settings']['status_mapping']['mode']
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
                'name'         => 'magento_orders_settings[status_mapping][processing]',
                'label'        => $this->__('Order Status is Unshipped / Partially Shipped'),
                'values'       => $statusList,
                'value'        => $formData['magento_orders_settings']['status_mapping']['processing'],
                'disabled'     => $isDisabledStatusStyle
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
                'disabled'     => $isDisabledStatusStyle
            ]
        );

        $this->setForm($form);

        $this->jsTranslator->addTranslations(
            [
                'No Customer entry is found for specified ID.' => $this->__(
                    'No Customer entry is found for specified ID.'
                ),
            ]
        );

        return parent::_prepareForm();
    }
}
