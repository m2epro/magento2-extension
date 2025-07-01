<?php

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData;

use Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\AfnQty\ProcessingRunner as Runner;
use Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\AfnQty\AccountUpdateIntervalManager;
use Ess\M2ePro\Model\ResourceModel\Amazon\Account\Collection as AmazonAccountCollection;

class AfnQty extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'amazon/listing/product/channel/synchronize_data/afn_qty';
    private const MERCHANT_INTERVAL = 7200; // 2 hours

    /** @var int (in seconds) */
    protected $interval = 600;
    /** @var \Ess\M2ePro\Helper\Server\Maintenance */
    private $serverMaintenanceHelper;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translationHelper;
    private AccountUpdateIntervalManager $accountUpdateIntervalManager;
    /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher */
    private $amazonConnectorDispatcher;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Account\CollectionFactory */
    private $amazonAccountCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing */
    private $listingResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product */
    private $listingProductResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product */
    private $amazonListingProductResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Other */
    private $listingOtherResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Other */
    private $amazonListingOtherResource;
    private \Ess\M2ePro\Model\Amazon\Account\Repository $accountRepository;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Account\Repository $accountRepository,
        \Ess\M2ePro\Helper\Server\Maintenance $serverMaintenanceHelper,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper,
        AccountUpdateIntervalManager $accountUpdateIntervalManager,
        \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $amazonConnectorDispatcher,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Account\CollectionFactory $amazonAccountCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing $listingResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product $amazonListingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Other $listingOtherResource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Other $amazonListingOtherResource,
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

        $this->accountRepository = $accountRepository;
        $this->serverMaintenanceHelper = $serverMaintenanceHelper;
        $this->translationHelper = $translationHelper;
        $this->accountUpdateIntervalManager = $accountUpdateIntervalManager;
        $this->amazonConnectorDispatcher = $amazonConnectorDispatcher;
        $this->amazonAccountCollectionFactory = $amazonAccountCollectionFactory;
        $this->listingResource = $listingResource;
        $this->listingProductResource = $listingProductResource;
        $this->amazonListingProductResource = $amazonListingProductResource;
        $this->listingOtherResource = $listingOtherResource;
        $this->amazonListingOtherResource = $amazonListingOtherResource;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isPossibleToRun(): bool
    {
        if ($this->serverMaintenanceHelper->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Log
     */
    protected function getSynchronizationLog(): \Ess\M2ePro\Model\Synchronization\Log
    {
        $synchronizationLog = parent::getSynchronizationLog();

        $synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_LISTINGS);

        return $synchronizationLog;
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Exception
     */
    protected function performActions()
    {
        $accountList = $this->accountRepository->findAllWithEnabledFbaInventory();
        if (empty($accountList)) {
            return;
        }

        foreach ($accountList as $account) {
            $this->processAccountIfEligible($account);
        }
    }

    private function processAccountIfEligible(\Ess\M2ePro\Model\Amazon\Account $account): void
    {
        $accountId = $account->getId();
        $this->getOperationHistory()->addText("Starting Merchant Account \"$accountId\"");
        if ($this->canSkipAccountProcessing($accountId)) {
            return;
        }

        $this->processAccountIfHasAfnProducts($account);
    }

    private function canSkipAccountProcessing(int $accountId): bool
    {
        return $this->isLockedAccount($accountId)
            || !$this->accountUpdateIntervalManager->isIntervalExceeded($accountId, self::MERCHANT_INTERVAL);
    }

    private function processAccountIfHasAfnProducts(\Ess\M2ePro\Model\Amazon\Account $account): void
    {
        $accountId = $account->getId();
        if ($this->hasAfnProducts($accountId)) {
            $this->getOperationHistory()->addTimePoint(
                __METHOD__ . 'process' . $accountId,
                "Process Merchant Account $accountId"
            );

            $this->processAccount($account);

            $this->getOperationHistory()->saveTimePoint(
                __METHOD__ . 'process' . $accountId
            );
        }
    }

    private function hasAfnProducts(int $accountId): bool
    {
        return $this->isM2eProListingsHaveAfnProducts($accountId)
            || $this->isUnmanagedListingsHaveAfnProducts($accountId);
    }

    private function getBaseCollectionForAfnProductsCheck(int $merchantAccountId): AmazonAccountCollection
    {
        $collection = $this->amazonAccountCollectionFactory->create();
        $collection->addFieldToFilter('main_table.account_id', $merchantAccountId);
        $collection->addFieldToFilter('is_afn_channel', 1);

        return $collection;
    }

    private function isM2eProListingsHaveAfnProducts(int $accountId): bool
    {
        $collection = $this->getBaseCollectionForAfnProductsCheck($accountId);
        $collection->joinInner(
            [
                'l' => $this->listingResource->getMainTable(),
            ],
            'l.account_id=main_table.account_id',
            []
        );
        $collection->joinInner(
            [
                'lp' => $this->listingProductResource->getMainTable(),
            ],
            'lp.listing_id=l.id',
            []
        );
        $collection->joinInner(
            [
                'alp' => $this->amazonListingProductResource->getMainTable(),
            ],
            'alp.listing_product_id=lp.id',
            []
        );

        $collection->getSelect()->limit(1);

        return (bool)$collection->getSize();
    }

    private function isUnmanagedListingsHaveAfnProducts(int $accountId): bool
    {
        $collection = $this->getBaseCollectionForAfnProductsCheck($accountId);
        $collection->joinInner(
            [
                'lo' => $this->listingOtherResource->getMainTable(),
            ],
            'lo.account_id=main_table.account_id',
            []
        );
        $collection->joinInner(
            [
                'alo' => $this->amazonListingOtherResource->getMainTable(),
            ],
            'alo.listing_other_id=lo.id',
            []
        );

        $collection->getSelect()->limit(1);

        return (bool)$collection->getSize();
    }

    private function isLockedAccount(int $accountId): bool
    {
        $lockItemNick = Runner::LOCK_ITEM_PREFIX . '_' . $accountId;

        /** @var \Ess\M2ePro\Model\Lock\Item\Manager $lockItemManager */
        $lockItemManager = $this->modelFactory->getObject(
            'Lock_Item_Manager',
            ['nick' => $lockItemNick]
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

    private function processAccount(\Ess\M2ePro\Model\Amazon\Account $account): void
    {
        try {
            /** @var AfnQty\Requester $connectorObj */
            $connectorObj = $this->amazonConnectorDispatcher->getConnectorByClass(
                \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\AfnQty\Requester::class,
                ['merchant_id' => $account->getMerchantId()],
                $account->getParentObject()
            );
            $this->amazonConnectorDispatcher->process($connectorObj);
        } catch (\Exception $exception) {
            $message = 'The "Get AFN Qty" Action for Amazon Merchant Account "%account%" was completed with error.';
            $message = $this->translationHelper->__($message, $account->getAccountId());

            $this->processTaskAccountException($message, __FILE__, __LINE__);
            $this->processTaskException($exception);
        }
    }
}
