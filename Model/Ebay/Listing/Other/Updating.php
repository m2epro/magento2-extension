<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Other;

class Updating extends \Ess\M2ePro\Model\AbstractModel
{
    public const EBAY_STATUS_ACTIVE = 'Active';
    public const EBAY_STATUS_ENDED = 'Ended';
    public const EBAY_STATUS_COMPLETED = 'Completed';

    public const EBAY_DURATION_GTC = 'GTC';
    public const EBAY_DURATION_DAYS_PREFIX = 'Days_';

    /** @var \Ess\M2ePro\Model\Account|null */
    protected $account = null;

    protected $resourceConnection;
    protected $activeRecordFactory;
    protected $ebayFactory;
    /** @var \Ess\M2ePro\Helper\Data */
    protected $helperData;

    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay */
    private $componentEbayCategoryEbay;

    public function __construct(
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay $componentEbayCategoryEbay,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        parent::__construct($helperFactory, $modelFactory);

        $this->resourceConnection = $resourceConnection;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->ebayFactory = $ebayFactory;
        $this->helperData = $helperData;
        $this->componentEbayCategoryEbay = $componentEbayCategoryEbay;
    }

    //########################################

    public function initialize(?\Ess\M2ePro\Model\Account $account = null)
    {
        $this->account = $account;
    }

    //########################################

    public function processResponseData($responseData)
    {
        $this->updateToTimeLastSynchronization($responseData);

        if (!isset($responseData['items']) || !is_array($responseData['items']) || count($responseData['items']) <= 0) {
            return;
        }

        $responseData['items'] = $this->filterReceivedOnlyOtherListings($responseData['items']);

        $isMappingEnabled = $this->getAccount()->getChildObject()->isOtherListingsMappingEnabled();

        if ($isMappingEnabled) {
            /** @var \Ess\M2ePro\Model\Ebay\Listing\Other\Mapping $mappingModel */
            $mappingModel = $this->modelFactory->getObject('Ebay_Listing_Other_Mapping');
            $mappingModel->initialize($this->getAccount());
        }

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

            $itemMarketplace = $this->ebayFactory->getCachedObjectLoaded(
                'Marketplace',
                $receivedItem['marketplace'],
                'code'
            );

            $newData = [
                'title' => (string)$receivedItem['title'],
                'currency' => (string)$receivedItem['currency'],
                'online_price' => (float)$receivedItem['currentPrice'],
                'online_qty' => (int)$receivedItem['quantity'],
                'online_qty_sold' => (int)$receivedItem['quantitySold'],
                'online_bids' => (int)$receivedItem['bidCount'],
                'online_main_category' => null,
                'online_categories_data' => null,
                'start_date' => (string)$this->helperData
                    ->createGmtDateTime($receivedItem['startTime'])
                    ->format('Y-m-d H:i:s'),
                'end_date' => (string)$this->helperData
                    ->createGmtDateTime($receivedItem['endTime'])
                    ->format('Y-m-d H:i:s'),
                'online_variations' => json_encode($receivedItem['variations'] ?? [], JSON_THROW_ON_ERROR)
            ];

            if (!empty($receivedItem['categories'])) {
                $categories = [
                    'category_main_id' => 0,
                    'category_secondary_id' => 0,
                    'store_category_main_id' => 0,
                    'store_category_secondary_id' => 0,
                ];

                foreach ($categories as $categoryKey => &$categoryValue) {
                    if (!empty($receivedItem['categories'][$categoryKey])) {
                        $categoryValue = $receivedItem['categories'][$categoryKey];
                    }
                }

                unset($categoryValue);

                $categoryPath = $this->componentEbayCategoryEbay->getPath(
                    $categories['category_main_id'],
                    $itemMarketplace->getId()
                );

                $newData['online_main_category'] = $categoryPath . ' (' . $categories['category_main_id'] . ')';
                $newData['online_categories_data'] = \Ess\M2ePro\Helper\Json::encode($categories);
            }

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
                $newData['marketplace_id'] = $itemMarketplace->getId();
            }

            $tempListingType = \Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder\General::LISTING_TYPE_AUCTION;
            if ($receivedItem['listingType'] == $tempListingType) {
                $newData['online_qty'] = 1;
            }

            if (
                $receivedItem['listingStatus'] == self::EBAY_STATUS_COMPLETED
                || $receivedItem['listingStatus'] == self::EBAY_STATUS_ENDED
            ) {
                $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_INACTIVE;
            } elseif (
                $receivedItem['listingStatus'] == self::EBAY_STATUS_ACTIVE &&
                $receivedItem['quantity'] - $receivedItem['quantitySold'] <= 0
            ) {
                $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN;
            } elseif ($receivedItem['listingStatus'] == self::EBAY_STATUS_ACTIVE) {
                $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;
            }

            if ($newData['status'] == \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN) {
                // Listed Hidden Status can be only for GTC items
                if (!$existsId || $existObject->getChildObject()->getOnlineDuration() === null) {
                    $newData['online_duration'] = \Ess\M2ePro\Helper\Component\Ebay::LISTING_DURATION_GTC;
                }
            }

            if ($existsId) {
                if ($newData['status'] != $existObject->getStatus()) {
                    $newData['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT;

                    $existObject->addData($newData);
                    $existObject->getChildObject()->addData($newData);
                }
            } else {
                $newData['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT;

                $existObject->setData($newData);
            }

            $existObject->save();

            if (!$existsId && $isMappingEnabled) {
                $mappingModel->autoMapOtherListingProduct($existObject);
            }
        }
        // ---------------------------------------
    }

    //########################################

    protected function updateToTimeLastSynchronization($responseData)
    {
        $tempToTime = $this->helperData->getCurrentGmtDate();

        if (isset($responseData['to_time'])) {
            if (is_array($responseData['to_time'])) {
                $tempToTime = [];
                foreach ($responseData['to_time'] as $tempToTime2) {
                    $tempToTime[] = (int)$this->helperData
                        ->createGmtDateTime($tempToTime2)
                        ->format('U');
                }
                sort($tempToTime, SORT_NUMERIC);
                $tempToTime = array_pop($tempToTime);
                $tempToTime = date('Y-m-d H:i:s', $tempToTime);
            } else {
                $tempToTime = $responseData['to_time'];
            }
        }

        if (!is_string($tempToTime) || empty($tempToTime)) {
            $tempToTime = $this->helperData->getCurrentGmtDate();
        }

        $childAccountObject = $this->getAccount()->getChildObject();
        $childAccountObject->setData('other_listings_last_synchronization', $tempToTime)->save();
    }

    // ---------------------------------------

    protected function filterReceivedOnlyOtherListings(array $receivedItems)
    {
        $connection = $this->resourceConnection->getConnection();

        $receivedItemsByItemId = [];
        $receivedItemsIds = [];

        foreach ($receivedItems as $receivedItem) {
            $receivedItemsIds[] = (string)$receivedItem['id'];
            $receivedItemsByItemId[(string)$receivedItem['id']] = $receivedItem;
        }

        foreach (array_chunk($receivedItemsIds, 500, true) as $partReceivedItemsIds) {
            $collection = $this->ebayFactory->getObject('Listing\Product')->getCollection();
            $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);

            $collection->getSelect()->join(
                ['l' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()],
                'main_table.listing_id = l.id',
                []
            );
            $collection->getSelect()->where('l.account_id = ?', (int)$this->getAccount()->getId());

            $collection->getSelect()->join(
                ['eit' => $this->activeRecordFactory->getObject('Ebay\Item')->getResource()->getMainTable()],
                'main_table.product_id = eit.product_id AND eit.account_id = ' . (int)$this->getAccount()->getId(),
                ['item_id']
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

    //########################################
}
