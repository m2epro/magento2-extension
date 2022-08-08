<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

use Ess\M2ePro\Model\Amazon\Account;
use Magento\Framework\Data\Form\Element\Fieldset;

class Order extends AbstractForm
{
    /** @var \Magento\Sales\Model\Order\Config */
    private $orderConfig;
    /** @var \Magento\Customer\Model\Group */
    private $customerGroup;
    /** @var \Magento\Tax\Model\ClassModel */
    private $taxClass;
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;
    /** @var \Ess\M2ePro\Helper\Magento\Store\Website */
    private $storeWebsiteHelper;
    /** @var \Ess\M2ePro\Model\Amazon\Account\Builder */
    private $accountBuilder;

    /**
     * @param \Ess\M2ePro\Helper\Module\Support $supportHelper
     * @param \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper
     * @param \Ess\M2ePro\Helper\Magento\Store\Website $storeWebsiteHelper
     * @param \Magento\Tax\Model\ClassModel $taxClass
     * @param \Magento\Customer\Model\Group $customerGroup
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Ess\M2ePro\Model\Amazon\Account\Builder $accountBuilder
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Magento\Store\Website $storeWebsiteHelper,
        \Magento\Tax\Model\ClassModel $taxClass,
        \Magento\Customer\Model\Group $customerGroup,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Ess\M2ePro\Model\Amazon\Account\Builder $accountBuilder,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->orderConfig = $orderConfig;
        $this->customerGroup = $customerGroup;
        $this->taxClass = $taxClass;
        $this->supportHelper = $supportHelper;
        $this->globalDataHelper = $globalDataHelper;
        $this->storeWebsiteHelper = $storeWebsiteHelper;
        $this->accountBuilder = $accountBuilder;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @param array $formData
     * @param \Magento\Framework\Data\Form\Element\Fieldset $fieldset
     * @param \Ess\M2ePro\Model\Account|null $account
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function addImportTaxRegistrationNumber(
        array $formData,
        Fieldset $fieldset,
        ?\Ess\M2ePro\Model\Account $account
    ): void {
        $type = 'hidden';
        $value = 0;
        $tooltip = '';

        if (
            isset($account)
            && $account->getChildObject()->getMarketplaceId() == \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_TR
        ) {
            $type = 'select';
            $value = $formData['magento_orders_settings']['tax']['import_tax_id_in_magento_order'];
            $tooltip = $this->getTooltipHtml(
                $this->__(
                    'Once enabled, find the Tax Registration Number displayed as VAT
in the Shipping Address of your Magento Order.'
                )
            );
        }

        $fieldset->addField(
            'magento_orders_tax_import_tax_id_in_magento_order',
            $type,
            [
                'name'    => 'magento_orders_settings[tax][import_tax_id_in_magento_order]',
                'label'   => $this->__('Import Tax Registration Number to Magento Order'),
                'values'  => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value' => $value,
                'after_element_html' => $tooltip
            ]
        );
    }

    protected function _prepareForm()
    {
        /** @var \Ess\M2ePro\Model\Account|null $account */
        $account = $this->globalDataHelper->getValue('edit_account');

        // ---------------------------------------
        $websites = $this->storeWebsiteHelper->getWebsites(true);
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
            ? \Ess\M2ePro\Helper\Json::decode($formData['magento_orders_settings']) : [];

        $defaults = $this->accountBuilder->getDefaultData();

        if (isset($formData['magento_orders_settings']['tax']['excluded_states'])) {
            unset($defaults['magento_orders_settings']['tax']['excluded_states']);
        }

        if (isset($formData['magento_orders_settings']['tax']['excluded_countries'])) {
            unset($defaults['magento_orders_settings']['tax']['excluded_countries']);
        }

        $isEdit = !empty($this->getRequest()->getParam('id'));

        if ($isEdit) {
            $defaults['magento_orders_settings']['refund_and_cancellation']['refund_mode'] = 0;
        }

        $formData = array_replace_recursive($defaults, $formData);

        if (is_array($formData['magento_orders_settings']['tax']['excluded_states'])) {
            $formData['magento_orders_settings']['tax']['excluded_states'] = implode(
                ',',
                $formData['magento_orders_settings']['tax']['excluded_states']
            );
        }

        if (is_array($formData['magento_orders_settings']['tax']['excluded_countries'])) {
            $formData['magento_orders_settings']['tax']['excluded_countries'] = implode(
                ',',
                $formData['magento_orders_settings']['tax']['excluded_countries']
            );
        }

        $form = $this->_formFactory->create();

        $form->addField(
            'amazon_accounts_orders',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    <<<HTML
<p>Specify how M2E Pro should manage the Orders imported from Amazon.</p><br/>
<p>You are able to configure the different rules of <strong>Magento Order Creation</strong> considering whether the
Item was listed via M2E Pro or by some other software.</p><br/>
<p>The <strong>Reserve Quantity</strong> feature will automatically work for imported Amazon Orders with Pending Status
to hold the Stock until Magento Order is created or the reservation term is expired.</p><br/>
<p>Moreover, you can provide the settings for <strong>Orders fulfilled by Amazon</strong>. Specify whether the
corresponding Magento Order has to be created and or not. Additionally, you are able to reduce Magento Stock taking
into account the FBA Orders.</p><br/>
<p>Besides, you can set your preferences for the <strong>Refund & Cancellation, Tax, Customer, Order Number</strong>
and <strong>Order Status Mapping</strong> Settings as well as specify the automatic creation of invoices and
shipment notifications.</p><br/>
<p>More detailed information you can find <a href="%url%" target="_blank" class="external-link">here</a>.</p>
HTML
                    ,
                    $this->supportHelper->getDocumentationArticleUrl('x/Xv8UB')
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'listed_by_m2e',
            [
                'legend'      => $this->__('Product Is Listed By M2E Pro'),
                'collapsable' => true
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
                    'Whether an Order has to be created in Magento if a sold Product belongs to M2E Pro Listings.'
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
                    'If Store View must be automatically taken from the Listing
                    or manually chosen from available Store View values.'
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
                'value'              => !empty($formData['magento_orders_settings']['listing']['store_id'])
                    ? $formData['magento_orders_settings']['listing']['store_id'] : '',
                'has_empty_option'   => true,
                'has_default_option' => false,
                'tooltip'            => $this->__('The Magento Store View that Orders will be placed in.')
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_accounts_magento_orders_listings_other',
            [
                'legend'      => $this->__('Product Is Listed By Any Other Software'),
                'collapsable' => true
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
                    'Choose whether a Magento Order should be created if an Amazon Order is received for an item that
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
                'value'              => !empty($formData['magento_orders_settings']['listing_other']['store_id'])
                    ? $formData['magento_orders_settings']['listing_other']['store_id'] : '',
                'required'           => true,
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
                'tooltip'      => $this->__('What has to be done if a Listed Product does not exist in Magento.')
                    . '<span id="magento_orders_listings_other_product_mode_note">'
                    . $this->__(
                        '<br/><b>Note:</b> Only Simple Products without Variations can be created in Magento.
                         If there is a Product with Variations on Amazon,
                         M2E Pro creates different Simple Products for each Variation.'
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
            'magento_block_amazon_accounts_magento_orders_fba',
            [
                'legend'      => $this->__('FBA Orders Settings'),
                'collapsable' => true,
                'tooltip'     => $this->__(
                    'In this Block you can manage Stock Inventory of Products fulfilled by Amazon  (FBA Orders).<br/>
                <b>Yes</b> - after Magento Order Creation of FBA Order, Quantity of Product reduces in Magento.<br/>
                <b>No</b> - Magento Order Creation of FBA Order does not affect Quantity of Magento Product.'
                )
            ]
        );

        $fieldset->addField(
            'magento_orders_fba_mode',
            'select',
            [
                'name'    => 'magento_orders_settings[fba][mode]',
                'label'   => $this->__('Create Order in Magento'),
                'values'  => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value'   => $formData['magento_orders_settings']['fba']['mode'],
                'tooltip' => $this->__(
                    'Whether an Order has to be created in Magento if a sold Product is fulfilled by Amazon.'
                )
            ]
        );

        $fieldset->addField(
            'magento_orders_fba_store_mode',
            'select',
            [
                'container_id' => 'magento_orders_fba_store_mode_container',
                'name'         => 'magento_orders_settings[fba][store_mode]',
                'label'        => $this->__('Create in separate Store View'),
                'values'       => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value'        => $formData['magento_orders_settings']['fba']['store_mode']
            ]
        );

        $fieldset->addField(
            'magento_orders_fba_store_id',
            self::STORE_SWITCHER,
            [
                'container_id'       => 'magento_orders_fba_store_id_container',
                'name'               => 'magento_orders_settings[fba][store_id]',
                'label'              => $this->__('Magento Store View'),
                'value'              => !empty($formData['magento_orders_settings']['fba']['store_id'])
                    ? $formData['magento_orders_settings']['fba']['store_id'] : '',
                'required'           => true,
                'has_empty_option'   => true,
                'has_default_option' => false
            ]
        );

        $fieldset->addField(
            'magento_orders_fba_stock_mode',
            'select',
            [
                'container_id' => 'magento_orders_fba_stock_mode_container',
                'name'         => 'magento_orders_settings[fba][stock_mode]',
                'label'        => $this->__('Manage Stock'),
                'values'       => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value'        => $formData['magento_orders_settings']['fba']['stock_mode'],
                'tooltip'      => $this->__(
                    'If <i>Yes</i>, after Magento Order Creation QTY of Magento Product reduces.'
                )
            ]
        );

        $shipByDateFieldset = $form->addFieldset(
            'magento_block_amazon_accounts_magento_orders_shipping_information',
            [
                'legend'      => $this->__('Shipping information'),
                'collapsable' => true
            ]
        );

        $shipByDateFieldset->addField(
            'magento_orders_ship_by_date_settings',
            'select',
            [
                'name'    => 'magento_orders_settings[shipping_information][ship_by_date]',
                'label'   => $this->__('Import Ship by date to Magento order'),
                'values'  => [
                    1 => $this->__('Yes'),
                    0 => $this->__('No'),
                ],
                'value'   => $formData['magento_orders_settings']['shipping_information']['ship_by_date'] ?? 1,
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_accounts_magento_orders_number',
            [
                'legend'      => $this->__('Magento Order Number'),
                'collapsable' => true,
                'tooltip'     => $this->__('Sets Magento Order number basing on the Settings below')
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
                    Account::MAGENTO_ORDERS_NUMBER_SOURCE_CHANNEL => $this->__('Amazon'),
                ],
                'value'   => $formData['magento_orders_settings']['number']['source'],
                'tooltip' => $this->__(
                    'If source is set to Magento, Magento Order numbers are created basing on your Magento Settings.
                    If source is set to Amazon, Magento Order numbers are the same as Amazon Order numbers.'
                )
            ]
        );

        $fieldset->addField(
            'magento_orders_number_prefix_container',
            self::CUSTOM_CONTAINER,
            [
                'text'      => $this->getLayout()
                        ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs\Order\PrefixesTable::class)
                        ->addData(['form_data' => $formData])
                        ->toHtml(),
                'css_class' => 'm2epro-fieldset-table',
                'style'     => 'padding: 0 !important;'
            ]
        );

        $fieldset->addField(
            'magento_orders_number_apply_to_amazon',
            'select',
            [
                'name'    => 'magento_orders_settings[number][apply_to_amazon]',
                'label'   => $this->__('Use as Your Seller Order ID'),
                'values'  => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value'   => $formData['magento_orders_settings']['number']['apply_to_amazon'],
                'tooltip' => $this->__(
                    'Set "Yes" to use Magento Order number as Your Seller Order ID in Amazon Order details.'
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_accounts_magento_orders_rules',
            [
                'legend'      => $this->__('Quantity Reservation'),
                'collapsable' => true,
                'tooltip'     => $this->__(
                    'Use the Reserve Quantity Option to prevent the Item being sold, before Magento Order created
                    (as the Product Stock QTY only reduces after Magento Order Creation).
                    It removes Items from Magento Stock at once Amazon Order comes from Amazon.
                    Reserve QTY will be used when Magento Order is created
                    or released when the term of QTY reservation has expired.'
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
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_accounts_magento_orders_refund_and_cancellation',
            [
                'legend'      => $this->__('Refund & Cancellation'),
                'collapsable' => true,
                'tooltip'     => $this->__(
                    'Enable an option Cancellation & Refund if Credit Memo is Created to run automatic Cancellation
                     of Amazon Orders or automatic Refund of Items associated to Amazon Orders at the moment
                     of Credit Memos creation in Magento Orders that were created by M2E Pro. <br/><br/>

                     In case Amazon Order has Status Unshipped and you created a Credit Memo for associated
                     Magento Order which include all bought Items,
                     Amazon Order Cancellation will be run automatically. <br/><br/>

                     Automatic Refund of bought Items associated to Amazon Order is available in case
                     Amazon Order has Status Shipped. Refund action will be run only for those Items for which
                     Credit Memos were created.'
                )
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
                'value'        => $formData['magento_orders_settings']['refund_and_cancellation']['refund_mode']
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_accounts_magento_orders_customer',
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
                    'There are several ways to specify a Customer for which Magento Orders will be created: <br/><br/>
                     <b>Guest Account</b> - the System does not require a Customer Account to be created.
                     Default Guest Account will be defined as a Customer. <br/>
                     <b>Note:</b> The Guest Checkout Option must be enabled in Magento.
                     (<i>Yes</i> must be chosen in the Allow Guest Checkout Option in
                     Magento > Stores > Configuration > Sales > Checkout). <br/>
                     <b>Predefined Customer</b> - the System uses one predefined
                     Customer for all Amazon Orders related to this Account. You will be required
                     to provide an ID of the existing Customer, which you can find in
                     Magento > Customers > All Customers. <br/>
                     <b>Create New</b> - a new Customer will be created in Magento,
                     using Amazon Customer data of Amazon Order. <br/>
                     <b>Note:</b> A unique Customer Identifier is his e-mail address.
                     If the one already exists among Magento Customers e-mails,
                     the System uses this Customer as owner of Order and links Order to him.
                      A new Customer will not be created. <br/>
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
                    'Choose if you want to use your customer’s shipping address as the billing one regularly
                    or only if the buyer and recipient have the same names.'
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_accounts_magento_orders_tax',
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
                    Account::MAGENTO_ORDERS_TAX_MODE_CHANNEL => $this->__('Amazon'),
                    Account::MAGENTO_ORDERS_TAX_MODE_MAGENTO => $this->__('Magento'),
                    Account::MAGENTO_ORDERS_TAX_MODE_MIXED   => $this->__('Amazon & Magento'),
                ],
                'value'   => $formData['magento_orders_settings']['tax']['mode'],
                'tooltip' => $this->__(
                    'Choose where the tax settings for your Magento Order will be taken from. See
                    <a href="%url%" target="_blank">this article</a> for more details.',
                    $this->supportHelper->getDocumentationArticleUrl('x/r4VcBQ')
                )
            ]
        );

        $button = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)->addData(
            [
                'label'   => $this->__('Select states'),
                'onclick' => 'AmazonAccountObj.openExcludedStatesPopup()',
                'class'   => 'action-primary',
                'style'   => 'margin-left: 70px;',
                'id'      => 'show_excluded_states_button'
            ]
        );

        $fieldset->addField(
            'magento_orders_tax_amazon_collects',
            'select',
            [
                'container_id'       => 'magento_orders_tax_amazon_collects_container',
                'name'               => 'magento_orders_settings[tax][amazon_collects]',
                'label'              => $this->__('Exclude tax collected by Amazon'),
                'values'             => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value'              => $formData['magento_orders_settings']['tax']['amazon_collects'],
                'after_element_html' => $this->getTooltipHtml(
                    $this->__(
                        "Tax won't be included in orders shipped to the selected states."
                    )
                ) . $button->toHtml()
            ]
        );

        $fieldset->addField(
            'magento_orders_tax_amazon_collects_for_uk_shipment',
            'select',
            [
                'container_id'       => 'magento_orders_tax_amazon_collects_for_uk_shipment_container',
                'name'               => 'magento_orders_settings[tax][amazon_collect_for_uk]',
                'label'              => $this->__('Exclude UK VAT collected by Amazon'),
                'values'             => [
                    Account::SKIP_TAX_FOR_UK_SHIPMENT_NONE               => $this->__('None'),
                    Account::SKIP_TAX_FOR_UK_SHIPMENT                    => $this->__('All orders with UK shipments'),
                    Account::SKIP_TAX_FOR_UK_SHIPMENT_WITH_CERTAIN_PRICE => $this->__(
                        'Orders under 135GBP price'
                    )
                ],
                'value'              => $formData['magento_orders_settings']['tax']['amazon_collect_for_uk'],
                'after_element_html' => $this->getTooltipHtml(
                    $this->__(
                        "VAT won't be included in orders with UK shipment. Find more info "
                        . '<a href="%url%" target="_blank">here</a>.',
                        $this->supportHelper->getDocumentationArticleUrl(
                            'x/r4VcBQ#TaxCalculationSettings-SkipTaxinUKOrders'
                        )
                    )
                )
            ]
        );

        $fieldset->addField(
            'magento_orders_tax_excluded_states',
            'hidden',
            [
                'name'  => 'magento_orders_settings[tax][excluded_states]',
                'value' => $formData['magento_orders_settings']['tax']['excluded_states'],
            ]
        );

        $button = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)->addData(
            [
                'label'   => $this->__('Select countries'),
                'onclick' => 'AmazonAccountObj.openExcludedCountriesPopup()',
                'class'   => 'action-primary',
                'style'   => 'margin-left: 70px;',
                'id'      => 'show_excluded_countries_button'
            ]
        );

        $fieldset->addField(
            'magento_orders_tax_amazon_collects_for_eea_shipment',
            'select',
            [
                'container_id'       => 'magento_orders_tax_amazon_collects_for_eea_shipment_container',
                'name'               => 'magento_orders_settings[tax][amazon_collect_for_eea]',
                'label'              => $this->__('Exclude EEA VAT collected by Amazon'),
                'style'              => 'max-width: 240px;',
                'values'             => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value'              => $formData['magento_orders_settings']['tax']['amazon_collect_for_eea'],
                'after_element_html' => $this->getTooltipHtml(
                    $this->__(
                        "VAT won't be included in orders shipped to the selected countries."
                    )
                ) . $button->toHtml()
            ]
        );

        $fieldset->addField(
            'magento_orders_tax_excluded_countries',
            'hidden',
            [
                'name'  => 'magento_orders_settings[tax][excluded_countries]',
                'value' => $formData['magento_orders_settings']['tax']['excluded_countries'],
            ]
        );

        $this->addImportTaxRegistrationNumber($formData, $fieldset, $account);

        $fieldset = $form->addFieldset(
            'magento_block_amazon_accounts_magento_orders_status_mapping',
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
                    'Set the correspondence between Amazon and Magento order statuses.
                    The status of your Magento order will be updated based on these settings.'
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
                'No Customer entry is found for specified ID.'                             => $this->__(
                    'No Customer entry is found for specified ID.'
                ),
                'Select states where tax will be excluded' => $this->__(
                    'Select states where tax will be excluded'
                ),
                'Select countries where VAT will be excluded' => $this->__(
                    'Select countries where VAT will be excluded'
                ),
            ]
        );

        return parent::_prepareForm();
    }
}
