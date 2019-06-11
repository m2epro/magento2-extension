<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

use Ess\M2ePro\Model\Walmart\Account;

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
        $ordersSettings = !is_null($account) ? $account->getChildObject()->getData('magento_orders_settings') : [];
        $ordersSettings = !empty($ordersSettings) ? $this->getHelper('Data')->jsonDecode($ordersSettings) : array();

        // ---------------------------------------
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
                    'store_id' => NULL,
                ),
                'number' => array(
                    'source' => Account::MAGENTO_ORDERS_NUMBER_SOURCE_MAGENTO,
                    'prefix' => array(
                        'mode'   => Account::MAGENTO_ORDERS_NUMBER_PREFIX_MODE_NO,
                        'prefix' => '',
                    )
                ),
                'tax' => array(
                    'mode' => Account::MAGENTO_ORDERS_TAX_MODE_MIXED
                ),
                'customer' => array(
                    'mode' => Account::MAGENTO_ORDERS_CUSTOMER_MODE_GUEST,
                    'id' => NULL,
                    'website_id' => NULL,
                    'group_id' => NULL,
                    'notifications' => array(
                        'invoice_created' => false,
                        'order_created' => false
                    ),
                ),
                'status_mapping' => array(
                    'mode' => Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT,
                    'processing' => Account::MAGENTO_ORDERS_STATUS_MAPPING_PROCESSING,
                    'shipped' => Account::MAGENTO_ORDERS_STATUS_MAPPING_SHIPPED,
                ),
                'invoice_mode' => Account::MAGENTO_ORDERS_INVOICE_MODE_YES,
                'shipment_mode' => Account::MAGENTO_ORDERS_SHIPMENT_MODE_YES
            )
        );

        $isEdit = !!$this->getRequest()->getParam('id');

        $isEdit && $defaults['magento_orders_settings']['refund_and_cancellation']['refund_mode'] = 0;

        $formData = array_replace_recursive($defaults, $formData);

        $form = $this->_formFactory->create();

        $form->addField('walmart_accounts_orders',
            self::HELP_BLOCK,
            [
                'content' => $this->__(<<<HTML
Specify how M2E Pro should process your Walmart sales. Enable an automatic Magento Order creation to reduce Magento
stock once an Item is purchased on the Walmart website.<br/>
You can select which tax settings should be applied to an Order,
activate an automatic invoice and shipment creation, etc.<br/><br/>

The detailed information can be found <a href="%url%" target="_blank">here</a>.
HTML
                    ,
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/VwBhAQ')
                )
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
                    'Enable to automatically create Magento Order if the Channel Order was placed for
                    the Item listed via M2E Pro.'
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
                    'Specify whether Magento Store View should be determined automatically
                    based on M2E Pro Listing settings or selected manually.'
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
                'value' => !empty($ordersSettings['listing']['store_id'])
                    ? $ordersSettings['listing']['store_id'] : '',
                'has_empty_option' => true,
                'has_default_option' => false,
                'tooltip' => $this->__('The Magento Store View that Orders will be placed in.')
            ]
        );

        $fieldset = $form->addFieldset('magento_block_walmart_accounts_magento_orders_listings_other',
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
                    'Whether an Order has to be created in Magento if a sold Product
                    does not belong to M2E Pro Listings.'
                )
            ]
        );

        $fieldset->addField('magento_orders_listings_other_store_id',
            self::STORE_SWITCHER,
            [
                'container_id' => 'magento_orders_listings_other_store_id_container',
                'name' => 'magento_orders_settings[listing_other][store_id]',
                'label' => $this->__('Magento Store View'),
                'value' => !empty($ordersSettings['listing_other']['store_id'])
                    ? $ordersSettings['listing_other']['store_id'] : '',
                'required' => true,
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
                'tooltip' => $this->__('What has to be done if a Listed Product does not exist in Magento.')
                    . '<span id="magento_orders_listings_other_product_mode_note">'
                    . $this->__(
                        '<br/><b>Note:</b> Only Simple Products without Variations can be created in Magento.
                         If there is a Product with Variations on Walmart,
                         M2E Pro creates different Simple Products for each Variation.'
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

        $fieldset = $form->addFieldset('magento_block_walmart_accounts_magento_orders_number',
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
                    Account::MAGENTO_ORDERS_NUMBER_SOURCE_CHANNEL => $this->__('Walmart'),
                ],
                'value' => $formData['magento_orders_settings']['number']['source'],
                'tooltip' => $this->__(
                    'If source is set to Magento, Magento Order numbers are created basing on your Magento Settings.
                    If source is set to Walmart, Magento Order numbers are the same as Walmart Order numbers.'
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
                'tooltip' => $this->__('Choose to set the prefix before Magento Order number.')
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

        $fieldset->addField('sample_walmart_order_id',
            'hidden',
            [
                'value' => '141-4423723-6495633'
            ]
        );

        $fieldset->addField('order_number_example',
            'label',
            [
                'label' => '',
                'note' => $this->__('e.g.') . ' <span id="order_number_example_container"></span>'
            ]
        );

        $fieldset = $form->addFieldset('magento_block_walmart_accounts_magento_orders_customer',
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
                'note' => $this->__('Customer for which Magento Orders will be created.'),
                'tooltip' => $this->__(
                    'There are several ways to specify a Customer for which Magento Orders will be created: <br/><br/>
                     <b>Guest Account</b> - the System does not require a Customer Account to be created.
                     Default Guest Account will be defined as a Customer. <br/>
                     <b>Note:</b> The Guest Checkout Option must be enabled in Magento.
                     (<i>Yes</i> must be chosen in the Allow Guest Checkout Option in
                     Magento > Stores > Configuration > Sales > Checkout). <br/>
                     <b>Predefined Customer</b> - the System uses one predefined
                     Customer for all Walmart Orders related to this Account. You will be required
                     to provide an ID of the existing Customer, which you can find in
                     Magento > Customers > All Customers. <br/>
                     <b>Create New</b> - a new Customer will be created in Magento,
                     using Walmart Customer data of Walmart Order. <br/>
                     <b>Note:</b> A unique Customer Identifier is his e-mail address.
                     If the one already exists among Magento Customers e-mails,
                     the System uses this Customer as owner of Order and links Order to him.
                      A new Customer will not be created. <br/>
                ')
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

        $fieldset = $form->addFieldset('magento_block_walmart_accounts_magento_orders_tax',
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
                    Account::MAGENTO_ORDERS_TAX_MODE_CHANNEL => $this->__('Walmart'),
                    Account::MAGENTO_ORDERS_TAX_MODE_MAGENTO => $this->__('Magento'),
                    Account::MAGENTO_ORDERS_TAX_MODE_MIXED => $this->__('Walmart & Magento'),
                ],
                'value' => $formData['magento_orders_settings']['tax']['mode'],
                'tooltip' => $this->__('This Section allows you to choose Tax Settings for Magento Order:
                    <ul class="list">
                        <li><b>Walmart</b> - Magento Order(s) uses Tax Settings from Walmart Listing(s).</li>
                        <li><b>Magento</b> - Magento Order(s) uses Magento Tax Settings.</li>
                        <li><b>Walmart & Magento</b> - if there are Tax Settings in Walmart Order,
                        they are used in Magento Order(s), otherwise, Magento Tax Settings are used.</li>
                        <li><b>None</b> - Walmart and Magento Tax Settings are ignored.</li>
                    </ul>'
                )
            ]
        );

        $fieldset = $form->addFieldset('magento_block_walmart_accounts_magento_orders_status_mapping',
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

            $formData['magento_orders_settings']['invoice_mode'] = Account::MAGENTO_ORDERS_INVOICE_MODE_YES;
            $formData['magento_orders_settings']['shipment_mode'] = Account::MAGENTO_ORDERS_SHIPMENT_MODE_YES;
        }

        $statusList = $this->orderConfig->getStatuses();

        $invoiceModeDisabled = $isDisabledStatusStyle ? 'disabled="disabled"' : '';
        $invoiceModeChecked = $formData['magento_orders_settings']['status_mapping']['mode']
                                             == Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT ||
                              $formData['magento_orders_settings']['invoice_mode']
                                             == Account::MAGENTO_ORDERS_INVOICE_MODE_YES
            ? 'checked="checked"' : '';

        $fieldset->addField('magento_orders_status_mapping_processing',
            'select',
            [
                'container_id' => 'magento_orders_status_mapping_processing_container',
                'name' => 'magento_orders_settings[status_mapping][processing]',
                'label' => $this->__('Order Status is Unshipped / Partially Shipped'),
                'values' => $statusList,
                'value' => $formData['magento_orders_settings']['status_mapping']['processing'],
                'disabled' => $isDisabledStatusStyle
            ]
        )->setAfterElementHtml(<<<HTML
<label for="magento_orders_invoice_mode">
<input id="magento_orders_invoice_mode"
       name="magento_orders_settings[invoice_mode]"
       type="checkbox" $invoiceModeChecked $invoiceModeDisabled> {$this->__('Automatic Invoice Creation')}</label>
HTML
        );

        $shipmentModeDisabled = $isDisabledStatusStyle ? 'disabled="disabled"' : '';
        $shipmentModeChecked = $formData['magento_orders_settings']['status_mapping']['mode']
                                    == Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT ||
                               $formData['magento_orders_settings']['shipment_mode']
                                    == Account::MAGENTO_ORDERS_SHIPMENT_MODE_YES
            ? 'checked="checked"' : '';

        $fieldset->addField('magento_orders_status_mapping_shipped',
            'select',
            [
                'container_id' => 'magento_orders_status_mapping_shipped_container',
                'name' => 'magento_orders_settings[status_mapping][shipped]',
                'label' => $this->__('Shipping Is Completed'),
                'values' => $statusList,
                'value' => $formData['magento_orders_settings']['status_mapping']['shipped'],
                'disabled' => $isDisabledStatusStyle
            ]
        )->setAfterElementHtml(<<<HTML
<label for="magento_orders_shipment_mode">
<input id="magento_orders_shipment_mode"
       name="magento_orders_settings[shipment_mode]"
       type="checkbox" $shipmentModeChecked $shipmentModeDisabled> {$this->__('Automatic Shipment Creation')}</label>
HTML
        );

        $this->setForm($form);

        $this->jsTranslator->addTranslations([
            'No Customer entry is found for specified ID.' => $this->__('No Customer entry is found for specified ID.'),
            'Prefix length should not be greater than 5 characters.' =>
                $this->__('Prefix length should not be greater than 5 characters.'),
        ]);

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
    M2ePro.formData.magento_orders_listings_store_id = "{$formData['magento_orders_settings']['listing']['store_id']}";

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