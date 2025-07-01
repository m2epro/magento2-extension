<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\AfnQty;

use Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel as ChangeProcessorAbstractModel;
use Ess\M2ePro\Model\Amazon\Account\FbaInventory\MagentoSourceUpdater;

class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\AfnQty\ItemsResponser
{
    private const ERROR_CODE_UNACCEPTABLE_REPORT_STATUS = 504;
    private const INSTRUCTION_INITIATOR = 'amazon_afn_qty_synchronization';

    /** @var ?\Ess\M2ePro\Model\Synchronization\Log */
    protected $synchronizationLog = null;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translationHelper;
    /** @var \Ess\M2ePro\Helper\Module\Logger */
    private $logger;
    private AccountUpdateIntervalManager $accountUpdateIntervalManager;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing */
    private $listingResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Account */
    private $amazonAccountResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction */
    private $instructionResource;
    /** @var array */
    private $instructionForCheckingProductData = [];
    /** @var \Ess\M2ePro\Model\Amazon\Account\FbaInventory\MagentoSourceUpdater */
    private $magentoSourceUpdater;
    /** @var \Ess\M2ePro\Model\Listing\Log */
    private $listingLogger;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Log */
    private $listingLoggerResource;
    private \Ess\M2ePro\Model\Amazon\Account\Repository $accountRepository;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Account\Repository $accountRepository,
        \Ess\M2ePro\Model\ResourceModel\Listing\Log $listingLoggerResource,
        \Ess\M2ePro\Model\Listing\Log $listingLogger,
        MagentoSourceUpdater $magentoSourceUpdater,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instructionResource,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper,
        \Ess\M2ePro\Helper\Module\Logger $logger,
        AccountUpdateIntervalManager $accountUpdateIntervalManager,
        \Ess\M2ePro\Model\ResourceModel\Listing $listingResource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Account $amazonAccountResource,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = []
    ) {
        parent::__construct(
            $response,
            $helperFactory,
            $modelFactory,
            $amazonFactory,
            $walmartFactory,
            $ebayFactory,
            $activeRecordFactory,
            $params
        );
        $this->listingLoggerResource = $listingLoggerResource;
        $this->listingLogger = $listingLogger;
        $this->translationHelper = $translationHelper;
        $this->logger = $logger;
        $this->accountUpdateIntervalManager = $accountUpdateIntervalManager;
        $this->listingResource = $listingResource;
        $this->amazonAccountResource = $amazonAccountResource;
        $this->instructionResource = $instructionResource;
        $this->magentoSourceUpdater = $magentoSourceUpdater;
        $this->accountRepository = $accountRepository;
    }

    /**
     * @return void|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Exception
     */
    protected function processResponseMessages()
    {
        parent::processResponseMessages();

        $isMessageReceived = false;
        foreach ($this->getResponse()->getMessages()->getEntities() as $message) {
            if (!$message->isError() && !$message->isWarning()) {
                continue;
            }

            $isMessageReceived = true;
            if ($message->getCode() == self::ERROR_CODE_UNACCEPTABLE_REPORT_STATUS) {
                $this->logger->process(
                    $this->translationHelper->__($message->getText()),
                    'Incorrect Amazon report'
                );

                continue;
            }

            $logType = $message->isError() ? \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
                : \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING;

            $this->getSynchronizationLog()->addMessage(
                $this->translationHelper->__($message->getText()),
                $logType
            );
        }

        if ($isMessageReceived) {
            $this->refreshLastUpdate(false);
        }
    }

    /**
     * @return bool
     */
    protected function isNeedProcessResponse(): bool
    {
        if (!parent::isNeedProcessResponse()) {
            return false;
        }

        if ($this->getResponse()->getMessages()->hasErrorEntities()) {
            return false;
        }

        return true;
    }

    /**
     * @param string $messageText
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function failDetected($messageText): void
    {
        parent::failDetected($messageText);

        $this->getSynchronizationLog()->addMessage(
            $this->translationHelper->__($messageText),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
        );
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Exception
     */
    protected function processResponseData(): void
    {
        $responseData = $this->getPreparedResponseData();
        $receivedItems = $responseData['data'];
        if (empty($receivedItems)) {
            $this->refreshLastUpdate(true);

            return;
        }

        $accountId = (int)$this->params['account_id'];
        if (!$this->isAccountExists($accountId)) {
            $this->refreshLastUpdate(true);

            return;
        }

        $keys = array_map('strval', array_column($receivedItems, 'sku'));

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $m2eproListingProductCollection */
        $m2eproListingProductCollection = $this->amazonFactory
            ->getObject('Listing_Product')
            ->getCollection();
        $m2eproListingProductCollection
            ->addFieldToFilter('sku', ['in' => $keys])
            ->getSelect()
            ->joinInner(
                [
                    'l' => $this->listingResource->getMainTable(),
                ],
                'l.id=main_table.listing_id',
                []
            )
            ->joinInner(
                [
                    'aa' => $this->amazonAccountResource->getMainTable(),
                ],
                'aa.account_id=l.account_id',
                []
            )
            ->where('is_afn_channel = 1')
            ->where('aa.account_id = ? ', $accountId);

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Other\Collection $unmanagedListingProductCollection */
        $unmanagedListingProductCollection = $this->amazonFactory
            ->getObject('Listing_Other')
            ->getCollection();
        $unmanagedListingProductCollection
            ->addFieldToFilter('sku', ['in' => $keys])
            ->getSelect()
            ->joinInner(
                [
                    'aa' => $this->amazonAccountResource->getMainTable(),
                ],
                'aa.account_id=main_table.account_id',
                []
            )
            ->where('is_afn_channel = 1')
            ->where('aa.account_id = ? ', $accountId);

        $normalizedReceivedItems = [];
        foreach ($receivedItems as $item) {
            $normalizedReceivedItems[strtolower($item['sku'])] = $item['qty'];
        }

        $this->magentoSourceUpdater->updateQty(
            $m2eproListingProductCollection->getItems(),
            $normalizedReceivedItems,
            $accountId
        );

        $this->updateItemsFromCollection($m2eproListingProductCollection, $normalizedReceivedItems);
        $this->updateItemsFromCollection($unmanagedListingProductCollection, $normalizedReceivedItems);

        $this->refreshLastUpdate(true);
        $this->instructionResource->add($this->instructionForCheckingProductData);
    }

    private function updateItemsFromCollection($collection, array $normalizedReceivedItems): void
    {
        foreach ($collection->getItems() as $item) {
            $sku = strtolower($item->getChildObject()->getSku());

            if (isset($normalizedReceivedItems[$sku])) {
                $this->updateItem(
                    $item,
                    $normalizedReceivedItems[$sku]
                );
            }
        }
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product|\Ess\M2ePro\Model\Listing\Other $item
     * @param int|string $afnQty
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function updateItem($item, $afnQty): void
    {
        $oldStatus = (int)$item->getData('status');
        $newStatus = $afnQty
            ? \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED :
            \Ess\M2ePro\Model\Listing\Product::STATUS_INACTIVE;

        $listingProduct = $item->getChildObject();
        $oldAfnQty = (int)$listingProduct->getOnlineAfnQty();

        if (
            $item instanceof \Ess\M2ePro\Model\Listing\Product
            && $afnQty != $oldAfnQty
        ) {
            $logMessage = __(
                'AFN Product QTY was changed from %1 to %2.',
                $oldAfnQty,
                $afnQty
            );
            $this->listingLogger->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
            $this->listingLogger->addProductMessage(
                $item->getListingId(),
                $item->getProductId(),
                $item->getId(),
                \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
                $this->listingLoggerResource->getNextActionId(),
                \Ess\M2ePro\Model\Listing\Log::ACTION_CHANNEL_CHANGE,
                $logMessage,
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS
            );
        }
        $item->getChildObject()->setData('online_afn_qty', $afnQty);
        $item->setData('status', $newStatus);
        $item->save();

        if (
            $item instanceof \Ess\M2ePro\Model\Listing\Product
            && $this->isStatusChangedFromInactiveToActive($oldStatus, $newStatus)
        ) {
            $this->addInstructionForCheckingProductData($item);
        }
    }

    private function isStatusChangedFromInactiveToActive(int $oldStatus, int $newStatus): bool
    {
        return $oldStatus === \Ess\M2ePro\Model\Listing\Product::STATUS_INACTIVE
            && $newStatus === \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;
    }

    /**
     * @param bool $success
     *
     * @return void
     * @throws \Exception
     */
    private function refreshLastUpdate(bool $success): void
    {
        $accountId = (int)$this->params['account_id'];

        if ($success) {
            $this->accountUpdateIntervalManager->setAccountLastUpdateNow($accountId);
        } else {
            $this->accountUpdateIntervalManager->resetAccountLastUpdate($accountId);
        }
    }

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Log
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getSynchronizationLog(): \Ess\M2ePro\Model\Synchronization\Log
    {
        if ($this->synchronizationLog !== null) {
            return $this->synchronizationLog;
        }

        $this->synchronizationLog = $this->activeRecordFactory->getObject('Synchronization\Log');
        $this->synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $this->synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_LISTINGS);

        return $this->synchronizationLog;
    }

    private function addInstructionForCheckingProductData(\Ess\M2ePro\Model\Listing\Product $item)
    {
        $this->instructionForCheckingProductData[] = [
            'listing_product_id' => $item->getId(),
            'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'type' => ChangeProcessorAbstractModel::INSTRUCTION_TYPE_PRODUCT_DATA_POTENTIALLY_CHANGED,
            'initiator' => self::INSTRUCTION_INITIATOR,
            'priority' => 100,
        ];
    }

    private function isAccountExists(int $accountId): bool
    {
        $account = $this->accountRepository->find($accountId);

        return (bool)$account;
    }
}
