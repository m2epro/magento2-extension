<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Walmart\Order;

use Ess\M2ePro\Helper\Component\Walmart;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Walmart\Order\Receive
 */
class Receive extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'walmart/order/receive';
    /** @var \Ess\M2ePro\Model\Order\SyncStatusManager */
    private $syncStatusManager;

    public function __construct(
        \Ess\M2ePro\Model\Cron\Manager $cronManager,
        \Ess\M2ePro\Helper\Data $helperData,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Model\Order\SyncStatusManager $syncStatusManager
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
        $this->syncStatusManager = $syncStatusManager;
    }

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Log
     */
    protected function getSynchronizationLog()
    {
        $synchronizationLog = parent::getSynchronizationLog();

        $synchronizationLog->setComponentMode(Walmart::NICK);
        $synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_ORDERS);

        return $synchronizationLog;
    }

    public function isPossibleToRun()
    {
        if ($this->getHelper('Server\Maintenance')->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    protected function performActions()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $accountsCollection */
        $accountsCollection = $this->parentFactory->getObject(Walmart::NICK, 'Account')->getCollection();

        /** @var \Ess\M2ePro\Model\Cron\Task\Walmart\Order\Creator $ordersCreator */
        $ordersCreator = $this->modelFactory->getObject('Cron_Task_Walmart_Order_Creator');
        $ordersCreator->setSynchronizationLog($this->getSynchronizationLog());

        $isSuccess = true;
        try {
            foreach ($accountsCollection->getItems() as $account) {
                /** @var \Ess\M2ePro\Model\Account $account * */

                try {
                    if ($this->isCanada($account)) {
                        $responseData = $this->receiveWalmartOrdersDataByCreateDate($account);
                        $lastSynchronizationDate = $responseData['to_create_date'];
                    } else {
                        $responseData = $this->receiveWalmartOrdersDataByUpdateDate($account);
                        $lastSynchronizationDate = $responseData['to_update_date'];
                    }

                    if (empty($responseData)) {
                        continue;
                    }

                    $processedWalmartOrders = $ordersCreator->processWalmartOrders(
                        $account,
                        $responseData['items'],
                        false
                    );
                    $ordersCreator->processMagentoOrders($processedWalmartOrders);

                    $account->getChildObject()->setData('orders_last_synchronization', $lastSynchronizationDate);
                    $account->getChildObject()->save();
                } catch (\Exception $exception) {
                    $isSuccess = false;
                    $message = $this->getHelper('Module_Translation')->__(
                        'The "Receive" Action for Walmart Account "%title%" was completed with error.',
                        $account->getTitle()
                    );

                    $this->processTaskAccountException($message, __FILE__, __LINE__);
                    $this->processTaskException($exception);
                }
            }
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            if (isset($e) || !$isSuccess) {
                $this->syncStatusManager->setLastRunAsFail(\Ess\M2ePro\Helper\Component\Walmart::NICK);
            } else {
                $this->syncStatusManager->setLastRunAsSuccess(\Ess\M2ePro\Helper\Component\Walmart::NICK);
            }
        }
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     *
     * @return array{"items": array, "to_create_date": string, "to_update_date": string}
     * @throws \Exception
     */
    protected function receiveWalmartOrdersDataByUpdateDate(\Ess\M2ePro\Model\Account $account): array
    {
        $lastFromDate = $account->getChildObject()->getData('orders_last_synchronization');
        if (!empty($lastFromDate)) {
            $fromDate = \Ess\M2ePro\Helper\Date::createDateGmt($lastFromDate);
        } else {
            $fromDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();
            $fromDate->modify('-1 day');
        }

        $toDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();

        // ----------------------------------------

        if ($fromDate >= $toDate) {
            $fromDate = clone $toDate;
            $fromDate->modify('-5 minutes');
        }

        // ----------------------------------------

        /** @var \Ess\M2ePro\Model\Walmart\Connector\Dispatcher $dispatcherObject */
        $dispatcherObject = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');

        // -------------------------------------

        $connectorObj = $dispatcherObject->getVirtualConnector(
            'orders',
            'get',
            'items',
            [
                'account' => $account->getChildObject()->getServerHash(),
                'from_update_date' => $fromDate->format('Y-m-d H:i:s'),
                'to_update_date' => $toDate->format('Y-m-d H:i:s'),
            ]
        );
        $dispatcherObject->process($connectorObj);

        // ----------------------------------------

        $this->processResponseMessages($connectorObj->getResponseMessages());

        // ----------------------------------------

        $responseData = $connectorObj->getResponseData();
        if (!isset($responseData['items'])) {
            /** @var \Ess\M2ePro\Helper\Module\Logger $moduleLogger */
            $moduleLogger = $this->getHelper('Module_Logger');
            $moduleLogger->process(
                [
                    'from_update_date' => $fromDate->format('Y-m-d H:i:s'),
                    'to_update_date' => $toDate->format('Y-m-d H:i:s'),
                    'account_id' => $account->getId(),
                    'response_data' => $responseData,
                    'response_messages' => $connectorObj->getResponseMessages(),
                ],
                'Walmart orders receive task - empty response'
            );

            return [];
        }

        return [
            'items' => $responseData['items'],
            'to_create_date' => $responseData['to_create_date'] ?? $toDate->format('Y-m-d H:i:s'),
            'to_update_date' => count($responseData['items']) > 0
                ? $responseData['to_create_date'] ?? $toDate->format('Y-m-d H:i:s')
                : $toDate->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     *
     * @return array{"items": array, "to_create_date": string, "to_update_date": string}
     * @throws \Exception
     */
    private function receiveWalmartOrdersDataByCreateDate(\Ess\M2ePro\Model\Account $account): array
    {
        $lastFromDate = $account->getChildObject()->getData('orders_last_synchronization');
        $fromDate = $this->prepareFromCreateDate($lastFromDate);
        $toDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();

        // ----------------------------------------

        if ($fromDate >= $toDate) {
            $fromDate = clone $toDate;
            $fromDate->modify('-5 minutes');
        }

        // ----------------------------------------

        /** @var \Ess\M2ePro\Model\Walmart\Connector\Dispatcher $dispatcherObject */
        $dispatcherObject = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');
        $orders = [[]];
        $breakDate = null;

        // -------------------------------------

        do {
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'orders',
                'get',
                'items',
                [
                    'account' => $account->getChildObject()->getServerHash(),
                    'from_create_date' => $fromDate->format('Y-m-d H:i:s'),
                    'to_create_date' => $toDate->format('Y-m-d H:i:s'),
                ]
            );
            $dispatcherObject->process($connectorObj);

            // ----------------------------------------

            $this->processResponseMessages($connectorObj->getResponseMessages());

            // ----------------------------------------

            $responseData = $connectorObj->getResponseData();
            if (!isset($responseData['items'])) {
                /** @var \Ess\M2ePro\Helper\Module\Logger $moduleLogger */
                $moduleLogger = $this->getHelper('Module_Logger');
                $moduleLogger->process(
                    [
                        'from_create_date' => $fromDate->format('Y-m-d H:i:s'),
                        'to_create_date' => $toDate->format('Y-m-d H:i:s'),
                        'account_id' => $account->getId(),
                        'response_data' => $responseData,
                        'response_messages' => $connectorObj->getResponseMessages(),
                    ],
                    'Walmart orders receive task - empty response'
                );

                return [];
            }

            // ----------------------------------------

            $fromDate = \Ess\M2ePro\Helper\Date::createDateGmt($responseData['to_create_date']);
            if ($breakDate !== null && $breakDate->getTimestamp() === $fromDate->getTimestamp()) {
                break;
            }

            $orders[] = $responseData['items'];
            $breakDate = $fromDate;

            if ($fromDate > $toDate) {
                break;
            }
        } while (!empty($responseData['items']));

        // ----------------------------------------

        return [
            'items' => array_merge(...$orders),
            'to_create_date' => $responseData['to_create_date'],
        ];
    }

    protected function processResponseMessages(array $messages = []): void
    {
        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message\Set $messagesSet */
        $messagesSet = $this->modelFactory->getObject('Connector_Connection_Response_Message_Set');
        $messagesSet->init($messages);

        foreach ($messagesSet->getEntities() as $message) {
            if (!$message->isError() && !$message->isWarning()) {
                continue;
            }

            $logType = $message->isError() ? \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
                : \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING;

            $this->getSynchronizationLog()->addMessage(
                $this->getHelper('Module_Translation')->__($message->getText()),
                $logType
            );
        }
    }

    /**
     * @param mixed $lastFromDate
     *
     * @return \DateTime
     * @throws \Exception
     */
    protected function prepareFromCreateDate($lastFromDate): \DateTime
    {
        $nowDateTime = \Ess\M2ePro\Helper\Date::createCurrentGmt();

        // ----------------------------------------

        if (!empty($lastFromDate)) {
            $lastFromDate = \Ess\M2ePro\Helper\Date::createDateGmt($lastFromDate);
        }

        if (empty($lastFromDate)) {
            $lastFromDate = clone $nowDateTime;
        }

        // ----------------------------------------

        $minDateTime = clone $nowDateTime;
        $minDateTime->modify('-1 day');

        if ($lastFromDate > $minDateTime) {
            $minPurchaseDateTime = $this->getMinPurchaseDateTime($minDateTime);
            if ($minPurchaseDateTime !== null) {
                $lastFromDate = $minPurchaseDateTime;
            }
        }

        // ----------------------------------------

        $minDateTime = clone $nowDateTime;
        $minDateTime->modify('-30 days');

        if ($lastFromDate < $minDateTime) {
            $lastFromDate = $minDateTime;
        }

        // ---------------------------------------

        return $lastFromDate;
    }

    /**
     * @param \DateTime $minPurchaseDateTime
     *
     * @return \DateTime|null
     * @throws \Exception
     */
    private function getMinPurchaseDateTime(\DateTime $minPurchaseDateTime): ?\DateTime
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Order\Collection $collection */
        $collection = $this->parentFactory->getObject(Walmart::NICK, 'Order')->getCollection();
        $collection->addFieldToFilter(
            'status',
            [
                'from' => \Ess\M2ePro\Model\Walmart\Order::STATUS_CREATED,
                'to' => \Ess\M2ePro\Model\Walmart\Order::STATUS_SHIPPED_PARTIALLY,
            ]
        );
        $collection->addFieldToFilter(
            'purchase_create_date',
            ['from' => $minPurchaseDateTime->format('Y-m-d H:i:s')]
        );
        $collection->getSelect()->limit(1);

        /** @var \Ess\M2ePro\Model\Order $order */
        $order = $collection->getFirstItem();
        if ($order->getId() === null) {
            return null;
        }

        $purchaseDateTime = \Ess\M2ePro\Helper\Date::createDateGmt($order->getChildObject()->getPurchaseCreateDate());
        $purchaseDateTime->modify('-1 second');

        return $purchaseDateTime;
    }

    private function isCanada(\Ess\M2ePro\Model\Account $account): bool
    {
        /** @var \Ess\M2ePro\Model\Walmart\Account $walmartAccount */
        $walmartAccount = $account->getChildObject();

        return $walmartAccount->getMarketplace()->getCode() === 'CA';
    }
}
