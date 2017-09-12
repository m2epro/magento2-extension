<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon;

/**
 * @method \Ess\M2ePro\Model\Order getParentObject()
 * @method \Ess\M2ePro\Model\ResourceModel\Amazon\Order getResource()
 */

class Order extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Amazon\AbstractModel
{
    // M2ePro\TRANSLATIONS
    // Order Status cannot be Updated. Reason: %msg%

    const STATUS_PENDING             = 0;
    const STATUS_UNSHIPPED           = 1;
    const STATUS_SHIPPED_PARTIALLY   = 2;
    const STATUS_SHIPPED             = 3;
    const STATUS_UNFULFILLABLE       = 4;
    const STATUS_CANCELED            = 5;
    const STATUS_INVOICE_UNCONFIRMED = 6;

    //########################################

    private $shippingAddressFactory;

    private $carrierFactory;

    private $orderSender;

    private $invoiceSender;

    private $subTotalPrice = NULL;

    private $grandTotalPrice = NULL;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Order\ShippingAddressFactory $shippingAddressFactory,
        \Magento\Shipping\Model\CarrierFactory $carrierFactory,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->shippingAddressFactory = $shippingAddressFactory;
        $this->carrierFactory = $carrierFactory;
        $this->orderSender = $orderSender;
        $this->invoiceSender = $invoiceSender;

        parent::__construct(
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Order');
    }

    //########################################

    public function getProxy()
    {
        return $this->modelFactory->getObject('Amazon\Order\Proxy', [
            'order' => $this
        ]);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Account
     */
    public function getAmazonAccount()
    {
        return $this->getParentObject()->getAccount()->getChildObject();
    }

    //########################################

    public function getAmazonOrderId()
    {
        return $this->getData('amazon_order_id');
    }

    public function getBuyerName()
    {
        return $this->getData('buyer_name');
    }

    public function getBuyerEmail()
    {
        return $this->getData('buyer_email');
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return (int)$this->getData('status');
    }

    public function getCurrency()
    {
        return $this->getData('currency');
    }

    public function getShippingService()
    {
        return $this->getData('shipping_service');
    }

    /**
     * @return float
     */
    public function getShippingPrice()
    {
        return (float)$this->getData('shipping_price');
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Order\ShippingAddress
     */
    public function getShippingAddress()
    {
        $address = $this->getHelper('Data')->jsonDecode($this->getData('shipping_address'));

        return $this->shippingAddressFactory->create([
            'order' => $this->getParentObject()
        ])->setData($address);
    }

    /**
     * @return array
     */
    public function getMerchantFulfillmentData()
    {
        return $this->getSettings('merchant_fulfillment_data');
    }

    //########################################

    public function getShipDateFrom()
    {
        $shippingDates = $this->getSettings('shipping_dates');
        return !empty($shippingDates['ship']['from']) ? $shippingDates['ship']['from'] : NULL;
    }

    public function getShipDateTo()
    {
        $shippingDates = $this->getSettings('shipping_dates');
        return !empty($shippingDates['ship']['to']) ? $shippingDates['ship']['to'] : NULL;
    }

    public function getDeliveryDateFrom()
    {
        $shippingDates = $this->getSettings('shipping_dates');
        return !empty($shippingDates['delivery']['from']) ? $shippingDates['delivery']['from'] : NULL;
    }

    public function getDeliveryDateTo()
    {
        $shippingDates = $this->getSettings('shipping_dates');
        return !empty($shippingDates['delivery']['to']) ? $shippingDates['delivery']['to'] : NULL;
    }

    //########################################

    /**
     * @return float
     */
    public function getPaidAmount()
    {
        return (float)$this->getData('paid_amount');
    }

    //########################################

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getTaxDetails()
    {
        return $this->getSettings('tax_details');
    }

    /**
     * @return float
     */
    public function getProductPriceTaxAmount()
    {
        $taxDetails = $this->getTaxDetails();
        return !empty($taxDetails['product']) ? (float)$taxDetails['product'] : 0.0;
    }

    /**
     * @return float
     */
    public function getShippingPriceTaxAmount()
    {
        $taxDetails = $this->getTaxDetails();
        return !empty($taxDetails['shipping']) ? (float)$taxDetails['shipping'] : 0.0;
    }

    /**
     * @return float
     */
    public function getGiftPriceTaxAmount()
    {
        $taxDetails = $this->getTaxDetails();
        return !empty($taxDetails['gift']) ? (float)$taxDetails['gift'] : 0.0;
    }

    /**
     * @return float|int
     */
    public function getProductPriceTaxRate()
    {
        $taxAmount = $this->getProductPriceTaxAmount() + $this->getGiftPriceTaxAmount();
        if ($taxAmount <= 0) {
            return 0;
        }

        $taxRate = ($taxAmount / ($this->getSubtotalPrice() - $this->getPromotionDiscountAmount())) * 100;

        return round($taxRate, 4);
    }

    /**
     * @return float|int
     */
    public function getShippingPriceTaxRate()
    {
        $taxAmount = $this->getShippingPriceTaxAmount();
        if ($taxAmount <= 0) {
            return 0;
        }

        $taxRate = ($taxAmount / ($this->getShippingPrice() - $this->getShippingDiscountAmount())) * 100;

        return round($taxRate, 4);
    }

    //########################################

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getDiscountDetails()
    {
        return $this->getSettings('discount_details');
    }

    /**
     * @return float
     */
    public function getPromotionDiscountAmount()
    {
        $discountDetails = $this->getDiscountDetails();
        return !empty($discountDetails['promotion']) ? $discountDetails['promotion'] : 0.0;
    }

    /**
     * @return float
     */
    public function getShippingDiscountAmount()
    {
        $discountDetails = $this->getDiscountDetails();
        return !empty($discountDetails['shipping']) ? $discountDetails['shipping'] : 0.0;
    }

    //########################################

    /**
     * @return bool
     */
    public function isFulfilledByAmazon()
    {
        return (bool)$this->getData('is_afn_channel');
    }

    //########################################

    public function isEligibleForMerchantFulfillment()
    {
        if ($this->isFulfilledByAmazon()) {
            return false;
        }

        if ($this->isPending() || $this->isCanceled()) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Marketplace $amazonMarketplace */
        $amazonMarketplace = $this->getAmazonAccount()->getMarketplace()->getChildObject();
        if (!$amazonMarketplace->isMerchantFulfillmentAvailable()) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isMerchantFulfillmentApplied()
    {
        $info = $this->getMerchantFulfillmentData();
        return !empty($info);
    }

    //########################################

    /**
     * @return bool
     */
    public function isPrime()
    {
        return (bool)$this->getData('is_prime');
    }

    /**
     * @return bool
     */
    public function isBusiness()
    {
        return (bool)$this->getData('is_business');
    }

    //########################################

    /**
     * @return bool
     */
    public function isPending()
    {
        return $this->getStatus() == self::STATUS_PENDING;
    }

    /**
     * @return bool
     */
    public function isUnshipped()
    {
        return $this->getStatus() == self::STATUS_UNSHIPPED;
    }

    /**
     * @return bool
     */
    public function isPartiallyShipped()
    {
        return $this->getStatus() == self::STATUS_SHIPPED_PARTIALLY;
    }

    /**
     * @return bool
     */
    public function isShipped()
    {
        return $this->getStatus() == self::STATUS_SHIPPED;
    }

    /**
     * @return bool
     */
    public function isUnfulfillable()
    {
        return $this->getStatus() == self::STATUS_UNFULFILLABLE;
    }

    /**
     * @return bool
     */
    public function isCanceled()
    {
        return $this->getStatus() == self::STATUS_CANCELED;
    }

    /**
     * @return bool
     */
    public function isInvoiceUnconfirmed()
    {
        return $this->getStatus() == self::STATUS_INVOICE_UNCONFIRMED;
    }

    //########################################

    /**
     * @return float|null
     */
    public function getSubtotalPrice()
    {
        if (is_null($this->subTotalPrice)) {
            $this->subTotalPrice = $this->getResource()->getItemsTotal($this->getId());
        }

        return $this->subTotalPrice;
    }

    /**
     * @return float
     */
    public function getGrandTotalPrice()
    {
        if (is_null($this->grandTotalPrice)) {
            $this->grandTotalPrice = $this->getSubtotalPrice();
            $this->grandTotalPrice += $this->getProductPriceTaxAmount();
            $this->grandTotalPrice += $this->getShippingPrice();
            $this->grandTotalPrice += $this->getShippingPriceTaxAmount();
            $this->grandTotalPrice += $this->getGiftPriceTaxAmount();
            $this->grandTotalPrice -= $this->getPromotionDiscountAmount();
            $this->grandTotalPrice -= $this->getShippingDiscountAmount();
        }

        return round($this->grandTotalPrice, 2);
    }

    //########################################

    public function getStatusForMagentoOrder()
    {
        $status = '';
        $this->isUnshipped()        && $status = $this->getAmazonAccount()->getMagentoOrdersStatusProcessing();
        $this->isPartiallyShipped() && $status = $this->getAmazonAccount()->getMagentoOrdersStatusProcessing();
        $this->isShipped()          && $status = $this->getAmazonAccount()->getMagentoOrdersStatusShipped();

        return $status;
    }

    //########################################

    /**
     * @return int|null
     */
    public function getAssociatedStoreId()
    {
        $storeId = NULL;

        $channelItems = $this->getParentObject()->getChannelItems();

        if (count($channelItems) == 0) {
            // 3rd party order
            // ---------------------------------------
            $storeId = $this->getAmazonAccount()->getMagentoOrdersListingsOtherStoreId();
            // ---------------------------------------
        } else {
            // M2E Pro order
            // ---------------------------------------
            if ($this->getAmazonAccount()->isMagentoOrdersListingsStoreCustom()) {
                $storeId = $this->getAmazonAccount()->getMagentoOrdersListingsStoreId();
            } else {
                $firstChannelItem = reset($channelItems);
                $storeId = $firstChannelItem->getStoreId();
            }
            // ---------------------------------------
        }

        if ($storeId == 0) {
            $storeId = $this->getHelper('Magento\Store')->getDefaultStoreId();
        }

        return $storeId;
    }

    //########################################

    /**
     * @return bool
     */
    public function isReservable()
    {
        if ($this->isCanceled()) {
            return false;
        }

        if ($this->isFulfilledByAmazon() &&
            (!$this->getAmazonAccount()->isMagentoOrdersFbaModeEnabled() ||
             !$this->getAmazonAccount()->isMagentoOrdersFbaStockEnabled())
        ) {
            return false;
        }

        return true;
    }

    //########################################

    /**
     * Check possibility for magento order creation
     *
     * @return bool
     */
    public function canCreateMagentoOrder()
    {
        if ($this->isPending() || $this->isCanceled()) {
            return false;
        }

        if ($this->isFulfilledByAmazon() && !$this->getAmazonAccount()->isMagentoOrdersFbaModeEnabled()) {
            return false;
        }

        return true;
    }

    public function beforeCreateMagentoOrder()
    {
        if ($this->isPending() || $this->isCanceled()) {
            throw new \Ess\M2ePro\Model\Exception('Magento Order Creation is not allowed for pending and
                canceled Amazon Orders.');
        }
    }

    public function afterCreateMagentoOrder()
    {
        if ($this->getAmazonAccount()->isMagentoOrdersCustomerNewNotifyWhenOrderCreated()) {
            $this->orderSender->send($this->getParentObject()->getMagentoOrder());
        }

        if ($this->isFulfilledByAmazon() && !$this->getAmazonAccount()->isMagentoOrdersFbaStockEnabled()) {
            $this->_eventManager->dispatch('ess_amazon_fba_magento_order_place_after', array(
                'magento_order' => $this->getParentObject()->getMagentoOrder()
            ));
        }
    }

    //########################################

    /**
     * @return bool
     */
    public function canCreateInvoice()
    {
        if ($this->getAmazonAccount()->isMagentoInvoiceCreationDisabled()) {
            return false;
        }

        if (!$this->getAmazonAccount()->isMagentoOrdersInvoiceEnabled()) {
            return false;
        }

        if ($this->isPending() || $this->isCanceled() || $this->isUnfulfillable() || $this->isInvoiceUnconfirmed()) {
            return false;
        }

        $magentoOrder = $this->getParentObject()->getMagentoOrder();
        if (is_null($magentoOrder)) {
            return false;
        }

        if ($magentoOrder->hasInvoices() || !$magentoOrder->canInvoice()) {
            return false;
        }

        return true;
    }

    // ---------------------------------------

    /**
     * @return \Magento\Sales\Model\Order\Invoice|null
     * @throws \Exception
     */
    public function createInvoice()
    {
        if (!$this->canCreateInvoice()) {
            return NULL;
        }

        $magentoOrder = $this->getParentObject()->getMagentoOrder();

        // Create invoice
        // ---------------------------------------
        /** @var $invoiceBuilder \Ess\M2ePro\Model\Magento\Order\Invoice */
        $invoiceBuilder = $this->modelFactory->getObject('Magento\Order\Invoice');
        $invoiceBuilder->setMagentoOrder($magentoOrder);
        $invoiceBuilder->buildInvoice();
        // ---------------------------------------

        $invoice = $invoiceBuilder->getInvoice();

        if ($this->getAmazonAccount()->isMagentoOrdersCustomerNewNotifyWhenInvoiceCreated()) {
            $this->invoiceSender->send($invoice);
        }

        return $invoice;
    }

    //########################################

    /**
     * @return bool
     */
    public function canCreateShipment()
    {
        if (!$this->getAmazonAccount()->isMagentoOrdersShipmentEnabled()) {
            return false;
        }

        if (!$this->isShipped()) {
            return false;
        }

        $magentoOrder = $this->getParentObject()->getMagentoOrder();
        if (is_null($magentoOrder)) {
            return false;
        }

        if ($magentoOrder->hasShipments() || !$magentoOrder->canShip()) {
            return false;
        }

        return true;
    }

    // ---------------------------------------

    /**
     * @return \Magento\Sales\Model\Order\Shipment|null
     */
    public function createShipment()
    {
        if (!$this->canCreateShipment()) {
            return NULL;
        }

        $magentoOrder = $this->getParentObject()->getMagentoOrder();

        // Create shipment
        // ---------------------------------------
        /** @var $shipmentBuilder \Ess\M2ePro\Model\Magento\Order\Shipment */
        $shipmentBuilder = $this->modelFactory->getObject('Magento\Order\Shipment');
        $shipmentBuilder->setMagentoOrder($magentoOrder);
        $shipmentBuilder->buildShipment();
        // ---------------------------------------

        return $shipmentBuilder->getShipment();
    }

    //########################################

    /**
     * @param array $trackingDetails
     * @return bool
     */
    public function canUpdateShippingStatus(array $trackingDetails = array())
    {
        if ($this->isShipped() && empty($trackingDetails)) {
            return false;
        }

        if ($this->isPending() || $this->isCanceled() || $this->isFulfilledByAmazon()) {
            return false;
        }

        return true;
    }

    /**
     * @param array $trackingDetails
     * @param array $items
     * @return bool
     */
    public function updateShippingStatus(array $trackingDetails = array(), array $items = array())
    {
        if (!$this->canUpdateShippingStatus($trackingDetails)) {
            return false;
        }

        if (!isset($trackingDetails['fulfillment_date'])) {
            $trackingDetails['fulfillment_date'] = $this->getHelper('Data')->getCurrentGmtDate();
        }

        $params = array(
            'amazon_order_id'  => $this->getAmazonOrderId(),
            'fulfillment_date' => $trackingDetails['fulfillment_date'],
            'tracking_number'  => NULL,
            'carrier_name'     => NULL,
            'shipping_method'  => NULL,
            'items'            => array()
        );

        if (!empty($trackingDetails['tracking_number'])) {
            $params['tracking_number'] = preg_replace('/[^A-Za-z0-9\s]/', '', $trackingDetails['tracking_number']);
            $params['carrier_name'] = 'custom';
        }

        if (!empty($trackingDetails['carrier_title'])) {
            $params['shipping_method'] = $trackingDetails['carrier_title'];
        }

        if (!empty($trackingDetails['carrier_code'])) {
            try {
                $carrier = $this->carrierFactory->create(
                    $trackingDetails['carrier_code'], $this->getParentObject()->getStoreId()
                );
            } catch (\Exception $e) {
                $carrier = false;
            }

            if ($carrier) {
                $params['carrier_name'] = $carrier->getConfigData('title');
            } else {
                $params['carrier_name'] = $trackingDetails['carrier_code'];
            }
        }

        foreach ($items as $item) {
            if (!isset($item['amazon_order_item_id']) || !isset($item['qty'])) {
                continue;
            }

            if ((int)$item['qty'] <= 0) {
                continue;
            }

            $params['items'][] = array(
                'amazon_order_item_id' => $item['amazon_order_item_id'],
                'qty' => (int)$item['qty']
            );
        }

        $orderId     = $this->getParentObject()->getId();
        $action      = \Ess\M2ePro\Model\Order\Change::ACTION_UPDATE_SHIPPING;
        $creatorType = \Ess\M2ePro\Model\Order\Change::CREATOR_TYPE_OBSERVER;
        $component   = \Ess\M2ePro\Helper\Component\Amazon::NICK;

        $this->activeRecordFactory->getObject('Order\Change')->create(
            $orderId, $action, $creatorType, $component, $params
        );

        return true;
    }

    //########################################

    /**
     * @return bool
     */
    public function canRefund()
    {
        if ($this->getStatus() == self::STATUS_CANCELED) {
            return false;
        }

        if (!$this->getAmazonAccount()->isRefundEnabled()) {
            return false;
        }

        return true;
    }

    /**
     * @param array $items
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function refund(array $items = array())
    {
        if (!$this->canRefund()) {
            return false;
        }

        $params = array(
            'order_id' => $this->getAmazonOrderId(),
            'currency' => $this->getCurrency(),
            'items'    => $items,
        );

        $totalItemsCount = $this->getParentObject()->getItemsCollection()->count();

        $orderId     = $this->getParentObject()->getId();
        $creatorType = \Ess\M2ePro\Model\Order\Change::CREATOR_TYPE_OBSERVER;
        $component   = \Ess\M2ePro\Helper\Component\Amazon::NICK;

        $changeCollection = $this->activeRecordFactory->getObject('Order\Change')->getCollection();
        $changeCollection->addFieldToFilter('order_id', $orderId);
        $changeCollection->addFieldToFilter('action',\Ess\M2ePro\Model\Order\Change::ACTION_UPDATE_SHIPPING);

        $action = \Ess\M2ePro\Model\Order\Change::ACTION_CANCEL;
        if ($this->isShipped() || $this->isPartiallyShipped() || count($items) != $totalItemsCount ||
            $this->isSetProcessingLock('update_shipping_status') || $changeCollection->getSize() > 0
        ) {
            if (empty($items)) {
                $this->getParentObject()->addErrorLog(
                    'Amazon Order was not refunded. Reason: %msg%',
                    array('msg' => 'Refund request was not submitted.
                                    To be processed through Amazon API, the refund must be applied to certain products
                                    in an order. Please indicate the number of each line item, that need to be refunded,
                                    in Credit Memo form.')
                );
                return false;
            }

            $action = \Ess\M2ePro\Model\Order\Change::ACTION_REFUND;
        }

        $this->activeRecordFactory->getObject('Order\Change')->create(
            $orderId, $action, $creatorType, $component, $params
        );

        return true;
    }

    //########################################
}