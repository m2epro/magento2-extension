<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive\BillingAddress;

class ProcessingRunner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Partial\Runner
{
    private const LOCK_ITEM_PREFIX = 'synchronization_amazon_order_get_billing_address';

    private \Ess\M2ePro\Model\Lock\Item\ManagerFactory $lockItemManagerFactory;
    private \Ess\M2ePro\Model\Amazon\Account\Repository $accountRepository;

    public function __construct(
        \Ess\M2ePro\Model\Lock\Item\ManagerFactory $lockItemManagerFactory,
        \Ess\M2ePro\Model\Amazon\Account\Repository $accountRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        parent::__construct(
            $parentFactory,
            $activeRecordFactory,
            $helperData,
            $helperFactory,
            $modelFactory
        );
        $this->lockItemManagerFactory = $lockItemManagerFactory;
        $this->accountRepository = $accountRepository;
    }

    public static function getLockNick(int $accountId): string
    {
        return self::LOCK_ITEM_PREFIX . '_' . $accountId;
    }

    protected function setLocks()
    {
        $params = $this->getParams();

        $lockItemNick = self::getLockNick((int)$params['account_id']);
        $lockItemManager = $this->lockItemManagerFactory->create($lockItemNick);

        $lockItemManager->create();

        $account = $this->accountRepository->get((int)$params['account_id']);

        $account->addProcessingLock(null, $this->getProcessingObject()->getId());
        $account->addProcessingLock('synchronization', $this->getProcessingObject()->getId());
        $account->addProcessingLock('synchronization_amazon', $this->getProcessingObject()->getId());
        $account->addProcessingLock(self::LOCK_ITEM_PREFIX, $this->getProcessingObject()->getId());
    }

    protected function unsetLocks()
    {
        $params = $this->getParams();

        $lockItemNick = self::getLockNick((int)$params['account_id']);
        $lockItemManager = $this->lockItemManagerFactory->create($lockItemNick);
        $lockItemManager->remove();

        $account = $this->accountRepository->get((int)$params['account_id']);

        $account->deleteProcessingLocks(null, $this->getProcessingObject()->getId());
        $account->deleteProcessingLocks('synchronization', $this->getProcessingObject()->getId());
        $account->deleteProcessingLocks('synchronization_amazon', $this->getProcessingObject()->getId());
        $account->deleteProcessingLocks(self::LOCK_ITEM_PREFIX, $this->getProcessingObject()->getId());
    }
}
