<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\AfnQty;

use Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\AfnQty\MerchantManager as MerchantManager;

class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\AfnQty\ItemsResponser
{
    private const ERROR_CODE_UNACCEPTABLE_REPORT_STATUS = 504;

    /** @var ?\Ess\M2ePro\Model\Synchronization\Log */
    protected $synchronizationLog = null;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translationHelper;
    /** @var \Ess\M2ePro\Helper\Module\Logger */
    private $logger;
    /** @var \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\AfnQty\MerchantManager */
    private $merchantManager;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing */
    private $listingResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Account */
    private $amazonAccountResource;

    /**
     * @param \Ess\M2ePro\Helper\Module\Translation $translationHelper
     * @param \Ess\M2ePro\Helper\Module\Logger $logger
     * @param MerchantManager $merchantManager
     * @param \Ess\M2ePro\Model\ResourceModel\Listing $listingResource
     * @param \Ess\M2ePro\Model\ResourceModel\Amazon\Account $amazonAccountResource
     * @param \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory
     * @param \Ess\M2ePro\Model\Connector\Connection\Response $response
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     * @param array $params
     *
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module\Translation $translationHelper,
        \Ess\M2ePro\Helper\Module\Logger $logger,
        MerchantManager $merchantManager,
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
        $this->translationHelper = $translationHelper;
        $this->logger = $logger;
        $this->merchantManager = $merchantManager;
        $this->merchantManager->init();
        $this->listingResource = $listingResource;
        $this->amazonAccountResource = $amazonAccountResource;
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

        $merchantId = $this->merchantManager->getMerchantIdByAccountId((int)$this->params['account_id']);
        // $this->params['account_id'] is always available
        // next lines is for possible situation with deleted account
        if (!$merchantId) {
            $this->refreshLastUpdate(true);

            return;
        }

        $keys = array_map(
            function ($value) {
                return (string)$value;
            },
            array_keys($receivedItems)
        );

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
            ->where('aa.merchant_id = ? AND is_afn_channel = 1', $merchantId);

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
            ->where('aa.merchant_id = ? AND is_afn_channel = 1', $merchantId);

        /** @var \Ess\M2ePro\Model\Listing\Product $item */
        foreach ($m2eproListingProductCollection->getItems() as $item) {
            $this->updateItem(
                $item,
                $receivedItems[$item->getChildObject()->getSku()]
            );
        }

        /** @var \Ess\M2ePro\Model\Listing\Other $item */
        foreach ($unmanagedListingProductCollection->getItems() as $item) {
            $this->updateItem(
                $item,
                $receivedItems[$item->getChildObject()->getSku()]
            );
        }

        $this->refreshLastUpdate(true);
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
        $item->getChildObject()->setData('online_afn_qty', $afnQty);
        $item->setData(
            'status',
            $afnQty ?
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED : \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED
        );
        $item->save();
    }

    /**
     * @param bool $success
     *
     * @return void
     * @throws \Exception
     */
    private function refreshLastUpdate(bool $success): void
    {
        $merchantId = $this->merchantManager->getMerchantIdByAccountId((int)$this->params['account_id']);
        // $this->params['account_id'] is always available
        // next lines is for possible situation with deleted account
        if (!$merchantId) {
            return;
        }

        if ($success) {
            $this->merchantManager->setMerchantLastUpdateNow($merchantId);
        } else {
            $this->merchantManager->resetMerchantLastUpdate($merchantId);
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
}
