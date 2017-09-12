<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Other;

class Updating extends \Ess\M2ePro\Model\AbstractModel
{
    const EBAY_STATUS_ACTIVE = 'Active';
    const EBAY_STATUS_ENDED = 'Ended';
    const EBAY_STATUS_COMPLETED = 'Completed';

    const EBAY_DURATION_GTC         = 'GTC';
    const EBAY_DURATION_DAYS_PREFIX = 'Days_';

    /**
     * @var \Ess\M2ePro\Model\Account|null
     */
    protected $account = NULL;

    protected $logsActionId = NULL;

    protected $resourceConnection;
    protected $activeRecordFactory;
    protected $ebayFactory;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->ebayFactory = $ebayFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function initialize(\Ess\M2ePro\Model\Account $account = NULL)
    {
        $this->account = $account;
    }

    //########################################

    public function processResponseData($responseData)
    {
        $this->updateToTimeLastSynchronization($responseData);

        if (!isset($responseData['items']) || !is_array($responseData['items']) ||
            count($responseData['items']) <= 0) {
            return;
        }

        $responseData['items'] = $this->filterReceivedOnlyOtherListings($responseData['items']);

        /** @var $logModel \Ess\M2ePro\Model\Listing\Other\Log */
        $logModel = $this->activeRecordFactory->getObject('Listing\Other\Log');
        $logModel->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);

        /** @var $mappingModel \Ess\M2ePro\Model\Ebay\Listing\Other\Mapping */
        $mappingModel = $this->modelFactory->getObject('Ebay\Listing\Other\Mapping');

        foreach ($responseData['items'] as $receivedItem) {

            $collection = $this->ebayFactory->getObject('Listing\Other')->getCollection()
                ->addFieldToFilter('item_id', $receivedItem['id'])
                ->addFieldToFilter('account_id', $this->getAccount()->getId())
                ->setPageSize(1);

            /** @var \Ess\M2ePro\Model\Listing\Other $existObject */
            $existObject = $collection->getFirstItem();
            $existsId = $existObject->getId();

            if ($existsId && $existObject->isBlocked()) {
                continue;
            }

            $newData = array(
                'title' => (string)$receivedItem['title'],
                'currency' => (string)$receivedItem['currency'],
                'online_price' => (float)$receivedItem['currentPrice'],
                'online_qty' => (int)$receivedItem['quantity'],
                'online_qty_sold' => (int)$receivedItem['quantitySold'],
                'online_bids' => (int)$receivedItem['bidCount'],
                'start_date' => (string)$this->getHelper('Data')->getDate($receivedItem['startTime']),
                'end_date' => (string)$this->getHelper('Data')->getDate($receivedItem['endTime'])
            );

            if (isset($receivedItem['listingDuration'])) {

                $duration = str_replace(self::EBAY_DURATION_DAYS_PREFIX, '', $receivedItem['listingDuration']);
                if ($duration == self::EBAY_DURATION_GTC) {
                    $duration = \Ess\M2ePro\Helper\Component\Ebay::LISTING_DURATION_GTC;
                }
                $newData['online_duration'] = $duration;
            }

            if (isset($receivedItem['sku'])) {
                $newData['sku'] = (string)$receivedItem['sku'];
            }

            if ($existsId) {
                $newData['id'] = $existsId;
            } else {
                $newData['item_id'] = (double)$receivedItem['id'];
                $newData['account_id'] = (int)$this->getAccount()->getId();
                $newData['marketplace_id'] = (int)$this->ebayFactory->getCachedObjectLoaded(
                    'Marketplace', $receivedItem['marketplace'], 'code'
                )->getId();
            }

            $tempListingType = \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\Selling::LISTING_TYPE_AUCTION;
            if ($receivedItem['listingType'] == $tempListingType) {
                $newData['online_qty'] = 1;
            }

            if (($receivedItem['listingStatus'] == self::EBAY_STATUS_COMPLETED ||
                 $receivedItem['listingStatus'] == self::EBAY_STATUS_ENDED) &&
                 $newData['online_qty'] == $newData['online_qty_sold']) {

                $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD;

            } else if ($receivedItem['listingStatus'] == self::EBAY_STATUS_COMPLETED) {

                $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED;

            } else if ($receivedItem['listingStatus'] == self::EBAY_STATUS_ENDED) {

                $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED;

            } else if ($receivedItem['listingStatus'] == self::EBAY_STATUS_ACTIVE &&
                       $receivedItem['quantity'] - $receivedItem['quantitySold'] <= 0) {

                $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN;

            } else if ($receivedItem['listingStatus'] == self::EBAY_STATUS_ACTIVE) {

                $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;
            }

            $accountOutOfStockControl = $this->getAccount()->getChildObject()->getOutOfStockControl(true);

            if (isset($receivedItem['out_of_stock'])) {

                $newData['additional_data'] = array('out_of_stock_control' => (bool)$receivedItem['out_of_stock']);
                $newData['additional_data'] = $this->getHelper('Data')->jsonEncode($newData['additional_data']);

            } elseif ($newData['status'] == \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN &&
                      !is_null($accountOutOfStockControl) && !$accountOutOfStockControl) {

                // Listed Hidden Status can be only for GTC items
                if (!$existsId || is_null($existObject->getChildObject()->getOnlineDuration())) {
                    $newData['online_duration'] = \Ess\M2ePro\Helper\Component\Ebay::LISTING_DURATION_GTC;
                }

                if ($existsId) {
                    $additionalData = $existObject->getAdditionalData();
                    empty($additionalData['out_of_stock_control']) && $additionalData['out_of_stock_control'] = true;
                } else {
                    $additionalData = array('out_of_stock_control' => true);
                }

                $newData['additional_data'] = $this->getHelper('Data')->jsonEncode($additionalData);
            }

            if ($existsId) {

                $tempLogMessages = array();

                if ($newData['online_price'] != $existObject->getChildObject()->getOnlinePrice()) {
                    // M2ePro\TRANSLATIONS
                    // Item Price was successfully changed from %from% to %to% .
                    $tempLogMessages[] = $this->getHelper('Module\Translation')->__(
                        'Item Price was successfully changed from %from% to %to% .',
                        $existObject->getChildObject()->getOnlinePrice(),
                        $newData['online_price']
                    );
                }

                if ($existObject->getChildObject()->getOnlineQty() != $newData['online_qty'] ||
                    $existObject->getChildObject()->getOnlineQtySold() != $newData['online_qty_sold']) {
                    // M2ePro\TRANSLATIONS
                    // Item QTY was successfully changed from %from% to %to% .
                    $tempLogMessages[] = $this->getHelper('Module\Translation')->__(
                        'Item QTY was successfully changed from %from% to %to% .',
                        ($existObject->getChildObject()->getOnlineQty() - $existObject->getChildObject()
                                                                                      ->getOnlineQtySold()),
                        ($newData['online_qty'] - $newData['online_qty_sold'])
                    );
                }

                if ($newData['status'] != $existObject->getStatus()) {
                    $newData['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT;

                    $statusChangedFrom = $this->getHelper('Component\Ebay')
                        ->getHumanTitleByListingProductStatus($existObject->getStatus());
                    $statusChangedTo = $this->getHelper('Component\Ebay')
                        ->getHumanTitleByListingProductStatus($newData['status']);

                    if (!empty($statusChangedFrom) && !empty($statusChangedTo)) {
                        // M2ePro\TRANSLATIONS
                        // Item Status was successfully changed from "%from%" to "%to%" .
                        $tempLogMessages[] = $this->getHelper('Module\Translation')->__(
                            'Item Status was successfully changed from "%from%" to "%to%" .',
                            $statusChangedFrom,
                            $statusChangedTo
                        );
                    }
                }

                foreach ($tempLogMessages as $tempLogMessage) {
                    $logModel->addProductMessage(
                        (int)$newData['id'],
                        \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
                        $this->getLogsActionId(),
                        \Ess\M2ePro\Model\Listing\Other\Log::ACTION_CHANNEL_CHANGE,
                        $tempLogMessage,
                        \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS,
                        \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
                    );
                }
            } else {
                $newData['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT;
            }

            if ($existsId) {
                $existObject->addData($newData);
                $existObject->getChildObject()->addData($newData);
            } else {
                $existObject->setData($newData);
            }

            $existObject->save();

            if (!$existsId) {

                $logModel->addProductMessage($existObject->getId(),
                     \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
                     NULL,
                     \Ess\M2ePro\Model\Listing\Other\Log::ACTION_ADD_ITEM,
                    // M2ePro\TRANSLATIONS
                    // Item was successfully Added
                     'Item was successfully Added',
                     \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
                     \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW);

                if (!$this->getAccount()->getChildObject()->isOtherListingsMappingEnabled()) {
                    continue;
                }

                $mappingModel->initialize($this->getAccount());
                $mappingModel->autoMapOtherListingProduct($existObject);
            }
        }
        // ---------------------------------------
    }

    //########################################

    protected function updateToTimeLastSynchronization($responseData)
    {
        $tempToTime = $this->getHelper('Data')->getCurrentGmtDate();

        if (isset($responseData['to_time'])) {
            if (is_array($responseData['to_time'])) {
                $tempToTime = array();
                foreach ($responseData['to_time'] as $tempToTime2) {
                    $tempToTime[] = strtotime($tempToTime2);
                }
                sort($tempToTime,SORT_NUMERIC);
                $tempToTime = array_pop($tempToTime);
                $tempToTime = date('Y-m-d H:i:s',$tempToTime);
            } else {
                $tempToTime = $responseData['to_time'];
            }
        }

        if (!is_string($tempToTime) || empty($tempToTime)) {
            $tempToTime = $this->getHelper('Data')->getCurrentGmtDate();
        }

        $childAccountObject = $this->getAccount()->getChildObject();
        $childAccountObject->setData('other_listings_last_synchronization', $tempToTime)->save();
    }

    // ---------------------------------------

    protected function filterReceivedOnlyOtherListings(array $receivedItems)
    {
        $connection = $this->resourceConnection->getConnection();

        $receivedItemsByItemId = array();
        $receivedItemsIds      = array();

        foreach ($receivedItems as $receivedItem) {
            $receivedItemsIds[] = (string)$receivedItem['id'];
            $receivedItemsByItemId[(string)$receivedItem['id']] = $receivedItem;
        }

        foreach (array_chunk($receivedItemsIds,500,true) as $partReceivedItemsIds) {

            if (count($partReceivedItemsIds) <= 0) {
                continue;
            }

            $collection = $this->ebayFactory->getObject('Listing\Product')->getCollection();
            $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);

            $collection->getSelect()->join(
                array('l' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()),
                'main_table.listing_id = l.id', array()
            );
            $collection->getSelect()->where('l.account_id = ?', (int)$this->getAccount()->getId());

            $collection->getSelect()->join(
                array('eit' => $this->activeRecordFactory->getObject('Ebay\Item')->getResource()->getMainTable()),
                'main_table.product_id = eit.product_id AND eit.account_id = '.(int)$this->getAccount()->getId(),
                array('item_id')
            );
            $collection->getSelect()->where('eit.item_id IN (?)', $partReceivedItemsIds);

            $queryStmt = $connection->query($collection->getSelect()->__toString());

            while (($itemId = $queryStmt->fetchColumn()) !== false) {
                unset($receivedItemsByItemId[$itemId]);
            }
        }

        return array_values($receivedItemsByItemId);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    protected function getAccount()
    {
        return $this->account;
    }

    protected function getLogsActionId()
    {
        if (!is_null($this->logsActionId)) {
            return $this->logsActionId;
        }

        return $this->logsActionId = $this->activeRecordFactory->getObject('Listing\Other\Log')
                                          ->getResource()->getNextActionId();
    }

    //########################################
}