<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\SynchronizeInventory\Amazon;

use Ess\M2ePro\Helper\Date as DateHelper;
use Ess\M2ePro\Model\Listing\SynchronizeInventory\AbstractExistingProductsHandler;
use Ess\M2ePro\Model\Cron\Task\Amazon\Listing\SynchronizeInventory\Responser;
use Ess\M2ePro\Model\Amazon\Listing\Product;
use Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product as AmazonListingProductResource;

/**
 * Class \Ess\M2ePro\Model\Listing\SynchronizeInventory\Amazon\ListingProductsHandler
 */
class ListingProductsHandler extends AbstractExistingProductsHandler
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection */
    protected $preparedListingProductsCollection;

    //########################################

    /**
     * @param array $responseData
     *
     * @return array|void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Zend_Db_Statement_Exception
     */
    public function handle(array $responseData)
    {
        $this->responseData = $responseData;
        $this->updateReceivedListingProducts();

        return $this->responseData;
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Zend_Db_Statement_Exception
     * @throws \Exception
     */
    protected function updateReceivedListingProducts()
    {
        /** @var \Ess\M2ePro\Model\Listing\Log $tempLog */
        $tempLog = $this->activeRecordFactory->getObject('Listing\Log');
        $tempLog->setComponentMode($this->getComponentMode());

        $componentHelper = $this->helperFactory->getObject('Component\Amazon');

        $parentIdsForProcessing = [];
        $instructionsData = [];

        foreach (array_chunk(array_keys($this->responseData), 200) as $skuPack) {
            $stmtTemp = $this->getPdoStatementExistingListings($skuPack);

            while ($existingItem = $stmtTemp->fetch()) {
                if (!isset($this->responseData[$existingItem['sku']])) {
                    continue;
                }

                $receivedItem = $this->responseData[$existingItem['sku']];
                unset($this->responseData[$existingItem['sku']]);

                $existingData = [
                    'general_id' => (string)$existingItem['general_id'],
                    'online_regular_price' => !empty($existingItem['online_regular_price'])
                        ? (float)$existingItem['online_regular_price'] : null,
                    'online_qty' => (int)$existingItem['online_qty'],
                    'is_afn_channel' => (bool)$existingItem['is_afn_channel'],
                    'is_isbn_general_id' => (bool)$existingItem['is_isbn_general_id'],
                    'status' => (int)$existingItem['status'],
                    'online_qty_last_update_date' =>
                        $existingItem[AmazonListingProductResource::COLUMN_ONLINE_QTY_LAST_UPDATE_DATE]
                ];

                $newData = [
                    'general_id' => (string)$receivedItem['identifiers']['general_id'],
                    'online_regular_price' => !empty($receivedItem['price']) ? (float)$receivedItem['price'] : null,
                    'online_qty' => (int)$receivedItem['qty'],
                    'is_afn_channel' => (bool)$receivedItem['channel']['is_afn'],
                    'is_isbn_general_id' => (bool)$receivedItem['identifiers']['is_isbn'],
                    'online_qty_last_update_date' => $receivedItem['system']['item_request_date']
                ];

                if ($newData['is_afn_channel']) {
                    $newData['online_qty'] = null;
                    $newData['status'] = $existingData['is_afn_channel']
                        ? $existingData['status']
                        : \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN;
                }

                if (!$newData['is_afn_channel'] && $existingItem['online_afn_qty'] !== null) {
                    $newData['online_afn_qty'] = null;
                }

                if (!$newData['is_afn_channel'] && isset($newData['online_qty'])) {
                    if ($newData['online_qty'] > 0) {
                        $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;
                    } else {
                        $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_INACTIVE;
                    }
                }

                $existingAdditionalData = \Ess\M2ePro\Helper\Json::decode($existingItem['additional_data']);
                $lastSynchDates = !empty($existingAdditionalData['last_synchronization_dates'])
                    ? $existingAdditionalData['last_synchronization_dates']
                    : [];

                if (!empty($lastSynchDates['qty'])) {
                    if ($this->isProductInfoOutdated($lastSynchDates['qty'])) {
                        unset($newData['online_qty'], $newData['status'], $newData['is_afn_channel']);
                        unset($existingData['online_qty'], $existingData['status'], $existingData['is_afn_channel']);
                    }
                }

                if (!empty($lastSynchDates['price'])) {
                    if ($this->isProductInfoOutdated($lastSynchDates['price'])) {
                        unset($newData['online_regular_price']);
                        unset($existingData['online_regular_price']);
                    }
                }

                if (!empty($lastSynchDates['fulfillment_switching'])) {
                    if ($this->isProductInfoOutdated($lastSynchDates['fulfillment_switching'])) {
                        unset($newData['online_qty'], $newData['status'], $newData['is_afn_channel']);
                        unset($existingData['online_qty'], $existingData['status'], $existingData['is_afn_channel']);
                    }
                }

                if (
                    $existingItem['is_repricing'] &&
                    !$existingItem['is_online_disabled'] &&
                    !$existingItem['is_online_inactive']
                ) {
                    unset($newData['online_regular_price'], $existingData['online_regular_price']);
                }

                if (!empty($existingData['online_qty_last_update_date'])) {
                    $lastQtyUpdateDate = DateHelper::createDateGmt($existingData['online_qty_last_update_date']);
                    $itemRequestDate = DateHelper::createDateGmt($newData['online_qty_last_update_date']);

                    if ($lastQtyUpdateDate > $itemRequestDate) {
                        unset(
                            $newData['online_qty'],
                            $newData['status'],
                            $newData['online_qty_last_update_date']
                        );
                        unset(
                            $existingData['online_qty'],
                            $existingData['status'],
                            $existingData['online_qty_last_update_date']
                        );
                    }
                }

                if ($newData == $existingData) {
                    continue;
                }

                $tempLogMessages = [];

                if ($this->isDataChanged($existingData, $newData, 'status')) {
                    $instructionsData[] = [
                        'listing_product_id' => (int)$existingItem['listing_product_id'],
                        'type' => Product::INSTRUCTION_TYPE_CHANNEL_STATUS_CHANGED,
                        'initiator' => Responser::INSTRUCTION_INITIATOR,
                        'priority' => 80,
                    ];

                    $newData['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT;

                    $statusChangedFrom = $componentHelper->getHumanTitleByListingProductStatus($existingData['status']);
                    $statusChangedTo = $componentHelper->getHumanTitleByListingProductStatus($newData['status']);

                    if (!empty($statusChangedFrom) && !empty($statusChangedTo)) {
                        $tempLogMessages[] = $this->helperFactory->getObject('Module_Translation')->__(
                            'Item Status was changed from "%from%" to "%to%" .',
                            $statusChangedFrom,
                            $statusChangedTo
                        );
                    }

                    if (
                        !empty($existingItem['is_variation_product'])
                        && !empty($existingItem['variation_parent_id'])
                    ) {
                        $parentIdsForProcessing[] = (int)$existingItem['variation_parent_id'];
                    }
                }

                if ($this->isDataChanged($existingData, $newData, 'online_qty')) {
                    $instructionsData[] = [
                        'listing_product_id' => (int)$existingItem['listing_product_id'],
                        'type' => Product::INSTRUCTION_TYPE_CHANNEL_QTY_CHANGED,
                        'initiator' => Responser::INSTRUCTION_INITIATOR,
                        'priority' => 80,
                    ];

                    $tempLogMessages[] = $this->helperFactory->getObject('Module_Translation')->__(
                        'Item QTY was changed from %from% to %to% .',
                        (int)$existingData['online_qty'],
                        (int)$newData['online_qty']
                    );

                    if (
                        !empty($existingItem['is_variation_product'])
                        && !empty($existingItem['variation_parent_id'])
                    ) {
                        $parentIdsForProcessing[] = (int)$existingItem['variation_parent_id'];
                    }
                }

                if ($this->isDataChanged($existingData, $newData, 'online_regular_price')) {
                    $instructionsData[] = [
                        'listing_product_id' => (int)$existingItem['listing_product_id'],
                        'type' => Product::INSTRUCTION_TYPE_CHANNEL_REGULAR_PRICE_CHANGED,
                        'initiator' => Responser::INSTRUCTION_INITIATOR,
                        'priority' => 60,
                    ];

                    $tempLogMessages[] = $this->helperFactory->getObject('Module_Translation')->__(
                        'Item Price was changed from %from% to %to% .',
                        (float)$existingData['online_regular_price'],
                        (float)$newData['online_regular_price']
                    );

                    if (
                        !empty($existingItem['is_variation_product'])
                        && !empty($existingItem['variation_parent_id'])
                    ) {
                        $parentIdsForProcessing[] = (int)$existingItem['variation_parent_id'];
                    }
                }

                foreach ($tempLogMessages as $tempLogMessage) {
                    $tempLog->addProductMessage(
                        $existingItem['listing_id'],
                        $existingItem['product_id'],
                        $existingItem['listing_product_id'],
                        \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
                        $this->getLogsActionId(),
                        \Ess\M2ePro\Model\Listing\Log::ACTION_CHANNEL_CHANGE,
                        $tempLogMessage,
                        \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS
                    );
                }

                $newData['id'] = (int)$existingItem['listing_product_id'];

                /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
                $listingProduct = $this->parentFactory->getObject($this->getComponentMode(), 'Listing_Product');
                $listingProduct->addData($newData)->getChildObject()->addData($newData);
                $listingProduct->save();
            }
        }

        $this->activeRecordFactory->getObject('Listing_Product_Instruction')->getResource()->add($instructionsData);
        $this->processParentProcessors($parentIdsForProcessing);
    }

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getPreparedProductsCollection()
    {
        if ($this->preparedListingProductsCollection !== null) {
            return $this->preparedListingProductsCollection;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->parentFactory->getObject($this->getComponentMode(), 'Listing\Product')->getCollection();
        $collection->joinListingTable();

        $collection->getSelect()->where('l.account_id = ?', (int)$this->getAccount()->getId());
        $collection->getSelect()->where(
            '`main_table`.`status` != ?',
            \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED
        );
        $collection->getSelect()->where("`second_table`.`is_variation_parent` != ?", 1);
        $collection->getSelect()->joinLeft(
            [
                'repricing' => $this->activeRecordFactory->getObject('Amazon_Listing_Product_Repricing')
                                                         ->getResource()->getMainTable(),
            ],
            'second_table.listing_product_id = repricing.listing_product_id',
            ['is_online_disabled', 'is_online_inactive']
        );

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS)->columns(
            [
                'main_table.listing_id',
                'main_table.product_id',
                'main_table.status',
                'main_table.additional_data',
                'second_table.sku',
                'second_table.general_id',
                'second_table.online_regular_price',
                'second_table.online_qty',
                'second_table.online_afn_qty',
                'second_table.is_afn_channel',
                'second_table.is_isbn_general_id',
                'second_table.listing_product_id',
                'second_table.is_variation_product',
                'second_table.variation_parent_id',
                'second_table.is_repricing',
                sprintf(
                    'second_table.%s',
                    AmazonListingProductResource::COLUMN_ONLINE_QTY_LAST_UPDATE_DATE
                ),
                'repricing.is_online_disabled',
                'repricing.is_online_inactive',
            ]
        );

        return $this->preparedListingProductsCollection = $collection;
    }

    /**
     * @param $lastDate
     *
     * @return bool
     * @throws \Exception
     */
    protected function isProductInfoOutdated($lastDate)
    {
        if (empty($this->responserParams['request_date'])) {
            return false;
        }

        $lastDate = new \DateTime($lastDate, new \DateTimeZone('UTC'));
        $requestDate = new \DateTime($this->responserParams['request_date'], new \DateTimeZone('UTC'));

        $lastDate->modify('+1 hour');

        return $lastDate > $requestDate;
    }

    /**
     * @return string
     */
    protected function getInventoryIdentifier()
    {
        return 'sku';
    }

    /**
     * @return string
     */
    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Amazon::NICK;
    }
}
