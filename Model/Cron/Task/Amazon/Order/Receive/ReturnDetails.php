<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive;

class ReturnDetails extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'amazon/order/receive/return_details';

    /** @var int $interval (in seconds) */
    protected $interval = 30 * 60;

    private \Ess\M2ePro\Helper\Server\Maintenance $serverMaintenanceHelper;
    private \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory;
    private \Ess\M2ePro\Model\Amazon\Connector\Orders\Get\ReturnDetails\RequestProcessor $returnDetailsProcessor;
    private \Ess\M2ePro\Model\Lock\Item\ManagerFactory $lockManagerFactory;
    private \Ess\M2ePro\Model\Amazon\Account\Repository $amazonAccountRepository;

    public function __construct(
        \Ess\M2ePro\Helper\Server\Maintenance $serverMaintenanceHelper,
        \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory,
        \Ess\M2ePro\Model\Amazon\Connector\Orders\Get\ReturnDetails\RequestProcessor $returnDetailsProcessor,
        \Ess\M2ePro\Model\Lock\Item\ManagerFactory $lockManagerFactory,
        \Ess\M2ePro\Model\Amazon\Account\Repository $amazonAccountRepository,
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
        $this->accountCollectionFactory = $accountCollectionFactory;
        $this->returnDetailsProcessor = $returnDetailsProcessor;
        $this->lockManagerFactory = $lockManagerFactory;
        $this->amazonAccountRepository = $amazonAccountRepository;
    }

    public function isPossibleToRun(): bool
    {
        if ($this->serverMaintenanceHelper->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    protected function performActions()
    {
        $accounts = $this->amazonAccountRepository->getAccountsForReceiveReturnDetails();
        foreach ($accounts as $account) {
            /** @var \Ess\M2ePro\Model\Amazon\Account $amazonAccount */
            $amazonAccount = $account->getChildObject();

            $this->getOperationHistory()->addText(
                sprintf(
                    'Starting Account "%s"',
                    $account->getTitle(),
                )
            );

            if ($this->isLockedAccount($account)) {
                continue;
            }

            $this->getOperationHistory()->addTimePoint(
                __METHOD__ . 'process' . $account->getId(),
                sprintf(
                    'Process Account "%s"',
                    $account->getTitle(),
                )
            );

            try {
                $fromDate = $amazonAccount->getOrderReturnDataLastSynchronization();
                $this->returnDetailsProcessor->process($account, $fromDate);
            } catch (\Throwable $exception) {
                $message = __(
                    'The "Order Get Return Details" Action for Amazon Account "%account_title" was completed with error.',
                    ['account_title' => $account->getTitle()]
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }
        }
    }

    private function isLockedAccount(\Ess\M2ePro\Model\Account $account): bool
    {
        $lockNick = sprintf(
            '%s_%s',
            \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive\ReturnDetails\ProcessingRunner::LOCK_ITEM_PREFIX,
            $account->getId()
        );
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
