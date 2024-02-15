<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Order;

class RetrieveFinalFee extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'ebay/order/retrieve_final_fee';

    /** @var int (in seconds) */
    protected $interval = 600;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Order\CollectionFactory */
    private $ebayOrderCollectionFactory;
    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registryManager;
    /** @var \Ess\M2ePro\Helper\Server\Maintenance */
    private $serverMaintenanceHelper;
    /** @var \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory */
    private $accountCollectionFactory;
    /** @var \Ess\M2ePro\Model\Ebay\Connector\Dispatcher */
    private $ebayDispatcher;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Ebay\Order\CollectionFactory $ebayOrderCollectionFactory,
        \Ess\M2ePro\Model\Registry\Manager $registryManager,
        \Ess\M2ePro\Helper\Server\Maintenance $serverMaintenanceHelper,
        \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory,
        \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $ebayDispatcher,
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

        $this->ebayOrderCollectionFactory = $ebayOrderCollectionFactory;
        $this->registryManager = $registryManager;
        $this->serverMaintenanceHelper = $serverMaintenanceHelper;
        $this->accountCollectionFactory = $accountCollectionFactory;
        $this->ebayDispatcher = $ebayDispatcher;
    }

    public function isPossibleToRun(): bool
    {
        if ($this->serverMaintenanceHelper->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    protected function performActions(): void
    {
        $accounts = $this->findAccountsForProcess();

        if (empty($accounts)) {
            return;
        }

        foreach ($accounts as $account) {
            $this->getOperationHistory()->addText('Starting account "' . $account->getTitle() . '"');

            $this->getOperationHistory()->addTimePoint(
                __METHOD__ . 'process' . $account->getId(),
                'Process account ' . $account->getTitle()
            );

            try {
                $this->processAccount($account);
            } catch (\Throwable $exception) {
                $message = (string)__(
                    'The "UpdateFinalFee" Action for eBay Account "%account" was completed with error.',
                    ['account' => $account->getTitle()]
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
    private function findAccountsForProcess(): array
    {
        $accountsCollection = $this->accountCollectionFactory->createWithEbayChildMode();

        $accounts = [];

        foreach ($accountsCollection->getItems() as $accountItem) {
            /** @var \Ess\M2ePro\Model\Account $accountItem */

            if (!$accountItem->getChildObject()->isFinalFeeUpdateEnabled()) {
                continue;
            }

            $accounts[] = $accountItem;
        }

        return $accounts;
    }

    private function processAccount(\Ess\M2ePro\Model\Account $account): void
    {
        $currentDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();

        $fromDate = $this->getLastProcessedToDate($account->getId());
        if ($fromDate === null) {
            $fromDate = clone $currentDate;
            $fromDate = $fromDate->setTime(0, 0);
        }

        [$feesDataByOrder, $toDate] = $this->receiveFinalFee($account, $fromDate, $currentDate);

        $this->setLastProcessedToDate($account->getId(), $toDate);

        if (empty($feesDataByOrder)) {
            return;
        }

        $this->updateFinalFees($feesDataByOrder);
    }

    private function receiveFinalFee(
        \Ess\M2ePro\Model\Account $account,
        \DateTime $fromDate,
        \DateTime $toDate
    ): array {
        /** @var \Ess\M2ePro\Model\Ebay\Connector\Order\Get\Fees $connectorObj */
        $connectorObj = $this->ebayDispatcher->getConnector(
            'order',
            'get',
            'fees',
            [
                'account' => $account->getChildObject()->getServerHash(),
                'from_date' => $fromDate->format('Y-m-d H:i:s'),
                'to_date' => $toDate->format('Y-m-d H:i:s'),
            ]
        );

        $this->ebayDispatcher->process($connectorObj);

        $responseData = $connectorObj->getResponseData();
        $feesByOrders = [];

        foreach ($responseData['orders'] as $orderData) {
            $feesByOrders[$orderData['id']] = $this->calculateTotalFee($orderData['items']);
        }

        $toDate = \Ess\M2ePro\Helper\Date::createDateGmt($responseData['to_date']);

        return [$feesByOrders, $toDate];
    }

    private function calculateTotalFee(array $orderItems): float
    {
        $totalFees = 0;

        foreach ($orderItems as $orderItem) {
            foreach ($orderItem['fees'] as $fee) {
                $totalFees += $fee['value'];
            }
        }

        return abs($totalFees);
    }

    private function updateFinalFees(array $feesByOrder): void
    {
        $ebayOrdersIds = array_keys($feesByOrder);

        $ebayOrdersCollection = $this->ebayOrderCollectionFactory->create();
        $ebayOrdersCollection->addFieldToFilter('ebay_order_id', ['in' => $ebayOrdersIds]);

        /** @var \Ess\M2ePro\Model\Ebay\Order $ebayOrder */
        foreach ($ebayOrdersCollection->getItems() as $ebayOrder) {
            $ebayOrderId = $ebayOrder->getEbayOrderId();
            $finalFee = $feesByOrder[$ebayOrderId];

            if ($ebayOrder->getFinalFee() !== $finalFee) {
                $ebayOrder->setFinalFee($finalFee);
                $ebayOrder->save();
            }
        }
    }

    private function getLastProcessedToDate(string $accountId): ?\DateTime
    {
        $lastProcessedToDate = $this->registryManager->getValue("/ebay/orders/get/fees/{$accountId}/to_date/");

        if ($lastProcessedToDate !== null) {
            $lastProcessedToDate = \Ess\M2ePro\Helper\Date::createDateGmt($lastProcessedToDate);
        }

        return $lastProcessedToDate;
    }

    private function setLastProcessedToDate(string $accountId, \DateTime $toDate): void
    {
        $this->registryManager->setValue(
            "/ebay/orders/get/fees/{$accountId}/to_date/",
            $toDate->format('Y-m-d H:i:s')
        );
    }
}
