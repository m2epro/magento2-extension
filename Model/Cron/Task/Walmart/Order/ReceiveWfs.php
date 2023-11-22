<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Cron\Task\Walmart\Order;

use Ess\M2ePro\Helper\Component\Walmart;
use Ess\M2ePro\Model\ResourceModel\Account\Collection;

class ReceiveWfs extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'walmart/order/receive_wfs';

    /** @var \Ess\M2ePro\Helper\Server\Maintenance */
    private $serverMaintenance;
    /** @var \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory */
    private $accountCollectionFactory;
    /** @var \Ess\M2ePro\Model\Cron\Task\Walmart\Order\Creator */
    private $ordersCreator;
    /** @var \Ess\M2ePro\Model\Walmart\Connector\DispatcherFactory */
    private $walmartConnectorDispatcherFactory;
    /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message\SetFactory */
    private $messageSetFactory;

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
        \Ess\M2ePro\Helper\Server\Maintenance $serverMaintenance,
        \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory,
        \Ess\M2ePro\Model\Cron\Task\Walmart\Order\Creator $ordersCreator,
        \Ess\M2ePro\Model\Walmart\Connector\DispatcherFactory $walmartConnectorDispatcherFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response\Message\SetFactory $messageSetFactory
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
        $this->serverMaintenance = $serverMaintenance;
        $this->accountCollectionFactory = $accountCollectionFactory;
        $this->ordersCreator = $ordersCreator;
        $this->walmartConnectorDispatcherFactory = $walmartConnectorDispatcherFactory;
        $this->messageSetFactory = $messageSetFactory;
    }

    protected function getSynchronizationLog(): \Ess\M2ePro\Model\Synchronization\Log
    {
        $synchronizationLog = parent::getSynchronizationLog();

        $synchronizationLog->setComponentMode(Walmart::NICK);
        $synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_ORDERS);

        return $synchronizationLog;
    }

    public function isPossibleToRun(): bool
    {
        if ($this->serverMaintenance->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    protected function performActions(): void
    {
        $accounts = $this->createWithWalmartChildModeWithoutCanada();
        $this->ordersCreator->setSynchronizationLog($this->getSynchronizationLog());

        foreach ($accounts as $account) {
            try {
                $rawOrder = $this->receiveOrderItems($account);

                $processedWalmartOrders = $this->ordersCreator->processWalmartOrders(
                    $account->getParentObject(),
                    $rawOrder,
                    true
                );
                $this->ordersCreator->processMagentoOrders($processedWalmartOrders);
            } catch (\Throwable $exception) {
                $message = (string)__(
                    'The "Receive Orders WFS" '
                    . 'Action for Walmart Account "%1" was completed with error.',
                    $account->getParentObject()->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }
        }
    }

    private function receiveOrderItems(\Ess\M2ePro\Model\Walmart\Account $account)
    {
        $lastFromDate = $account->getOrdersWfsLastSynchronization();

        $fromDate = $this->prepareFromCreateDate($lastFromDate);
        $toDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();

        if ($fromDate >= $toDate) {
            $fromDate = clone $toDate;
            $fromDate->modify('-5 minutes');
        }

        $dispatcherObject = $this->walmartConnectorDispatcherFactory->create();
        /** @var \Ess\M2ePro\Model\Walmart\Connector\Orders\Get\WfsItems $connectorObj */
        $connectorObj = $dispatcherObject->getConnector(
            'orders',
            'get',
            'wfsItems',
            [
                'account' => $account->getServerHash(),
                'from_update_date' => $fromDate->format('Y-m-d H:i:s'),
                'to_update_date' => $toDate->format('Y-m-d H:i:s'),
            ]
        );

        $dispatcherObject->process($connectorObj);

        $this->processResponseMessages($connectorObj->getResponseMessages());

        $responseData = $connectorObj->getResponseData();

        $account->setOrdersWfsLastSynchronization(
            \Ess\M2ePro\Helper\Date::createDateGmt($responseData['to_update_date'])
        )
                ->save();

        return $responseData['items'];
    }

    protected function prepareFromCreateDate(?\DateTime $lastFromDate): \DateTime
    {
        if (empty($lastFromDate)) {
            return \Ess\M2ePro\Helper\Date::createCurrentGmt()->modify('-1 day');
        }

        return $lastFromDate;
    }

    private function processResponseMessages(array $messages = []): void
    {
        $messagesSet = $this->messageSetFactory->create();
        $messagesSet->init($messages);

        foreach ($messagesSet->getEntities() as $message) {
            if (!$message->isError() && !$message->isWarning()) {
                continue;
            }

            $logType = $message->isError()
                ? \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
                : \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING;

            $this->getSynchronizationLog()->addMessage(
                (string)__($message->getText()),
                $logType
            );
        }
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Account[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function createWithWalmartChildModeWithoutCanada(): array
    {
        $collection =  $this->accountCollectionFactory->createWithWalmartChildMode();

        $result = [];
        /** @var \Ess\M2ePro\Model\Account $account */
        foreach ($collection->getItems() as $account) {

            /** @var \Ess\M2ePro\Model\Walmart\Account $walmartAccount */
            $walmartAccount = $account->getChildObject();
            if ($walmartAccount->getMarketplace()->getCode() === 'CA') {
                continue;
            }

            $result[] = $walmartAccount;
        }

        return $result;
    }
}
