<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model;

/**
 * @method \Ess\M2ePro\Model\Amazon\Order|\Ess\M2ePro\Model\Amazon\Order getChildObject()
 */
class Order extends ActiveRecord\Component\Parent\AbstractModel
{
    // M2ePro\TRANSLATIONS
    // Magento Order was not created. Reason: %msg%
    // Magento Order #%order_id% was created.
    // Payment Transaction was not created. Reason: %msg%
    // Invoice was not created. Reason: %msg%
    // Invoice #%invoice_id% was created.
    // Shipment was not created. Reason: %msg%
    // Shipment #%shipment_id% was created.
    // Tracking details were not imported. Reason: %msg%
    // Tracking details were imported.
    // Magento Order #%order_id% was canceled.
    // Magento Order #%order_id% was not canceled. Reason: %msg%
    // Store does not exist.
    // Payment method "M2E Pro Payment" is disabled in Magento Configuration.
    // Shipping method "M2E Pro Shipping" is disabled in Magento Configuration.

    private $storeManager;

    private $orderFactory;

    private $resourceConnection;

    private $account = NULL;

    private $marketplace = NULL;

    private $magentoOrder = NULL;

    private $shippingAddress = NULL;

    /** @var \Ess\M2ePro\Model\ResourceModel\Order\Item\Collection */
    private $itemsCollection = NULL;

    private $proxy = NULL;

    /** @var \Ess\M2ePro\Model\Order\Reserve */
    private $reserve = NULL;

    private $statusUpdateRequired = false;

    //########################################

    /** @var \Ess\M2ePro\Model\Order\Log */
    private $logModel = NULL;

    // ########################################

    public function __construct(
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [])
    {
        $this->storeManager = $storeManager;
        $this->orderFactory = $orderFactory;
        $this->resourceConnection = $resourceConnection;

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

    // ########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Order');
    }

    //########################################

    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        foreach ($this->getItemsCollection()->getItems() as $item) {
            /** @var $item \Ess\M2ePro\Model\Order\Item */
            $item->delete();
        }
        $this->deleteChildInstance();

        $this->activeRecordFactory->getObject('Order\Change')->getCollection()
            ->addFieldToFilter('order_id', $this->getId())
            ->walk('delete');

        $this->account = NULL;
        $this->magentoOrder = NULL;
        $this->itemsCollection = NULL;
        $this->proxy = NULL;

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

    public function getStoreId()
    {
        return $this->getData('store_id');
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
     * @return $this
     */
    public function setAccount(\Ess\M2ePro\Model\Account $account)
    {
        $this->account = $account;
        return $this;
    }

    /**
     * @throws \LogicException
     * @return \Ess\M2ePro\Model\Account
     */
    public function getAccount()
    {
        if (is_null($this->account)) {
            $this->account = $this->parentFactory->getCachedObjectLoaded(
                $this->getComponentMode(), 'Account', $this->getAccountId()
            );
        }

        return $this->account;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Marketplace $marketplace
     * @return $this
     */
    public function setMarketplace(\Ess\M2ePro\Model\Marketplace $marketplace)
    {
        $this->marketplace = $marketplace;
        return $this;
    }

    /**
     * @throws \LogicException
     * @return \Ess\M2ePro\Model\Marketplace
     */
    public function getMarketplace()
    {
        if (is_null($this->marketplace)) {
            $this->marketplace = $this->parentFactory->getCachedObjectLoaded(
                $this->getComponentMode(), 'Marketplace', $this->getMarketplaceId()
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
        if (is_null($this->reserve)) {
            $this->reserve = $this->modelFactory->getObject('Order\Reserve', [
                'order' => $this
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

    // ########################################

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\Order\Item\Collection
     */
    public function getItemsCollection()
    {
        if (is_null($this->itemsCollection)) {
            $this->itemsCollection = $this->parentFactory->getObject($this->getComponentMode(), 'Order\Item')
                                                         ->getCollection()
                                                         ->addFieldToFilter('order_id', $this->getId());

            foreach ($this->itemsCollection as $item) {
                /** @var $item \Ess\M2ePro\Model\Order\Item */
                $item->setOrder($this);
            }
        }

        return $this->itemsCollection;
    }

    // ---------------------------------------

    /**
     * Check whether the order has only single item ordered
     *
     * @return bool
     */
    public function isSingle()
    {
        return $this->getItemsCollection()->count() == 1;
    }

    /**
     * Check whether the order has multiple items ordered
     *
     * @return bool
     */
    public function isCombined()
    {
        return $this->getItemsCollection()->count() > 1;
    }

    // ---------------------------------------

    /**
     * Get instances of the channel items (Ebay\Item, Amazon\Item etc)
     *
     * @return array
     */
    public function getChannelItems()
    {
        $channelItems = array();

        foreach ($this->getItemsCollection()->getItems() as $item) {
            $channelItem = $item->getChildObject()->getChannelItem();

            if (is_null($channelItem)) {
                continue;
            }

            $channelItems[] = $channelItem;
        }

        return $channelItems;
    }

    // ---------------------------------------

    /**
     * Check whether the order has items, listed by M2E Pro (also true for mapped 3rd party listings)
     *
     * @return bool
     */
    public function hasListingItems()
    {
        $channelItems = $this->getChannelItems();

        return count($channelItems) > 0;
    }

    /**
     * Check whether the order has items, listed by 3rd party software
     *
     * @return bool
     */
    public function hasOtherListingItems()
    {
        $channelItems = $this->getChannelItems();

        return count($channelItems) != $this->getItemsCollection()->count();
    }

    //########################################

    public function addLog($description, $type, array $params = array(), array $links = array())
    {
        /** @var $log \Ess\M2ePro\Model\Order\Log */
        $log = $this->getLog();

        if (!empty($params)) {
            $description = $this->getHelper('Module\Log')->encodeDescription($description, $params, $links);
        }

        $log->addMessage($this->getId(), $description, $type);
    }

    public function addSuccessLog($description, array $params = array(), array $links = array())
    {
        $this->addLog($description, \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS, $params, $links);
    }

    public function addNoticeLog($description, array $params = array(), array $links = array())
    {
        $this->addLog($description, \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE, $params, $links);
    }

    public function addWarningLog($description, array $params = array(), array $links = array())
    {
        $this->addLog($description, \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING, $params, $links);
    }

    public function addErrorLog($description, array $params = array(), array $links = array())
    {
        $this->addLog($description, \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR, $params, $links);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Order\ShippingAddress
     */
    public function getShippingAddress()
    {
        if (is_null($this->shippingAddress)) {
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
        if (is_null($this->getMagentoOrderId())) {
            return NULL;
        }

        if (is_null($this->magentoOrder)) {
            $this->magentoOrder = $this->orderFactory->create()->load($this->getMagentoOrderId());
        }

        return !is_null($this->magentoOrder->getId()) ? $this->magentoOrder : NULL;
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
     * @return \Ess\M2ePro\Model\Order\Proxy
     */
    public function getProxy()
    {
        if (is_null($this->proxy)) {
            $this->proxy = $this->getChildObject()->getProxy();
        }

        return $this->proxy;
    }

    //########################################

    /**
     * Find the store, where order should be placed
     *
     * @param bool $strict
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function associateWithStore($strict = true)
    {
        $storeId = $this->getStoreId() ? $this->getStoreId() : $this->getChildObject()->getAssociatedStoreId();
        $store = $this->storeManager->getStore($storeId);

        if (is_null($store->getId())) {
            throw new \Ess\M2ePro\Model\Exception('Store does not exist.');
        }

        if ($this->getStoreId() != $store->getId()) {
            $this->setData('store_id', $store->getId())->save();
        }

        if (!$store->getConfig('payment/m2epropayment/active') && $strict) {
            throw new \Ess\M2ePro\Model\Exception('Payment method "M2E Pro Payment" is disabled in
                Magento Configuration.');
        }

        if (!$store->getConfig('carriers/m2eproshipping/active') && $strict) {
            throw new \Ess\M2ePro\Model\Exception('Shipping method "M2E Pro Shipping" is disabled in
                Magento Configuration.');
        }
    }

    //########################################

    /**
     * Associate each order item with product in magento
     *
     * @param bool $strict
     * @throws \Exception|null
     */
    public function associateItemsWithProducts($strict = true)
    {
        $exception = null;

        foreach ($this->getItemsCollection()->getItems() as $item) {
            try {
                /** @var $item \Ess\M2ePro\Model\Order\Item */
                $item->associateWithProduct();
            } catch (\Exception $e) {
                if (is_null($exception)) {
                    $exception = $e;
                }
            }
        }

        if ($strict && $exception) {
            throw $exception;
        }
    }

    //########################################

    public function isReservable()
    {
        if (!is_null($this->getMagentoOrderId())) {
            return false;
        }

        if ($this->getReserve()->isPlaced()) {
            return false;
        }

        if (method_exists($this->getChildObject(), 'isReservable')) {
            return $this->getChildObject()->isReservable();
        }

        return true;
    }

    //########################################

    public function canCreateMagentoOrder()
    {
        if (!is_null($this->getMagentoOrderId())) {
            return false;
        }

        if (!$this->getChildObject()->canCreateMagentoOrder()) {
            return false;
        }

        return true;
    }

    //########################################

    private function beforeCreateMagentoOrder()
    {
        if (method_exists($this->getChildObject(), 'beforeCreateMagentoOrder')) {
            $this->getChildObject()->beforeCreateMagentoOrder();
        }

        $reserve = $this->getReserve();

        if ($reserve->isPlaced()) {
            $reserve->setFlag('order_reservation', true);
            $reserve->release();
        }
    }

    public function createMagentoOrder()
    {
        try {

            // Check if we are wrapped by an another MySql transaction
            // ---------------------------------------
            $connection = $this->resourceConnection->getConnection();
            if ($transactionLevel = $connection->getTransactionLevel()) {

                $this->getHelper('Module\Logger')->process(
                    array(
                        'transaction_level' => $transactionLevel
                    ),
                    'MySql Transaction Level Problem'
                );

                while ($connection->getTransactionLevel()) {
                    $connection->rollBack();
                }
            }
            // ---------------------------------------

            // Store must be initialized before products
            // ---------------------------------------
            $this->associateWithStore();
            $this->associateItemsWithProducts();
            // ---------------------------------------

            $this->beforeCreateMagentoOrder();

            // Create magento order
            // ---------------------------------------
            $proxy = $this->getProxy()->setStore($this->getStore());

            /** @var $magentoQuoteBuilder \Ess\M2ePro\Model\Magento\Quote */
            $magentoQuoteBuilder = $this->modelFactory->getObject('Magento\Quote', ['proxyOrder' => $proxy]);
            $magentoQuoteBuilder->buildQuote();

            /** @var $magentoOrderBuilder \Ess\M2ePro\Model\Magento\Order */
            $magentoOrderBuilder = $this->modelFactory->getObject(
                'Magento\Order', ['quote' => $magentoQuoteBuilder->getQuote()]
            );
            $magentoOrderBuilder->buildOrder();

            $this->magentoOrder = $magentoOrderBuilder->getOrder();

            $this->setData('magento_order_id', $this->magentoOrder->getId());
            $this->setMagentoOrder($this->magentoOrder);

            $this->save();

            $this->afterCreateMagentoOrder();

            unset($magentoQuoteBuilder);
            unset($magentoOrderBuilder);
            // ---------------------------------------

        } catch (\Exception $e) {

            /**
             * \Magento\CatalogInventory\Model\StockManagement::registerProductsSale()
             * could open an transaction and may does not
             * close it in case of Exception. So all the next changes may be lost.
             */
            $connection = $this->resourceConnection->getConnection();
            if ($transactionLevel = $connection->getTransactionLevel()) {

                $this->getHelper('Module\Logger')->process(
                    array(
                        'transaction_level' => $transactionLevel,
                        'error'             => $e->getMessage(),
                        'trace'             => $e->getTraceAsString()
                    ),
                    'MySql Transaction Level Problem'
                );

                while ($connection->getTransactionLevel()) {
                    $connection->rollBack();
                }
            }

            $this->_eventManager->dispatch('m2epro_order_place_failure', array('order' => $this));

            $this->addErrorLog('Magento Order was not created. Reason: %msg%', array('msg' => $e->getMessage()));
            $this->helperFactory->getObject('Module\Exception')->process($e, false);

            // reserve qty back only if it was canceled before the order creation process started
            // ---------------------------------------
            if ($this->isReservable() && $this->getReserve()->getFlag('order_reservation')) {
                $this->getReserve()->place();
            }
            // ---------------------------------------

            throw $e;
        }
    }

    public function afterCreateMagentoOrder()
    {
        // add history comments
        // ---------------------------------------
        /** @var $magentoOrderUpdater \Ess\M2ePro\Model\Magento\Order\Updater */
        $magentoOrderUpdater = $this->modelFactory->getObject('Magento\Order\Updater');
        $magentoOrderUpdater->setMagentoOrder($this->getMagentoOrder());
        $magentoOrderUpdater->updateComments($this->getProxy()->getComments());
        $magentoOrderUpdater->finishUpdate();
        // ---------------------------------------

        $this->_eventManager->dispatch('m2epro_order_place_success', array('order' => $this));

        $this->addSuccessLog('Magento Order #%order_id% was created.', array(
            '!order_id' => $this->getMagentoOrder()->getRealOrderId()
        ));

        if (method_exists($this->getChildObject(), 'afterCreateMagentoOrder')) {
            $this->getChildObject()->afterCreateMagentoOrder();
        }
    }

    public function updateMagentoOrderStatus()
    {
        if (is_null($this->getMagentoOrder())) {
            return;
        }

        /** @var $magentoOrderUpdater \Ess\M2ePro\Model\Magento\Order\Updater */
        $magentoOrderUpdater = $this->modelFactory->getObject('Magento\Order\Updater');
        $magentoOrderUpdater->setMagentoOrder($this->getMagentoOrder());
        $magentoOrderUpdater->updateStatus($this->getChildObject()->getStatusForMagentoOrder());
        $magentoOrderUpdater->finishUpdate();
    }

    //########################################

    /**
     * @return bool
     */
    public function canCancelMagentoOrder()
    {
        $magentoOrder = $this->getMagentoOrder();

        if (is_null($magentoOrder) || $magentoOrder->isCanceled()) {
            return false;
        }

        return true;
    }

    public function cancelMagentoOrder()
    {
        if (!$this->canCancelMagentoOrder()) {
            return;
        }

        try {
            /** @var $magentoOrderUpdater \Ess\M2ePro\Model\Magento\Order\Updater */
            $magentoOrderUpdater = $this->modelFactory->getObject('Magento\Order\Updater');
            $magentoOrderUpdater->setMagentoOrder($this->getMagentoOrder());
            $magentoOrderUpdater->cancel();

            $this->addSuccessLog('Magento Order #%order_id% was canceled.', array(
                '!order_id' => $this->getMagentoOrder()->getRealOrderId()
            ));
        } catch (\Exception $e) {
            $this->addErrorLog('Magento Order #%order_id% was not canceled. Reason: %msg%', array(
                '!order_id' => $this->getMagentoOrder()->getRealOrderId(),
                'msg' => $e->getMessage()
            ));
            throw $e;
        }
    }

    //########################################

    public function createInvoice()
    {
        $invoice = null;

        try {
            $invoice = $this->getChildObject()->createInvoice();
        } catch (\Exception $e) {
            $this->addErrorLog('Invoice was not created. Reason: %msg%', array('msg' => $e->getMessage()));
        }

        if (!is_null($invoice)) {
            $this->addSuccessLog('Invoice #%invoice_id% was created.', array(
                '!invoice_id' => $invoice->getIncrementId()
            ));
        }

        return $invoice;
    }

    //########################################

    public function createShipment()
    {
        $shipment = null;

        try {
            $shipment = $this->getChildObject()->createShipment();
        } catch (\Exception $e) {
            $this->addErrorLog('Shipment was not created. Reason: %msg%', array('msg' => $e->getMessage()));
        }

        if (!is_null($shipment)) {
            $this->addSuccessLog('Shipment #%shipment_id% was created.', array(
                '!shipment_id' => $shipment->getIncrementId()
            ));

            $this->addCreatedMagentoShipment($shipment);
        }

        return $shipment;
    }

    //########################################
}