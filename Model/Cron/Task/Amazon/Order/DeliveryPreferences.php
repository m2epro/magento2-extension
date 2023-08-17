<?php

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order;

class DeliveryPreferences extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'amazon/order/delivery_preferences';

    /** @var int (in seconds) */
    protected $interval = 600;
    /** @var \Ess\M2ePro\Helper\Server\Maintenance */
    private $serverMaintenanceHelper;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translationHelper;
    /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher */
    private $amazonConnectorDispatcher;
    /** @var \Ess\M2ePro\Model\Lock\Item\ManagerFactory */
    private $lockItemManagerFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Order\CollectionFactory */
    private $orderCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Order */
    private $orderAmazonResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Account */
    private $amazonAccountResource;

    public function __construct(
        \Ess\M2ePro\Helper\Server\Maintenance $serverMaintenanceHelper,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper,
        \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $amazonConnectorDispatcher,
        \Ess\M2ePro\Model\Lock\Item\ManagerFactory $lockItemManagerFactory,
        \Ess\M2ePro\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Order $orderAmazonResource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Account $amazonAccountResource,
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
        $this->translationHelper = $translationHelper;
        $this->amazonConnectorDispatcher = $amazonConnectorDispatcher;
        $this->lockItemManagerFactory = $lockItemManagerFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderAmazonResource = $orderAmazonResource;
        $this->amazonAccountResource = $amazonAccountResource;
    }

    public function isPossibleToRun(): bool
    {
        if ($this->serverMaintenanceHelper->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    protected function getSynchronizationLog(): \Ess\M2ePro\Model\Synchronization\Log
    {
        $synchronizationLog = parent::getSynchronizationLog();

        $synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_ORDERS);

        return $synchronizationLog;
    }

    protected function performActions(): void
    {
        $permittedAccounts = $this->getPermittedAccounts();

        if (empty($permittedAccounts)) {
            return;
        }

        foreach ($permittedAccounts as $merchantId => $account) {
            $this->getOperationHistory()->addText("Starting Merchant \"$merchantId\"");

            if (!$this->isLockedAccount($account)) {
                $this->getOperationHistory()->addTimePoint(
                    __METHOD__ . 'process' . $merchantId,
                    "Process Merchant $merchantId"
                );

                try {
                    $this->processAccount($merchantId, $account);
                } catch (\Exception $exception) {
                    $message = 'The "Get Order Delivery Preferences" Action for Amazon Merchant "%merchant%"';
                    $message .= ' was completed with error.';
                    $message = $this->translationHelper->__($message, $merchantId);

                    $this->processTaskAccountException($message, __FILE__, __LINE__);
                    $this->processTaskException($exception);
                }

                $this->getOperationHistory()->saveTimePoint(__METHOD__ . 'process' . $account->getId());
            }
        }
    }

    protected function getPermittedAccounts(): array
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $accountsCollection */
        $accountsCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Account'
        )->getCollection();

        $accounts = [];
        foreach ($accountsCollection->getItems() as $accountItem) {
            /** @var \Ess\M2ePro\Model\Account $accountItem */

            $merchantId = $accountItem->getChildObject()->getMerchantId();
            if (isset($accounts[$merchantId])) {
                continue;
            }

            $accounts[$merchantId] = $accountItem;
        }

        return $accounts;
    }

    protected function isLockedAccount(\Ess\M2ePro\Model\Account $account): bool
    {
        $lockItemNick =
            \Ess\M2ePro\Model\Cron\Task\Amazon\Order\DeliveryPreferences\ProcessingRunner::LOCK_ITEM_PREFIX .
            '_' . $account->getId();

        /** @var \Ess\M2ePro\Model\Lock\Item\Manager $lockItemManager */
        $lockItemManager = $this->lockItemManagerFactory->create(
            $lockItemNick
        );

        if (!$lockItemManager->isExist()) {
            return false;
        }

        if ($lockItemManager->isInactiveMoreThanSeconds(\Ess\M2ePro\Model\Processing\Runner::MAX_LIFETIME)) {
            $lockItemManager->remove();

            return false;
        }

        return true;
    }

    protected function processAccount($merchantId, \Ess\M2ePro\Model\Account $account): void
    {
        $order = $this->getLatestOrder($merchantId);

        if (!$order->getId()) {
            return;
        }

        $date = \Ess\M2ePro\Helper\Date::createCurrentGmt();
        $toDate = $date->modify('-30 minutes')->format('Y-m-d H:i:s');
        $fromDate = $order->getCreateDate();

        /** @var DeliveryPreferences\Requester $connectorObj */
        $connectorObj = $this->amazonConnectorDispatcher->getCustomConnector(
            'Cron_Task_Amazon_Order_DeliveryPreferences_Requester',
            [
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
            $account
        );
        $this->amazonConnectorDispatcher->process($connectorObj);
    }

    private function getLatestOrder($merchantId): \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel
    {
        $date = \Ess\M2ePro\Helper\Date::createCurrentGmt();
        $latestDate = clone $date;
        $earlyDate = $date->modify('-30 minutes')->format('Y-m-d H:i:s');
        $latestDate = $latestDate->modify('-30 days')->format('Y-m-d H:i:s');

        /** @var \Ess\M2ePro\Model\ResourceModel\Order\Collection $collection */
        $collection = $this->orderCollectionFactory->create();
        $collection->joinInner(
            ['second_table' => $this->orderAmazonResource->getMainTable()],
            'second_table.order_id = main_table.id'
        );
        $collection->joinInner(
            ['account_table' => $this->amazonAccountResource->getMainTable()],
            '(`main_table`.`account_id` = `account_table`.`account_id`)'
        );

        $collection->addFieldToFilter('main_table.component_mode', \Ess\M2ePro\Helper\Component\Amazon::NICK);
        $collection->addFieldToFilter('account_table.merchant_id', $merchantId);
        $collection->addFieldToFilter('second_table.status', \Ess\M2ePro\Model\Amazon\Order::STATUS_UNSHIPPED);
        $collection->addFieldToFilter('second_table.is_get_delivery_preferences', 0);
        $collection->addFieldToFilter('main_table.create_date', ['lt' => $earlyDate]);
        $collection->addFieldToFilter('main_table.create_date', ['gt' => $latestDate]);
        $collection->getSelect()->order('main_table.create_date ASC');
        $collection->getSelect()->limit(1);

        return $collection->getFirstItem();
    }
}
