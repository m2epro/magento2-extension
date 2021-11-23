<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Listing\Product\StopQueue
 */
class StopQueue extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'listing/product/stop_queue';

    /**
     * @var int (in seconds)
     */
    protected $interval = 3600;

    /**
     * @var int (30 days)
     */
    const MAX_PROCESSED_LIFETIME_HOURS_INTERVAL = 720;

    const EBAY_REQUEST_MAX_ITEMS_COUNT   = 10;
    const AMAZON_REQUEST_MAX_ITEMS_COUNT = 10000;
    const WALMART_REQUEST_MAX_ITEMS_COUNT = 10000;

    protected $amazonThrottlingManager;
    protected $walmartThrottlingManager;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Walmart\ThrottlingManager $walmartThrottlingManager,
        \Ess\M2ePro\Model\Amazon\ThrottlingManager $amazonThrottlingManager,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->amazonThrottlingManager = $amazonThrottlingManager;
        $this->walmartThrottlingManager = $walmartThrottlingManager;
        parent::__construct(
            $eventManager,
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $taskRepo,
            $resource
        );
    }

    //########################################

    public function isPossibleToRun()
    {
        if ($this->getHelper('Server\Maintenance')->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    //########################################

    protected function performActions()
    {
        $this->removeOldRecords();

        $this->processEbay();
        $this->processAmazon();
        $this->processWalmart();
    }

    //########################################

    public function removeOldRecords()
    {
        $minDateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $minDateTime->modify('- '.self::MAX_PROCESSED_LIFETIME_HOURS_INTERVAL.' hours');

        $collection = $this->activeRecordFactory->getObject('StopQueue')->getCollection();
        $collection->addFieldToFilter('is_processed', 1);
        $collection->addFieldToFilter('update_date', ['lt' => $minDateTime->format('Y-m-d H:i:s')]);

        /** @var \Ess\M2ePro\Model\StopQueue[] $items */
        $items = $collection->getItems();

        foreach ($items as $item) {
            $item->delete();
        }
    }

    //----------------------------------------

    protected function processEbay()
    {
        /** @var \Ess\M2ePro\Model\StopQueue[] $items */
        $items = $this->getNotProcessedItems(\Ess\M2ePro\Helper\Component\Ebay::NICK);
        if (empty($items)) {
            return;
        }

        $processedItemsIds               = [];
        $accountsMarketplacesRequestData = [];

        foreach ($items as $item) {
            $processedItemsIds[] = $item->getId();

            $itemAdditionalData = $item->getAdditionalData();
            if (empty($itemAdditionalData['request_data'])) {
                continue;
            }

            $itemRequestData = $itemAdditionalData['request_data'];

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
            list($account, $marketplace, $actionType) = explode('_', $accountMarketplaceActionType);

            if ((int)$actionType === \Ess\M2ePro\Model\Listing\Product::ACTION_STOP) {
                $this->stopItemEbay($account, $marketplace, $accountMarketplaceRequestData);
            } else {
                $this->hideItemEbay($account, $marketplace, $accountMarketplaceRequestData);
            }
        }

        $this->markItemsAsProcessed($processedItemsIds);
    }

    //----------------------------------------

    protected function stopItemEbay($account, $marketplace, $accountMarketplaceRequestData)
    {
        $requestDataPacks = array_chunk($accountMarketplaceRequestData, self::EBAY_REQUEST_MAX_ITEMS_COUNT);

        foreach ($requestDataPacks as $requestDataPack) {
            $requestData = [
                'account'     => $account,
                'marketplace' => $marketplace,
                'items'       => $requestDataPack,
            ];

            $dispatcher = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
            $connector  = $dispatcher->getVirtualConnector('item', 'update', 'ends', $requestData);
            $dispatcher->process($connector);
        }
    }

    protected function hideItemEbay($account, $marketplace, $accountMarketplaceRequestData)
    {
        foreach ($accountMarketplaceRequestData as $requestData) {
            $requestData = [
                'account'     => $account,
                'marketplace' => $marketplace,
                'item_id'     => $requestData['item_id'],
                'qty'         => 0
            ];

            $dispatcher = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
            $connector = $dispatcher->getVirtualConnector('item', 'update', 'reviseManager', $requestData);
            $dispatcher->process($connector);
        }
    }

    //########################################

    protected function processAmazon()
    {
        /** @var \Ess\M2ePro\Model\StopQueue[] $items */
        $items = $this->getNotProcessedItems(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        if (empty($items)) {
            return;
        }

        $processedItemsIds   = [];
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
                'id'  => $item->getId(),
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

            $accountObject = $accountsCollection->getItemByColumnValue('server_hash', $account);

            if ($accountObject !== null &&
                $this->amazonThrottlingManager->getAvailableRequestsCount(
                    $accountObject->getChildObject()->getMerchantId(),
                    \Ess\M2ePro\Model\Amazon\ThrottlingManager::REQUEST_TYPE_FEED
                ) <= 0) {
                continue;
            }

            foreach ($requestDataPacks as $requestDataPack) {
                $requestData = [
                    'account' => $account,
                    'items'   => $requestDataPack,
                ];

                $dispatcher = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
                $connector  = $dispatcher->getVirtualConnector('product', 'update', 'entities', $requestData);
                $dispatcher->process($connector);

                if ($accountObject !== null) {
                    $this->amazonThrottlingManager->registerRequests(
                        $accountObject->getChildObject()->getMerchantId(),
                        \Ess\M2ePro\Model\Amazon\ThrottlingManager::REQUEST_TYPE_FEED,
                        1
                    );
                }
            }
        }

        $this->markItemsAsProcessed($processedItemsIds);
    }

    //########################################

    protected function processWalmart()
    {
        /** @var \Ess\M2ePro\Model\StopQueue[] $items */
        $items = $this->getNotProcessedItems(\Ess\M2ePro\Helper\Component\Walmart::NICK);
        if (empty($items)) {
            return;
        }

        $processedItemsIds   = [];
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
                'id'       => $item->getId(),
                'sku'      => $itemRequestData['sku'],
                'wpid'     => $itemRequestData['wpid'],
                'qty'      => 0,
                'lag_time' => 0
            ];
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $accountsCollection */
        $accountsCollection = $this->parentFactory->getObject(\Ess\M2ePro\Helper\Component\Walmart::NICK, 'Account')
            ->getCollection();
        $accountsCollection->addFieldToFilter('server_hash', array_keys($accountsRequestData));

        foreach ($accountsRequestData as $account => $accountRequestData) {
            $requestDataPacks = array_chunk($accountRequestData, self::WALMART_REQUEST_MAX_ITEMS_COUNT);

            $accountObject = $accountsCollection->getItemByColumnValue('server_hash', $account);

            if ($accountObject !== null &&
                ($this->walmartThrottlingManager->getAvailableRequestsCount(
                    $accountObject->getId(),
                    \Ess\M2ePro\Model\Walmart\ThrottlingManager::REQUEST_TYPE_UPDATE_QTY
                ) <= 0 ||
                $this->walmartThrottlingManager->getAvailableRequestsCount(
                    $accountObject->getId(),
                    \Ess\M2ePro\Model\Walmart\ThrottlingManager::REQUEST_TYPE_UPDATE_LAG_TIME
                ) <= 0)) {
                continue;
            }

            foreach ($requestDataPacks as $requestDataPack) {
                $requestData = [
                    'account' => $account,
                    'items'   => $requestDataPack,
                ];

                /** @var \Ess\M2ePro\Model\Walmart\Connector\Dispatcher $dispatcher */
                $dispatcher = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');
                $connector  = $dispatcher->getVirtualConnector('product', 'update', 'entities', $requestData);
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

    //########################################

    protected function getNotProcessedItems($component)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\StopQueue\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('StopQueue')->getCollection();
        $collection->addFieldToFilter('is_processed', 0);
        $collection->addFieldToFilter('component_mode', $component);

        return $collection->getItems();
    }

    protected function markItemsAsProcessed(array $itemsIds)
    {
        if (empty($itemsIds)) {
            return;
        }

        $this->resource->getConnection()->update(
            $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_stop_queue'),
            ['is_processed' => 1, 'update_date' => $this->getHelper('Data')->getCurrentGmtDate()],
            ['id IN (?)' => $itemsIds]
        );
    }

    //########################################
}
