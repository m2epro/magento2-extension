<?php

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order\Get;

class Fees extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'amazon/order/get/fees';

    /** @var int (in seconds) */
    protected $interval = 600;
    /** @var \Ess\M2ePro\Helper\Server\Maintenance */
    private $serverMaintenanceHelper;
    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registryManager;
    /** @var \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory */
    private $accountCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Order\CollectionFactory */
    private $amazonOrderCollectionFactory;
    /** @var \Ess\M2ePro\Model\Amazon\Connector\DispatcherFactory */
    private $amazonConnectorDispatcherFactory;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Connector\DispatcherFactory $amazonConnectorDispatcherFactory,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Order\CollectionFactory $amazonOrderCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory,
        \Ess\M2ePro\Model\Registry\Manager $registryManager,
        \Ess\M2ePro\Helper\Server\Maintenance $serverMaintenanceHelper,
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
        $this->registryManager = $registryManager;
        $this->accountCollectionFactory = $accountCollectionFactory;
        $this->amazonOrderCollectionFactory = $amazonOrderCollectionFactory;
        $this->amazonConnectorDispatcherFactory = $amazonConnectorDispatcherFactory;
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
        $permittedAccounts = $this->getPermittedAccounts();

        if (empty($permittedAccounts)) {
            return;
        }

        $currentDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();

        foreach ($permittedAccounts as $merchantId => $account) {
            $lastProcessedToDate = $this->getLastProcessedToDate($merchantId);

            if ($lastProcessedToDate === null) {
                $fromDate = clone $currentDate;

                $fromDate = $fromDate->setTime(0, 0);
            } else {
                $fromDate = $lastProcessedToDate;
            }

            $responseData = $this->receiveFees($account, $fromDate, $currentDate);

            $this->setLastProcessedToDate($merchantId, $responseData['to_date']);

            if (empty($responseData['orders'])) {
                continue;
            }

            $feesByOrderId = $this->prepareResponseData($responseData);

            if (!empty($feesByOrderId)) {
                $this->updateFinalFees($feesByOrderId);
            }
        }
    }

    private function getPermittedAccounts(): array
    {
        $accountsCollection = $this->accountCollectionFactory->createWithAmazonChildMode();

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

    private function receiveFees(\Ess\M2ePro\Model\Account $account, \DateTime $fromDate, \DateTime $toDate): array
    {
        $dispatcher = $this->amazonConnectorDispatcherFactory->create();
        $connectorObj = $dispatcher->getVirtualConnector(
            'orders',
            'get',
            'fees',
            [
                'account' => $account->getChildObject()->getServerHash(),
                'from_date' => $fromDate->format('Y-m-d H:i:s'),
                'to_date' => $toDate->format('Y-m-d H:i:s'),
            ]
        );

        $dispatcher->process($connectorObj);

        return $connectorObj->getResponseData();
    }

    /**
     * @param array $responseData
     *
     * @return array
     *
     * Input array $responseData:
     * [
     *     'orders' => [
     *         [
     *             'id' => int,      // Order id
     *             'items' => [
     *                 [
     *                     'fees' => [
     *                         [
     *                             'name' => string,
     *                             'title' => string,
     *                             'value' => float,
     *                         ],
     *                         //  ... Other fees ...
     *                     ],
     *                 ],
     *                 // ... Other items ...
     *             ],
     *         ],
     *         // ... Other orders ...
     *     ],
     * ]
     *
     * Output array:
     * [
     *     int => [
     *         [
     *             'title' => string,
     *             'value' => float,
     *         ],
     *         // ... Other fees ...
     *     ],
     *     // ... Other orders ...
     * ]
     */
    private function prepareResponseData(array $responseData): array
    {
        $preparedData = [];

        foreach ($responseData['orders'] as $order) {
            $fees = [];
            foreach ($order['items'] as $item) {
                foreach ($item['fees'] as $fee) {
                    $name = $fee['name'];
                    $title = $fee['title'];
                    $value = $fee['value'];

                    if (!isset($fees[$name])) {
                        $fees[$name] = [
                            'title' => $title,
                            'value' => 0
                        ];
                    }

                    $fees[$name]['value'] += $value;
                }
            }

            $preparedData[$order['id']] = array_values($fees);
        }

        return $preparedData;
    }

    private function updateFinalFees(array $feesByOrderId): void
    {
        $amazonOrdersIds = array_keys($feesByOrderId);

        $amazonOrdersCollection = $this->amazonOrderCollectionFactory->create();
        $amazonOrdersCollection->addFieldToFilter('amazon_order_id', ['in' => $amazonOrdersIds]);

        /** @var \Ess\M2ePro\Model\Amazon\Order $amazonOrder */
        foreach ($amazonOrdersCollection->getItems() as $amazonOrder) {
            $amazonOrderId = $amazonOrder->getAmazonOrderId();
            $responseFinalFees = $feesByOrderId[$amazonOrderId];

            if ($amazonOrder->isNewFinalFees($responseFinalFees)) {
                $amazonOrder->setFinalFees($responseFinalFees);
                $amazonOrder->save();

                $this->eventManager->dispatch('ess_amazon_order_fees_save_after', [
                    'magento_order_id' => $amazonOrder->getParentObject()->getMagentoOrderId(),
                    'amazon_order_id' => $amazonOrderId,
                    'final_fees' => $responseFinalFees,
                ]);
            }
        }
    }

    private function getLastProcessedToDate(string $merchantId): ?\DateTime
    {
        $lastProcessedToDate = $this->registryManager->getValue("/amazon/orders/get/fees/{$merchantId}/to_date/");

        if ($lastProcessedToDate !== null) {
            $lastProcessedToDate = \Ess\M2ePro\Helper\Date::createDateGmt($lastProcessedToDate);
        }

        return $lastProcessedToDate;
    }

    private function setLastProcessedToDate(string $merchantId, string $toDate): void
    {
        $this->registryManager->setValue(
            "/amazon/orders/get/fees/{$merchantId}/to_date/",
            $toDate
        );
    }
}
