<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData;

use Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\AfnQty\ProcessingRunner as Runner;
use Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\AfnQty\MerchantManager as MerchantManager;
use Ess\M2ePro\Model\ResourceModel\Amazon\Account\Collection as AmazonAccountCollection;

class AfnQty extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'amazon/listing/product/channel/synchronize_data/afn_qty';
    private const MERCHANT_INTERVAL = 14400; // 4 hours

    /** @var int (in seconds) */
    protected $interval = 600;
    /** @var \Ess\M2ePro\Helper\Server\Maintenance */
    private $serverMaintenanceHelper;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translationHelper;
    /** @var \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\AfnQty\MerchantManager */
    private $merchantManager;
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

    /**
     * @param \Ess\M2ePro\Helper\Server\Maintenance $serverMaintenanceHelper
     * @param \Ess\M2ePro\Helper\Module\Translation $translationHelper
     * @param MerchantManager $merchantManager
     * @param \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $amazonConnectorDispatcher
     * @param \Ess\M2ePro\Model\ResourceModel\Amazon\Account\CollectionFactory $amazonAccountCollectionFactory
     * @param \Ess\M2ePro\Model\ResourceModel\Listing $listingResource
     * @param \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource
     * @param \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product $amazonListingProductResource
     * @param \Ess\M2ePro\Model\ResourceModel\Listing\Other $listingOtherResource
     * @param \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Other $amazonListingOtherResource
     * @param \Ess\M2ePro\Helper\Data $helperData
     * @param \Magento\Framework\Event\Manager $eventManager
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo
     * @param \Magento\Framework\App\ResourceConnection $resource
     *
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function __construct(
        \Ess\M2ePro\Helper\Server\Maintenance $serverMaintenanceHelper,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper,
        MerchantManager $merchantManager,
        \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $amazonConnectorDispatcher,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Account\CollectionFactory $amazonAccountCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing $listingResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product $amazonListingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Other $listingOtherResource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Other $amazonListingOtherResource,
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
        $this->merchantManager = $merchantManager;
        $this->merchantManager->init();
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
        $merchantsIds = $this->merchantManager->getMerchantsIds();
        if (empty($merchantsIds)) {
            return;
        }

        foreach ($merchantsIds as $merchantId) {
            $this->getOperationHistory()->addText("Starting Merchant \"$merchantId\"");
            if (
                $this->isLockedMerchant($merchantId)
                || !$this->merchantManager->isIntervalExceeded($merchantId, self::MERCHANT_INTERVAL)
            ) {
                continue;
            }

            $this->getOperationHistory()->addTimePoint(
                __METHOD__ . 'process' . $merchantId,
                "Process Merchant $merchantId"
            );

            try {
                $this->processMerchant($merchantId);
            } catch (\Exception $exception) {
                $message = 'The "Get AFN Qty" Action for Amazon Merchant "%merchant%" was completed with error.';
                $message = $this->translationHelper->__($message, $merchantId);

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            $this->getOperationHistory()->saveTimePoint(__METHOD__ . 'process' . $merchantId);
        }
    }

    /**
     * @param string $merchantId
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic|\Magento\Framework\Exception\LocalizedException
     */
    protected function processMerchant(string $merchantId): void
    {
        $merchantAccountsIds = $this->merchantManager->getMerchantAccountsIds($merchantId);
        if (
            $this->isM2eProListingsHaveAfnProducts($merchantAccountsIds)
            || $this->isUnmanagedListingsHaveAfnProducts($merchantAccountsIds)
        ) {
            $someAccount = $this->merchantManager->getMerchantAccount($merchantId);
            /** @var AfnQty\Requester $connectorObj */
            $connectorObj = $this->amazonConnectorDispatcher->getCustomConnector(
                'Cron_Task_Amazon_Listing_Product_Channel_SynchronizeData_AfnQty_Requester',
                ['merchant_id' => $merchantId],
                $someAccount
            );
            $this->amazonConnectorDispatcher->process($connectorObj);
        }
    }

    /**
     * @param array $merchantAccountsIds
     *
     * @return \Ess\M2ePro\Model\ResourceModel\Amazon\Account\Collection
     */
    private function getBaseCollectionForAfnProductsCheck(array $merchantAccountsIds): AmazonAccountCollection
    {
        $collection = $this->amazonAccountCollectionFactory->create();
        $collection->addFieldToFilter('main_table.account_id', ['in' => $merchantAccountsIds]);
        $collection->addFieldToFilter('is_afn_channel', 1);

        return $collection;
    }

    /**
     * @param array $merchantAccountsIds
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function isM2eProListingsHaveAfnProducts(array $merchantAccountsIds): bool
    {
        $collection = $this->getBaseCollectionForAfnProductsCheck($merchantAccountsIds);
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

        return (bool)$collection->getSize();
    }

    /**
     * @param array $merchantAccountsIds
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function isUnmanagedListingsHaveAfnProducts(array $merchantAccountsIds): bool
    {
        $collection = $this->getBaseCollectionForAfnProductsCheck($merchantAccountsIds);
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

        return (bool)$collection->getSize();
    }

    /**
     * @param string $merchantId
     *
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function isLockedMerchant(string $merchantId): bool
    {
        $lockItemNick = Runner::LOCK_ITEM_PREFIX . '_' . $merchantId;

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
}
