<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\ListingsProducts;

/**
 * Class \Ess\M2ePro\Model\Ebay\Synchronization\ListingsProducts\Update
 */
class Update extends AbstractModel
{
    const EBAY_STATUS_ACTIVE    = 'Active';
    const EBAY_STATUS_ENDED     = 'Ended';
    const EBAY_STATUS_COMPLETED = 'Completed';

    const INCREASE_SINCE_TIME_MAX_ATTEMPTS     = 10;
    const INCREASE_SINCE_TIME_BY               = 2;
    const INCREASE_SINCE_TIME_MIN_INTERVAL_SEC = 10;

    private $logsActionId = null;

    private $listingsProductsLockStatus = [];

    private $listingsProductsIdsForNeedSynchRulesCheck = [];

    //########################################

    /**
     * @return string
     */
    protected function getNick()
    {
        return '/update/';
    }

    /**
     * @return string
     */
    protected function getTitle()
    {
        return 'Update Listings Products';
    }

    // ---------------------------------------

    /**
     * @return int
     */
    protected function getPercentsStart()
    {
        return 30;
    }

    /**
     * @return int
     */
    protected function getPercentsEnd()
    {
        return 100;
    }

    //########################################

    protected function performActions()
    {
        $accounts = $this->ebayFactory->getObject('Account')->getCollection()->getItems();

        if (count($accounts) <= 0) {
            return;
        }

        $iteration = 0;
        $percentsForOneStep = $this->getPercentsInterval() / count($accounts);

        foreach ($accounts as $account) {

            /** @var $account \Ess\M2ePro\Model\Account **/

            $this->getActualOperationHistory()->addText('Starting Account "'.$account->getTitle().'"');
            // M2ePro\TRANSLATIONS
            // The "Update Listings Products" Action for eBay Account: "%account_title%" is started. Please wait...
            $status = 'The "Update Listings Products" Action for eBay Account: "%account_title%" is started. ';
            $status .= 'Please wait...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $account->getTitle())
            );

            $this->getActualOperationHistory()->addTimePoint(
                __METHOD__.'process'.$account->getId(),
                'Process Account '.$account->getTitle()
            );

            try {
                $this->processAccount($account);
            } catch (\Exception $exception) {
                $message = $this->getHelper('Module\Translation')->__(
                    'The "Update Listings Products" Action for eBay Account: "%account%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'process'.$account->getId());

            // M2ePro\TRANSLATIONS
            // The "Update Listings Products" Action for eBay Account: "%account_title%" is finished. Please wait...
            $status = 'The "Update Listings Products" Action for eBay Account: "%account_title%" is finished.'.
                ' Please wait...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $account->getTitle())
            );
            $this->getActualLockItem()->setPercents($this->getPercentsStart() + $iteration * $percentsForOneStep);
            $this->getActualLockItem()->activate();

            $iteration++;
        }

        if (!empty($this->listingsProductsIdsForNeedSynchRulesCheck)) {
            $this->activeRecordFactory->getObject('Listing\Product')->getResource()
                ->setNeedSynchRulesCheck(
                    array_unique($this->listingsProductsIdsForNeedSynchRulesCheck)
                );
        }
    }

    // ---------------------------------------

    private function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        $changesByAccount = $this->getChangesByAccount($account);

        if (!isset($changesByAccount['items']) || !isset($changesByAccount['to_time'])) {
            return;
        }

        $this->getHelper('Data_Cache_Runtime')->setValue(
            'item_get_changes_data_' . $account->getId(),
            $changesByAccount
        );

        foreach ($changesByAccount['items'] as $change) {
            /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */

            $listingProduct = $this->getHelper('Component\Ebay')->getListingProductByEbayItem(
                $change['id'],
                $account->getId()
            );

            if ($listingProduct === null) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
            $ebayListingProduct = $listingProduct->getChildObject();

            $isVariationOnChannel = !empty($change['variations']);
            $isVariationInMagento = $ebayListingProduct->isVariationsReady();

            if ($isVariationOnChannel != $isVariationInMagento) {
                continue;
            }

            // Listing product isn't listed and it child must have another item_id
            if ($listingProduct->getStatus() != \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED &&
                $listingProduct->getStatus() != \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN) {
                continue;
            }

            $this->listingsProductsLockStatus[$listingProduct->getId()] =
                $listingProduct->isSetProcessingLock('in_action');

            $dataForUpdate = array_merge(
                $this->getProductDatesChanges($listingProduct, $change),
                $this->getProductStatusChanges($listingProduct, $change),
                $this->getProductQtyChanges($listingProduct, $change)
            );

            if (!$isVariationOnChannel || !$isVariationInMagento) {
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

                $dataForUpdate = array_merge(
                    $dataForUpdate,
                    $this->getVariationProductPriceChanges($listingProduct, $listingProductVariations)
                );

                $oldListingProductStatus = $listingProduct->getStatus();

                $listingProduct->addData($dataForUpdate);
                $listingProduct->getChildObject()->addData($dataForUpdate);
                $listingProduct->save();

                if ($oldListingProductStatus != $listingProduct->getStatus()) {
                    $listingProduct->getChildObject()->updateVariationsStatus();
                }
            }
        }

        $account->getChildObject()->setData('defaults_last_synchronization', $changesByAccount['to_time'])->save();
    }

    //########################################

    private function getChangesByAccount(\Ess\M2ePro\Model\Account $account)
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $sinceTime = $this->prepareSinceTime($account->getChildObject()->getData('defaults_last_synchronization'));
        $toTime    = clone $now;

        $operationHistory = $this->getActualOperationHistory()->getParentObject('synchronization');
        if ($operationHistory !== null) {
            $toTime = $operationHistory->getData('start_date');
            $toTime = new \DateTime($toTime, new \DateTimeZone('UTC'));

            if ($sinceTime->getTimestamp() >= $toTime->getTimestamp()) {
                $sinceTime = clone $toTime;
                $sinceTime->modify('- 1 minute');
            }
        }

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

    private function receiveChangesFromEbay(
        \Ess\M2ePro\Model\Account $account,
        array $paramsConnector = [],
        $tryNumber = 0
    ) {
        $dispatcherObj = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
        $connectorObj = $dispatcherObj->getVirtualConnector(
            'item',
            'get',
            'changes',
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
            $this->helperFactory->getObject('Module\Logger')->process(
                $logData,
                "ebay no changes received - #{$tryNumber} try"
            );

            return null;
        }

        return $responseData;
    }

    //########################################

    private function processResponseMessages(array $messages)
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

            $this->getLog()->addMessage(
                $this->getHelper('Module\Translation')->__($message->getText()),
                $logType,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );
        }
    }

    //########################################

    private function getProductDatesChanges(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $change)
    {
        return [
            'start_date' => $this->getHelper('Component\Ebay')->timeToString($change['startTime']),
            'end_date'   => $this->getHelper('Component\Ebay')->timeToString($change['endTime'])
        ];
    }

    private function getProductStatusChanges(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $change)
    {
        $data = [];

        $qty = (int)$change['quantity'] < 0 ? 0 : (int)$change['quantity'];
        $qtySold = (int)$change['quantitySold'] < 0 ? 0 : (int)$change['quantitySold'];

        if (($change['listingStatus'] == self::EBAY_STATUS_COMPLETED ||
             $change['listingStatus'] == self::EBAY_STATUS_ENDED) &&
             $listingProduct->getStatus() != \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN &&
             $qty == $qtySold) {
            $data['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD;
        } elseif ($change['listingStatus'] == self::EBAY_STATUS_COMPLETED) {
            $data['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED;
        } elseif ($change['listingStatus'] == self::EBAY_STATUS_ENDED) {
            $data['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED;
        } elseif ($change['listingStatus'] == self::EBAY_STATUS_ACTIVE &&
                   $qty - $qtySold <= 0) {
            $data['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN;
        } elseif ($change['listingStatus'] == self::EBAY_STATUS_ACTIVE) {
            $data['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;
        }

        $accountOutOfStockControl = $listingProduct->getAccount()->getChildObject()->getOutOfStockControl(true);

        if (isset($change['out_of_stock'])) {
            $data['additional_data'] = ['out_of_stock_control' => (bool)$change['out_of_stock']];
        } elseif ($data['status'] == \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN &&
            $accountOutOfStockControl !== null && !$accountOutOfStockControl) {
            // Listed Hidden Status can be only for GTC items
            if ($listingProduct->getChildObject()->getOnlineDuration() === null) {
                $data['online_duration'] = \Ess\M2ePro\Helper\Component\Ebay::LISTING_DURATION_GTC;
            }

            $additionalData = $listingProduct->getAdditionalData();
            empty($additionalData['out_of_stock_control']) && $additionalData['out_of_stock_control'] = true;
            $data['additional_data'] = $this->getHelper('Data')->jsonEncode($additionalData);
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
            // M2ePro\TRANSLATIONS
            // Item Status was successfully changed from "%from%" to "%to%" .
            $this->logReportChange($listingProduct, $this->getHelper('Module\Translation')->__(
                'Item Status was successfully changed from "%from%" to "%to%" .',
                $statusChangedFrom,
                $statusChangedTo
            ));
        }

        $this->activeRecordFactory->getObject('ProductChange')->addUpdateAction(
            $listingProduct->getProductId(),
            \Ess\M2ePro\Model\ProductChange::INITIATOR_SYNCHRONIZATION
        );

        if ($this->listingsProductsLockStatus[$listingProduct->getId()]) {
            $this->listingsProductsIdsForNeedSynchRulesCheck[] = $listingProduct->getId();
        }

        return $data;
    }

    private function getProductQtyChanges(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $change)
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
            $this->logReportChange($listingProduct, $this->getHelper('Module\Translation')->__(
                'Item QTY was successfully changed from %from% to %to% .',
                ($ebayListingProduct->getOnlineQty() - $ebayListingProduct->getOnlineQtySold()),
                ($data['online_qty'] - $data['online_qty_sold'])
            ));

            $this->activeRecordFactory->getObject('ProductChange')->addUpdateAction(
                $listingProduct->getProductId(),
                \Ess\M2ePro\Model\ProductChange::INITIATOR_SYNCHRONIZATION
            );

            if ($this->listingsProductsLockStatus[$listingProduct->getId()]) {
                $this->listingsProductsIdsForNeedSynchRulesCheck[] = $listingProduct->getId();
            }
        }

        return $data;
    }

    // ---------------------------------------

    private function getSimpleProductPriceChanges(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $change)
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
                $this->logReportChange($listingProduct, $this->getHelper('Module\Translation')->__(
                    'Item Price was successfully changed from %from% to %to% .',
                    $ebayListingProduct->getOnlineCurrentPrice(),
                    $data['online_current_price']
                ));

                $this->activeRecordFactory->getObject('ProductChange')->addUpdateAction(
                    $listingProduct->getProductId(),
                    \Ess\M2ePro\Model\ProductChange::INITIATOR_SYNCHRONIZATION
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
    private function getVariationProductPriceChanges(
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

    private function processVariationChanges(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        array $listingProductVariations,
        array $changeVariations
    ) {
        $variationsSnapshot = $this->getVariationsSnapshot($listingProductVariations);
        if (count($variationsSnapshot) <= 0) {
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

                if ($this->listingsProductsLockStatus[$listingProduct->getId()] &&
                    ($ebayVariation->getOnlineQty() != $updateData['online_qty'] ||
                        $ebayVariation->getOnlineQtySold() != $updateData['online_qty_sold'])
                ) {
                    $this->listingsProductsIdsForNeedSynchRulesCheck[] = $listingProduct->getId();
                }

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
                    $variationSnapshot['variation']->getChildObject()->addData($updateData)->save();
                    $variationSnapshot['variation']->getChildObject()->setStatus($listingProduct->getStatus());
                }

                break;
            }
        }

        if ($hasVariationPriceChanges) {
            $this->logReportChange($listingProduct, $this->getHelper('Module\Translation')->__(
                'Price of some Variations was successfully changed.'
            ));
        }

        if ($hasVariationQtyChanges) {
            $this->logReportChange($listingProduct, $this->getHelper('Module\Translation')->__(
                'QTY of some Variations was successfully changed.'
            ));
        }

        if ($hasVariationPriceChanges || $hasVariationQtyChanges) {
            $this->activeRecordFactory->getObject('ProductChange')->addUpdateAction(
                $listingProduct->getProductId(),
                \Ess\M2ePro\Model\ProductChange::INITIATOR_SYNCHRONIZATION
            );
        }
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product\Variation[] $variations
     * @return array
     */
    private function getVariationsSnapshot(array $variations)
    {
        $variationIds = [];
        foreach ($variations as $variation) {
            $variationIds[] = $variation->getId();
        }

        $optionCollection = $this->ebayFactory->getObject('Listing_Product_Variation_Option')->getCollection();
        $optionCollection->addFieldToFilter('listing_product_variation_id', ['in' => $variationIds]);

        $snapshot = [];

        foreach ($variations as $variation) {
            $options = $optionCollection->getItemsByColumnValue('listing_product_variation_id', $variation->getId());

            if (count($options) <= 0) {
                continue;
            }

            $snapshot[] = [
                'variation' => $variation,
                'options'   => $options
            ];
        }

        return $snapshot;
    }

    private function isVariationEqualWithChange(
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

    private function prepareSinceTime($sinceTime)
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

    private function getLogsActionId()
    {
        if ($this->logsActionId === null) {
            $this->logsActionId = $this->activeRecordFactory->getObject('Listing\Log')
                                       ->getResource()->getNextActionId();
        }
        return $this->logsActionId;
    }

    private function getActualListingType(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $change)
    {
        $validEbayValues = [
           \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\Selling::LISTING_TYPE_AUCTION,
           \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\Selling::LISTING_TYPE_FIXED
        ];

        if (isset($change['listingType']) && in_array($change['listingType'], $validEbayValues)) {
            switch ($change['listingType']) {
                case \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\Selling::LISTING_TYPE_AUCTION:
                    $result =\Ess\M2ePro\Model\Ebay\Template\SellingFormat::LISTING_TYPE_AUCTION;
                    break;
                case \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\Selling::LISTING_TYPE_FIXED:
                    $result =\Ess\M2ePro\Model\Ebay\Template\SellingFormat::LISTING_TYPE_FIXED;
                    break;
            }
        } else {
            $result = $listingProduct->getChildObject()->getListingType();
        }

        return $result;
    }

    //########################################

    private function logReportChange(\Ess\M2ePro\Model\Listing\Product $listingProduct, $logMessage)
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
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
        );
    }

    //########################################
}
