<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\SynchronizeInventory\Walmart;

use Ess\M2ePro\Model\Listing\SynchronizeInventory\AbstractExistingProductsHandler;
use Ess\M2ePro\Model\Cron\Task\Walmart\Listing\SynchronizeInventory\Responser;
use Ess\M2ePro\Model\Walmart\Listing\Product;

class ListingProductsHandler extends AbstractExistingProductsHandler
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection */
    protected $preparedListingProductsCollection;

    //########################################

    /**
     * @param array $responseData
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
        $tempLog = $this->activeRecordFactory->getObject('Listing\Log');
        $tempLog->setComponentMode($this->getComponentMode());

        $dataHelper      = $this->helperFactory->getObject('Data');
        $componentHelper = $this->helperFactory->getObject('Component\Walmart');

        $parentIdsForProcessing = [];
        $instructionsData = [];

        foreach (array_chunk(array_keys($this->responseData), 200) as $wpids) {
            $stmtTemp = $this->getPdoStatementExistingListings($wpids);

            while ($existingItem = $stmtTemp->fetch()) {
                if (!isset($this->responseData[$existingItem['wpid']])) {
                    continue;
                }

                $receivedItem = $this->responseData[$existingItem['wpid']];
                unset($this->responseData[$existingItem['wpid']]);

                $isOnlinePriceInvalid = in_array(
                    \Ess\M2ePro\Helper\Component\Walmart::PRODUCT_STATUS_CHANGE_REASON_INVALID_PRICE,
                    $receivedItem['status_change_reason']
                );

                $newData = [
                    'upc'                     => !empty($receivedItem['upc']) ? (string)$receivedItem['upc'] : null,
                    'gtin'                    => !empty($receivedItem['gtin']) ? (string)$receivedItem['gtin'] : null,
                    'wpid'                    => (string)$receivedItem['wpid'],
                    'item_id'                 => (string)$receivedItem['item_id'],
                    'online_qty'              => (int)$receivedItem['qty'],
                    'publish_status'          => (string)$receivedItem['publish_status'],
                    'lifecycle_status'        => (string)$receivedItem['lifecycle_status'],
                    'status_change_reasons'   => $dataHelper->jsonEncode($receivedItem['status_change_reason']),
                    'is_online_price_invalid' => $isOnlinePriceInvalid,
                    'is_missed_on_channel'    => false,
                ];

                $newData['status'] = $componentHelper->getResultProductStatus(
                    $receivedItem['publish_status'], $receivedItem['lifecycle_status'], $newData['online_qty']
                );

                $existingData = [
                    'upc'                     => !empty($existingItem['upc']) ? (string)$existingItem['upc'] : null,
                    'gtin'                    => !empty($existingItem['gtin']) ? (string)$existingItem['gtin'] : null,
                    'wpid'                    => (string)$existingItem['wpid'],
                    'item_id'                 => (string)$existingItem['item_id'],
                    'online_qty'              => (int)$existingItem['online_qty'],
                    'status'                  => (int)$existingItem['status'],
                    'publish_status'          => (string)$existingItem['publish_status'],
                    'lifecycle_status'        => (string)$existingItem['lifecycle_status'],
                    'status_change_reasons'   => (string)$existingItem['status_change_reasons'],
                    'is_online_price_invalid' => (bool)$existingItem['is_online_price_invalid'],
                    'is_missed_on_channel'    => (bool)$existingItem['is_missed_on_channel'],
                ];

                $existingAdditionalData = $dataHelper->jsonDecode($existingItem['additional_data']);
                $lastSynchDates         = !empty($existingAdditionalData['last_synchronization_dates'])
                    ? $existingAdditionalData['last_synchronization_dates']
                    : [];

                if (!empty($lastSynchDates['qty']) && !empty($receivedItem['actual_on_date'])) {
                    if ($this->isProductInfoOutdated($lastSynchDates['qty'], $receivedItem['actual_on_date'])) {
                        unset(
                            $newData['online_qty'], $newData['status'],
                            $newData['lifecycle_status'], $newData['publish_status']
                        );
                        unset(
                            $existingData['online_qty'], $existingData['status'],
                            $existingData['lifecycle_status'], $existingData['publish_status']
                        );
                    }
                }

                if (!empty($lastSynchDates['price']) && !empty($receivedItem['actual_on_date'])) {
                    if ($this->isProductInfoOutdated($lastSynchDates['price'], $receivedItem['actual_on_date'])) {
                        unset(
                            $newData['status'], $newData['lifecycle_status'],
                            $newData['publish_status'], $newData['is_online_price_invalid']
                        );
                        unset(
                            $existingData['status'], $existingData['lifecycle_status'],
                            $existingData['publish_status'], $existingData['is_online_price_invalid']
                        );
                    }
                }

                if ($newData == $existingData) {
                    continue;
                }

                $tempLogMessages = [];

                if ($this->isDataChanged($existingData, $newData, 'status')) {
                    $instructionsData[] = [
                        'listing_product_id' => $existingItem['listing_product_id'],
                        'type'               => Product::INSTRUCTION_TYPE_CHANNEL_STATUS_CHANGED,
                        'initiator'          => Responser::INSTRUCTION_INITIATOR,
                        'priority'           => 80,
                    ];

                    $newData['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT;

                    $statusChangedFrom = $componentHelper->getHumanTitleByListingProductStatus($existingData['status']);
                    $statusChangedTo   = $componentHelper->getHumanTitleByListingProductStatus($newData['status']);

                    if (!empty($statusChangedFrom) && !empty($statusChangedTo)) {
                        $tempLogMessages[] = $this->helperFactory->getObject('Module_Translation')->__(
                            'Item Status was changed from "%from%" to "%to%" .',
                            $statusChangedFrom,
                            $statusChangedTo
                        );
                    }

                    if (!empty($existingItem['is_variation_product']) && !empty($existingItem['variation_parent_id'])) {
                        $parentIdsForProcessing[] = (int)$existingItem['variation_parent_id'];
                    }
                }

                if ($this->isDataChanged($existingData, $newData, 'online_qty')) {
                    $instructionsData[] = [
                        'listing_product_id' => $existingItem['listing_product_id'],
                        'type'               => Product::INSTRUCTION_TYPE_CHANNEL_QTY_CHANGED,
                        'initiator'          => Responser::INSTRUCTION_INITIATOR,
                        'priority'           => 80,
                    ];

                    $tempLogMessages[] = $this->helperFactory->getObject('Module_Translation')->__(
                        'Item QTY was changed from %from% to %to% .',
                        (int)$existingData['online_qty'],
                        (int)$newData['online_qty']
                    );

                    if (!empty($existingItem['is_variation_product']) && !empty($existingItem['variation_parent_id'])) {
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

    //########################################

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getPreparedProductsCollection()
    {
        if ($this->preparedListingProductsCollection !== null) {
            return $this->preparedListingProductsCollection;
        }

        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection */
        $collection = $this->parentFactory->getObject($this->getComponentMode(), 'Listing\Product')->getCollection();
        $collection->joinListingTable();

        $collection->getSelect()->where('l.account_id = ?', (int)$this->getAccount()->getId());
        $collection->getSelect()->where(
            '`main_table`.`status` != ?',
            \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED
        );
        $collection->getSelect()->where("`second_table`.`wpid` is not null and `second_table`.`wpid` != ''");
        $collection->getSelect()->where("`second_table`.`is_variation_parent` != ?", 1);

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS)->columns(
            [
                'main_table.listing_id',
                'main_table.product_id',
                'main_table.status',
                'main_table.additional_data',
                'second_table.sku',
                'second_table.upc',
                'second_table.ean',
                'second_table.gtin',
                'second_table.wpid',
                'second_table.item_id',
                'second_table.online_qty',
                'second_table.listing_product_id',
                'second_table.is_variation_product',
                'second_table.variation_parent_id',
                'second_table.is_online_price_invalid',
                'second_table.publish_status',
                'second_table.lifecycle_status',
                'second_table.status_change_reasons',
                'second_table.is_missed_on_channel',
            ]
        );

        return $this->preparedListingProductsCollection = $collection;
    }

    /**
     * @param $lastDate
     * @param $actualOnDate
     * @return bool
     * @throws \Exception
     */
    protected function isProductInfoOutdated($lastDate, $actualOnDate)
    {
        $lastDate = new \DateTime($lastDate, new \DateTimeZone('UTC'));
        $actualOnDate = new \DateTime($actualOnDate, new \DateTimeZone('UTC'));

        $lastDate->modify('+1 hour');

        return $lastDate > $actualOnDate;
    }

    /**
     * @return string
     */
    protected function getInventoryIdentifier()
    {
        return 'wpid';
    }

    /**
     * @return string
     */
    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Walmart::NICK;
    }

    //########################################
}
