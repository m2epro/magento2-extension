<?php

namespace Ess\M2ePro\Model\Ebay;

use Ess\M2ePro\Helper\Component\Ebay as EbayHelper;
use Magento\Sales\Model\Order\Creditmemo;
use Ess\M2ePro\Model\ResourceModel\Ebay\Order as ResourceEbayOrder;

/**
 * @method \Ess\M2ePro\Model\Order getParentObject()
 * @method \Ess\M2ePro\Model\ResourceModel\Ebay\Order getResource()
 */
class Order extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Ebay\AbstractModel
{
    public const CHECKOUT_STATUS_INCOMPLETE = 0;
    public const CHECKOUT_STATUS_COMPLETED = 1;

    public const PAYMENT_STATUS_NOT_SELECTED = 0;
    public const PAYMENT_STATUS_ERROR = 1;
    public const PAYMENT_STATUS_PROCESS = 2;
    public const PAYMENT_STATUS_COMPLETED = 3;

    public const SHIPPING_STATUS_NOT_SELECTED = 0;
    public const SHIPPING_STATUS_PROCESSING = 1;
    public const SHIPPING_STATUS_COMPLETED = 2;

    public const STATUS_PENDING = 0;
    public const STATUS_UNSHIPPED = 1;
    public const STATUS_SHIPPED = 2;
    public const STATUS_CANCELED = 3;
    public const STATUS_PENDING_RESERVED = 4;
    public const STATUS_RETURNED = 5;
    public const BUYER_CANCELLATION_STATUS_NOT_REQUESTED = 0;
    public const BUYER_CANCELLATION_STATUS_REQUESTED = 1;
    public const BUYER_CANCELLATION_STATUS_APPROVED = 2;
    public const BUYER_CANCELLATION_STATUS_REJECTED = 3;
    public const BUYER_RETURN_REQUESTED_STATUS_NOT_REQUESTED = 0;
    public const BUYER_RETURN_REQUESTED_STATUS_REQUESTED = 1;
    public const BUYER_RETURN_REQUESTED_STATUS_APPROVED = 2;
    public const BUYER_RETURN_REQUESTED_STATUS_DECLINED = 3;

    /** All reasons: https://developer.ebay.com/Devzone/post-order/types/CancelReasonEnum.html */
    public const CANCEL_REASON_DEFAULT = 'OTHER';
    public const CANCEL_REASON_BUYER_ASK = 'BUYER_ASKED_CANCEL';

    /** @var \Ess\M2ePro\Model\Ebay\Order\ShippingAddressFactory */
    private $shippingAddressFactory;
    /** @var \Ess\M2ePro\Model\Magento\Order\ShipmentFactory */
    private $shipmentFactory;
    private $externalTransactionsCollection = null;
    /** @var \Magento\Sales\Model\Order\Email\Sender\OrderSender */
    private $orderSender;
    /** @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender */
    private $invoiceSender;
    private $subTotalPrice = null;
    private $grandTotalPrice = null;
    /** @var \Ess\M2ePro\Helper\Component\Ebay */
    private $componentEbay;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Other */
    private $listingOtherResourceModel;
    /** @var \Magento\Sales\Model\Order\CreditmemoFactory */
    private $creditmemoFactory;
    /** @var \Magento\Sales\Model\Service\CreditmemoService */
    private $creditmemoService;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay $componentEbay,
        \Ess\M2ePro\Model\Magento\Order\ShipmentFactory $shipmentFactory,
        \Ess\M2ePro\Model\Ebay\Order\ShippingAddressFactory $shippingAddressFactory,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Other $listingOtherResourceModel,
        \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory,
        \Magento\Sales\Model\Service\CreditmemoService $creditmemoService,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
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

        $this->shipmentFactory = $shipmentFactory;
        $this->shippingAddressFactory = $shippingAddressFactory;
        $this->orderSender = $orderSender;
        $this->invoiceSender = $invoiceSender;
        $this->componentEbay = $componentEbay;
        $this->listingOtherResourceModel = $listingOtherResourceModel;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoService = $creditmemoService;
    }

    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Ebay\Order::class);
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Order\ProxyObject
     */
    public function getProxy()
    {
        return $this->modelFactory->getObject('Ebay_Order_ProxyObject', ['order' => $this]);
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Account
     */
    public function getEbayAccount()
    {
        return $this->getParentObject()->getAccount()->getChildObject();
    }

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\Ebay\Order\ExternalTransaction\Collection
     */
    public function getExternalTransactionsCollection()
    {
        if ($this->externalTransactionsCollection === null) {
            $this->externalTransactionsCollection = $this->activeRecordFactory
                ->getObject('Ebay_Order_ExternalTransaction')
                ->getCollection()
                ->addFieldToFilter('order_id', $this->getData('order_id'));
        }

        return $this->externalTransactionsCollection;
    }

    /**
     * @return bool
     */
    public function hasExternalTransactions()
    {
        return $this->getExternalTransactionsCollection()->getSize() > 0;
    }

    public function getEbayOrderId()
    {
        return $this->getData('ebay_order_id');
    }

    public function getSellingManagerId()
    {
        return $this->getData('selling_manager_id');
    }

    public function getBuyerName()
    {
        return $this->getData('buyer_name');
    }

    public function getBuyerEmail()
    {
        return $this->getData('buyer_email');
    }

    public function getBuyerUserId()
    {
        return $this->getData('buyer_user_id');
    }

    public function getBuyerMessage()
    {
        return $this->getData('buyer_message');
    }

    public function getBuyerTaxId()
    {
        return $this->getData('buyer_tax_id');
    }

    public function getCurrency()
    {
        return $this->getData('currency');
    }

    public function setFinalFee(float $value): void
    {
        $this->setData('final_fee', $value);
    }

    public function getFinalFee(): ?float
    {
        return $this->getData('final_fee');
    }

    public function getApproximatelyFinalFee()
    {
        /** @var \Ess\M2ePro\Model\Order\Item[] $items */
        $items = $this->getParentObject()->getItemsCollection()->getItems();

        $finalFee = 0;
        foreach ($items as $item) {
            $finalFee += $item->getChildObject()->getFinalFee();
        }

        return $finalFee;
    }

    public function getPaidAmount()
    {
        return $this->getData('paid_amount');
    }

    public function getSavedAmount()
    {
        return $this->getData('saved_amount');
    }

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getMagentoOrdersNumberMarketplacePrefix()
    {
        return strtoupper($this->getParentObject()->getMarketplace()->getChildObject()->getOriginCountry());
    }

    public function getTaxReference()
    {
        return $this->getData('tax_reference');
    }

    public function getTaxDetails()
    {
        return $this->getSettings('tax_details');
    }

    /**
     * @return float
     */
    public function getTaxRate()
    {
        $taxDetails = $this->getTaxDetails();
        if (empty($taxDetails)) {
            return 0.0;
        }

        return (float)$taxDetails['rate'];
    }

    /**
     * @return float
     */
    public function getTaxAmount()
    {
        $taxDetails = $this->getTaxDetails();
        if (empty($taxDetails)) {
            return 0.0;
        }

        return (float)$taxDetails['amount'];
    }

    /**
     * @return bool
     */
    public function isShippingPriceHasTax()
    {
        if (!$this->hasTax()) {
            return false;
        }

        if ($this->isVatTax()) {
            return true;
        }

        $taxDetails = $this->getTaxDetails();

        return isset($taxDetails['includes_shipping']) ? (bool)$taxDetails['includes_shipping'] : false;
    }

    /**
     * @return bool
     */
    public function hasTax()
    {
        $taxDetails = $this->getTaxDetails();

        return !empty($taxDetails['rate']);
    }

    /**
     * @return bool
     */
    public function isSalesTax()
    {
        if (!$this->hasTax()) {
            return false;
        }

        $taxDetails = $this->getTaxDetails();

        return !$taxDetails['is_vat'];
    }

    /**
     * @return bool
     */
    public function isVatTax()
    {
        if (!$this->hasTax()) {
            return false;
        }

        $taxDetails = $this->getTaxDetails();

        return $taxDetails['is_vat'];
    }

    public function getWasteRecyclingFee()
    {
        $resultFee = 0.0;

        foreach ($this->getParentObject()->getItemsCollection() as $item) {
            /** @var \Ess\M2ePro\Model\Ebay\Order\Item $ebayItem */
            $ebayItem = $item->getChildObject();

            $resultFee += $ebayItem->getWasteRecyclingFee();
        }

        return $resultFee;
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getShippingDetails()
    {
        return $this->getSettings('shipping_details');
    }

    /**
     * @return string
     */
    public function getShippingService()
    {
        $shippingDetails = $this->getShippingDetails();

        return isset($shippingDetails['service']) ? $shippingDetails['service'] : '';
    }

    /**
     * @return float
     */
    public function getShippingPrice()
    {
        $shippingDetails = $this->getShippingDetails();

        return isset($shippingDetails['price']) ? (float)$shippingDetails['price'] : 0.0;
    }

    /**
     * @return string
     */
    public function getShippingDate()
    {
        $shippingDetails = $this->getShippingDetails();

        return isset($shippingDetails['date']) ? $shippingDetails['date'] : '';
    }

    public function getShippingDateTo()
    {
        return $this->getData('shipping_date_to');
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Order\ShippingAddress
     */
    public function getShippingAddress()
    {
        $shippingDetails = $this->getShippingDetails();
        $address = isset($shippingDetails['address']) ? $shippingDetails['address'] : [];

        return $this->shippingAddressFactory->create(
            [
                'order' => $this->getParentObject(),
            ]
        )->setData($address);
    }

    /**
     * @return array
     */
    public function getShippingTrackingDetails()
    {
        /** @var \Ess\M2ePro\Model\Order\Item[] $items */
        $items = $this->getParentObject()->getItemsCollection()->getItems();

        $trackingDetails = [];
        foreach ($items as $item) {
            $trackingDetails = array_merge($trackingDetails, $item->getChildObject()->getTrackingDetails());
        }

        $existedTrackingNumbers = [];

        foreach ($trackingDetails as $key => $trackingDetail) {
            if (in_array($trackingDetail['number'], $existedTrackingNumbers)) {
                unset($trackingDetails[$key]);
                continue;
            }

            $existedTrackingNumbers[] = $trackingDetail['number'];
        }

        return $trackingDetails;
    }

    /**
     * @return array
     */
    public function getGlobalShippingDetails()
    {
        $shippingDetails = $this->getShippingDetails();

        return isset($shippingDetails['global_shipping_details'])
            ? $shippingDetails['global_shipping_details'] : [];
    }

    /**
     * @return bool
     */
    public function isUseGlobalShippingProgram()
    {
        return !empty($this->getGlobalShippingDetails());
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Order\ShippingAddress
     */
    public function getGlobalShippingWarehouseAddress()
    {
        if (!$this->isUseGlobalShippingProgram()) {
            return null;
        }

        $globalShippingData = $this->getGlobalShippingDetails();
        $warehouseAddress = is_array($globalShippingData['warehouse_address'])
            ? $globalShippingData['warehouse_address'] : [];

        return $this->shippingAddressFactory->create(
            [
                'order' => $this->getParentObject(),
            ]
        )->setData($warehouseAddress);
    }

    /**
     * @return bool
     */
    public function isUseClickAndCollect()
    {
        $clickEndCollectDetails = $this->getClickAndCollectDetails();

        return !empty($clickEndCollectDetails);
    }

    /**
     * @return array
     */
    public function getClickAndCollectDetails()
    {
        $shippingDetails = $this->getShippingDetails();

        return isset($shippingDetails['click_and_collect_details'])
            ? $shippingDetails['click_and_collect_details'] : [];
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getPaymentDetails()
    {
        return $this->getSettings('payment_details');
    }

    /**
     * @return string
     */
    public function getPaymentMethod()
    {
        $paymentDetails = $this->getPaymentDetails();

        return isset($paymentDetails['method']) ? $paymentDetails['method'] : '';
    }

    /**
     * @return string
     */
    public function getPaymentDate()
    {
        $paymentDetails = $this->getPaymentDetails();

        return isset($paymentDetails['date']) ? $paymentDetails['date'] : '';
    }

    public function getPurchaseUpdateDate()
    {
        return $this->getData('purchase_update_date');
    }

    public function getPurchaseCreateDate()
    {
        return $this->getData(ResourceEbayOrder::COLUMN_PURCHASE_CREATE_DATE);
    }

    /**
     * @return bool
     */
    public function isCheckoutCompleted()
    {
        return (int)$this->getData('checkout_status') == self::CHECKOUT_STATUS_COMPLETED;
    }

    /**
     * @return bool
     */
    public function isPaymentCompleted()
    {
        return (int)$this->getData('payment_status') == self::PAYMENT_STATUS_COMPLETED;
    }

    /**
     * @return bool
     */
    public function isPaymentMethodNotSelected()
    {
        return (int)$this->getData('payment_status') == self::PAYMENT_STATUS_NOT_SELECTED;
    }

    /**
     * @return bool
     */
    public function isPaymentInProcess()
    {
        return (int)$this->getData('payment_status') == self::PAYMENT_STATUS_PROCESS;
    }

    /**
     * @return bool
     */
    public function isPaymentFailed()
    {
        return (int)$this->getData('payment_status') == self::PAYMENT_STATUS_ERROR;
    }

    /**
     * @return bool
     */
    public function isPaymentStatusUnknown()
    {
        return !$this->isPaymentCompleted() &&
            !$this->isPaymentMethodNotSelected() &&
            !$this->isPaymentInProcess() &&
            !$this->isPaymentFailed();
    }

    /**
     * @return bool
     */
    public function isShippingCompleted()
    {
        return (int)$this->getData('shipping_status') == self::SHIPPING_STATUS_COMPLETED;
    }

    /**
     * @return bool
     */
    public function isShippingMethodNotSelected()
    {
        return (int)$this->getData('shipping_status') == self::SHIPPING_STATUS_NOT_SELECTED;
    }

    /**
     * @return bool
     */
    public function isShippingInProcess()
    {
        return (int)$this->getData('shipping_status') == self::SHIPPING_STATUS_PROCESSING;
    }

    /**
     * @return bool
     */
    public function isShippingStatusUnknown()
    {
        return !$this->isShippingCompleted() &&
            !$this->isShippingMethodNotSelected() &&
            !$this->isShippingInProcess();
    }

    /**
     * @return bool
     */
    public function isCanceled()
    {
        return (bool)$this->getData('cancellation_status');
    }

    public function getBuyerCancellationStatus(): int
    {
        return (int)$this->getData('buyer_cancellation_status');
    }

    public function isBuyerReturnRequested(): bool
    {
        return $this->getBuyerReturnRequested() === self::BUYER_RETURN_REQUESTED_STATUS_REQUESTED;
    }

    public function isBuyerReturnApproved(): bool
    {
        return $this->getBuyerReturnRequested() === self::BUYER_RETURN_REQUESTED_STATUS_APPROVED;
    }

    public function isReturnRequestedProcessPossible(): bool
    {
        return !$this->isCanceled() && $this->isShippingCompleted();
    }

    public function setReturnRequestedStatus(int $status): void
    {
        $this->setData('buyer_return_requested', $status);
    }

    public function getBuyerReturnRequested(): int
    {
        return (int)$this->getData('buyer_return_requested');
    }

    public function isBuyerCancellationStatusNotRequested(): bool
    {
        return $this->getBuyerCancellationStatus() === self::BUYER_CANCELLATION_STATUS_NOT_REQUESTED;
    }

    public function isBuyerCancellationStatusRequested(): bool
    {
        return $this->getBuyerCancellationStatus() === self::BUYER_CANCELLATION_STATUS_REQUESTED;
    }

    public function isBuyerCancellationStatusApproved(): bool
    {
        return $this->getBuyerCancellationStatus() === self::BUYER_CANCELLATION_STATUS_APPROVED;
    }

    public function isBuyerCancellationStatusRejected(): bool
    {
        return $this->getBuyerCancellationStatus() === self::BUYER_CANCELLATION_STATUS_REJECTED;
    }

    public function setBuyerCancellationStatus(int $status): void
    {
        $this->setData('buyer_cancellation_status', $status);
    }

    public function isBuyerCancellationPossible(): bool
    {
        return !$this->isCanceled() && !$this->isShippingCompleted();
    }

    /**
     * @return float|int|null
     */
    public function getSubtotalPrice()
    {
        if ($this->subTotalPrice === null) {
            $subtotal = 0;

            foreach ($this->getParentObject()->getItemsCollection() as $item) {
                /** @var \Ess\M2ePro\Model\Order\Item $item */
                $subtotal += $item->getChildObject()->getPrice() * $item->getChildObject()->getQtyPurchased();
            }

            $this->subTotalPrice = $subtotal;
        }

        return $this->subTotalPrice;
    }

    /**
     * @return float|null
     */
    public function getGrandTotalPrice()
    {
        if ($this->grandTotalPrice === null) {
            $this->grandTotalPrice = $this->getSubtotalPrice();
            $this->grandTotalPrice += round((float)$this->getShippingPrice(), 2);
            $this->grandTotalPrice += round((float)$this->getTaxAmount(), 2);
            $this->grandTotalPrice += round((float)$this->getWasteRecyclingFee(), 2);
        }

        return $this->grandTotalPrice;
    }

    public function getStatusForMagentoOrder()
    {
        $status = '';
        $this->isCheckoutCompleted() && $status = $this->getEbayAccount()->getMagentoOrdersStatusNew();
        $this->isPaymentCompleted() && $status = $this->getEbayAccount()->getMagentoOrdersStatusPaid();
        $this->isShippingCompleted() && $status = $this->getEbayAccount()->getMagentoOrdersStatusShipped();

        return $status;
    }

    /**
     * @return int|null
     */
    public function getAssociatedStoreId()
    {
        $channelItems = $this->getParentObject()->getChannelItems();

        if (empty($channelItems)) {
            $storeId = $this->getEbayAccount()->getMagentoOrdersListingsOtherStoreId();
        } else {
            /** @var \Ess\M2ePro\Model\Ebay\Item $firstChannelItem */
            $firstChannelItem = reset($channelItems);
            $itemIsFromOtherListing = $this->listingOtherResourceModel->isItemFromOtherListing(
                $firstChannelItem->getProductId(),
                $firstChannelItem->getAccountId(),
                $firstChannelItem->getMarketplaceId()
            );

            if ($itemIsFromOtherListing) {
                $storeId = $this->getEbayAccount()->getMagentoOrdersListingsOtherStoreId();
            } elseif ($this->getEbayAccount()->isMagentoOrdersListingsStoreCustom()) {
                $storeId = $this->getEbayAccount()->getMagentoOrdersListingsStoreId();
            } else {
                $storeId = $firstChannelItem->getStoreId();
            }
        }

        if ($storeId == 0) {
            $storeId = $this->getHelper('Magento\Store')->getDefaultStoreId();
        }

        return $storeId;
    }

    /**
     * @return bool
     */
    public function canCreateMagentoOrder()
    {
        if ($this->isCanceled()) {
            return false;
        }

        $ebayAccount = $this->getEbayAccount();

        if (
            !$this->isCheckoutCompleted() &&
            ($ebayAccount->shouldCreateMagentoOrderWhenCheckedOut() ||
                $ebayAccount->shouldCreateMagentoOrderWhenCheckedOutAndPaid())
        ) {
            return false;
        }

        if (!$this->isPaymentCompleted() && $ebayAccount->shouldCreateMagentoOrderWhenCheckedOutAndPaid()) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isReservable()
    {
        return !$this->isCanceled();
    }

    public function beforeCreateMagentoOrder()
    {
        $buyerName = $this->getBuyerName();
        if (!empty($buyerName)) {
            return;
        }

        $buyerInfo = $this->getBuyerInfo();

        $shippingDetails = $this->getShippingDetails();
        $shippingDetails['address'] = $buyerInfo['address'];

        $buyerName = trim($buyerInfo['name']);

        $this->getParentObject()->setData('buyer_name', $buyerName);
        $this->getParentObject()->setSettings('shipping_details', $shippingDetails);

        $this->getParentObject()->save();
    }

    public function afterCreateMagentoOrder()
    {
        if ($this->getEbayAccount()->isMagentoOrdersCustomerNewNotifyWhenOrderCreated()) {
            $this->orderSender->send($this->getParentObject()->getMagentoOrder());
        }
    }

    /**
     * @return bool
     */
    public function canCreatePaymentTransaction()
    {
        if (!$this->hasExternalTransactions()) {
            return false;
        }

        $magentoOrder = $this->getParentObject()->getMagentoOrder();
        if ($magentoOrder === null) {
            return false;
        }

        return true;
    }

    public function createPaymentTransactions()
    {
        if (!$this->canCreatePaymentTransaction()) {
            return null;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Order\ProxyObject $proxy */
        $proxy = $this->getParentObject()->getProxy();
        $proxy->setStore($this->getParentObject()->getStore());

        foreach ($proxy->getPaymentTransactions() as $transaction) {
            try {
                /** @var \Ess\M2ePro\Model\Magento\Order\PaymentTransaction $paymentTransactionBuilder */
                $paymentTransactionBuilder = $this->modelFactory->getObject('Magento_Order_PaymentTransaction');
                $paymentTransactionBuilder->setMagentoOrder($this->getParentObject()->getMagentoOrder());
                $paymentTransactionBuilder->setData($transaction);
                $paymentTransactionBuilder->buildPaymentTransaction();
            } catch (\Exception $e) {
                $this->getParentObject()->addErrorLog(
                    'Payment Transaction was not created. Reason: %msg%',
                    ['msg' => $e->getMessage()]
                );
            }
        }
    }

    /**
     * @return bool
     */
    public function canCreateInvoice()
    {
        if (!$this->isPaymentCompleted()) {
            return false;
        }

        if (!$this->getEbayAccount()->isMagentoOrdersInvoiceEnabled()) {
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

        /** @var \Ess\M2ePro\Model\Magento\Order\Invoice $invoiceBuilder */
        $invoiceBuilder = $this->modelFactory->getObject('Magento_Order_Invoice');
        $invoiceBuilder->setMagentoOrder($magentoOrder);
        $invoiceBuilder->buildInvoice();

        $invoice = $invoiceBuilder->getInvoice();

        if ($this->getEbayAccount()->isMagentoOrdersCustomerNewNotifyWhenInvoiceCreated()) {
            $this->invoiceSender->send($invoice);
        }

        return $invoice;
    }

    /**
     * @return bool
     */
    public function canCreateShipments()
    {
        if (!$this->isShippingCompleted()) {
            return false;
        }

        if (!$this->getEbayAccount()->isMagentoOrdersShipmentEnabled()) {
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

    /**
     * @return \Magento\Sales\Model\Order\Shipment[]|null
     */
    public function createShipments()
    {
        if (!$this->canCreateShipments()) {
            return null;
        }

        /** @var \Ess\M2ePro\Model\Magento\Order\Shipment $shipmentBuilder */
        $shipmentBuilder = $this->shipmentFactory->create($this->getParentObject()->getMagentoOrder());
        $shipmentBuilder->setMagentoOrder($this->getParentObject()->getMagentoOrder());
        $shipmentBuilder->buildShipments();

        return $shipmentBuilder->getShipments();
    }

    /**
     * @return bool
     */
    public function canCreateTracks()
    {
        $trackingDetails = $this->getShippingTrackingDetails();
        if (empty($trackingDetails)) {
            return false;
        }

        $magentoOrder = $this->getParentObject()->getMagentoOrder();
        if ($magentoOrder === null) {
            return false;
        }

        if (!$magentoOrder->hasShipments()) {
            return false;
        }

        return true;
    }

    /**
     * @return array|null
     */
    public function createTracks()
    {
        if (!$this->canCreateTracks()) {
            return null;
        }

        $tracks = [];

        try {
            /** @var \Ess\M2ePro\Model\Magento\Order\Shipment\Track $trackBuilder */
            $trackBuilder = $this->modelFactory->getObject('Magento_Order_Shipment_Track');
            $trackBuilder->setMagentoOrder($this->getParentObject()->getMagentoOrder());
            $trackBuilder->setTrackingDetails($this->getShippingTrackingDetails());
            $trackBuilder->setSupportedCarriers($this->getHelper('Component\Ebay')->getCarriers());
            $trackBuilder->buildTracks();
            $tracks = $trackBuilder->getTracks();
        } catch (\Exception $e) {
            $this->getParentObject()->addErrorLog(
                'Tracking details were not imported. Reason: %msg%',
                ['msg' => $e->getMessage()]
            );
        }

        if (!empty($tracks)) {
            $this->getParentObject()->addSuccessLog('Tracking details were imported.');
        }

        return $tracks;
    }

    private function processConnector($action, array $params = [])
    {
        /** @var \Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher $dispatcher */
        $dispatcher = $this->modelFactory->getObject('Ebay_Connector_Order_Dispatcher');

        return $dispatcher->process($action, $this->getParentObject(), $params);
    }

    /**
     * @return bool
     */
    public function canUpdatePaymentStatus()
    {
        // ebay restriction
        if (stripos($this->getPaymentMethod(), 'paisa') !== false) {
            return false;
        }

        return !$this->isPaymentCompleted() && !$this->isPaymentStatusUnknown();
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    public function updatePaymentStatus(array $params = [])
    {
        if (!$this->canUpdatePaymentStatus()) {
            return false;
        }

        $action = \Ess\M2ePro\Model\Order\Change::ACTION_UPDATE_PAYMENT;
        $creator = $this->getParentObject()->getLog()->getInitiator();
        $component = \Ess\M2ePro\Helper\Component\Ebay::NICK;

        $this->activeRecordFactory->getObject('Order\Change')->create(
            $this->getId(),
            $action,
            $creator,
            $component,
            $params
        );

        return true;
    }

    /**
     * @param array $trackingDetails
     *
     * @return bool
     */
    public function canUpdateShippingStatus(array $trackingDetails = [])
    {
        if ($this->isShippingStatusUnknown()) {
            return false;
        }

        // ebay restriction
        if (stripos($this->getPaymentMethod(), 'paisa') !== false) {
            return false;
        }

        if (!$this->isShippingMethodNotSelected() && !$this->isShippingInProcess() && empty($trackingDetails)) {
            return false;
        }

        return true;
    }

    /**
     * @param array{"carrier_code": string, "carrier_title": string} $trackingDetails
     * @param \Ess\M2ePro\Model\Order\Item[] $items
     *
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function updateShippingStatus(array $trackingDetails = [], array $items = [])
    {
        if (!$this->canUpdateShippingStatus($trackingDetails)) {
            return false;
        }

        if (!empty($trackingDetails['carrier_code'])) {
            $trackingDetails['carrier_title'] = $this->componentEbay->getCarrierTitle(
                $trackingDetails['carrier_code'],
                isset($trackingDetails['carrier_title']) ? $trackingDetails['carrier_title'] : ''
            );
        }

        if (!empty($trackingDetails['carrier_title'])) {
            if (
                $trackingDetails['carrier_title'] == \Ess\M2ePro\Model\Order\Shipment\Handler::CUSTOM_CARRIER_CODE &&
                !empty($trackingDetails['shipping_method'])
            ) {
                $trackingDetails['carrier_title'] = $trackingDetails['shipping_method'];
            }

            // remove unsupported by eBay symbols
            $trackingDetails['carrier_title'] = str_replace(
                ['\'', '"', '+', '(', ')'],
                [],
                $trackingDetails['carrier_title']
            );
        }

        $params = $trackingDetails;
        foreach ($items as $item) {
            $itemData = ['item_id' => $item->getId()];
            if ($shippedQty = $item->getChildObject()->getShippedQtyTmpValue()) {
                $itemData['shipped_qty'] = $shippedQty;
            }
            $params['items'][] = $itemData;
        }

        $this->activeRecordFactory->getObject('Order_Change')->create(
            $this->getParentObject()->getId(),
            \Ess\M2ePro\Model\Order\Change::ACTION_UPDATE_SHIPPING,
            $this->getParentObject()->getLog()->getInitiator(),
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            $params
        );

        return true;
    }

    private function getBuyerInfo()
    {
        /** @var \Ess\M2ePro\Model\Order\Item $firstItem */
        $firstItem = $this->getParentObject()->getItemsCollection()->getFirstItem();

        $params = [
            'item_id' => $firstItem->getChildObject()->getItemId(),
            'transaction_id' => $firstItem->getChildObject()->getTransactionId(),
        ];

        /** @var \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcherObj */
        $dispatcherObj = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
        $connectorObj = $dispatcherObj->getVirtualConnector(
            'orders',
            'get',
            'itemTransactions',
            $params,
            'buyer_info',
            null,
            $this->getParentObject()->getAccount(),
            null
        );

        $dispatcherObj->process($connectorObj);
        $buyerInfo = $connectorObj->getResponseData();

        return $buyerInfo;
    }

    public function canRefund(): bool
    {
        if ($this->isCanceled()) {
            return false;
        }

        if (!$this->getEbayAccount()->isRefundEnabled()) {
            return false;
        }

        if ($this->isMarketplaceNotSupportedForRefund()) {
            return false;
        }

        return true;
    }

    private function isMarketplaceNotSupportedForRefund(): bool
    {
        $notSupportedMarketplacesForRefund = [
            EbayHelper::MARKETPLACE_IN,
        ];

        return in_array($this->getParentObject()->getMarketplaceId(), $notSupportedMarketplacesForRefund);
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function cancel()
    {
        if (!$this->canRefund()) {
            return false;
        }

        $params = [
            'channel_order_id' => $this->getEbayOrderId(),
            'reason' => self::CANCEL_REASON_BUYER_ASK,
        ];

        $this->activeRecordFactory->getObject('Order\Change')->create(
            $this->getParentObject()->getId(),
            \Ess\M2ePro\Model\Order\Change::ACTION_CANCEL,
            $this->getParentObject()->getLog()->getInitiator(),
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            $params
        );

        return true;
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function refund(array $items = [], ?Creditmemo $creditMemo = null)
    {
        if (!$this->canRefund()) {
            return false;
        }

        $adjustmentFee = $creditMemo !== null ? $creditMemo->getAdjustmentNegative() : null;

        $params = [
            'channel_order_id' => $this->getEbayOrderId(),
            'cancelReason' => self::CANCEL_REASON_BUYER_ASK,
            'adjustment_fee' => $adjustmentFee,
            'total_refund_amount' => $creditMemo->getGrandTotal(),
        ];

        $this->activeRecordFactory->getObject('Order\Change')->create(
            $this->getParentObject()->getId(),
            \Ess\M2ePro\Model\Order\Change::ACTION_REFUND,
            $this->getParentObject()->getLog()->getInitiator(),
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            $params
        );

        return true;
    }

    public function delete()
    {
        $table = $this->activeRecordFactory->getObject('Ebay_Order_ExternalTransaction')->getResource()->getMainTable();
        $this->_getResource()->getConnection()->delete(
            $table,
            ['order_id = ?' => $this->getData('order_id')]
        );

        return parent::delete();
    }

    public function canCreateCreditMemo(): bool
    {
        if (!$this->getEbayAccount()->isCreateCreditMemoEnabled()) {
            return false;
        }

        $magentoOrder = $this->getParentObject()->getMagentoOrder();
        if ($magentoOrder === null) {
            return false;
        }

        if ($magentoOrder->hasCreditmemos() || !$magentoOrder->canCreditmemo()) {
            return false;
        }

        return true;
    }

    public function createCreditMemo(): ?\Magento\Sales\Model\Order\Creditmemo
    {
        if (!$this->canCreateCreditMemo()) {
            return null;
        }

        $creditMemo = $this->creditmemoFactory->createByOrder($this->getParentObject()->getMagentoOrder());

        foreach ($creditMemo->getAllItems() as $creditMemoItem) {
            $creditMemoItem->setBackToStock(true);
        }

        $this->creditmemoService->refund($creditMemo);
        return $creditMemo;
    }
}
