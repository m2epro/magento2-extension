<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\ListingsProducts;

class RemoveDuplicates extends AbstractModel
{
    const BLOCKED_PRODUCTS_PER_SYNCH = 10;
    const MAX_ALLOWED_BLOCKED_PRODUCTS = 100;
    const MIN_SECONDS_FROM_FAILED_REQUEST = 300;

    private $duplicatedItems = array();

    //########################################

    protected function getNick()
    {
        return '/remove_duplicates/';
    }

    protected function getTitle()
    {
        return 'Remove Duplicated Products';
    }

    // ---------------------------------------

    protected function getPercentsStart()
    {
        return 0;
    }

    protected function getPercentsEnd()
    {
        return 30;
    }

    //########################################

    protected function performActions()
    {
        $this->checkTooManyBlockedListingProducts();
        $this->processListingProducts();
        $this->stopDuplicatedItems();
    }

    //########################################

    private function checkTooManyBlockedListingProducts()
    {
        $collection = $this->getBlockedListingProductCollection();
        $blockedCount = $collection->getSize();

        if ($blockedCount <= self::MAX_ALLOWED_BLOCKED_PRODUCTS) {
            return;
        }

        $collection->getSelect()->limit($blockedCount - self::MAX_ALLOWED_BLOCKED_PRODUCTS);

        foreach ($collection->getItems() as $product) {

            /** @var $product \Ess\M2ePro\Model\Listing\Product */

            $productStatus = \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED;

            $additionalData = $product->getAdditionalData();
            if (!empty($additionalData['last_failed_action_data']['previous_status'])) {
                $productStatus = $additionalData['last_failed_action_data']['previous_status'];
            }

            $this->modifyAndLogListingProduct($product, $productStatus);
        }
    }

    //########################################

    private function processListingProducts()
    {
        $collection = $this->getBlockedListingProductCollection();
        $collection->getSelect()->limit(self::BLOCKED_PRODUCTS_PER_SYNCH);
        $products = $collection->getItems();

        if (empty($products)) {
            return;
        }

        foreach ($products as $product) {

            /** @var $product \Ess\M2ePro\Model\Listing\Product */

            $productStatus = \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED;

            try {

                $additionalData = $product->getAdditionalData();
                if (empty($additionalData['last_failed_action_data'])) {
                    throw new \Ess\M2ePro\Model\Exception('last_failed_action_data is empty');
                }

                $lastFailedActionData = $additionalData['last_failed_action_data'];

                $requestTime = new \DateTime($lastFailedActionData['request_time']);
                $currentTime = new \DateTime($this->getHelper('Data')->getCurrentGmtDate());
                if ($currentTime->format('U') - $requestTime->format('U') < self::MIN_SECONDS_FROM_FAILED_REQUEST) {
                    continue;
                }

                $productStatus = (int)$lastFailedActionData['previous_status'];
                $action = (int)$lastFailedActionData['action'];
                $accountId = (int)$product->getData('account_id');
                $marketplaceId = (int)$product->getData('marketplace_id');

                if (!isset($this->duplicatedItems[$accountId])) {
                    $this->duplicatedItems[$accountId] = array();
                }
                if (!isset($this->duplicatedItems[$accountId][$marketplaceId])) {
                    $this->duplicatedItems[$accountId][$marketplaceId] = array();
                }

                if ($action == \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST) {

                    $itemInfo = $this->getEbayItemInfo(
                        $lastFailedActionData['native_request_data']['item_id'],
                        $accountId
                    );

                    if (empty($itemInfo['relisted_item_id'])) {
                        throw new \Ess\M2ePro\Model\Exception('Duplicate was not found');
                    }

                    $this->duplicatedItems[$accountId][$marketplaceId][] = $itemInfo['relisted_item_id'];
                    $this->modifyAndLogListingProduct($product, $productStatus, $itemInfo['relisted_item_id']);

                    continue;
                }

                $timeFrom = new \DateTime($lastFailedActionData['request_time']);
                $timeTo = new \DateTime($lastFailedActionData['request_time']);

                $timeFrom->modify('-1 minute');
                $timeTo->modify('+1 minute');

                $possibleDuplicates = $this->getEbayItemsByStartTimeInterval($timeFrom, $timeTo, $accountId);

                $marketplaceCode = $this->activeRecordFactory->getObjectLoaded('Marketplace', $marketplaceId)
                                                             ->getCode();

                $duplicatedItem = $this->getDuplicateItemFromPossible($possibleDuplicates, array(
                    'title' => $lastFailedActionData['native_request_data']['title'],
                    'sku' => $lastFailedActionData['native_request_data']['sku'],
                    'marketplace' => $marketplaceCode,
                ));

                if (empty($duplicatedItem)) {
                    throw new \Ess\M2ePro\Model\Exception('Duplicate was not found');
                }

                $this->duplicatedItems[$accountId][$marketplaceId][] = $duplicatedItem['id'];
                $this->modifyAndLogListingProduct($product, $productStatus, $duplicatedItem['id']);

            } catch(\Exception $e) {
                $this->modifyAndLogListingProduct($product, $productStatus);
            }
        }
    }

    // ---------------------------------------

    private function stopDuplicatedItems()
    {
        if (empty($this->duplicatedItems)) {
            return;
        }

        foreach ($this->duplicatedItems as $accountId => $marketplaceItems) {

            foreach ($marketplaceItems as $marketplaceId => $itemIds) {

                if (empty($itemIds)) {
                    continue;
                }

                $itemsParts = array_chunk(array_unique($itemIds), 10);

                foreach ($itemsParts as $itemsPart) {
                    try {

                        $dispatcherObj = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');
                        $connectorObj = $dispatcherObj->getVirtualConnector('item','update','ends',
                                                                            array('items' => $itemsPart),NULL,
                                                                            $marketplaceId,$accountId,NULL);
                        $dispatcherObj->process($connectorObj);

                    } catch (\Exception $e) {}
                }
            }
        }
    }

    //########################################

    private function getBlockedListingProductCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing\Product')->getCollection();

        $listingTable = $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable();
        $collection->addFieldToFilter('main_table.component_mode',\Ess\M2ePro\Helper\Component\Ebay::NICK)
            ->addFieldToFilter('status', \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED)
            ->join(
                ['Listing' => $listingTable], 'Listing.id=main_table.listing_id',
                array('Listing.account_id', 'Listing.marketplace_id')
            );

        return $collection;
    }

    //########################################

    private function getEbayItemInfo($itemId, $accountId)
    {
        $dispatcherObj = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');
        $connectorObj = $dispatcherObj->getVirtualConnector('item','get','info',
                                                            array('item_id' => $itemId),NULL,
                                                            NULL,$accountId);

        $dispatcherObj->process($connectorObj);
        $responseData = $connectorObj->getResponseData();

        return isset($responseData['result']) ? $responseData['result'] : array();
    }

    private function getEbayItemsByStartTimeInterval($timeFrom, $timeTo, $accountId)
    {
        is_object($timeFrom) && $timeFrom = $timeFrom->format('Y-m-d H:i:s');
        is_object($timeTo)   && $timeTo = $timeTo->format('Y-m-d H:i:s');

        $dispatcherObj = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');
        $connectorObj = $dispatcherObj->getVirtualConnector('item','get','all',
                                                            array('since_time'=>$timeFrom,
                                                                  'to_time'=>$timeTo),NULL,
                                                            NULL,$accountId);

        $dispatcherObj->process($connectorObj);
        $responseData = $connectorObj->getResponseData();

        return isset($responseData['items']) ? $responseData['items'] : array();
    }

    // ---------------------------------------

    private function getDuplicateItemFromPossible(array $possibleDuplicates, array $searchParams)
    {
        if (empty($possibleDuplicates)) {
            return array();
        }

        foreach ($possibleDuplicates as $item) {

            $isFound = true;

            foreach ($searchParams as $key => $value) {

                if (trim($item[$key]) == trim($value)) {
                    continue;
                }

                $isFound = false;
                break;
            }

            if (!$isFound) {
                continue;
            }

            return $item;
        }

        return array();
    }

    //########################################

    private function modifyAndLogListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct,
                                                $status, $duplicateItemId = null)
    {
        /** @var \Ess\M2ePro\Model\Listing\Log $logModel */
        $logModel = $this->activeRecordFactory->getObject('Listing\Log');
        $logModel->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);

        $logsActionId = $logModel->getResource()->getNextActionId();

        $statusLogMessage = $this->getStatusLogMessage($listingProduct->getStatus(), $status);

        $logModel->addProductMessage(
            $listingProduct->getData('listing_id'),
            $listingProduct->getData('product_id'),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            $logsActionId,
            \Ess\M2ePro\Model\Listing\Log::ACTION_CHANNEL_CHANGE,
            $statusLogMessage,
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
        );

        $additionalData = $listingProduct->getAdditionalData();
        unset($additionalData['last_failed_action_data']);

        $listingProduct->addData(array(
            'status' => $status,
            'additional_data' => $this->getHelper('Data')->jsonEncode($additionalData),
        ))->save();

        $listingProduct->getChildObject()->updateVariationsStatus();

        if (is_null($duplicateItemId)) {
            return;
        }

        // M2ePro\TRANSLATIONS
        // Duplicated Item %item_id% was found and Stopped on eBay.
        $textToTranslate = 'Duplicated Item %item_id% was found and stopped on eBay.';
        $duplicateDeletedMessage = $this->getHelper('Module\Translation')->__($textToTranslate, $duplicateItemId);

        $logModel->addProductMessage(
            $listingProduct->getData('listing_id'),
            $listingProduct->getData('product_id'),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            $logsActionId,
            \Ess\M2ePro\Model\Listing\Log::ACTION_CHANNEL_CHANGE,
            $duplicateDeletedMessage,
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
        );
    }

    // ---------------------------------------

    private function getStatusLogMessage($statusFrom, $statusTo)
    {
        $message = '';

        $statusChangedFrom = $this->getHelper('Component\Ebay')
            ->getHumanTitleByListingProductStatus($statusFrom);
        $statusChangedTo = $this->getHelper('Component\Ebay')
            ->getHumanTitleByListingProductStatus($statusTo);

        if (!empty($statusChangedFrom) && !empty($statusChangedTo)) {
            // M2ePro\TRANSLATIONS
            // Item Status was successfully changed from "%from%" to "%to%" .
            $message = $this->getHelper('Module\Translation')->__(
                'Item Status was successfully changed from "%from%" to "%to%" .',
                $statusChangedFrom,
                $statusChangedTo
            );
        }

        return $message;
    }

    //########################################
}