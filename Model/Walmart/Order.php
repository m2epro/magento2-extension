<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart;

/**
 * Class \Ess\M2ePro\Model\Walmart\Order
 */
/**
 * @method \Ess\M2ePro\Model\Order getParentObject()
 * @method \Ess\M2ePro\Model\ResourceModel\Walmart\Order getResource()
 */
class Order extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Walmart\AbstractModel
{
    // M2ePro\TRANSLATIONS
    // Order Status cannot be Updated. Reason: %msg%

    const STATUS_CREATED = 0;
    const STATUS_UNSHIPPED = 1;
    const STATUS_SHIPPED_PARTIALLY = 2;
    const STATUS_SHIPPED = 3;
    const STATUS_CANCELED = 5;

    private $shipmentFactory;

    private $subTotalPrice = null;

    private $grandTotalPrice = null;

    protected $shippingAddressFactory;

    private $orderSender;

    private $invoiceSender;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Magento\Order\ShipmentFactory $shipmentFactory,
        \Ess\M2ePro\Model\Walmart\Order\ShippingAddressFactory $shippingAddressFactory,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->shipmentFactory = $shipmentFactory;
        $this->shippingAddressFactory = $shippingAddressFactory;
        $this->orderSender = $orderSender;
        $this->invoiceSender = $invoiceSender;

        parent::__construct(
            $walmartFactory,
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
        $this->_init('Ess\M2ePro\Model\ResourceModel\Walmart\Order');
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Walmart\Order\ProxyObject
     */
    public function getProxy()
    {
        return $this->modelFactory->getObject('Walmart_Order_ProxyObject', ['order' => $this]);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Walmart\Account
     */
    public function getWalmartAccount()
    {
        return $this->getParentObject()->getAccount()->getChildObject();
    }

    //########################################

    public function getWalmartOrderId()
    {
        return $this->getData('walmart_order_id');
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
     * @return \Ess\M2ePro\Model\Walmart\Order\ShippingAddress
     */
    public function getShippingAddress()
    {
        $address = $this->getHelper('Data')->jsonDecode($this->getData('shipping_address'));

        return $this->shippingAddressFactory->create([
            'order' => $this->getParentObject()
        ])->setData($address);
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
     * @return float|int
     */
    public function getProductPriceTaxRate()
    {
        $taxAmount = $this->getProductPriceTaxAmount();
        if ($taxAmount <= 0) {
            return 0;
        }

        if ($this->getSubtotalPrice() <= 0) {
            return 0;
        }

        $taxRate = ($taxAmount / $this->getSubtotalPrice()) * 100;

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

        if ($this->getShippingPrice() <= 0) {
            return 0;
        }

        $taxRate = ($taxAmount / $this->getShippingPrice()) * 100;

        return round($taxRate, 4);
    }

    //########################################

    /**
     * @return bool
     */
    public function isCreated()
    {
        return $this->getStatus() == self::STATUS_CREATED;
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
    public function isCanceled()
    {
        return $this->getStatus() == self::STATUS_CANCELED;
    }

    //########################################

    /**
     * @return float|null
     */
    public function getSubtotalPrice()
    {
        if ($this->subTotalPrice === null) {
            $this->subTotalPrice = $this->getResource()->getItemsTotal($this->getId());
        }

        return $this->subTotalPrice;
    }

    /**
     * @return float
     */
    public function getGrandTotalPrice()
    {
        if ($this->grandTotalPrice === null) {
            $this->grandTotalPrice = $this->getSubtotalPrice();
            $this->grandTotalPrice += $this->getProductPriceTaxAmount();
            $this->grandTotalPrice += $this->getShippingPrice();
            $this->grandTotalPrice += $this->getShippingPriceTaxAmount();
        }

        return round($this->grandTotalPrice, 2);
    }

    //########################################

    public function getStatusForMagentoOrder()
    {
        $status = '';
        $this->isUnshipped() && $status = $this->getWalmartAccount()->getMagentoOrdersStatusProcessing();
        $this->isPartiallyShipped() && $status = $this->getWalmartAccount()->getMagentoOrdersStatusProcessing();
        $this->isShipped() && $status = $this->getWalmartAccount()->getMagentoOrdersStatusShipped();

        return $status;
    }

    //########################################

    /**
     * @return int|null
     */
    public function getAssociatedStoreId()
    {
        $storeId = null;

        $channelItems = $this->getParentObject()->getChannelItems();

        if (count($channelItems) == 0) {
            // 3rd party order
            // ---------------------------------------
            $storeId = $this->getWalmartAccount()->getMagentoOrdersListingsOtherStoreId();
            // ---------------------------------------
        } else {
            // M2E Pro order
            // ---------------------------------------
            if ($this->getWalmartAccount()->isMagentoOrdersListingsStoreCustom()) {
                $storeId = $this->getWalmartAccount()->getMagentoOrdersListingsStoreId();
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
        return true;
    }

    /**
     * Check possibility for magento order creation
     *
     * @return bool
     */
    public function canCreateMagentoOrder()
    {
        if ($this->isCanceled()) {
            return false;
        }

        return true;
    }

    public function canAcknowledgeOrder()
    {
        foreach ($this->getParentObject()->getItemsCollection()->getItems() as $item) {
            /**@var \Ess\M2ePro\Model\Walmart\Order\Item $item */
            if (!$item->canCreateMagentoOrder()) {
                return false;
            }
        }

        return true;
    }

    //########################################

    public function beforeCreateMagentoOrder()
    {
        if ($this->isCanceled()) {
            throw new \Ess\M2ePro\Model\Exception(
                'Magento Order Creation is not allowed for canceled Walmart Orders.'
            );
        }
    }

    public function afterCreateMagentoOrder()
    {
        if ($this->getWalmartAccount()->isMagentoOrdersCustomerNewNotifyWhenOrderCreated()) {
            $this->orderSender->send($this->getParentObject()->getMagentoOrder());
        }
    }

    //########################################

    /**
     * @return bool
     */
    public function canCreateInvoice()
    {
        if ($this->getWalmartAccount()->isMagentoInvoiceCreationDisabled()) {
            return false;
        }

        if (!$this->getWalmartAccount()->isMagentoOrdersInvoiceEnabled()) {
            return false;
        }

        if ($this->isCanceled()) {
            return false;
        }

        $magentoOrder = $this->getParentObject()->getMagentoOrder();
        if ($magentoOrder === null) {
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
            return null;
        }

        $magentoOrder = $this->getParentObject()->getMagentoOrder();

        // Create invoice
        // ---------------------------------------
        /** @var $invoiceBuilder \Ess\M2ePro\Model\Magento\Order\Invoice */
        $invoiceBuilder = $this->modelFactory->getObject('Magento_Order_Invoice');
        $invoiceBuilder->setMagentoOrder($magentoOrder);
        $invoiceBuilder->buildInvoice();
        // ---------------------------------------

        $invoice = $invoiceBuilder->getInvoice();

        if ($this->getWalmartAccount()->isMagentoOrdersCustomerNewNotifyWhenInvoiceCreated()) {
            $this->invoiceSender->send($invoice);
        }

        return $invoice;
    }

    //########################################

    /**
     * @return bool
     */
    public function canCreateShipments()
    {
        if (!$this->getWalmartAccount()->isMagentoOrdersShipmentEnabled()) {
            return false;
        }

        if (!$this->isShipped()) {
            return false;
        }

        $magentoOrder = $this->getParentObject()->getMagentoOrder();
        if ($magentoOrder === null) {
            return false;
        }

        if ($magentoOrder->hasShipments() || !$magentoOrder->canShip()) {
            return false;
        }

        return true;
    }

    // ---------------------------------------

    /**
     * @return \Magento\Sales\Model\Order\Shipment[]|null
     */
    public function createShipments()
    {
        if (!$this->canCreateShipments()) {
            return null;
        }

        /** @var $shipmentBuilder \Ess\M2ePro\Model\Magento\Order\Shipment */
        $shipmentBuilder = $this->shipmentFactory->create($this->getParentObject()->getMagentoOrder());
        $shipmentBuilder->setMagentoOrder($this->getParentObject()->getMagentoOrder());
        $shipmentBuilder->buildShipments();

        return $shipmentBuilder->getShipments();
    }

    //########################################

    /**
     * @param array $trackingDetails
     * @return bool
     */
    public function canUpdateShippingStatus()
    {
        if ($this->isCanceled()) {
            return false;
        }

        return true;
    }

    /**
     * @param array $trackingDetails
     * @param array $items
     * @return bool
     */
    public function updateShippingStatus(array $trackingDetails = [], array $items = [])
    {
        if (!$this->canUpdateShippingStatus()) {
            return false;
        }

        if (empty($trackingDetails['tracking_number'])) {
            $this->getParentObject()->addErrorLog(
                'Walmart Order was not shipped. Reason: %msg%',
                [
                    'msg' => 'Order status was not updated to Shipped on Walmart because a tracking number
                                is missing. Please insert the valid tracking number into the Order shipment.'
                ]
            );
            return false;
        }

        if (!isset($trackingDetails['fulfillment_date'])) {
            $trackingDetails['fulfillment_date'] = $this->getHelper('Data')->getCurrentGmtDate();
        }

        if (!empty($trackingDetails['carrier_code'])) {
            $trackingDetails['carrier_title'] = $this->getHelper('Component\Walmart')->getCarrierTitle(
                $trackingDetails['carrier_code'],
                isset($trackingDetails['carrier_title']) ? $trackingDetails['carrier_title'] : ''
            );
        }

        if (!empty($trackingDetails['carrier_title'])) {
            if ($trackingDetails['carrier_title'] == \Ess\M2ePro\Model\Order\Shipment\Handler::CUSTOM_CARRIER_CODE &&
                !empty($trackingDetails['shipping_method'])) {
                $trackingDetails['carrier_title'] = $trackingDetails['shipping_method'];
            }
        }

        $params = [
            'walmart_order_id' => $this->getWalmartOrderId(),
            'fulfillment_date' => $trackingDetails['fulfillment_date'],
            'items'            => []
        ];

        foreach ($items as $item) {
            if (!isset($item['walmart_order_item_id']) || !isset($item['qty'])) {
                continue;
            }

            if ((int)$item['qty'] <= 0) {
                continue;
            }

            $params['items'][] = [
                'walmart_order_item_id' => $item['walmart_order_item_id'],
                'qty'                   => (int)$item['qty'],
                'tracking_details'      => [
                    'ship_date' => $trackingDetails['fulfillment_date'],
                    'method'    => $this->getShippingService(),
                    'carrier'   => $trackingDetails['carrier_title'],
                    'number'    => $trackingDetails['tracking_number'],
                ],
            ];
        }

        $orderId = $this->getParentObject()->getId();
        $action = \Ess\M2ePro\Model\Order\Change::ACTION_UPDATE_SHIPPING;
        $creatorType = \Ess\M2ePro\Model\Order\Change::CREATOR_TYPE_OBSERVER;
        $component = \Ess\M2ePro\Helper\Component\Walmart::NICK;

        /** @var \Ess\M2ePro\Model\Order\Change $change */
        $change = $this->activeRecordFactory
            ->getObject('Order\Change')
            ->getCollection()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('action', $action)
            ->addFieldToFilter('processing_attempt_count', 0)
            ->getFirstItem();

        if ($change->getId()) {
            $this->updateOrderChange($change, $params);
        } else {
            $this->activeRecordFactory->getObject('Order\Change')->create(
                $orderId,
                $action,
                $creatorType,
                $component,
                $params
            );
        }

        return true;
    }

    /**
     * @param \Ess\M2ePro\Model\Order\Change $change
     * @param array $params
     */
    private function updateOrderChange(\Ess\M2ePro\Model\Order\Change $change, array $params)
    {
        $existingParams = $change->getParams();
        foreach ($params['items'] as $newItem) {
            foreach ($existingParams['items'] as &$existingItem) {
                if ($newItem['walmart_order_item_id'] === $existingItem['walmart_order_item_id']) {
                    $newQtyTotal = $newItem['qty'] + $existingItem['qty'];
                    $maxQtyTotal = $this->walmartFactory->getObject('Order_Item')
                        ->getCollection()
                        ->addFieldToFilter('order_id', $this->getId())
                        ->addFieldToFilter('walmart_order_item_id', $existingItem['walmart_order_item_id'])
                        ->getFirstItem()
                        ->getChildObject()
                        ->getQty();
                    $newQtyTotal >= $maxQtyTotal && $newQtyTotal = $maxQtyTotal;
                    $existingItem['qty'] = $newQtyTotal;
                    continue 2;
                }
            }
            unset($existingItem);
            $existingParams['items'][] = $newItem;
        }

        $change->setParams($this->getHelper('Data')->jsonEncode($existingParams))->save();
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

        return true;
    }

    /**
     * @param array $items
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function refund(array $items = [])
    {
        if (!$this->canRefund()) {
            return false;
        }

        $params = [
            'order_id' => $this->getWalmartOrderId(),
            'currency' => $this->getCurrency(),
            'items'    => $items,
        ];

        $orderId = $this->getParentObject()->getId();
        $creatorType = \Ess\M2ePro\Model\Order\Change::CREATOR_TYPE_OBSERVER;
        $component = \Ess\M2ePro\Helper\Component\Walmart::NICK;

        $action = \Ess\M2ePro\Model\Order\Change::ACTION_CANCEL;
        if ($this->isShipped() || $this->isPartiallyShipped() || $this->isSetProcessingLock('update_shipping_status')) {
            if (empty($items)) {
                $this->getParentObject()->addErrorLog(
                    'Walmart Order was not refunded. Reason: %msg%',
                    [
                        'msg' => 'Refund request was not submitted.
                                    To be processed through Walmart API, the refund must be applied to certain products
                                    in an order. Please indicate the number of each line item, that need to be refunded,
                                    in Credit Memo form.'
                    ]
                );
                return false;
            }

            $action = \Ess\M2ePro\Model\Order\Change::ACTION_REFUND;
        }

        $this->activeRecordFactory->getObject('Order\Change')->create(
            $orderId,
            $action,
            $creatorType,
            $component,
            $params
        );

        return true;
    }

    //########################################
}
