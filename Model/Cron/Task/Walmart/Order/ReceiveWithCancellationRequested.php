<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Walmart\Order;

use Ess\M2ePro\Helper\Component\Walmart;

class ReceiveWithCancellationRequested extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'walmart/order/receive_with_cancellation_requested';

    private const INTERVAL_TO_FIRST_CHECK_BUYER_CANCELLATION_REQUESTS = 259200; // 3 days
    private const INTERVAL_TO_COMMON_CHECK_BUYER_CANCELLATION_REQUESTS = 86400; // 1 day
    private const INTERVAL_FOR_ACCOUNT_SYNCHRONIZATION = 7200;                  // 2 hours

    private const REGISTRY_PREFIX = '/walmart/order/receive_with_cancellation_requested/by_account/';
    private const REGISTRY_SUFFIX = '/last_update/';

    /** @var \Ess\M2ePro\Helper\Server\Maintenance */
    private $serverMaintenanceHelper;
    /** @var \Ess\M2ePro\Helper\Module\Logger */
    private $moduleLoggerHelper;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $moduleTranslationHelper;
    /** @var \Ess\M2ePro\Model\Walmart\Connector\DispatcherFactory */
    private $walmartConnectorDispatcherFactory;
    /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message\SetFactory */
    private $messageSetFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Order */
    private $walmartOrderResource;
    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registryManager;
    /** @var \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory */
    private $accountCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Order\Item\CollectionFactory */
    private $orderItemCollectionFactory;
    /** @var \Ess\M2ePro\Model\Synchronization\Log */
    private $synchronizationLog;
    /** @var bool */
    private $accountSynchronizationFail = false;

    /**
     * @param \Ess\M2ePro\Helper\Server\Maintenance $serverMaintenanceHelper
     * @param \Ess\M2ePro\Helper\Module\Logger $moduleLoggerHelper
     * @param \Ess\M2ePro\Helper\Module\Translation $moduleTranslationHelper
     * @param \Ess\M2ePro\Model\Walmart\Connector\DispatcherFactory $walmartConnectorDispatcherFactory
     * @param \Ess\M2ePro\Model\Connector\Connection\Response\Message\SetFactory $messageSetFactory
     * @param \Ess\M2ePro\Model\ResourceModel\Walmart\Order $walmartOrderResource
     * @param \Ess\M2ePro\Model\Registry\Manager $registryManager
     * @param \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory
     * @param \Ess\M2ePro\Model\ResourceModel\Order\Item\CollectionFactory $orderItemCollectionFactory
     * @param \Ess\M2ePro\Helper\Data $helperData
     * @param \Magento\Framework\Event\Manager $eventManager
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        \Ess\M2ePro\Helper\Server\Maintenance $serverMaintenanceHelper,
        \Ess\M2ePro\Helper\Module\Logger $moduleLoggerHelper,
        \Ess\M2ePro\Helper\Module\Translation $moduleTranslationHelper,
        \Ess\M2ePro\Model\Walmart\Connector\DispatcherFactory $walmartConnectorDispatcherFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response\Message\SetFactory $messageSetFactory,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Order $walmartOrderResource,
        \Ess\M2ePro\Model\Registry\Manager $registryManager,
        \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Order\Item\CollectionFactory $orderItemCollectionFactory,
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
            $helperData,
            $eventManager,
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $taskRepo,
            $resource
        );
        $this->serverMaintenanceHelper = $serverMaintenanceHelper;
        $this->moduleLoggerHelper = $moduleLoggerHelper;
        $this->moduleTranslationHelper = $moduleTranslationHelper;
        $this->walmartConnectorDispatcherFactory = $walmartConnectorDispatcherFactory;
        $this->messageSetFactory = $messageSetFactory;
        $this->walmartOrderResource = $walmartOrderResource;
        $this->registryManager = $registryManager;
        $this->accountCollectionFactory = $accountCollectionFactory;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
    }

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Log
     */
    protected function getSynchronizationLog(): \Ess\M2ePro\Model\Synchronization\Log
    {
        if ($this->synchronizationLog === null) {
            $this->synchronizationLog = parent::getSynchronizationLog();

            $this->synchronizationLog->setComponentMode(Walmart::NICK);
            $this->synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_ORDERS);
        }

        return $this->synchronizationLog;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isPossibleToRun(): bool
    {
        if ($this->serverMaintenanceHelper->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function performActions()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $accountsCollection */
        $accountsCollection = $this->accountCollectionFactory
            ->create(['childMode' => \Ess\M2ePro\Helper\Component\Walmart::NICK]);

        /** @var \Ess\M2ePro\Model\Account $account * */
        foreach ($accountsCollection->getItems() as $account) {
            try {
                $accountId = (int)$account->getId();
                if (!$this->isItemsReceiveRequired($accountId)) {
                    continue;
                }

                $this->accountSynchronizationFail = false;
                $responseData = $this->receiveOrderItems($account, $this->getReceiveInterval($accountId));
                $this->processOrderItems($responseData);

                if (!$this->accountSynchronizationFail) {
                    $this->updateLastReceiveDate($accountId);
                }
            } catch (\Throwable $exception) {
                $message = $this->moduleTranslationHelper->__(
                    'The "Receive Orders with Buyer Cancellation Requested" '
                    . 'Action for Walmart Account "%title%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }
        }
    }

    /**
     * @param int $accountId
     *
     * @return string
     */
    private function getRegistryKey(int $accountId): string
    {
        return self::REGISTRY_PREFIX . $accountId . self::REGISTRY_SUFFIX;
    }

    /**
     * @param int $accountId
     *
     * @return bool
     * @throws \Exception
     */
    private function isItemsReceiveRequired(int $accountId): bool
    {
        $lastUpdate = $this->registryManager->getValue($this->getRegistryKey($accountId));
        if (!$lastUpdate) {
            return true;
        }

        $now = \Ess\M2ePro\Helper\Date::createCurrentGmt();
        $lastUpdateDateTime = \Ess\M2ePro\Helper\Date::createDateGmt($lastUpdate);

        return $now->getTimestamp() - $lastUpdateDateTime->getTimestamp() > self::INTERVAL_FOR_ACCOUNT_SYNCHRONIZATION;
    }

    /**
     * @param int $accountId
     *
     * @return void
     * @throws \Exception
     */
    private function updateLastReceiveDate(int $accountId)
    {
        $now = \Ess\M2ePro\Helper\Date::createCurrentGmt();
        $this->registryManager->setValue(
            $this->getRegistryKey($accountId),
            $now->format('Y-m-d H:i:s')
        );
    }

    /**
     * @param int $accountId
     *
     * @return int
     */
    private function getReceiveInterval(int $accountId): int
    {
        $lastUpdate = $this->registryManager->getValue($this->getRegistryKey($accountId));

        return $lastUpdate ?
            self::INTERVAL_TO_COMMON_CHECK_BUYER_CANCELLATION_REQUESTS
            : self::INTERVAL_TO_FIRST_CHECK_BUYER_CANCELLATION_REQUESTS;
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @param int $interval
     *
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Exception
     */
    private function receiveOrderItems(\Ess\M2ePro\Model\Account $account, int $interval): array
    {
        $fromDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();
        $fromDate->modify("-$interval seconds");

        /** @var \Ess\M2ePro\Model\Walmart\Connector\Dispatcher $dispatcherObject */
        $dispatcherObject = $this->walmartConnectorDispatcherFactory->create();
        /** @var \Ess\M2ePro\Model\Walmart\Connector\Orders\Get\ItemsCancellationRequested $connectorObj */
        $connectorObj = $dispatcherObject->getConnector(
            'orders',
            'get',
            'itemsCancellationRequested',
            [
                'account' => $account->getChildObject()->getServerHash(),
                'from_create_date' => $fromDate->format('Y-m-d H:i:s'),
            ]
        );
        $dispatcherObject->process($connectorObj);

        $this->processResponseMessages($connectorObj->getResponseMessages());

        return $connectorObj->getResponseData();
    }

    /**
     * @param array $items
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function processOrderItems(array $items)
    {
        foreach ($items as $item) {
            /** @var \Ess\M2ePro\Model\ResourceModel\Order\Item\Collection $collection */
            $collection = $this->orderItemCollectionFactory
                ->create(['childMode' => \Ess\M2ePro\Helper\Component\Walmart::NICK]);

            $collection
                ->addFieldToFilter('walmart_order_id', $item['walmart_order_id'])
                ->addFieldToFilter('sku', $item['sku']);
            $collection->getSelect()->join(
                ['wo' => $this->walmartOrderResource->getMainTable()],
                'main_table.order_id=wo.order_id',
                []
            );

            /** @var \Ess\M2ePro\Model\Order\Item $existItem */
            $existItem = $collection->getFirstItem();

            if (!$existItem->getId()) {
                $this->moduleLoggerHelper->process(
                    [
                        'walmart_order_id' => $item['walmart_order_id'],
                        'sku' => $item['sku'],
                    ],
                    'Walmart orders receive with cancellation requested task - cannot find order item'
                );

                continue;
            }

            $cancellationRequestSavedPreviously = $existItem->getChildObject()
                                                            ->isBuyerCancellationRequested();
            if ($cancellationRequestSavedPreviously) {
                continue;
            }

            $existItem->getChildObject()
                      ->setData('buyer_cancellation_requested', 1)
                      ->save();

            $this->notifyAboutBuyerCancellationRequested($existItem->getChildObject());
        }
    }

    /**
     * @param \Ess\M2ePro\Model\Walmart\Order\Item $walmartOrderItem
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function notifyAboutBuyerCancellationRequested(\Ess\M2ePro\Model\Walmart\Order\Item $walmartOrderItem)
    {
        $description = 'A buyer requested to cancel the item(s) "%item_name%" from the order #%order_number%.';

        $walmartOrder = $walmartOrderItem->getWalmartOrder();
        $walmartOrder->getParentObject()->addWarningLog(
            $description,
            [
                '!order_number' => $walmartOrder->getWalmartOrderId(),
                '!item_name' => $walmartOrderItem->getTitle(),
            ]
        );
    }

    /**
     * @param array $messages
     *
     * @return void
     */
    private function processResponseMessages(array $messages = [])
    {
        $messagesSet = $this->messageSetFactory->create();
        $messagesSet->init($messages);

        foreach ($messagesSet->getEntities() as $message) {
            if (!$message->isError() && !$message->isWarning()) {
                continue;
            }

            if ($message->isError()) {
                $this->accountSynchronizationFail = true;
            }

            $logType = $message->isError() ? \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
                : \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING;

            $this->getSynchronizationLog()->addMessage(
                $this->moduleTranslationHelper->__($message->getText()),
                $logType
            );
        }
    }
}
