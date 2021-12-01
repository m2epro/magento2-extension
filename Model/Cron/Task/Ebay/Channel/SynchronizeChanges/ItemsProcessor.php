<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Channel\SynchronizeChanges;

use \Ess\M2ePro\Model\Cron\Task\Ebay\Channel\SynchronizeChanges\ItemsProcessor\StatusResolver;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Ebay\Channel\SynchronizeChanges\ItemsProcessor
 */
class ItemsProcessor extends \Ess\M2ePro\Model\AbstractModel
{
    const INSTRUCTION_INITIATOR = 'channel_changes_synchronization';

    const INCREASE_SINCE_TIME_MAX_ATTEMPTS     = 10;
    const INCREASE_SINCE_TIME_BY               = 2;
    const INCREASE_SINCE_TIME_MIN_INTERVAL_SEC = 10;

    protected $logsActionId = null;

    /** @var \Ess\M2ePro\Model\Synchronization\ */
    protected $synchronizationLog = null;

    protected $receiveChangesToDate = null;

    protected $ebayFactory;
    protected $activeRecordFactory;

    //####################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->ebayFactory = $ebayFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //####################################

    public function setSynchronizationLog(\Ess\M2ePro\Model\Synchronization\Log $log)
    {
        $this->synchronizationLog = $log;
        return $this;
    }

    public function setReceiveChangesToDate($toDate)
    {
        $this->receiveChangesToDate = $toDate;
        return $this;
    }

    //####################################

    public function process()
    {
        $accounts = $this->ebayFactory->getObject('Account')->getCollection()->getItems();

        foreach ($accounts as $account) {
            try {
                $this->processAccount($account);
            } catch (\Exception $e) {
                $this->getHelper('Module\Exception')->process($e);
                $this->synchronizationLog->addMessageFromException($e);
            }
        }
    }

    // ---------------------------------------

    protected function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        $changesByAccount = $this->getChangesByAccount($account);

        if (!isset($changesByAccount['items']) || !isset($changesByAccount['to_time'])) {
            return;
        }

        $changesPerEbayItemId = [];

        foreach ($changesByAccount['items'] as $itemChange) {
            $changesPerEbayItemId[$itemChange['id']] = $itemChange;
        }

        foreach (array_chunk(array_keys($changesPerEbayItemId), 500) as $ebayItemIds) {

            /** @var $collection \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection */
            $collection = $this->ebayFactory->getObject('Listing_Product')->getCollection();
            $collection->getSelect()->join(
                ['mei' => $this->activeRecordFactory->getObject('Ebay\Item')->getResource()->getMainTable()],
                "(second_table.ebay_item_id = mei.id AND mei.account_id = {$account->getId()})",
                ['item_id']
            );
            $collection->addFieldToFilter('mei.item_id', ['in' => $ebayItemIds]);

            foreach ($collection->getItems() as $listingProduct) {
                /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
                $ebayListingProduct = $listingProduct->getChildObject();

                $change = $changesPerEbayItemId[$listingProduct->getData('item_id')];

                $isVariationOnChannel = !empty($change['variations']);
                $isVariationInMagento = $ebayListingProduct->isVariationsReady();

                if ($isVariationOnChannel != $isVariationInMagento) {
                    continue;
                }

                /** @var StatusResolver $statusResolver */
                $statusResolver = $this->modelFactory->getObject(
                    'Cron_Task_Ebay_Channel_SynchronizeChanges_ItemsProcessor_StatusResolver'
                );

                $isStatusResolved = $statusResolver->resolveStatus(
                    (int)$change['quantity'] <= 0 ? 0 : (int)$change['quantity'],
                    (int)$change['quantitySold'] <= 0 ? 0 : (int)$change['quantitySold'],
                    $change['listingStatus'],
                    $listingProduct
                );

                if (!$isStatusResolved) {
                    continue;
                }

                // @codingStandardsIgnoreLine
                $dataForUpdate = array_merge(
                    $this->getProductStatusChanges($listingProduct, $statusResolver),
                    $this->getProductDatesChanges($change),
                    $this->getProductQtyChanges($listingProduct, $change)
                );

                if (!$isVariationOnChannel || !$isVariationInMagento) {
                    // @codingStandardsIgnoreLine
                    $dataForUpdate = array_merge(
                        $dataForUpdate,
                        $this->getSimpleProductPriceChanges($listingProduct, $change)
                    );

                    $listingProduct->addData($dataForUpdate);
                    $listingProduct->getChildObject()->addData($dataForUpdate);
                    $listingProduct->save();
                } else {
                    $listingProductVariations = $listingProduct->getVariations(true);

                    $this->processVariationChanges($listingProduct, $listingProductVariations, $change['variations']);

                    // @codingStandardsIgnoreLine
                    $dataForUpdate = array_merge(
                        $dataForUpdate,
                        $this->getVariationProductPriceChanges($listingProduct, $listingProductVariations)
                    );

                    $oldListingProductStatus = $listingProduct->getStatus();

                    $listingProduct->addData($dataForUpdate);
                    $listingProduct->getChildObject()->addData($dataForUpdate);
                    $listingProduct->save();

                    if ($oldListingProductStatus != $listingProduct->getStatus()) {
                        $ebayListingProduct->updateVariationsStatus();
                    }
                }
            }
        }

        $account->getChildObject()->setData('inventory_last_synchronization', $changesByAccount['to_time'])->save();
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @return array
     * @throws \Exception
     */
    protected function getChangesByAccount(\Ess\M2ePro\Model\Account $account)
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $sinceTime = $this->prepareSinceTime($account->getChildObject()->getData('inventory_last_synchronization'));
        $toTime    = clone $now;

        if ($this->receiveChangesToDate !== null) {
            $toTime = $this->receiveChangesToDate;
            $toTime = new \DateTime($toTime, new \DateTimeZone('UTC'));

            if ($sinceTime->getTimestamp() >= $toTime->getTimestamp()) {
                $sinceTime = clone $toTime;
                $sinceTime->modify('- 1 minute');
            }
        }

        $toTime = $this->modifyToTimeConsideringOrderLastSynch($account, $toTime);

        $response = $this->receiveChangesFromEbay(
            $account,
            [
                'since_time' => $sinceTime->format('Y-m-d H:i:s'),
                'to_time'    => $toTime->format('Y-m-d H:i:s')
            ]
        );

        if ($response) {
            return (array)$response;
        }

        // -- to many changes are received. try to receive changes for the latest day
        $currentInterval = $toTime->diff($sinceTime);
        if ($currentInterval->days >= 1) {
            $sinceTime = clone $toTime;
            $sinceTime->modify('-1 day');

            $response = $this->receiveChangesFromEbay(
                $account,
                [
                    'since_time' => $sinceTime->format('Y-m-d H:i:s'),
                    'to_time'    => $toTime->format('Y-m-d H:i:s')
                ]
            );

            if ($response) {
                return (array)$response;
            }
        }

        // --

        // -- to many changes are received. increase the sinceData step by step by 2
        $iteration = 0;
        do {
            $iteration++;

            $offset = ceil(($toTime->getTimestamp() - $sinceTime->getTimestamp()) / self::INCREASE_SINCE_TIME_BY);
            $toTime->modify("-{$offset} seconds");

            $currentInterval = $toTime->getTimestamp() - $sinceTime->getTimestamp();

            if ($currentInterval < self::INCREASE_SINCE_TIME_MIN_INTERVAL_SEC ||
                $iteration > self::INCREASE_SINCE_TIME_MAX_ATTEMPTS) {
                $sinceTime = clone $now;
                $sinceTime->modify('-5 seconds');

                $toTime = clone $now;
            }

            $response = $this->receiveChangesFromEbay(
                $account,
                [
                    'since_time' => $sinceTime->format('Y-m-d H:i:s'),
                    'to_time'    => $toTime->format('Y-m-d H:i:s')
                ],
                $iteration
            );

            if ($response) {
                return (array)$response;
            }
        } while ($iteration <= self::INCREASE_SINCE_TIME_MAX_ATTEMPTS);
        // --

        return [];
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @param \DateTime $toTime
     * @return \DateTime
     * @throws \Exception
     *
     * Do not download inventory events until order will be imported to avoid excessive relist action
     */
    protected function modifyToTimeConsideringOrderLastSynch(\Ess\M2ePro\Model\Account $account, \DateTime $toTime)
    {
        $orderLastSynchDate = new \DateTime(
            $account->getChildObject()->getData('orders_last_synchronization'),
            new \DateTimeZone('UTC')
        );

        if ($orderLastSynchDate->getTimestamp() < $toTime->getTimestamp()) {
            return $orderLastSynchDate;
        }

        return $toTime;
    }

    //########################################

    protected function receiveChangesFromEbay(
        \Ess\M2ePro\Model\Account $account,
        array $paramsConnector = [],
        $tryNumber = 0
    ) {
        $dispatcherObj = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
        $connectorObj = $dispatcherObj->getVirtualConnector(
            'inventory',
            'get',
            'events',
            $paramsConnector,
            null,
            null,
            $account->getId()
        );

        $dispatcherObj->process($connectorObj);
        $this->processResponseMessages($connectorObj->getResponseMessages());

        $responseData = $connectorObj->getResponseData();

        if (!isset($responseData['items']) || !isset($responseData['to_time'])) {
            $logData = [
                'params'            => $paramsConnector,
                'account_id'        => $account->getId(),
                'response_data'     => $responseData,
                'response_messages' => $connectorObj->getResponseMessages()
            ];
            $this->getHelper('Module\Logger')->process($logData, "ebay no changes received - #{$tryNumber} try");

            return null;
        }

        return $responseData;
    }

    protected function processResponseMessages(array $messages)
    {
        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message\Set $messagesSet */
        $messagesSet = $this->modelFactory->getObject('Connector_Connection_Response_Message_Set');
        $messagesSet->init($messages);

        foreach ($messagesSet->getEntities() as $message) {
            if ($message->getCode() == 21917062) {
                continue;
            }

            if (!$message->isError() && !$message->isWarning()) {
                continue;
            }

            $logType = $message->isError() ? \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
                : \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING;

            $this->synchronizationLog->addMessage(
                $this->getHelper('Module\Translation')->__($message->getText()),
                $logType
            );
        }
    }

    //########################################

    protected function getProductDatesChanges(array $change)
    {
        return [
            'start_date' => $this->getHelper('Component\Ebay')->timeToString($change['startTime']),
            'end_date' => $this->getHelper('Component\Ebay')->timeToString($change['endTime'])
        ];
    }

    protected function getProductStatusChanges(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        StatusResolver $statusResolver
    ) {
        $data = [];
        $data['status'] = $statusResolver->getProductStatus();

        if ($onlineDuration = $statusResolver->getOnlineDuration()) {
            $data['online_duration'] = $onlineDuration;
        }

        if ($additionalData = $statusResolver->getProductAdditionalData()) {
            $data['additional_data'] = $additionalData;
        }

        if ($listingProduct->getStatus() == $data['status']) {
            return $data;
        }

        $data['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT;

        $statusChangedFrom = $this->getHelper('Component\Ebay')
            ->getHumanTitleByListingProductStatus($listingProduct->getStatus());
        $statusChangedTo = $this->getHelper('Component\Ebay')
            ->getHumanTitleByListingProductStatus($data['status']);

        if (!empty($statusChangedFrom) && !empty($statusChangedTo)) {
            $this->logReportChange(
                $listingProduct,
                $this->getHelper('Module\Translation')->__(
                    'Item Status was changed from "%from%" to "%to%" .',
                    $statusChangedFrom,
                    $statusChangedTo
                )
            );
        }

        $this->addInstruction(
            $listingProduct,
            \Ess\M2ePro\Model\Ebay\Listing\Product::INSTRUCTION_TYPE_CHANNEL_STATUS_CHANGED,
            80
        );

        return $data;
    }

    protected function getProductQtyChanges(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $change)
    {
        $data = [];

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $data['online_qty'] = (int)$change['quantity'] < 0 ? 0 : (int)$change['quantity'];
        $data['online_qty_sold'] = (int)$change['quantitySold'] < 0 ? 0 : (int)$change['quantitySold'];

        if ($ebayListingProduct->isVariationsReady()) {
            return $data;
        }

        $listingType = $this->getActualListingType($listingProduct, $change);

        if ($listingType == \Ess\M2ePro\Model\Ebay\Template\SellingFormat::LISTING_TYPE_AUCTION) {
            $data['online_qty'] = 1;
            $data['online_bids'] = (int)$change['bidCount'] < 0 ? 0 : (int)$change['bidCount'];
        }

        if ($ebayListingProduct->getOnlineQty() != $data['online_qty'] ||
            $ebayListingProduct->getOnlineQtySold() != $data['online_qty_sold']) {
            $this->logReportChange(
                $listingProduct,
                $this->getHelper('Module\Translation')->__(
                    'Item QTY was changed from %from% to %to% .',
                    ($ebayListingProduct->getOnlineQty() - $ebayListingProduct->getOnlineQtySold()),
                    ($data['online_qty'] - $data['online_qty_sold'])
                )
            );

            $this->addInstruction(
                $listingProduct,
                \Ess\M2ePro\Model\Ebay\Listing\Product::INSTRUCTION_TYPE_CHANNEL_QTY_CHANGED,
                80
            );
        }

        return $data;
    }

    // ---------------------------------------

    protected function getSimpleProductPriceChanges(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $change)
    {
        $data = [];

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        if ($ebayListingProduct->isVariationsReady()) {
            return $data;
        }

        $data['online_current_price'] = (float)$change['currentPrice'] < 0 ? 0 : (float)$change['currentPrice'];
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $listingType = $this->getActualListingType($listingProduct, $change);

        if ($listingType == \Ess\M2ePro\Model\Ebay\Template\SellingFormat::LISTING_TYPE_FIXED) {
            if ($ebayListingProduct->getOnlineCurrentPrice() != $data['online_current_price']) {
                $this->logReportChange(
                    $listingProduct,
                    $this->getHelper('Module\Translation')->__(
                        'Item Price was changed from %from% to %to% .',
                        $ebayListingProduct->getOnlineCurrentPrice(),
                        $data['online_current_price']
                    )
                );

                $this->addInstruction(
                    $listingProduct,
                    \Ess\M2ePro\Model\Ebay\Listing\Product::INSTRUCTION_TYPE_CHANNEL_PRICE_CHANGED,
                    60
                );
            }
        }

        return $data;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param \Ess\M2ePro\Model\Listing\Product\Variation[] $variations
     * @return array
     */
    protected function getVariationProductPriceChanges(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        array $variations
    ) {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $calculateWithEmptyQty = $ebayListingProduct->isOutOfStockControlEnabled();

        $onlineCurrentPrice  = null;

        foreach ($variations as $variation) {

            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Variation $ebayVariation */
            $ebayVariation = $variation->getChildObject();

            if (!$calculateWithEmptyQty && $ebayVariation->getOnlineQty() <= 0) {
                continue;
            }

            if ($onlineCurrentPrice !== null && $ebayVariation->getOnlinePrice() >= $onlineCurrentPrice) {
                continue;
            }

            $onlineCurrentPrice = $ebayVariation->getOnlinePrice();
        }

        return ['online_current_price' => $onlineCurrentPrice];
    }

    //########################################

    protected function processVariationChanges(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        array $listingProductVariations,
        array $changeVariations
    ) {
        $variationsSnapshot = $this->getVariationsSnapshot($listingProductVariations);
        if (empty($variationsSnapshot)) {
            return;
        }

        $hasVariationPriceChanges = false;
        $hasVariationQtyChanges   = false;

        foreach ($changeVariations as $changeVariation) {
            foreach ($variationsSnapshot as $variationSnapshot) {
                if (!$this->isVariationEqualWithChange($listingProduct, $changeVariation, $variationSnapshot)) {
                    continue;
                }

                $updateData = [
                    'online_price' => (float)$changeVariation['price'] < 0 ? 0 : (float)$changeVariation['price'],
                    'online_qty' => (int)$changeVariation['quantity'] < 0 ? 0 : (int)$changeVariation['quantity'],
                    'online_qty_sold' => (int)$changeVariation['quantitySold'] < 0 ?
                        0 : (int)$changeVariation['quantitySold']
                ];

                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Variation $ebayVariation */
                $ebayVariation = $variationSnapshot['variation']->getChildObject();

                $isVariationChanged = false;

                if ($ebayVariation->getOnlinePrice() != $updateData['online_price']) {
                    $hasVariationPriceChanges = true;
                    $isVariationChanged       = true;
                }

                if ($ebayVariation->getOnlineQty() != $updateData['online_qty'] ||
                    $ebayVariation->getOnlineQtySold() != $updateData['online_qty_sold']) {
                    $hasVariationQtyChanges = true;
                    $isVariationChanged     = true;
                }

                if ($isVariationChanged) {
                    $variationSnapshot['variation']->addData($updateData);
                    $variationSnapshot['variation']->getChildObject()->addData($updateData);
                    $variationSnapshot['variation']->getChildObject()->setStatus($listingProduct->getStatus());
                }

                break;
            }
        }

        if ($hasVariationPriceChanges) {
            $this->logReportChange(
                $listingProduct,
                $this->getHelper('Module\Translation')->__(
                    'Price of some Variations was changed.'
                )
            );

            $this->addInstruction(
                $listingProduct,
                \Ess\M2ePro\Model\Ebay\Listing\Product::INSTRUCTION_TYPE_CHANNEL_PRICE_CHANGED,
                60
            );
        }

        if ($hasVariationQtyChanges) {
            $this->logReportChange(
                $listingProduct,
                $this->getHelper('Module\Translation')->__(
                    'QTY of some Variations was changed.'
                )
            );

            $this->addInstruction(
                $listingProduct,
                \Ess\M2ePro\Model\Ebay\Listing\Product::INSTRUCTION_TYPE_CHANNEL_QTY_CHANGED,
                80
            );
        }
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product\Variation[] $variations
     * @return array
     */
    protected function getVariationsSnapshot(array $variations)
    {
        $variationIds = [];
        foreach ($variations as $variation) {
            $variationIds[] = $variation->getId();
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Variation\Option\Collection $optionCollection */
        $optionCollection = $this->ebayFactory->getObject('Listing_Product_Variation_Option')->getCollection();
        $optionCollection->addFieldToFilter('listing_product_variation_id', ['in' => $variationIds]);

        $snapshot = [];

        foreach ($variations as $variation) {
            $options = $optionCollection->getItemsByColumnValue('listing_product_variation_id', $variation->getId());

            if (empty($options)) {
                continue;
            }

            $snapshot[] = [
                'variation' => $variation,
                'options'   => $options
            ];
        }

        return $snapshot;
    }

    protected function isVariationEqualWithChange(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        array $changeVariation,
        array $variationSnapshot
    ) {
        if (count($variationSnapshot['options']) != count($changeVariation['specifics'])) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();
        $specificsReplacements = $ebayListingProduct->getVariationSpecificsReplacements();

        foreach ($variationSnapshot['options'] as $variationSnapshotOption) {
            /** @var \Ess\M2ePro\Model\Listing\Product\Variation\Option $variationSnapshotOption */

            $variationSnapshotOptionName  = trim($variationSnapshotOption->getData('attribute'));
            $variationSnapshotOptionValue = trim($variationSnapshotOption->getData('option'));

            if (array_key_exists($variationSnapshotOptionName, $specificsReplacements)) {
                $variationSnapshotOptionName = $specificsReplacements[$variationSnapshotOptionName];
            }

            $haveOption = false;

            foreach ($changeVariation['specifics'] as $changeVariationOption => $changeVariationValue) {
                if ($variationSnapshotOptionName == trim($changeVariationOption) &&
                    $variationSnapshotOptionValue == trim($changeVariationValue)) {
                    $haveOption = true;
                    break;
                }
            }

            if ($haveOption === false) {
                return false;
            }
        }

        return true;
    }

    //########################################

    protected function prepareSinceTime($sinceTime)
    {
        if (empty($sinceTime)) {
            $sinceTime = new \DateTime('now', new \DateTimeZone('UTC'));
            $sinceTime->modify('-5 seconds');

            return $sinceTime;
        }

        $minTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $minTime->modify('-5 days');

        $sinceTime = new \DateTime($sinceTime, new \DateTimeZone('UTC'));

        if ($sinceTime->getTimestamp() < $minTime->getTimestamp()) {
            return $minTime;
        }

        $maxSinceTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $maxSinceTime->modify('-1 minute');

        if ($sinceTime->getTimestamp() > $maxSinceTime->getTimestamp()) {
            return $maxSinceTime;
        }

        return $sinceTime;
    }

    // ---------------------------------------

    protected function getLogsActionId()
    {
        if ($this->logsActionId === null) {
            $this->logsActionId = $this->activeRecordFactory->getObject('Listing\Log')
                ->getResource()->getNextActionId();
        }

        return $this->logsActionId;
    }

    protected function getActualListingType(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $change)
    {
        $validEbayValues = [
            \Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder\General::LISTING_TYPE_AUCTION,
            \Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder\General::LISTING_TYPE_FIXED
        ];

        if (isset($change['listingType']) && in_array($change['listingType'], $validEbayValues)) {
            switch ($change['listingType']) {
                case \Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder\General::LISTING_TYPE_AUCTION:
                    $result = \Ess\M2ePro\Model\Ebay\Template\SellingFormat::LISTING_TYPE_AUCTION;
                    break;
                case \Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder\General::LISTING_TYPE_FIXED:
                    $result = \Ess\M2ePro\Model\Ebay\Template\SellingFormat::LISTING_TYPE_FIXED;
                    break;
            }
        } else {
            $result = $listingProduct->getChildObject()->getListingType();
        }

        return $result;
    }

    //########################################

    protected function addInstruction(\Ess\M2ePro\Model\Listing\Product $listingProduct, $type, $priority)
    {
        $instruction = $this->activeRecordFactory->getObject('Listing_Product_Instruction');
        $instruction->setData(
            [
                'listing_product_id' => $listingProduct->getId(),
                'component'          => \Ess\M2ePro\Helper\Component\Ebay::NICK,
                'type'               => $type,
                'initiator'          => self::INSTRUCTION_INITIATOR,
                'priority'           => $priority,
            ]
        );
        $instruction->save();
    }

    protected function logReportChange(\Ess\M2ePro\Model\Listing\Product $listingProduct, $logMessage)
    {
        if (empty($logMessage)) {
            return;
        }

        $log = $this->activeRecordFactory->getObject('Listing\Log');
        $log->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);

        $log->addProductMessage(
            $listingProduct->getListingId(),
            $listingProduct->getProductId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            $this->getLogsActionId(),
            \Ess\M2ePro\Model\Listing\Log::ACTION_CHANNEL_CHANGE,
            $logMessage,
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS
        );
    }

    //########################################
}
