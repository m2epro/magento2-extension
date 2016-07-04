<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\ListingsProducts;

final class Update extends AbstractModel
{
    const EBAY_STATUS_ACTIVE = 'Active';
    const EBAY_STATUS_ENDED = 'Ended';
    const EBAY_STATUS_COMPLETED = 'Completed';

    private $logsActionId = NULL;

    private $listingsProductsLockStatus = array();

    private $listingsProductsIdsForActionSkipping = array();

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

        if (!empty($this->listingsProductsIdsForActionSkipping)) {
            $this->activeRecordFactory->getObject('Ebay\Processing\Action\Item')->getResource()
                ->markAsSkipped(
                    array_unique($this->listingsProductsIdsForActionSkipping)
                );
        }
    }

    // ---------------------------------------

    private function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        $sinceTime = $this->prepareSinceTime($account->getData('defaults_last_synchronization'));
        $changesByAccount = $this->getChangesByAccount($account, $sinceTime);

        if (!isset($changesByAccount['items']) || !isset($changesByAccount['to_time'])) {
            return;
        }

        $account->getChildObject()->setData('defaults_last_synchronization', $changesByAccount['to_time'])->save();

        $this->getHelper('Data\Cache\Session')->setValue(
            'item_get_changes_data_' . $account->getId(), $changesByAccount
        );

        foreach ($changesByAccount['items'] as $change) {

            /* @var $listingProduct \Ess\M2ePro\Model\Listing\Product */

            $listingProduct = $this->getHelper('Component\Ebay')->getListingProductByEbayItem(
                $change['id'], $account->getId()
            );

            if (is_null($listingProduct)) {
                continue;
            }

            // Listing product isn't listed and it child must have another item_id
            if ($listingProduct->getStatus() != \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED &&
                $listingProduct->getStatus() != \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN) {
                continue;
            }

            $this->listingsProductsLockStatus[$listingProduct->getId()] =
                $listingProduct->isSetProcessingLock('in_action');

            $this->processListingProduct($listingProduct,$change);

            if (empty($change['variations'])) {
                continue;
            }

            $variations = $listingProduct->getVariations(true);

            if (count($variations) <= 0) {
                continue;
            }

            $variationsSnapshot = $this->getVariationsSnapshot($variations);

            if (count($variationsSnapshot) <= 0) {
                return;
            }

            $this->processListingProductVariation($variationsSnapshot,$change['variations'], $listingProduct);
        }
    }

    private function processListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $change)
    {
        $oldStatus = $listingProduct->getStatus();

        $updateData = array_merge(
            $this->getProductPriceChanges($listingProduct, $change),
            $this->getProductQtyChanges($listingProduct, $change),
            $this->getProductDatesChanges($listingProduct, $change),
            $this->getProductStatusChanges($listingProduct, $change)
        );

        $listingProduct->addData($updateData)->save();

        if ($oldStatus !== $updateData['status']) {
            $listingProduct->getChildObject()->updateVariationsStatus();
        }
    }

    private function processListingProductVariation(array $variationsSnapshot,
                                                    array $changeVariations,
                                                    \Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        foreach ($changeVariations as $changeVariation) {
            foreach ($variationsSnapshot as $variationSnapshot) {

                if (!$this->isVariationEqualWithChange($changeVariation,$variationSnapshot)) {
                    continue;
                }

                $updateData = array(
                    'online_price' => (float)$changeVariation['price'] < 0 ? 0 : (float)$changeVariation['price'],
                    'online_qty' => (int)$changeVariation['quantity'] < 0 ? 0 : (int)$changeVariation['quantity'],
                    'online_qty_sold' => (int)$changeVariation['quantitySold'] < 0 ?
                                                                0 : (int)$changeVariation['quantitySold']
                );

                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Variation $ebayVariationObj */
                $ebayVariationObj = $variationSnapshot['variation']->getChildObject();

                if ($this->listingsProductsLockStatus[$listingProduct->getId()] &&
                    ($ebayVariationObj->getOnlineQty() != $updateData['online_qty'] ||
                     $ebayVariationObj->getOnlineQtySold() != $updateData['online_qty_sold'])
                ) {
                    $this->listingsProductsIdsForActionSkipping[] = $listingProduct->getId();
                }

                if ($ebayVariationObj->getOnlinePrice() != $updateData['online_price'] ||
                    $ebayVariationObj->getOnlineQty() != $updateData['online_qty'] ||
                    $ebayVariationObj->getOnlineQtySold() != $updateData['online_qty_sold']) {

                    $variationSnapshot['variation']->addData($updateData)->save();
                    $variationSnapshot['variation']->getChildObject()->setStatus($listingProduct->getStatus());
                }

                break;
            }
        }
    }

    //########################################

    private function getChangesByAccount(\Ess\M2ePro\Model\Account $account, $sinceTime)
    {
        $nextSinceTime = new \DateTime($sinceTime, new \DateTimeZone('UTC'));

        $toTime = NULL;

        $operationHistory = $this->getActualOperationHistory()->getParentObject('synchronization');
        if (!is_null($operationHistory)) {
            $toTime = $operationHistory->getData('start_date');

            if ($nextSinceTime->format('U') >= strtotime($toTime)) {
                $nextSinceTime = new \DateTime($toTime, new \DateTimeZone('UTC'));
                $nextSinceTime->modify('- 1 minute');
            }
        }

        $response = $this->receiveChangesFromEbay(
            $account, array('since_time' => $nextSinceTime->format('Y-m-d H:i:s'), 'to_time' => $toTime)
        );

        if ($response) {
            return (array)$response;
        }

        $previousSinceTime = $nextSinceTime;

        $nextSinceTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $nextSinceTime->modify("-1 day");

        if ($previousSinceTime->format('U') < $nextSinceTime->format('U')) {

            // from day behind now
            $response = $this->receiveChangesFromEbay(
                $account, array('since_time' => $nextSinceTime->format('Y-m-d H:i:s'), 'to_time' => $toTime)
            );

            if ($response) {
                return (array)$response;
            }

            $previousSinceTime = $nextSinceTime;
        }

        $nextSinceTime = new \DateTime('now', new \DateTimeZone('UTC'));

        if ($previousSinceTime->format('U') < $nextSinceTime->format('U')) {

            // from now
            $response = $this->receiveChangesFromEbay(
                $account, array('since_time' => $nextSinceTime->format('Y-m-d H:i:s'), 'to_time' => $toTime)
            );

            if ($response) {
                return (array)$response;
            }
        }

        return array();
    }

    private function receiveChangesFromEbay(\Ess\M2ePro\Model\Account $account, array $paramsConnector = array())
    {
        $dispatcherObj = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');
        $connectorObj = $dispatcherObj->getVirtualConnector('item','get','changes',
                                                            $paramsConnector,NULL,
                                                            NULL,$account->getId());

        $dispatcherObj->process($connectorObj);
        $this->processResponseMessages($connectorObj->getResponseMessages());

        $responseData = $connectorObj->getResponseData();

        if (!isset($responseData['items']) || !isset($responseData['to_time'])) {
            return NULL;
        }

        return $responseData;
    }

    private function processResponseMessages(array $messages)
    {
        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message\Set $messagesSet */
        $messagesSet = $this->modelFactory->getObject('Connector\Connection\Response\Message\Set');
        $messagesSet->init($messages);

        foreach ($messagesSet->getEntities() as $message) {

            if ($message->getCode() == 21917062) {
                continue;
            }

            if (!$message->isError() && !$message->isWarning()) {
                continue;
            }

            $logType = $message->isError() ? \Ess\M2ePro\Model\Log\AbstractLog::TYPE_ERROR
                : \Ess\M2ePro\Model\Log\AbstractLog::TYPE_WARNING;

            $this->getLog()->addMessage(
                $this->getHelper('Module\Translation')->__($message->getText()),
                $logType,
                \Ess\M2ePro\Model\Log\AbstractLog::PRIORITY_HIGH
            );
        }
    }

    //########################################

    private function getProductPriceChanges(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $change)
    {
        $data = array();

        $data['online_current_price'] = (float)$change['currentPrice'] < 0 ? 0 : (float)$change['currentPrice'];

        $listingType = $this->getActualListingType($listingProduct, $change);

        if ($listingType ==\Ess\M2ePro\Model\Ebay\Template\SellingFormat::LISTING_TYPE_FIXED) {

            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
            $ebayListingProduct = $listingProduct->getChildObject();

            if ($ebayListingProduct->getOnlineCurrentPrice() != $data['online_current_price']) {
                // M2ePro\TRANSLATIONS
                // Item Price was successfully changed from %from% to %to% .
                $this->logReportChange($listingProduct, $this->getHelper('Module\Translation')->__(
                    'Item Price was successfully changed from %from% to %to% .',
                    $ebayListingProduct->getOnlineCurrentPrice(),
                    $data['online_current_price']
                ));

                $this->activeRecordFactory->getObject('ProductChange')->addUpdateAction(
                    $listingProduct->getProductId(),\Ess\M2ePro\Model\ProductChange::INITIATOR_SYNCHRONIZATION
                );
            }
        }

        return $data;
    }

    private function getProductQtyChanges(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $change)
    {
        $data = array();

        $data['online_qty'] = (int)$change['quantity'] < 0 ? 0 : (int)$change['quantity'];
        $data['online_qty_sold'] = (int)$change['quantitySold'] < 0 ? 0 : (int)$change['quantitySold'];

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $listingType = $this->getActualListingType($listingProduct, $change);

        if ($listingType == \Ess\M2ePro\Model\Ebay\Template\SellingFormat::LISTING_TYPE_AUCTION) {
            $data['online_qty'] = 1;
            $data['online_bids'] = (int)$change['bidCount'] < 0 ? 0 : (int)$change['bidCount'];
        }

        if ($ebayListingProduct->getOnlineQty() != $data['online_qty'] ||
            $ebayListingProduct->getOnlineQtySold() != $data['online_qty_sold']) {
            // M2ePro\TRANSLATIONS
            // Item QTY was successfully changed from %from% to %to% .
            $this->logReportChange($listingProduct, $this->getHelper('Module\Translation')->__(
                'Item QTY was successfully changed from %from% to %to% .',
                ($ebayListingProduct->getOnlineQty() - $ebayListingProduct->getOnlineQtySold()),
                ($data['online_qty'] - $data['online_qty_sold'])
            ));

            $this->activeRecordFactory->getObject('ProductChange')->addUpdateAction(
                $listingProduct->getProductId(),\Ess\M2ePro\Model\ProductChange::INITIATOR_SYNCHRONIZATION
            );

            if ($this->listingsProductsLockStatus[$listingProduct->getId()]) {
                $this->listingsProductsIdsForActionSkipping[] = $listingProduct->getId();
            }
        }

        return $data;
    }

    private function getProductDatesChanges(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $change)
    {
        return array(
            'start_date' =>\Ess\M2ePro\Model\Ebay\Connector\Command\RealTime::ebayTimeToString($change['startTime']),
            'end_date' =>\Ess\M2ePro\Model\Ebay\Connector\Command\RealTime::ebayTimeToString($change['endTime'])
        );
    }

    // ---------------------------------------

    private function getProductStatusChanges(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $change)
    {
        $data = array();

        $qty = (int)$change['quantity'] < 0 ? 0 : (int)$change['quantity'];
        $qtySold = (int)$change['quantitySold'] < 0 ? 0 : (int)$change['quantitySold'];

        if (($change['listingStatus'] == self::EBAY_STATUS_COMPLETED ||
             $change['listingStatus'] == self::EBAY_STATUS_ENDED) &&
             $qty == $qtySold) {

            $data['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD;

        } else if ($change['listingStatus'] == self::EBAY_STATUS_COMPLETED) {

            $data['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED;

        } else if ($change['listingStatus'] == self::EBAY_STATUS_ENDED) {

            $data['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED;

        } else if ($change['listingStatus'] == self::EBAY_STATUS_ACTIVE &&
                   $qty - $qtySold <= 0) {

            $data['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN;

        } else if ($change['listingStatus'] == self::EBAY_STATUS_ACTIVE) {

            $data['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;
        }

        $accountOutOfStockControl = $listingProduct->getAccount()->getChildObject()->getOutOfStockControl(true);

        if (isset($change['out_of_stock'])) {

            $data['additional_data'] = array('out_of_stock_control' => (bool)$change['out_of_stock']);

        } elseif ($data['status'] == \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN &&
            !is_null($accountOutOfStockControl) && !$accountOutOfStockControl) {

            // Listed Hidden Status can be only for GTC items
            if (is_null($listingProduct->getChildObject()->getOnlineDuration())) {
                $data['online_duration'] = \Ess\M2ePro\Helper\Component\Ebay::LISTING_DURATION_GTC;
            }

            $additionalData = $listingProduct->getAdditionalData();
            empty($additionalData['out_of_stock_control']) && $additionalData['out_of_stock_control'] = true;
            $data['additional_data'] = json_encode($additionalData);
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
            $listingProduct->getProductId(),\Ess\M2ePro\Model\ProductChange::INITIATOR_SYNCHRONIZATION
        );

        if ($this->listingsProductsLockStatus[$listingProduct->getId()]) {
            $this->listingsProductsIdsForActionSkipping[] = $listingProduct->getId();
        }

        return $data;
    }

    //########################################

    private function getVariationsSnapshot(array $variations)
    {
        $snapshot = array();

        foreach ($variations as $variation) {

            /** @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */

            $options = $variation->getOptions(true);

            if (count($options) <= 0) {
                continue;
            }

            $snapshot[] = array(
                'variation' => $variation,
                'options' => $options
            );
        }

        return $snapshot;
    }

    private function isVariationEqualWithChange(array $changeVariation, array $variationSnapshot)
    {
        if (count($variationSnapshot['options']) != count($changeVariation['specifics'])) {
            return false;
        }

        foreach ($variationSnapshot['options'] as $variationSnapshotOption) {

            $haveOption = false;

            foreach ($changeVariation['specifics'] as $changeVariationOption=>$changeVariationValue) {

                if ($variationSnapshotOption->getData('attribute') == $changeVariationOption &&
                    $variationSnapshotOption->getData('option') == $changeVariationValue) {
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
        $minTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $minTime->modify("-1 month");

        if (empty($sinceTime) || strtotime($sinceTime) < (int)$minTime->format('U')) {
            $sinceTime = new \DateTime('now', new \DateTimeZone('UTC'));
            $sinceTime = $sinceTime->format('Y-m-d H:i:s');
        }

        return $sinceTime;
    }

    // ---------------------------------------

    private function getLogsActionId()
    {
        if (is_null($this->logsActionId)) {
            $this->logsActionId = $this->activeRecordFactory->getObject('Listing\Log')->getNextActionId();
        }
        return $this->logsActionId;
    }

    private function getActualListingType(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $change)
    {
        $validEbayValues = array(
           \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\Selling::LISTING_TYPE_AUCTION,
           \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\Selling::LISTING_TYPE_FIXED
        );

        if (isset($change['listingType']) && in_array($change['listingType'],$validEbayValues)) {

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
            \Ess\M2ePro\Model\Log\AbstractLog::TYPE_SUCCESS,
            \Ess\M2ePro\Model\Log\AbstractLog::PRIORITY_LOW
        );
    }

    //########################################
}