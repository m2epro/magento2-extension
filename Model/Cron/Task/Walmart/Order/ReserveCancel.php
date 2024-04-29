<?php

namespace Ess\M2ePro\Model\Cron\Task\Walmart\Order;

class ReserveCancel extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'walmart/order/reserve_cancel';

    /** @var \Ess\M2ePro\Model\ResourceModel\Order\CollectionFactory */
    private $orderCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory */
    private $accountCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
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
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->accountCollectionFactory = $accountCollectionFactory;
    }

    protected function getSynchronizationLog(): \Ess\M2ePro\Model\Synchronization\Log
    {
        $synchronizationLog = parent::getSynchronizationLog();

        $synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Walmart::NICK);
        $synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_ORDERS);

        return $synchronizationLog;
    }

    // ----------------------------------------

    protected function performActions()
    {
        $permittedAccounts = $this->getPermittedAccounts();
        if (empty($permittedAccounts)) {
            return;
        }

        $this->getSynchronizationLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);

        foreach ($permittedAccounts as $account) {
            $this->getOperationHistory()->addText('Starting Account "' . $account->getTitle() . '"');

            try {
                $this->processAccount($account);
            } catch (\Exception $exception) {
                $message = __(
                    'The "Reserve Cancellation" Action for Walmart Account "%account" was completed with error.',
                    ['account' => $account->getTitle()]
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }
        }
    }

    // ----------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Account[]
     */
    private function getPermittedAccounts(): array
    {
        $accountsCollection = $this->accountCollectionFactory->createWithWalmartChildMode();

        return $accountsCollection->getItems();
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        foreach ($this->getOrdersForRelease($account) as $order) {
            $order->getReserve()->release();
        }
    }

    /**
     * @return \Ess\M2ePro\Model\Order[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Exception
     */
    private function getOrdersForRelease(\Ess\M2ePro\Model\Account $account): array
    {
        $collection = $this->orderCollectionFactory
            ->createWithWalmartChildMode()
            ->addFieldToFilter('account_id', $account->getId())
            ->addFieldToFilter('reservation_state', \Ess\M2ePro\Model\Order\Reserve::STATE_PLACED);

        /** @var \Ess\M2ePro\Model\Walmart\Account $walmartAccount */
        $walmartAccount = $account->getChildObject();
        $reservationDays = $walmartAccount->getQtyReservationDays();

        $minReservationStartDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();
        $minReservationStartDate->modify("- $reservationDays days");
        $minReservationStartDate = $minReservationStartDate->format('Y-m-d H:i');

        $collection->addFieldToFilter('reservation_start_date', ['lteq' => $minReservationStartDate]);

        return $collection->getItems();
    }
}
