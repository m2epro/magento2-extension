<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive;

use Ess\M2ePro\Helper\Date as DateHelper;

class InvoiceData extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'amazon/order/receive/invoice_data';

    private const AVAILABLE_MARKETPLACE_IDS = [
        \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_UK,
        \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_DE,
        \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_FR,
        \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_IT,
        \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_ES,
        \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_PL,
        \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_SE,
    ];

    private const ORDER_LIMIT = 200;
    private const MERCHANT_RESEND_INTERVAL_IN_HOURS = 4;

    /** @var int $interval 15 minutes (in seconds) */
    protected $interval = 15 * 60;

    private \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory;
    private \Ess\M2ePro\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory;
    private \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $amazonConnectorDispatcher;
    /** @var \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive\InvoiceData\MerchantIntervalManager */
    private InvoiceData\MerchantIntervalManager $merchantIntervalManager;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $amazonConnectorDispatcher,
        \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive\InvoiceData\MerchantIntervalManager $merchantIntervalManager,
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
        $this->accountCollectionFactory = $accountCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->amazonConnectorDispatcher = $amazonConnectorDispatcher;
        $this->merchantIntervalManager = $merchantIntervalManager;
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Exception
     */
    public function performActions()
    {
        $permittedAccountAndOrders = $this->getPermittedAccounts();
        if (empty($permittedAccountAndOrders)) {
            return;
        }

        foreach ($permittedAccountAndOrders as $merchantId => $accounts) {
            if (
                !$this->merchantIntervalManager->isLastRunLessThenHours(
                    $merchantId,
                    self::MERCHANT_RESEND_INTERVAL_IN_HOURS
                )
            ) {
                continue;
            }

            $orders = $this->getOrdersByAccounts($accounts);
            if (empty($orders)) {
                continue;
            }

            $firstAccount = reset($accounts);

            $this->getOperationHistory()->addText('Starting account "' . $firstAccount->getTitle() . '"');
            $this->getOperationHistory()->addTimePoint(
                __METHOD__ . 'process' . $firstAccount->getId(),
                'Process account ' . $firstAccount->getTitle()
            );

            try {
                $this->processAccount($firstAccount, [
                    'from_date' => $this->getMinimumPurchaseCreateDate($orders)->format('Y-m-d H:i:s'),
                    'order_ids' => $this->getOrderIds($orders),
                ]);
            } catch (\Throwable $exception) {
                $message = __(
                    'The "Invoice Data" Action for Amazon Account "%account%" was completed with error.',
                    $firstAccount->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            $this->merchantIntervalManager->updateLastSendDate($merchantId);
        }
    }

    /**
     * @return array{string: \Ess\M2ePro\Model\Account[] }
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getPermittedAccounts(): array
    {
        $accountCollection = $this->accountCollectionFactory->createWithAmazonChildMode();
        $accountCollection->addFieldToFilter(
            'marketplace_id',
            ['in' => self::AVAILABLE_MARKETPLACE_IDS]
        );

        $accountsByMerchantId = [];
        /** @var \Ess\M2ePro\Model\Account $account */
        foreach ($accountCollection->getItems() as $account) {
            $merchantId = $account->getChildObject()->getMerchantId();
            $accountsByMerchantId[$merchantId][] = $account;
        }

        return $accountsByMerchantId;
    }

    /**
     * @param \Ess\M2ePro\Model\Account[] $accounts
     *
     * @return \Ess\M2ePro\Model\Order[]
     * @throws \Exception
     */
    private function getOrdersByAccounts(array $accounts): array
    {
        $orderCollection = $this->orderCollectionFactory->createWithAmazonChildMode();
        $orderCollection->addFieldToFilter('tax_registration_id', ['null' => true]);

        $accountIds = array_map(function (\Ess\M2ePro\Model\Account $account) {
            return (int)$account->getId();
        }, $accounts);
        $orderCollection->addFieldToFilter('account_id', ['in' => $accountIds]);

        $monthAgo = DateHelper::createCurrentGmt()->modify('-1 month')->format('Y-m-d H:i:s');
        $orderCollection->addFieldToFilter(
            'purchase_create_date',
            ['gteq' => $monthAgo]
        );

        $orderCollection->setOrder(
            'purchase_create_date',
            \Magento\Framework\Data\Collection::SORT_ORDER_ASC
        );

        $orderCollection->setPageSize(self::ORDER_LIMIT);

        return array_values($orderCollection->getItems());
    }

    private function processAccount(\Ess\M2ePro\Model\Account $account, array $requestParams): void
    {
        $connector = $this
            ->amazonConnectorDispatcher
            ->getConnectorByClass(
                \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive\InvoiceData\Requester::class,
                $requestParams,
                $account
            );

        $this->amazonConnectorDispatcher->process($connector);
    }

    /**
     * @param \Ess\M2ePro\Model\Order[] $orders
     *
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Exception
     */
    private function getMinimumPurchaseCreateDate(array $orders): \DateTime
    {
        $minimumPurchaseCreateDate = null;
        foreach ($orders as $order) {
            /** @var \Ess\M2ePro\Model\Amazon\Order $child */
            $child = $order->getChildObject();
            $purchaseCreateDate = DateHelper::createDateGmt(
                $child->getData(\Ess\M2ePro\Model\ResourceModel\Amazon\Order::COLUMN_PURCHASE_CREATE_DATE)
            );

            if (
                $minimumPurchaseCreateDate === null
                || $minimumPurchaseCreateDate->getTimestamp() > $purchaseCreateDate->getTimestamp()
            ) {
                $minimumPurchaseCreateDate = $purchaseCreateDate;
            }
        }

        return $minimumPurchaseCreateDate;
    }

    /**
     * @param \Ess\M2ePro\Model\Order[] $orders
     *
     * @return string[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getOrderIds(array $orders): array
    {
        return array_map(function (\Ess\M2ePro\Model\Order $order) {
            return (string)$order->getChildObject()->getAmazonOrderId();
        }, $orders);
    }
}
