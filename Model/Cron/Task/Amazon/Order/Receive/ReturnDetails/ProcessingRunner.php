<?php

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive\ReturnDetails;

class ProcessingRunner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Single\Runner
{
    public const LOCK_ITEM_PREFIX = 'synchronization_amazon_order_get_returnDetails';

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

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function setLocks(): void
    {
        $params = $this->getParams();

        $lockItemNick = self::LOCK_ITEM_PREFIX . '_' . $params['account_id'];
        $lockItemManager = $this->lockItemManagerFactory->create($lockItemNick);

        $lockItemManager->create();

        $account = $this->accountRepository->get((int)$params['account_id']);

        $account->addProcessingLock(null, $this->getProcessingObject()->getId());
        $account->addProcessingLock('synchronization', $this->getProcessingObject()->getId());
        $account->addProcessingLock('synchronization_amazon', $this->getProcessingObject()->getId());
        $account->addProcessingLock(self::LOCK_ITEM_PREFIX, $this->getProcessingObject()->getId());
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function unsetLocks(): void
    {
        $params = $this->getParams();

        $lockItemNick = self::LOCK_ITEM_PREFIX . '_' . $params['account_id'];
        $lockItemManager = $this->lockItemManagerFactory->create($lockItemNick);
        $lockItemManager->remove();

        $account = $this->accountRepository->get((int)$params['account_id']);

        $account->deleteProcessingLocks(null, $this->getProcessingObject()->getId());
        $account->deleteProcessingLocks('synchronization', $this->getProcessingObject()->getId());
        $account->deleteProcessingLocks('synchronization_amazon', $this->getProcessingObject()->getId());
        $account->deleteProcessingLocks(self::LOCK_ITEM_PREFIX, $this->getProcessingObject()->getId());
    }
}
