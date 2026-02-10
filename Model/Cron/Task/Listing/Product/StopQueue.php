<?php

namespace Ess\M2ePro\Model\Cron\Task\Listing\Product;

class StopQueue extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'listing/product/stop_queue';

    /**
     * @var int (in seconds)
     */
    protected $interval = 3600;

    /**
     * @var int (30 days)
     */
    public const MAX_PROCESSED_LIFETIME_HOURS_INTERVAL = 720;

    public const EBAY_REQUEST_MAX_ITEMS_COUNT = 10;
    public const AMAZON_REQUEST_MAX_ITEMS_COUNT = 10000;
    public const WALMART_REQUEST_MAX_ITEMS_COUNT = 10000;

    private \Ess\M2ePro\Model\Walmart\ThrottlingManager $walmartThrottlingManager;
    private \Ess\M2ePro\Model\ResourceModel\StopQueue $stopQueueResource;
    private \Ess\M2ePro\Model\ResourceModel\StopQueue\CollectionFactory $collectionFactory;
    private \Ess\M2ePro\Helper\Server\Maintenance $maintenanceHelper;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\ThrottlingManager $walmartThrottlingManager,
        \Ess\M2ePro\Model\ResourceModel\StopQueue $stopQueueResource,
        \Ess\M2ePro\Model\ResourceModel\StopQueue\CollectionFactory $collectionFactory,
        \Ess\M2ePro\Helper\Server\Maintenance $maintenanceHelper,
        \Ess\M2ePro\Model\Cron\Manager $cronManager,
        \Ess\M2ePro\Helper\Data $helperData,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        parent::__construct(
            $cronManager,
            $helperData,
            $eventManager,
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $taskRepo,
            $resource
        );
        $this->walmartThrottlingManager = $walmartThrottlingManager;
        $this->stopQueueResource = $stopQueueResource;
        $this->collectionFactory = $collectionFactory;
        $this->maintenanceHelper = $maintenanceHelper;
    }

    public function isPossibleToRun(): bool
    {
        if ($this->maintenanceHelper->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    protected function performActions(): void
    {
        $this->removeOldRecords();

        $this->processEbay();
        $this->processAmazon();
        $this->processWalmart();
    }

    private function removeOldRecords(): void
    {
        $minDateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $minDateTime->modify('- ' . self::MAX_PROCESSED_LIFETIME_HOURS_INTERVAL . ' hours');

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('is_processed', 1);
        $collection->addFieldToFilter('update_date', ['lt' => $minDateTime->format('Y-m-d H:i:s')]);

        /** @var \Ess\M2ePro\Model\StopQueue[] $items */
        $items = $collection->getItems();

        foreach ($items as $item) {
            $item->delete();
        }
    }

    //----------------------------------------

    private function processEbay(): void
    {
        /** @var \Ess\M2ePro\Model\StopQueue[] $items */
        $items = $this->getNotProcessedItems(\Ess\M2ePro\Helper\Component\Ebay::NICK);
        if (empty($items)) {
            return;
        }

        $processedItemsIds = [];
        $accountsMarketplacesRequestData = [];

        foreach ($items as $item) {
            $processedItemsIds[] = $item->getId();

            $itemAdditionalData = $item->getAdditionalData();
            if (empty($itemAdditionalData['request_data'])) {
                continue;
            }

            $itemRequestData = $itemAdditionalData['request_data'];
            if (empty($itemRequestData['item_id'])) {
                continue;
            }

            $accountMarketplaceActionType = $itemRequestData['account']
                . '_' .
                $itemRequestData['marketplace']
                . '_' .
                $itemRequestData['action_type'];

            $accountsMarketplacesRequestData[$accountMarketplaceActionType][] = [
                'item_id' => $itemRequestData['item_id'],
            ];
        }

        foreach ($accountsMarketplacesRequestData as $accountMarketplaceActionType => $accountMarketplaceRequestData) {
            [$account, $marketplace, $actionType] = explode('_', $accountMarketplaceActionType);

            if ((int)$actionType === \Ess\M2ePro\Model\Listing\Product::ACTION_STOP) {
                $this->stopItemEbay($account, $marketplace, $accountMarketplaceRequestData);
            } else {
                $this->hideItemEbay($account, $marketplace, $accountMarketplaceRequestData);
            }
        }

        $this->markItemsAsProcessed($processedItemsIds);
    }

    //----------------------------------------

    private function stopItemEbay($account, $marketplace, $accountMarketplaceRequestData): void
    {
        $requestDataPacks = array_chunk($accountMarketplaceRequestData, self::EBAY_REQUEST_MAX_ITEMS_COUNT);

        foreach ($requestDataPacks as $requestDataPack) {
            $requestData = [
                'account' => $account,
                'marketplace' => $marketplace,
                'items' => $requestDataPack,
            ];

            /** @var \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher */
            $dispatcher = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
            $connector = $dispatcher->getVirtualConnector('item', 'update', 'ends', $requestData);
            $dispatcher->process($connector);
        }
    }

    private function hideItemEbay($account, $marketplace, $accountMarketplaceRequestData): void
    {
        foreach ($accountMarketplaceRequestData as $requestData) {
            $requestData = [
                'account' => $account,
                'marketplace' => $marketplace,
                'item_id' => $requestData['item_id'],
                'qty' => 0,
            ];

            /** @var \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher */
            $dispatcher = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
            $connector = $dispatcher->getVirtualConnector('item', 'update', 'reviseManager', $requestData);
            $dispatcher->process($connector);
        }
    }

    // ----------------------------------------

    private function processAmazon(): void
    {
        /** @var \Ess\M2ePro\Model\StopQueue[] $items */
        $items = $this->getNotProcessedItems(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        if (empty($items)) {
            return;
        }

        $processedItemsIds = [];
        $accountsRequestData = [];

        foreach ($items as $item) {
            $processedItemsIds[] = $item->getId();

            $itemAdditionalData = $item->getAdditionalData();
            if (empty($itemAdditionalData['request_data'])) {
                continue;
            }

            $itemRequestData = $itemAdditionalData['request_data'];

            $account = $itemRequestData['account'];

            $accountsRequestData[$account][] = [
                'id' => $item->getId(),
                'sku' => $itemRequestData['sku'],
                'qty' => 0,
            ];
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $accountsCollection */
        $accountsCollection = $this->parentFactory->getObject(\Ess\M2ePro\Helper\Component\Amazon::NICK, 'Account')
                                                  ->getCollection();
        $accountsCollection->addFieldToFilter('server_hash', array_keys($accountsRequestData));

        foreach ($accountsRequestData as $account => $accountRequestData) {
            $requestDataPacks = array_chunk($accountRequestData, self::AMAZON_REQUEST_MAX_ITEMS_COUNT);

            foreach ($requestDataPacks as $requestDataPack) {
                $requestData = [
                    'account' => $account,
                    'items' => $requestDataPack,
                ];

                /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcher */
                $dispatcher = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
                $connector = $dispatcher->getVirtualConnector('product', 'update', 'entities', $requestData);
                $dispatcher->process($connector);
            }
        }

        $this->markItemsAsProcessed($processedItemsIds);
    }

    private function processWalmart(): void
    {
        /** @var \Ess\M2ePro\Model\StopQueue[] $items */
        $items = $this->getNotProcessedItems(\Ess\M2ePro\Helper\Component\Walmart::NICK);
        if (empty($items)) {
            return;
        }

        $processedItemsIds = [];
        $accountsRequestData = [];

        foreach ($items as $item) {
            $processedItemsIds[] = $item->getId();

            $itemAdditionalData = $item->getAdditionalData();
            if (empty($itemAdditionalData['request_data'])) {
                continue;
            }

            $itemRequestData = $itemAdditionalData['request_data'];

            $account = $itemRequestData['account'];

            $accountsRequestData[$account][] = [
                'id' => $item->getId(),
                'sku' => $itemRequestData['sku'],
                'wpid' => $itemRequestData['wpid'],
                'qty' => 0,
                'lag_time' => 0,
            ];
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $accountsCollection */
        $accountsCollection = $this->parentFactory->getObject(\Ess\M2ePro\Helper\Component\Walmart::NICK, 'Account')
                                                  ->getCollection();
        $accountsCollection->addFieldToFilter('server_hash', array_keys($accountsRequestData));

        foreach ($accountsRequestData as $account => $accountRequestData) {
            $requestDataPacks = array_chunk($accountRequestData, self::WALMART_REQUEST_MAX_ITEMS_COUNT);

            $accountObject = $accountsCollection->getItemByColumnValue('server_hash', $account);

            if (
                $accountObject !== null &&
                ($this->walmartThrottlingManager->getAvailableRequestsCount(
                    $accountObject->getId(),
                    \Ess\M2ePro\Model\Walmart\ThrottlingManager::REQUEST_TYPE_UPDATE_QTY
                ) <= 0 ||
                    $this->walmartThrottlingManager->getAvailableRequestsCount(
                        $accountObject->getId(),
                        \Ess\M2ePro\Model\Walmart\ThrottlingManager::REQUEST_TYPE_UPDATE_LAG_TIME
                    ) <= 0)
            ) {
                continue;
            }

            foreach ($requestDataPacks as $requestDataPack) {
                $requestData = [
                    'account' => $account,
                    'items' => $requestDataPack,
                ];

                /** @var \Ess\M2ePro\Model\Walmart\Connector\Dispatcher $dispatcher */
                $dispatcher = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');
                $connector = $dispatcher->getVirtualConnector('product', 'update', 'entities', $requestData);
                $dispatcher->process($connector);

                if ($accountObject !== null) {
                    $this->walmartThrottlingManager->registerRequests(
                        $accountObject->getId(),
                        \Ess\M2ePro\Model\Walmart\ThrottlingManager::REQUEST_TYPE_UPDATE_QTY,
                        1
                    );
                    $this->walmartThrottlingManager->registerRequests(
                        $accountObject->getId(),
                        \Ess\M2ePro\Model\Walmart\ThrottlingManager::REQUEST_TYPE_UPDATE_LAG_TIME,
                        1
                    );
                }
            }
        }

        $this->markItemsAsProcessed($processedItemsIds);
    }

    // ----------------------------------------

    private function getNotProcessedItems(string $component)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('is_processed', 0);
        $collection->addFieldToFilter('component_mode', $component);

        return $collection->getItems();
    }

    private function markItemsAsProcessed(array $itemsIds): void
    {
        if (empty($itemsIds)) {
            return;
        }

        $this->resource->getConnection()->update(
            $this->stopQueueResource->getMainTable(),
            [
                'is_processed' => 1,
                'update_date' => \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s'),
            ],
            ['id IN (?)' => $itemsIds]
        );
    }
}
