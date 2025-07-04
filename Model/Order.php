<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model;

use Ess\M2ePro\Model\Magento\Quote\FailDuringEventProcessing;
use Ess\M2ePro\Model\Order\Exception\ProductCreationDisabled;
use Ess\M2ePro\Model\Log\AbstractModel as Log;

/**
 * @method \Ess\M2ePro\Model\Amazon\Order|\Ess\M2ePro\Model\Ebay\Order|\Ess\M2ePro\Model\Walmart\Order getChildObject()
 */
class Order extends ActiveRecord\Component\Parent\AbstractModel
{
    public const ADDITIONAL_DATA_KEY_IN_ORDER = 'm2epro_order';
    public const ADDITIONAL_DATA_KEY_VAT_REVERSE_CHARGE = 'vat_reverse_charge';

    public const MAGENTO_ORDER_CREATION_FAILED_NO = 0;
    public const MAGENTO_ORDER_CREATION_FAILED_YES = 1;

    private $storeManager;

    private $orderFactory;

    private $resourceConnection;

    private $account = null;

    private $marketplace = null;

    private $magentoOrder = null;

    private $shippingAddress = null;

    /** @var \Ess\M2ePro\Model\ResourceModel\Order\Item\Collection */
    private $itemsCollection = null;

    private $proxy = null;

    /** @var \Ess\M2ePro\Model\Order\Reserve */
    private $reserve = null;

    private $statusUpdateRequired = false;

    private $helperModuleException;
    private $helperModuleLog;

    /** @var \Ess\M2ePro\Model\Order\Log */
    private $logModel = null;

    private $productHelper = null;

    /** @var Magento\Quote\Manager|null */
    private $quoteManager = null;
    /** @var \Ess\M2ePro\Model\Order\Note\Repository */
    private $noteRepository;
    /** @var \Ess\M2ePro\Model\Order\EventDispatcher */
    private $eventDispatcher;

    public function __construct(
        \Ess\M2ePro\Model\Order\EventDispatcher $eventDispatcher,
        \Ess\M2ePro\Model\Order\Note\Repository $noteRepository,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Helper\Product $productHelper,
        \Ess\M2ePro\Model\Magento\Quote\Manager $quoteManager,
        \Ess\M2ePro\Helper\Module\Exception $helperModuleException,
        \Ess\M2ePro\Helper\Module\Log $helperModuleLog,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->noteRepository = $noteRepository;
        $this->storeManager = $storeManager;
        $this->orderFactory = $orderFactory;
        $this->resourceConnection = $resourceConnection;
        $this->productHelper = $productHelper;
        $this->quoteManager = $quoteManager;
        $this->helperModuleException = $helperModuleException;
        $this->helperModuleLog = $helperModuleLog;

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

        $this->eventDispatcher = $eventDispatcher;
    }

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Order::class);
    }

    //########################################

    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        $this->noteRepository->deleteByOrderId($this->getId());

        foreach ($this->getItemsCollection()->getItems() as $item) {
            /** @var \Ess\M2ePro\Model\Order\Item $item */
            $item->delete();
        }
        $this->deleteChildInstance();

        $this->activeRecordFactory->getObject('Order\Change')->getCollection()
                                  ->addFieldToFilter('order_id', $this->getId())
                                  ->walk('delete');

        $this->account = null;
        $this->magentoOrder = null;
        $this->itemsCollection = null;
        $this->proxy = null;

        return parent::delete();
    }

    //########################################

    public function getAccountId()
    {
        return $this->getData('account_id');
    }

    public function getMarketplaceId()
    {
        return $this->getData('marketplace_id');
    }

    public function getMagentoOrderId()
    {
        return $this->getData('magento_order_id');
    }

    public function isMagentoOrderCreationFailed()
    {
        return (bool)(int)$this->getData('magento_order_creation_failure');
    }

    public function getMagentoOrderCreationFailsCount()
    {
        return (int)$this->getData('magento_order_creation_fails_count');
    }

    public function getMagentoOrderCreationLatestAttemptDate()
    {
        return $this->getData('magento_order_creation_latest_attempt_date');
    }

    public function getStoreId()
    {
        return $this->getData('store_id');
    }

    public function getCreateDate()
    {
        return $this->getData('create_date');
    }

    /**
     * @return int
     */
    public function getReservationState()
    {
        return (int)$this->getData('reservation_state');
    }

    public function getAdditionalData()
    {
        return $this->getSettings('additional_data');
    }

    //########################################

    public function setStatusUpdateRequired($isRequired = true)
    {
        $this->statusUpdateRequired = $isRequired;

        return $this;
    }

    public function getStatusUpdateRequired()
    {
        return $this->statusUpdateRequired;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Account $account
     *
     * @return $this
     */
    public function setAccount(\Ess\M2ePro\Model\Account $account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Account
     * @throws \LogicException
     */
    public function getAccount()
    {
        if ($this->account === null) {
            $this->account = $this->parentFactory->getCachedObjectLoaded(
                $this->getComponentMode(),
                'Account',
                $this->getAccountId()
            );
        }

        return $this->account;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Marketplace $marketplace
     *
     * @return $this
     */
    public function setMarketplace(\Ess\M2ePro\Model\Marketplace $marketplace)
    {
        $this->marketplace = $marketplace;

        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     * @throws \LogicException
     */
    public function getMarketplace()
    {
        if ($this->marketplace === null) {
            $this->marketplace = $this->parentFactory->getCachedObjectLoaded(
                $this->getComponentMode(),
                'Marketplace',
                $this->getMarketplaceId()
            );
        }

        return $this->marketplace;
    }

    //########################################

    /**
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    public function getStore()
    {
        return $this->storeManager->getStore($this->getStoreId());
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Order\Reserve
     */
    public function getReserve()
    {
        if ($this->reserve === null) {
            $this->reserve = $this->modelFactory->getObject('Order\Reserve', [
                'order' => $this,
            ]);
        }

        return $this->reserve;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Order\Log
     */
    public function getLog()
    {
        if (!$this->logModel) {
            $this->logModel = $this->activeRecordFactory->getObject('Order\Log');
            $this->logModel->setComponentMode($this->getComponentMode());
        }

        return $this->logModel;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\Order\Item\Collection
     */
    public function getItemsCollection()
    {
        if ($this->itemsCollection === null) {
            $this->itemsCollection = $this->parentFactory->getObject($this->getComponentMode(), 'Order\Item')
                                                         ->getCollection()
                                                         ->addFieldToFilter('order_id', $this->getId());

            foreach ($this->itemsCollection as $item) {
                /** @var \Ess\M2ePro\Model\Order\Item $item */
                $item->setOrder($this);
            }
        }

        return $this->itemsCollection;
    }

    /**
     * @return Order\Item[]
     */
    public function getItems(): array
    {
        return $this->getItemsCollection()->getItems();
    }

    // ---------------------------------------

    /**
     * Check whether the order has only single item ordered
     * @return bool
     */
    public function isSingle()
    {
        return $this->getItemsCollection()->getSize() == 1;
    }

    /**
     * Check whether the order has multiple items ordered
     * @return bool
     */
    public function isCombined()
    {
        return $this->getItemsCollection()->getSize() > 1;
    }

    // ---------------------------------------

    /**
     * Get instances of the channel items (Ebay\Item, Amazon\Item etc)
     * @return array
     */
    public function getChannelItems(): array
    {
        $channelItems = [];

        foreach ($this->getItems() as $item) {
            $channelItem = $item->getChildObject()->getChannelItem();

            if ($channelItem === null) {
                continue;
            }

            $channelItems[] = $channelItem;
        }

        return $channelItems;
    }

    // ---------------------------------------

    /**
     * Check whether the order has items, listed by M2E Pro (also true for linked Unmanaged listings)
     * @return bool
     */
    public function hasListingItems()
    {
        $channelItems = $this->getChannelItems();

        return !empty($channelItems);
    }

    /**
     * Check whether the order has items, listed by Unmanaged software
     * @return bool
     */
    public function hasOtherListingItems()
    {
        $channelItems = $this->getChannelItems();

        return count($channelItems) != $this->getItemsCollection()->getSize();
    }

    //########################################

    public function addLog(
        $description,
        $type,
        array $params = [],
        array $links = [],
        $isUnique = false,
        $additionalData = []
    ) {
        /** @var \Ess\M2ePro\Model\Order\Log $log */
        $log = $this->getLog();

        if (!empty($params)) {
            $description = $this->helperModuleLog->encodeDescription($description, $params, $links);
        }

        return $log->addMessage($this, $description, $type, $additionalData, $isUnique);
    }

    public function addSuccessLog(
        $description,
        array $params = [],
        array $links = [],
        $isUnique = false,
        $additionalData = []
    ) {
        return $this->addLog($description, Log::TYPE_SUCCESS, $params, $links, $isUnique, $additionalData);
    }

    public function addInfoLog(
        $description,
        array $params = [],
        array $links = [],
        $isUnique = false,
        $additionalData = []
    ) {
        return $this->addLog($description, Log::TYPE_INFO, $params, $links, $isUnique, $additionalData);
    }

    public function addWarningLog(
        $description,
        array $params = [],
        array $links = [],
        $isUnique = false,
        $additionalData = []
    ) {
        return $this->addLog($description, Log::TYPE_WARNING, $params, $links, $isUnique, $additionalData);
    }

    public function addErrorLog(
        $description,
        array $params = [],
        array $links = [],
        $isUnique = false,
        $additionalData = []
    ) {
        return $this->addLog($description, Log::TYPE_ERROR, $params, $links, $isUnique, $additionalData);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Order\ShippingAddress
     */
    public function getShippingAddress()
    {
        if ($this->shippingAddress === null) {
            $this->shippingAddress = $this->getChildObject()->getShippingAddress();
        }

        return $this->shippingAddress;
    }

    //########################################

    public function setMagentoOrder($order)
    {
        $this->magentoOrder = $order;

        return $this;
    }

    /**
     * @return null|\Magento\Sales\Model\Order
     */
    public function getMagentoOrder()
    {
        if ($this->getMagentoOrderId() === null) {
            return null;
        }

        if ($this->magentoOrder === null) {
            $this->magentoOrder = $this->orderFactory->create()->load($this->getMagentoOrderId());
        }

        return $this->magentoOrder->getId() !== null ? $this->magentoOrder : null;
    }

    //########################################

    public function addCreatedMagentoShipment(\Magento\Sales\Model\Order\Shipment $magentoShipment)
    {
        $additionalData = $this->getAdditionalData();
        $additionalData['created_shipments_ids'][] = $magentoShipment->getId();
        $this->setSettings('additional_data', $additionalData)->save();

        return $this;
    }

    public function isMagentoShipmentCreatedByOrder(\Magento\Sales\Model\Order\Shipment $magentoShipment)
    {
        $additionalData = $this->getAdditionalData();
        if (empty($additionalData['created_shipments_ids']) || !is_array($additionalData['created_shipments_ids'])) {
            return false;
        }

        return in_array($magentoShipment->getId(), $additionalData['created_shipments_ids']);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Order\ProxyObject
     */
    public function getProxy()
    {
        if ($this->proxy === null) {
            $this->proxy = $this->getChildObject()->getProxy();
        }

        return $this->proxy;
    }

    //########################################

    /**
     * Find the store, where order should be placed
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function associateWithStore()
    {
        $storeId = $this->getStoreId() ? $this->getStoreId() : $this->getChildObject()->getAssociatedStoreId();
        $store = $this->storeManager->getStore($storeId);

        if ($store->getId() === null) {
            throw new \Ess\M2ePro\Model\Exception('Store does not exist.');
        }

        if ($this->getStoreId() != $store->getId()) {
            $this->setData('store_id', $store->getId())->save();
        }

        if (!$store->getConfig('payment/m2epropayment/active')) {
            throw new \Ess\M2ePro\Model\Exception(
                'Payment method "M2E Pro Payment" is disabled under
                <i>Stores > Settings > Configuration > Sales > Payment Methods > M2E Pro Payment.</i>'
            );
        }

        if (!$store->getConfig('carriers/m2eproshipping/active')) {
            throw new \Ess\M2ePro\Model\Exception(
                'Shipping method "M2E Pro Shipping" is disabled under
                <i>Stores > Settings > Configuration > Sales > Shipping Methods > M2E Pro Shipping.</i>'
            );
        }
    }

    //########################################

    /**
     * Associate each order item with product in magento
     * @throws Exception|null
     */
    public function associateItemsWithProducts()
    {
        foreach ($this->getItemsCollection()->getItems() as $item) {
            /** @var \Ess\M2ePro\Model\Order\Item $item */
            $item->associateWithProduct();
        }
    }

    //########################################

    public function isReservable()
    {
        if ($this->getMagentoOrderId() !== null) {
            return false;
        }

        if ($this->getReserve()->isPlaced()) {
            return false;
        }

        if (!$this->getChildObject()->isReservable()) {
            return false;
        }

        foreach ($this->getItemsCollection()->getItems() as $item) {
            /** @var \Ess\M2ePro\Model\Order\Item $item */

            if (!$item->isReservable()) {
                return false;
            }
        }

        return true;
    }

    //########################################

    public function canCreateMagentoOrder()
    {
        if ($this->getMagentoOrderId() !== null) {
            return false;
        }

        if (!$this->getChildObject()->canCreateMagentoOrder()) {
            return false;
        }

        foreach ($this->getItemsCollection()->getItems() as $item) {
            /** @var \Ess\M2ePro\Model\Order\Item $item */

            if (!$item->canCreateMagentoOrder()) {
                return false;
            }
        }

        return true;
    }

    //########################################

    private function beforeCreateMagentoOrder($canCreateExistOrder)
    {
        if ($this->getMagentoOrderId() !== null && !$canCreateExistOrder) {
            throw new \Ess\M2ePro\Model\Exception('Magento Order is already created.');
        }

        if (method_exists($this->getChildObject(), 'beforeCreateMagentoOrder')) {
            $this->getChildObject()->beforeCreateMagentoOrder();
        }

        $reserve = $this->getReserve();

        if ($reserve->isPlaced()) {
            $reserve->setFlag('order_reservation', true);
            $reserve->release();
        }
    }

    public function createMagentoOrder($canCreateExistOrder = false)
    {
        try {
            // Check if we are wrapped by an another MySql transaction
            // ---------------------------------------
            $connection = $this->resourceConnection->getConnection();
            if ($transactionLevel = $connection->getTransactionLevel()) {
                $this->getHelper('Module\Logger')->process(
                    [
                        'transaction_level' => $transactionLevel,
                    ],
                    'MySql Transaction Level Problem'
                );

                while ($connection->getTransactionLevel()) {
                    $connection->rollBack();
                }
            }
            // ---------------------------------------

            /**
             *  Since version 2.1.8 Magento added check if product is saleable before creating quote.
             *  When order is creating from back-end, this check is skipped. See example at
             *  Magento\Sales\Controller\Adminhtml\Order\Create.php
             */
            $this->productHelper->setSkipSaleableCheck(true);

            // Store must be initialized before products
            // ---------------------------------------
            $this->associateWithStore();
            $this->associateItemsWithProducts();
            // ---------------------------------------

            $this->beforeCreateMagentoOrder($canCreateExistOrder);

            // Create magento order
            // ---------------------------------------
            $proxy = $this->getProxy()->setStore($this->getStore());

            /** @var \Ess\M2ePro\Model\Magento\Quote\Builder $magentoQuoteBuilder */
            $magentoQuoteBuilder = $this->modelFactory->getObject('Magento_Quote_Builder', ['proxyOrder' => $proxy]);
            $magentoQuote = $magentoQuoteBuilder->build();

            $this->getHelper('Data\GlobalData')->unsetValue(self::ADDITIONAL_DATA_KEY_IN_ORDER);
            $this->getHelper('Data\GlobalData')->setValue(self::ADDITIONAL_DATA_KEY_IN_ORDER, $this);

            try {
                $this->magentoOrder = $this->quoteManager->submit($magentoQuote);
            } catch (FailDuringEventProcessing $e) {
                $this->addWarningLog(
                    'Magento Order was created.
                     However one or more post-processing actions on Magento Order failed.
                     This may lead to some issues in the future.
                     Please check the configuration of the ancillary services of your Magento.
                     For more details, read the original Magento warning: %msg%.',
                    [
                        'msg' => $e->getMessage(),
                    ]
                );
                $this->magentoOrder = $e->getOrder();
            }

            $magentoOrderId = $this->getMagentoOrderId();

            if (empty($magentoOrderId)) {
                $this->addData([
                    'magento_order_id' => $this->magentoOrder->getId(),
                    'magento_order_creation_failure' => self::MAGENTO_ORDER_CREATION_FAILED_NO,
                    'magento_order_creation_latest_attempt_date' => $this->getHelper('Data')->getCurrentGmtDate(),
                ]);

                $this->setMagentoOrder($this->magentoOrder);
                $this->save();
            }

            $this->afterCreateMagentoOrder();
            unset($magentoQuoteBuilder);
        } catch (\Exception $e) {
            unset($magentoQuoteBuilder);
            $this->getHelper('Data\GlobalData')->unsetValue(self::ADDITIONAL_DATA_KEY_IN_ORDER);

            /**
             * \Magento\CatalogInventory\Model\StockManagement::registerProductsSale()
             * could open an transaction and may does not
             * close it in case of Exception. So all the next changes may be lost.
             */
            $connection = $this->resourceConnection->getConnection();
            if ($transactionLevel = $connection->getTransactionLevel()) {
                $this->getHelper('Module\Logger')->process(
                    [
                        'transaction_level' => $transactionLevel,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ],
                    'MySql Transaction Level Problem'
                );

                while ($connection->getTransactionLevel()) {
                    $connection->rollBack();
                }
            }

            $this->_eventManager->dispatch('ess_order_place_failure', ['order' => $this]);

            $this->addData([
                'magento_order_creation_failure' => self::MAGENTO_ORDER_CREATION_FAILED_YES,
                'magento_order_creation_fails_count' => $this->getMagentoOrderCreationFailsCount() + 1,
                'magento_order_creation_latest_attempt_date' => $this->getHelper('Data')->getCurrentGmtDate(),
            ]);
            $this->save();

            $message = 'Magento Order was not created. Reason: %msg%';
            if ($e instanceof ProductCreationDisabled) {
                $this->addInfoLog($message, ['msg' => $e->getMessage()], [], true);
            } else {
                $this->addErrorLog($message, ['msg' => $e->getMessage()]);
            }

            if ($this->isReservable()) {
                $this->getReserve()->place();
            }

            throw $e;
        }
    }

    public function afterCreateMagentoOrder()
    {
        // add history comments
        // ---------------------------------------
        /** @var \Ess\M2ePro\Model\Magento\Order\Updater $magentoOrderUpdater */
        $magentoOrderUpdater = $this->modelFactory->getObject('Magento_Order_Updater');
        $magentoOrderUpdater->setMagentoOrder($this->getMagentoOrder());
        $magentoOrderUpdater->updateComments($this->getProxy()->getComments());
        $magentoOrderUpdater->finishUpdate();
        // ---------------------------------------

        $this->eventDispatcher->dispatchEventsMagentoOrderCreated($this);

        $this->addSuccessLog('Magento Order #%order_id% was created.', [
            '!order_id' => $this->getMagentoOrder()->getRealOrderId(),
        ]);

        if (method_exists($this->getChildObject(), 'afterCreateMagentoOrder')) {
            $this->getChildObject()->afterCreateMagentoOrder();
        }
    }

    public function updateMagentoOrderStatus()
    {
        $magentoOrder = $this->getMagentoOrder();
        if ($magentoOrder === null) {
            return;
        }

        $state = $magentoOrder->getState();
        if (
            $state === \Magento\Sales\Model\Order::STATE_CLOSED
            || $state === \Magento\Sales\Model\Order::STATE_COMPLETE
            || $state === \Magento\Sales\Model\Order::STATE_CANCELED
            || $this->getChildObject()->isCanceled()
        ) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Magento\Order\Updater $magentoOrderUpdater */
        $magentoOrderUpdater = $this->modelFactory->getObject('Magento_Order_Updater');
        $magentoOrderUpdater->setMagentoOrder($magentoOrder);
        $magentoOrderUpdater->updateStatus($this->getChildObject()->getStatusForMagentoOrder());
        $magentoOrderUpdater->finishUpdate();
    }

    //########################################

    public function canCancelMagentoOrder()
    {
        $magentoOrder = $this->getMagentoOrder();

        if ($magentoOrder === null || $magentoOrder->isCanceled() || $magentoOrder->hasCreditmemos()) {
            return false;
        }

        if ($magentoOrder->canUnhold()) {
            $errorMessage = 'Cancel is not allowed for Orders which were put on Hold.';
            $messageAddedSuccessfully = $this->addErrorLog(
                'Magento Order #%order_id% was not canceled. Reason: %msg%',
                [
                    '!order_id' => $this->getMagentoOrder()->getRealOrderId(),
                    'msg' => $errorMessage,
                ],
                [],
                true
            );

            return $messageAddedSuccessfully ? $errorMessage : false;
        }

        if (
            $magentoOrder->getState() === \Magento\Sales\Model\Order::STATE_COMPLETE ||
            $magentoOrder->getState() === \Magento\Sales\Model\Order::STATE_CLOSED
        ) {
            return false;
        }

        $allInvoiced = true;
        foreach ($magentoOrder->getAllItems() as $item) {
            if ($item->getQtyToInvoice()) {
                $allInvoiced = false;
                break;
            }
        }
        if ($allInvoiced && !$this->getChildObject()->canCreateCreditMemo()) {
            $errorMessage = 'Cancel is not allowed for Orders with Invoiced Items.';
            $messageAddedSuccessfully = $this->addErrorLog(
                'Magento Order #%order_id% was not canceled. Reason: %msg%',
                [
                    '!order_id' => $this->getMagentoOrder()->getRealOrderId(),
                    'msg' => $errorMessage,
                ],
                [],
                true
            );

            return $messageAddedSuccessfully ? $errorMessage : false;
        }

        return true;
    }

    public function cancelMagentoOrder()
    {
        if ($this->canCancelMagentoOrder() !== true) {
            return;
        }

        try {
            /** @var \Ess\M2ePro\Model\Magento\Order\Updater $magentoOrderUpdater */
            $magentoOrderUpdater = $this->modelFactory->getObject('Magento_Order_Updater');
            $magentoOrderUpdater->setMagentoOrder($this->getMagentoOrder());
            $magentoOrderUpdater->cancel();

            $this->addSuccessLog('Magento Order #%order_id% was canceled.', [
                '!order_id' => $this->getMagentoOrder()->getRealOrderId(),
            ]);
        } catch (\Exception $exception) {
            $this->helperModuleException->process($exception);
        }
    }

    //########################################

    public function createInvoice()
    {
        $invoice = null;

        try {
            $invoice = $this->getChildObject()->createInvoice();
        } catch (\Exception $e) {
            $this->helperModuleException->process($e);
            $this->addErrorLog('Invoice was not created. Reason: %msg%', ['msg' => $e->getMessage()]);
        }

        if ($invoice !== null) {
            $this->addSuccessLog('Invoice #%invoice_id% was created.', [
                '!invoice_id' => $invoice->getIncrementId(),
            ]);

            $this->eventDispatcher->dispatchEventInvoiceCreated($this);
        }

        return $invoice;
    }

    //########################################

    public function createShipments()
    {
        if (!$this->getChildObject()->canCreateShipments()) {
            if ($this->getMagentoOrder() && $this->getMagentoOrder()->getIsVirtual()) {
                $this->addInfoLog(
                    'Magento Order was created without the Shipping Address since your Virtual Product ' .
                    'has no weight and cannot be shipped.'
                );
            }

            return null;
        }

        $shipments = [];

        try {
            $shipments = $this->getChildObject()->createShipments();
        } catch (\Exception $e) {
            $this->helperModuleException->process($e);
            $this->addErrorLog('Shipment was not created. Reason: %msg%', ['msg' => $e->getMessage()]);
        }

        if ($shipments !== null) {
            foreach ($shipments as $shipment) {
                $this->addSuccessLog('Shipment #%shipment_id% was created.', [
                    '!shipment_id' => $shipment->getIncrementId(),
                ]);

                $this->addCreatedMagentoShipment($shipment);
            }

            if (empty($shipments)) {
                $this->addWarningLog('Shipment was not created.');
            }
        }

        return $shipments;
    }

    /**
     * @return bool
     * @throws Exception\Logic
     */
    public function isOrderStatusUpdatingToShipped()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Order\Change\Collection $changes */
        $changes = $this->activeRecordFactory->getObject('Order\Change')->getCollection();
        $changes->addFieldToFilter('order_id', $this->getId());
        $changes->addFieldToFilter('action', \Ess\M2ePro\Model\Order\Change::ACTION_UPDATE_SHIPPING);

        return $this->getChildObject()->isSetProcessingLock('update_shipping_status') || $changes->getSize() > 0;
    }

    public function markAsVatChanged(): void
    {
        $additionalData = $this->getAdditionalData();
        $now = \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s');
        $additionalData[self::ADDITIONAL_DATA_KEY_VAT_REVERSE_CHARGE] = $now;
        $this->setSettings('additional_data', $additionalData);
        $this->save();
    }

    public function createCreditMemo(): ?\Magento\Sales\Model\Order\Creditmemo
    {
        $creditMemo = null;

        try {
            $creditMemo = $this->getChildObject()->createCreditMemo();
        } catch (\Throwable $e) {
            $this->helperModuleException->process($e);
            $this->addErrorLog('CreditMemo was not created. Reason: %msg%', ['msg' => $e->getMessage()]);
        }

        if ($creditMemo !== null) {
            $this->addSuccessLog('Credit Memo #%creditMemo_id% was created.', [
                '!creditMemo_id' => $creditMemo->getIncrementId(),
            ]);
        }

        return $creditMemo;
    }
}
