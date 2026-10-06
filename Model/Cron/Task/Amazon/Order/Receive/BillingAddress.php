<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive;

class BillingAddress extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'amazon/order/receive/billing_address';

    /** @var int $interval (in seconds) */
    protected $interval = 5 * 60;

    private \Ess\M2ePro\Helper\Server\Maintenance $serverMaintenanceHelper;
    private \Ess\M2ePro\Model\Amazon\Account\Repository $accountRepository;
    private \Ess\M2ePro\Model\Amazon\Order\Repository $orderRepository;
    private \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $connectorDispatcher;
    private \Ess\M2ePro\Model\Lock\Item\ManagerFactory $lockManagerFactory;

    public function __construct(
        \Ess\M2ePro\Helper\Server\Maintenance $serverMaintenanceHelper,
        \Ess\M2ePro\Model\Amazon\Account\Repository $accountRepository,
        \Ess\M2ePro\Model\Amazon\Order\Repository $orderRepository,
        \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $connectorDispatcher,
        \Ess\M2ePro\Model\Lock\Item\ManagerFactory $lockManagerFactory,
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
        $this->serverMaintenanceHelper = $serverMaintenanceHelper;
        $this->accountRepository = $accountRepository;
        $this->orderRepository = $orderRepository;
        $this->connectorDispatcher = $connectorDispatcher;
        $this->lockManagerFactory = $lockManagerFactory;
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

    protected function performActions()
    {
        $permittedAccounts = $this->getPermittedAccounts();
        if (empty($permittedAccounts)) {
            return;
        }

        foreach ($permittedAccounts as $account) {
            $this
                ->getOperationHistory()
                ->addText('Starting "Fetch billing address" for account "' . $account->getTitle() . '"');

            if ($this->isLockedAccount($account)) {
                $this
                    ->getOperationHistory()
                    ->addText('Account "' . $account->getTitle() . '"' . ' is locked');

                continue;
            }

            $this->getOperationHistory()->addTimePoint(
                __METHOD__ . 'process' . $account->getId(),
                'Process account ' . $account->getTitle()
            );

            try {
                $this->processAccount($account);
            } catch (\Exception $exception) {
                $message = \__(
                    'The "Receive billing address" Action for Amazon Account "%account" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            $this->getOperationHistory()->saveTimePoint(__METHOD__ . 'process' . $account->getId());
        }
    }

    /**
     * @return \Ess\M2ePro\Model\Account[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getPermittedAccounts(): array
    {
        $allAccounts = $this->accountRepository->getAll();

        $result = [];
        foreach ($allAccounts as $account) {
            if (!$account->isEnabledRetrieveAccurateBillingAddress()) {
                continue;
            }

            if (!$account->getMarketplace()->isEuropeanRegion()) {
                continue;
            }

            $result[] = $account->getParentObject();
        }

        return $result;
    }

    private function processAccount(\Ess\M2ePro\Model\Account $account): void
    {
        $olderOrder = $this->orderRepository->findOlderForReceiveBillingAddress((int)$account->getId());
        if ($olderOrder === null) {
            return;
        }

        $date = $olderOrder->getChildObject()->getPurchaseCreateDate();

        $connector = $this->connectorDispatcher
            ->getConnectorByClass(
                BillingAddress\Requester::class,
                ['from_date' => $date->format('Y-m-d H:i:s')],
                $account
            );

        $this->connectorDispatcher->process($connector);
    }

    private function isLockedAccount(\Ess\M2ePro\Model\Account $account): bool
    {
        $lockNick = \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive\BillingAddress\ProcessingRunner::getLockNick((int)$account->getId());
        $lockItem = $this->lockManagerFactory->create($lockNick);
        if (!$lockItem->isExist()) {
            return false;
        }

        if ($lockItem->isInactiveMoreThanSeconds(\Ess\M2ePro\Model\Processing\Runner::MAX_LIFETIME)) {
            $lockItem->remove();

            return false;
        }

        return true;
    }
}
