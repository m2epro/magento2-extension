<?php

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order;

class Update extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'amazon/order/update';
    public const ORDER_CHANGES_PER_ACCOUNT = 300;
    /** @var \Ess\M2ePro\Model\ResourceModel\Order\Change\CollectionFactory */
    private $orderChangeCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Order\Change */
    private $orderChargeResource;

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
        \Ess\M2ePro\Model\ResourceModel\Order\Change\CollectionFactory $orderChangeCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Order\Change $orderChargeResource
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
        $this->orderChangeCollectionFactory = $orderChangeCollectionFactory;
        $this->orderChargeResource = $orderChargeResource;
    }

    public function isPossibleToRun()
    {
        if ($this->getHelper('Server\Maintenance')->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Log
     */
    protected function getSynchronizationLog()
    {
        $synchronizationLog = parent::getSynchronizationLog();

        $synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_ORDERS);

        return $synchronizationLog;
    }

    protected function performActions()
    {
        $this->deleteNotActualChanges();

        $permittedAccounts = $this->getPermittedAccounts();
        if (empty($permittedAccounts)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Account $account */
        foreach ($permittedAccounts as $account) {
            $this->getOperationHistory()->addText('Starting Account "' . $account->getTitle() . '"');
            $this->getOperationHistory()->addTimePoint(
                __METHOD__ . 'process' . $account->getId(),
                'Process Account ' . $account->getTitle()
            );

            try {
                $this->processAccount($account);
            } catch (\Throwable $exception) {
                $message = (string)__(
                    'The "Update" Action for Amazon Account "%account" was completed with error.',
                    ['account' => $account->getTitle()]
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            $this->getOperationHistory()->saveTimePoint(__METHOD__ . 'process' . $account->getId());
        }
    }

    protected function getPermittedAccounts()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $accountsCollection */
        $accountsCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Account'
        )->getCollection();

        return $accountsCollection->getItems();
    }

    protected function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        $updateShippingChanges = $this->getOrderUpdateShippingChanges($account);

        if (empty($updateShippingChanges)) {
            return;
        }

        $this->orderChargeResource->incrementAttemptCount(array_keys($updateShippingChanges));

        /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcherObject */
        $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');

        foreach ($updateShippingChanges as $change) {
            $changeParams = $change->getParams();

            $connectorData = [
                'order_id' => $change->getOrderId(),
                'change_id' => $change->getId(),
                'amazon_order_id' => $changeParams['amazon_order_id'],
                'tracking_number' => $changeParams['tracking_number'] ?? null,
                'carrier_name' => $changeParams['carrier_title'] ?? null,
                'carrier_code' => $changeParams['carrier_code'] ?? null,
                'fulfillment_date' => $changeParams['fulfillment_date'],
                'shipping_method' => $changeParams['shipping_method'] ?? null,
                'items' => $changeParams['items'],
            ];

            /** @var \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Update\Requester $connectorObj */
            $connectorObj = $dispatcherObject->getCustomConnector(
                'Cron_Task_Amazon_Order_Update_Requester',
                ['order' => $connectorData],
                $account
            );
            $dispatcherObject->process($connectorObj);
        }
    }

    /**
     * @return \Ess\M2ePro\Model\Order\Change[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getOrderUpdateShippingChanges(\Ess\M2ePro\Model\Account $account): array
    {
        $changesCollection = $this->orderChangeCollectionFactory->create();
        $changesCollection->addAccountFilter($account->getId());
        $changesCollection->addProcessingAttemptDateFilter();
        $changesCollection->addFieldToFilter('component', \Ess\M2ePro\Helper\Component\Amazon::NICK);
        $changesCollection->addFieldToFilter('action', \Ess\M2ePro\Model\Order\Change::ACTION_UPDATE_SHIPPING);
        $changesCollection->getSelect()->joinLeft(
            ['pl' => $this->activeRecordFactory->getObject('Processing\Lock')->getResource()->getMainTable()],
            'pl.object_id = main_table.order_id AND pl.model_name = \'Order\'',
            []
        );
        $changesCollection->addFieldToFilter('pl.id', ['null' => true]);
        $changesCollection->getSelect()->limit(self::ORDER_CHANGES_PER_ACCOUNT);
        $changesCollection->getSelect()->group(['order_id']);

        /** @var \Ess\M2ePro\Model\Order\Change[] $items */
        $items = $changesCollection->getItems();

        return $items;
    }

    protected function deleteNotActualChanges(): void
    {
        $this->orderChargeResource->deleteByProcessingAttemptCount(
            \Ess\M2ePro\Model\Order\Change::MAX_ALLOWED_PROCESSING_ATTEMPTS,
            \Ess\M2ePro\Helper\Component\Amazon::NICK
        );
    }
}
