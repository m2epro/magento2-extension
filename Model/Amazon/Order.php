<?php

namespace Ess\M2ePro\Model\Amazon;

use Magento\Sales\Model\Order\Creditmemo;
use Ess\M2ePro\Model\ResourceModel\Amazon\Order as ResourceAmazonOrder;

/**
 * @method \Ess\M2ePro\Model\Order getParentObject()
 * @method ResourceAmazonOrder getResource()
 */
class Order extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Amazon\AbstractModel
{
    public const STATUS_PENDING = 0;
    public const STATUS_UNSHIPPED = 1;
    public const STATUS_SHIPPED_PARTIALLY = 2;
    public const STATUS_SHIPPED = 3;
    public const STATUS_UNFULFILLABLE = 4;
    public const STATUS_CANCELED = 5;
    public const STATUS_INVOICE_UNCONFIRMED = 6;
    public const STATUS_PENDING_RESERVED = 7;
    public const STATUS_CANCELLATION_REQUESTED = 8;

    public const INVOICE_SOURCE_MAGENTO = 'magento';
    public const INVOICE_SOURCE_EXTENSION = 'extension';

    private const DATE_INTERVAL_IN_HOURS_FOR_INVOICE_SENDING_FROM_REPORT  = 24;

    /** @var \Ess\M2ePro\Model\Magento\Order\ShipmentFactory */
    private $shipmentFactory;
    /** @var \Ess\M2ePro\Model\Amazon\Order\ShippingAddressFactory */
    private $shippingAddressFactory;
    /** @var \Magento\Sales\Model\Order\Email\Sender\OrderSender */
    private $orderSender;
    /** @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender */
    private $invoiceSender;
    /** @var \Ess\M2ePro\Model\Amazon\Order\Tax\PriceTaxRateFactory */
    private $priceTaxRateFactory;
    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    protected $amazonHelper;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory */
    protected $amazonFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Other */
    private $listingOtherResourceModel;

    /** @var null|float  */
    private $subTotalPrice = null;
    /** @var null|float  */
    private $grandTotalPrice = null;
    private \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory;
    /** @var \Magento\Sales\Model\Service\CreditmemoService */
    private $creditmemoService;
    private Magento\Order\Item\UpdateCustomizationDetails $updateCustomizationDetails;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Order\Tax\PriceTaxRateFactory $priceTaxRateFactory,
        \Ess\M2ePro\Helper\Component\Amazon $amazonHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\Magento\Order\ShipmentFactory $shipmentFactory,
        \Ess\M2ePro\Model\Amazon\Order\ShippingAddressFactory $shippingAddressFactory,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Amazon\Magento\Order\Item\UpdateCustomizationDetails $updateCustomizationDetails,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Other $listingOtherResourceModel,
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

        $this->priceTaxRateFactory = $priceTaxRateFactory;
        $this->amazonHelper = $amazonHelper;
        $this->amazonFactory = $amazonFactory;
        $this->shipmentFactory = $shipmentFactory;
        $this->shippingAddressFactory = $shippingAddressFactory;
        $this->orderSender = $orderSender;
        $this->invoiceSender = $invoiceSender;
        $this->listingOtherResourceModel = $listingOtherResourceModel;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoService = $creditmemoService;
        $this->updateCustomizationDetails = $updateCustomizationDetails;
    }

    public function _construct()
    {
        parent::_construct();
        $this->_init(ResourceAmazonOrder::class);
    }

    public function getProxy()
    {
        return $this->modelFactory->getObject(
            'Amazon_Order_ProxyObject',
            ['order' => $this]
        );
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Account
     */
    public function getAmazonAccount()
    {
        return $this->getParentObject()->getAccount()->getChildObject();
    }

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

    public function getShippingCategory()
    {
        return $this->getData('shipping_category');
    }

    public function getShippingMapping()
    {
        return $this->getData('shipping_mapping');
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
        $address = \Ess\M2ePro\Helper\Json::decode($this->getData('shipping_address'));

        return $this->shippingAddressFactory->create([
            'order' => $this->getParentObject(),
        ])->setData($address);
    }

    public function getPurchaseCreateDate(): \DateTime
    {
        return \Ess\M2ePro\Helper\Date::createDateInCurrentZone(
            $this->getDataByKey(ResourceAmazonOrder::COLUMN_PURCHASE_CREATE_DATE)
        );
    }

    /**
     * @return array
     */
    public function getMerchantFulfillmentData()
    {
        return $this->getSettings('merchant_fulfillment_data');
    }

    public function getShippingDateTo()
    {
        return $this->getData('shipping_date_to');
    }

    public function getDeliveryDateTo()
    {
        return $this->getData('delivery_date_to');
    }

    public function getIossNumber()
    {
        return $this->getData('ioss_number');
    }

    public function getTaxRegistrationId()
    {
        return $this->getData('tax_registration_id');
    }

    public function setTaxRegistrationId(string $taxRegistrationId): Order
    {
        $this->setData('tax_registration_id', $taxRegistrationId);

        return $this;
    }

    /**
     * @return bool
     */
    public function isBuyerRequestedCancel()
    {
        return (bool)$this->getData('is_buyer_requested_cancel');
    }

    public function getBuyerCancelReason()
    {
        return $this->getData('buyer_cancel_reason');
    }

    /**
     * @return float
     */
    public function getPaidAmount()
    {
        return (float)$this->getData('paid_amount');
    }

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
        return $this->priceTaxRateFactory->createProductPriceTaxRateByOrder($this)->getValue();
    }

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

    /**
     * @return bool
     */
    public function isFulfilledByAmazon()
    {
        return (bool)$this->getData('is_afn_channel');
    }

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

    public function isReplacement(): bool
    {
        return (bool)$this->getData('is_replacement');
    }

    /**
     * @return bool
     */
    public function isSoldByAmazon(): bool
    {
        return (bool)$this->getData('is_sold_by_amazon');
    }

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
        $status = $this->getStatus();

        return $status == self::STATUS_UNSHIPPED || $status == self::STATUS_CANCELLATION_REQUESTED;
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

    //----------------------------------

    public function isInvoiceSent(): bool
    {
        return (bool)$this->getDataByKey(ResourceAmazonOrder::COLUMN_IS_INVOICE_SENT);
    }

    public function isExistsDateOfInvoiceSent(): bool
    {
        return $this->getDataByKey(ResourceAmazonOrder::COLUMN_DATE_OF_INVOICE_SENDING) !== null;
    }

    public function getDateOfInvoiceSent(): \DateTime
    {
        if (!$this->isExistsDateOfInvoiceSent()) {
            throw new \LogicException('Date Of Invoice Sent is not set');
        }

        return \Ess\M2ePro\Helper\Date::createDateGmt(
            $this->getDataByKey(ResourceAmazonOrder::COLUMN_DATE_OF_INVOICE_SENDING)
        );
    }

    public function markThatInvoiceSentToChannel(): self
    {
        $this->setData(ResourceAmazonOrder::COLUMN_IS_INVOICE_SENT, 1);
        $this->setData(
            ResourceAmazonOrder::COLUMN_DATE_OF_INVOICE_SENDING,
            \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s')
        );

        return $this;
    }

    //----------------------------------

    /**
     * @return bool
     */
    public function isCancellationRequested()
    {
        return $this->getStatus() == self::STATUS_CANCELLATION_REQUESTED;
    }

    /**
     * @return bool
     */
    public function isMagentoOrderIdAppliedToAmazonOrder()
    {
        $realMagentoOrderId = $this->getData('seller_order_id');

        return empty($realMagentoOrderId);
    }

    /**
     * @return string
     */
    public function getSellerOrderId()
    {
        return $this->getData('seller_order_id');
    }

    public function getSubtotalPrice(): float
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
            $this->grandTotalPrice += $this->getGiftPriceTaxAmount();
            $this->grandTotalPrice -= $this->getPromotionDiscountAmount();
            $this->grandTotalPrice -= $this->getShippingDiscountAmount();
        }

        return round($this->grandTotalPrice, 2);
    }

    public function getStatusForMagentoOrder()
    {
        $status = '';
        $this->isUnshipped() && $status = $this->getAmazonAccount()->getMagentoOrdersStatusProcessing();
        $this->isPartiallyShipped() && $status = $this->getAmazonAccount()->getMagentoOrdersStatusProcessing();
        $this->isShipped() && $status = $this->getAmazonAccount()->getMagentoOrdersStatusShipped();

        return $status;
    }

    /**
     * @return int|null
     */
    public function getAssociatedStoreId()
    {
        $channelItems = $this->getParentObject()->getChannelItems();

        if (count($channelItems) == 0) {
            $storeId = $this->getAmazonAccount()->getMagentoOrdersListingsOtherStoreId();
        } else {
            /** @var \Ess\M2ePro\Model\Amazon\Item $firstChannelItem */
            $firstChannelItem = reset($channelItems);
            $itemIsFromOtherListing = $this->listingOtherResourceModel->isItemFromOtherListing(
                $firstChannelItem->getProductId(),
                $firstChannelItem->getAccountId(),
                $firstChannelItem->getMarketplaceId()
            );

            if ($itemIsFromOtherListing) {
                $storeId = $this->getAmazonAccount()->getMagentoOrdersListingsOtherStoreId();
            } elseif ($this->getAmazonAccount()->isMagentoOrdersListingsStoreCustom()) {
                $storeId = $this->getAmazonAccount()->getMagentoOrdersListingsStoreId();
            } else {
                $storeId = $firstChannelItem->getStoreId();
            }
        }

        if ($this->isFulfilledByAmazon() && $this->getAmazonAccount()->isMagentoOrdersFbaStoreModeEnabled()) {
            $storeId = $this->getAmazonAccount()->getMagentoOrdersFbaStoreId();
        }

        if ($storeId == 0) {
            $storeId = $this->getHelper('Magento\Store')->getDefaultStoreId();
        }

        return $storeId;
    }

    /**
     * @return bool
     */
    public function isReservable()
    {
        if ($this->isCanceled()) {
            return false;
        }

        if (
            $this->isFulfilledByAmazon() &&
            (!$this->getAmazonAccount()->isMagentoOrdersFbaModeEnabled() ||
                !$this->getAmazonAccount()->isMagentoOrdersFbaStockEnabled())
        ) {
            return false;
        }

        return true;
    }

    /**
     * Check possibility for magento order creation
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

    public function beforeCreateMagentoOrder(): void
    {
        if ($this->isPending() || $this->isCanceled()) {
            throw new \Ess\M2ePro\Model\Exception(
                'Magento Order Creation is not allowed for pending and canceled Amazon Orders.'
            );
        }
    }

    public function afterCreateMagentoOrder(): void
    {
        if ($this->getAmazonAccount()->isMagentoOrdersCustomerNewNotifyWhenOrderCreated()) {
            $this->orderSender->send($this->getParentObject()->getMagentoOrder());
        }

        if ($this->isFulfilledByAmazon() && !$this->getAmazonAccount()->isMagentoOrdersFbaStockEnabled()) {
            $this->_eventManager->dispatch('ess_amazon_fba_magento_order_place_after', [
                'magento_order' => $this->getParentObject()->getMagentoOrder(),
            ]);
        }

        $this->updateCustomizationDetails->process($this);
    }

    /**
     * @return bool
     */
    public function canCreateInvoice()
    {
        if (!$this->getAmazonAccount()->isMagentoOrdersInvoiceEnabled()) {
            return false;
        }

        if ($this->isPending() || $this->isCanceled() || $this->isUnfulfillable() || $this->isInvoiceUnconfirmed()) {
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

        // Create invoice
        // ---------------------------------------
        /** @var \Ess\M2ePro\Model\Magento\Order\Invoice $invoiceBuilder */
        $invoiceBuilder = $this->modelFactory->getObject('Magento_Order_Invoice');
        $invoiceBuilder->setMagentoOrder($magentoOrder);
        $invoiceBuilder->buildInvoice();
        // ---------------------------------------

        $invoice = $invoiceBuilder->getInvoice();

        if ($this->getAmazonAccount()->isMagentoOrdersCustomerNewNotifyWhenInvoiceCreated()) {
            $this->invoiceSender->send($invoice);
        }

        $this->sendInvoice();

        return $invoice;
    }

    /**
     * @return bool
     */
    public function canCreateShipments()
    {
        if (!$this->getAmazonAccount()->isMagentoOrdersShipmentEnabled()) {
            return false;
        }

        if ($this->isFulfilledByAmazon() && !$this->getAmazonAccount()->isMagentoOrdersShipmentEnabledForFBA()) {
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
     * @param array $trackingDetails
     *
     * @return bool
     */
    public function canUpdateShippingStatus(array $trackingDetails = [])
    {
        if ($this->isFulfilledByAmazon()) {
            return false;
        }

        if ($this->isPending() || $this->isCanceled()) {
            return false;
        }

        if ($this->isUnshipped() || $this->isPartiallyShipped()) {
            return true;
        }

        if (empty($trackingDetails)) {
            return false;
        }

        return true;
    }

    /**
     * @param array $trackingDetails
     * @param array $items
     *
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function updateShippingStatus(array $trackingDetails = [], array $items = [])
    {
        if (!$this->canUpdateShippingStatus($trackingDetails)) {
            return false;
        }

        if (
            empty($trackingDetails['carrier_code'])
            && !$this->getAmazonAccount()->isUpdateWithoutTrackToMagentoOrder()
        ) {
            return false;
        }

        if (!isset($trackingDetails['fulfillment_date'])) {
            $trackingDetails['fulfillment_date'] = $this->getHelper('Data')->getCurrentGmtDate();
        }

        if (!empty($trackingDetails['carrier_code'])) {
            $trackingDetails['carrier_title'] = $this->getHelper('Component_Amazon')->getCarrierTitle(
                $trackingDetails['carrier_code'],
                $trackingDetails['carrier_title'] ?? ''
            );
        }

        if (
            !empty($trackingDetails['carrier_title'])
            && $trackingDetails['carrier_title'] == \Ess\M2ePro\Model\Order\Shipment\Handler::CUSTOM_CARRIER_CODE
            && !empty($trackingDetails['shipping_method'])
        ) {
            $trackingDetails['carrier_title'] = $trackingDetails['shipping_method'];
        }

        $params = array_merge([
            'amazon_order_id' => $this->getAmazonOrderId(),
            'fulfillment_date' => $trackingDetails['fulfillment_date'],
            'items' => [],
        ], $trackingDetails);

        foreach ($items as $item) {
            if (!isset($item['amazon_order_item_id']) || !isset($item['qty'])) {
                continue;
            }

            if ((int)$item['qty'] <= 0) {
                continue;
            }

            $params['items'][] = [
                'amazon_order_item_id' => $item['amazon_order_item_id'],
                'qty' => (int)$item['qty'],
            ];
        }

        /** @var \Ess\M2ePro\Model\Order\Change $change */
        $change = $this->activeRecordFactory
            ->getObject('Order_Change')
            ->getCollection()
            ->addFieldToFilter('order_id', $this->getParentObject()->getId())
            ->addFieldToFilter('action', \Ess\M2ePro\Model\Order\Change::ACTION_UPDATE_SHIPPING)
            ->addFieldToFilter('processing_attempt_count', 0)
            ->getFirstItem();

        $existingParams = $change->getParams();

        $newTrackingNumber = !empty($trackingDetails['tracking_number']) ? $trackingDetails['tracking_number'] : '';
        $oldTrackingNumber = !empty($existingParams['tracking_number']) ? $existingParams['tracking_number'] : '';
        $oldCarrierCode = !empty($existingParams['carrier_code']) ? $existingParams['carrier_code'] : '';
        $oldCarrierTitle = !empty($existingParams['carrier_title']) ? $existingParams['carrier_title'] : '';
        $newCarrierCode = !empty($trackingDetails['carrier_code']) ? $trackingDetails['carrier_code'] : '';
        $newCarrierTitle = !empty($trackingDetails['carrier_title']) ? $trackingDetails['carrier_title'] : '';

        if (
            !$change->getId()
            || $newTrackingNumber !== $oldTrackingNumber
            || $newCarrierTitle !== $oldCarrierTitle
            || $newCarrierCode !== $oldCarrierCode
        ) {
            $this->activeRecordFactory->getObject('Order_Change')->create(
                $this->getParentObject()->getId(),
                \Ess\M2ePro\Model\Order\Change::ACTION_UPDATE_SHIPPING,
                $this->getParentObject()->getLog()->getInitiator(),
                \Ess\M2ePro\Helper\Component\Amazon::NICK,
                $params
            );

            return true;
        }

        $existingParams = $change->getParams();
        foreach ($params['items'] as $newItem) {
            foreach ($existingParams['items'] as &$existingItem) {
                if ($newItem['amazon_order_item_id'] === $existingItem['amazon_order_item_id']) {
                    $newQtyTotal = $newItem['qty'] + $existingItem['qty'];

                    $maxQtyTotal = $this->activeRecordFactory
                        ->getObject('Amazon_Order_Item')
                        ->getCollection()
                        ->addFieldToFilter(
                            'amazon_order_item_id',
                            $existingItem['amazon_order_item_id']
                        )
                        ->getFirstItem()
                        ->getQtyPurchased();
                    $newQtyTotal >= $maxQtyTotal && $newQtyTotal = $maxQtyTotal;
                    $existingItem['qty'] = $newQtyTotal;
                    continue 2;
                }
            }

            unset($existingItem);
            $existingParams['items'][] = $newItem;
        }

        $change->setData('params', \Ess\M2ePro\Helper\Json::encode($existingParams));
        $change->save();

        return true;
    }

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
     *
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function refund(array $items = [], ?Creditmemo $creditMemo = null)
    {
        if (!$this->canRefund()) {
            return false;
        }

        $adjustmentFee = $creditMemo !== null ? (float)$creditMemo->getAdjustmentNegative() : 0.0;
        $adjustmentRefund = $creditMemo !== null ? (float)$creditMemo->getAdjustmentPositive() : 0.0;
        $shippingRefund = $creditMemo !== null ? (float)$creditMemo->getShippingAmount() : 0.0;
        $shippingTaxRefund = $creditMemo !== null ? (float)$creditMemo->getShippingTaxAmount() : 0.0;

        $params = [
            'order_id' => $this->getAmazonOrderId(),
            'currency' => $this->getCurrency(),
            'adjustment_fee' => $adjustmentFee,
            'adjustment_refund' => $adjustmentRefund,
            'shipping_refund' => $shippingRefund,
            'shipping_tax_refund' => $shippingTaxRefund,
            'items' => $items,
        ];

        $isRefundAction = $this->isShipped()
            || $this->isPartiallyShipped()
            || $this->getParentObject()->isOrderStatusUpdatingToShipped();

        $action = $isRefundAction
            ? \Ess\M2ePro\Model\Order\Change::ACTION_REFUND
            : \Ess\M2ePro\Model\Order\Change::ACTION_CANCEL;

        if ($action == \Ess\M2ePro\Model\Order\Change::ACTION_REFUND) {
            if (empty($items)) {
                $this->getParentObject()->addErrorLog(
                    'Amazon Order was not refunded. Reason: %msg%',
                    [
                        'msg' => 'Refund request was not submitted.
                                    To be processed through Amazon API, the refund must be applied to certain products
                                    in an order. Please indicate the number of each line item, that need to be refunded,
                                    in Credit Memo form.',
                    ]
                );

                return false;
            }

            if (
                empty($adjustmentFee)
                && empty($adjustmentRefund)
                && empty($shippingRefund)
                && empty(array_sum(array_column($items, 'cancelled_qty')))
            ) {
                $this->getParentObject()->addErrorLog(
                    'Amazon order cannot be refunded: The Credit Memo does not specify any refund amount. ' .
                    'Please ensure the Credit Memo includes a refund cost to process the refund.',
                );

                return false;
            }
        }

        if (
            $action == \Ess\M2ePro\Model\Order\Change::ACTION_CANCEL
            && $this->isCancellationRequested()
        ) {
            $params['cancel_reason'] =
                \Ess\M2ePro\Model\Amazon\Order\Creditmemo\Handler::AMAZON_REFUND_REASON_BUYER_CANCELED;
        }

        $this->activeRecordFactory->getObject('Order\Change')->create(
            $this->getParentObject()->getId(),
            $action,
            $this->getParentObject()->getLog()->getInitiator(),
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            $params
        );

        return true;
    }

    /**
     * @return bool
     */
    public function canSendMagentoCreditmemo()
    {
        if (!$this->getAmazonAccount()->getMarketplace()->getChildObject()->isVatCalculationServiceAvailable()) {
            return false;
        }

        if ($this->getAmazonAccount()->isAutoInvoicingDisabled()) {
            return false;
        }

        if (!$this->getAmazonAccount()->isUploadMagentoInvoices()) {
            return false;
        }

        $magentoOrder = $this->getParentObject()->getMagentoOrder();
        if ($magentoOrder === null) {
            return false;
        }

        if (!$this->getParentObject()->getMagentoOrder()->hasCreditmemos()) {
            return false;
        }

        /** @var \Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection $creditmemos */
        $creditmemos = $this->getParentObject()->getMagentoOrder()->getCreditmemosCollection();
        /** @var \Magento\Sales\Model\Order\Creditmemo $creditmemo */
        $creditmemo = $creditmemos->getLastItem();

        if ($this->getGrandTotalPrice() !== round((float)$creditmemo->getGrandTotal(), 2)) {
            return false;
        }

        return true;
    }

    public function sendCreditmemo()
    {
        if (!$this->canSendMagentoCreditmemo()) {
            return false;
        }

        $params = [
            'invoice_source' => self::INVOICE_SOURCE_MAGENTO,
            'document_type' => \Ess\M2ePro\Model\Amazon\Order\Invoice::DOCUMENT_TYPE_CREDIT_NOTE,
        ];

        $this->sendInvoiceDocument($params);

        return true;
    }

    /**
     * @return bool
     */
    public function canSendMagentoInvoice()
    {
        if (!$this->getAmazonAccount()->getMarketplace()->getChildObject()->isVatCalculationServiceAvailable()) {
            return false;
        }

        if ($this->getAmazonAccount()->isAutoInvoicingDisabled()) {
            return false;
        }

        if (!$this->getAmazonAccount()->isUploadMagentoInvoices()) {
            return false;
        }

        $magentoOrder = $this->getParentObject()->getMagentoOrder();
        if ($magentoOrder === null) {
            return false;
        }

        if (!$magentoOrder->hasInvoices()) {
            return false;
        }

        /** @var \Magento\Sales\Model\ResourceModel\Order\Invoice\Collection $invoices */
        $invoices = $magentoOrder->getInvoiceCollection();
        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        $invoice = $invoices->getLastItem();

        if ($this->getGrandTotalPrice() !== round((float)$invoice->getGrandTotal(), 2)) {
            return false;
        }

        return true;
    }

    public function sendInvoice()
    {
        if (!$this->canSendMagentoInvoice()) {
            return false;
        }

        $params = [
            'invoice_source' => self::INVOICE_SOURCE_MAGENTO,
            'document_type' => \Ess\M2ePro\Model\Amazon\Order\Invoice::DOCUMENT_TYPE_INVOICE,
        ];

        $this->sendInvoiceDocument($params);

        return true;
    }

    public function canSendInvoiceFromReport(): bool
    {
        if (!$this->getAmazonAccount()->getMarketplace()->getChildObject()->isVatCalculationServiceAvailable()) {
            return false;
        }

        if (!$this->getAmazonAccount()->isVatCalculationServiceEnabled()) {
            return false;
        }

        if (!$this->getAmazonAccount()->isInvoiceGenerationByExtension()) {
            return false;
        }

        $reportData = $this->getSettings('invoice_data_report');
        if (empty($reportData)) {
            return false;
        }

        if (!$this->canSendInvoiceFromReportAgain()) {
            return false;
        }

        return true;
    }

    private function canSendInvoiceFromReportAgain(): bool
    {
        if (!$this->isInvoiceSent()) {
            return true;
        }

        $limitDateOfInvoiceSent = \M2E\AmazonMcf\Helper\Date::createCurrentGmt()->modify(
            sprintf('-%d hours', self::DATE_INTERVAL_IN_HOURS_FOR_INVOICE_SENDING_FROM_REPORT)
        );
        if (
            $this->isExistsDateOfInvoiceSent()
            && $this->getDateOfInvoiceSent() > $limitDateOfInvoiceSent
        ) {
            return false;
        }

        return true;
    }

    public function sendInvoiceFromReport()
    {
        if (!$this->canSendInvoiceFromReport()) {
            return false;
        }

        $params = [
            'invoice_source' => self::INVOICE_SOURCE_EXTENSION,
            'document_type' => '',
        ];

        $this->sendInvoiceDocument($params);

        return true;
    }

    public function sendInvoiceDocument(array $params)
    {
        if (empty($params['invoice_source'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('invoice source param not found.');
        }

        $this->activeRecordFactory->getObject('Order\Change')->create(
            $this->getParentObject()->getId(),
            \Ess\M2ePro\Model\Order\Change::ACTION_SEND_INVOICE,
            $this->getParentObject()->getLog()->getInitiator(),
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            $params
        );

        return true;
    }

    public function delete()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Order\Invoice\Collection $invoiceCollection */
        $invoiceCollection = $this->activeRecordFactory->getObject('Amazon_Order_Invoice')->getCollection();
        $invoiceCollection->addFieldToFilter('order_id', $this->getId());
        foreach ($invoiceCollection->getItems() as $invoice) {
            $invoice->delete();
        }

        return parent::delete();
    }

    public function setIsGetDeliveryPreferences(): Order
    {
        $this->setData('is_get_delivery_preferences', 1);

        return $this;
    }

    public function setFinalFees(array $data): Order
    {
        $this->setData('final_fees', \Ess\M2ePro\Helper\Json::encode($data));

        return $this;
    }

    public function getFinalFees(): array
    {
        return $this->getData('final_fees') !== null
            ? \Ess\M2ePro\Helper\Json::decode($this->getData('final_fees')) : [];
    }

    public function isNewFinalFees(array $newFees): bool
    {
        $currentFees = $this->getFinalFees();

        return $currentFees !== $newFees;
    }

    public function canCreateCreditMemo(): bool
    {
        $magentoOrder = $this->getParentObject()->getMagentoOrder();
        if ($magentoOrder === null) {
            return false;
        }

        if ($magentoOrder->hasCreditmemos() || !$magentoOrder->canCreditmemo()) {
            return false;
        }

        if ($this->isCanceled()) {
            return $this->getAmazonAccount()->isCreateCreditMemoEnabled();
        }

        if (
            !$this->getAmazonAccount()->isCreateCreditMemoBuyerRequestedCancelEnabled()
            && $this->isBuyerRequestedCancel()
        ) {
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

    public function getReplacedAmazonOrderId(): ?string
    {
        return $this->getData('replaced_amazon_order_id');
    }

    public function setReplacedAmazonOrderId(?string $value): void
    {
        $this->setData('replaced_amazon_order_id', $value);
    }

    public function getPaymentMethodDetails(): array
    {
        $data = $this->getData(ResourceAmazonOrder::COLUMN_PAYMENT_METHOD_DETAILS);
        if (empty($data)) {
            return [];
        }

        return json_decode($data, true);
    }
}
