<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Synchronization\ListingsProducts\Update;

use \Ess\M2ePro\Model\Walmart\Listing\Product as WalmartProduct;

/**
 * Class \Ess\M2ePro\Model\Walmart\Synchronization\ListingsProducts\Update\Responser
 */
class Responser extends \Ess\M2ePro\Model\Walmart\Connector\Inventory\Get\ItemsResponser
{
    protected $resourceConnection;

    protected $activeRecordFactory;

    protected $logsActionId = null;
    protected $synchronizationLog = null;

    // ########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = []
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($walmartFactory, $response, $helperFactory, $modelFactory, $params);
    }

    // ########################################

    protected function processResponseMessages()
    {
        parent::processResponseMessages();

        foreach ($this->getResponse()->getMessages()->getEntities() as $message) {
            if (!$message->isError() && !$message->isWarning()) {
                continue;
            }

            $logType = $message->isError() ? \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
                : \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING;

            $this->getSynchronizationLog()->addMessage(
                $this->getHelper('Module\Translation')->__($message->getText()),
                $logType,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );
        }
    }

    protected function isNeedProcessResponse()
    {
        if (!parent::isNeedProcessResponse()) {
            return false;
        }

        if ($this->getResponse()->getMessages()->hasErrorEntities()) {
            return false;
        }

        return true;
    }

    // ########################################

    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        $this->getSynchronizationLog()->addMessage(
            $this->getHelper('Module\Translation')->__($messageText),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
        );
    }

    // ########################################

    protected function processResponseData()
    {
        try {
            $this->updateReceivedListingsProducts();
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);

            $this->getSynchronizationLog()->addMessage(
                $this->getHelper('Module\Translation')->__($exception->getMessage()),
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );
        }
    }

    // ########################################

    protected function updateReceivedListingsProducts()
    {
        /** @var $stmtTemp \Zend_Db_Statement_Pdo */
        $stmtTemp = $this->getPdoStatementExistingListings(true);

        $tempLog = $this->activeRecordFactory->getObject('Listing\Log');
        $tempLog->setComponentMode(\Ess\M2ePro\Helper\Component\Walmart::NICK);

        $responseData = $this->getPreparedResponseData();

        $parentIdsForProcessing = [];
        $listingsProductsIdsForNeedSynchRulesCheck = [];

        while ($existingItem = $stmtTemp->fetch()) {
            if (!isset($responseData['data'][$existingItem['wpid']])) {
                continue;
            }

            $receivedItem = $responseData['data'][$existingItem['wpid']];

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
                'channel_url'             => (string)$receivedItem['item_page_url'],
                'publish_status'          => (string)$receivedItem['publish_status'],
                'lifecycle_status'        => (string)$receivedItem['lifecycle_status'],
                'status_change_reasons'   =>
                    $this->getHelper('Data')->jsonEncode($receivedItem['status_change_reason']),
                'is_online_price_invalid' => $isOnlinePriceInvalid,
                'is_missed_on_channel'    => false,
            ];

            $newData['status'] = $this->getHelper('Component\Walmart')->getResultProductStatus(
                $receivedItem['publish_status'],
                $receivedItem['lifecycle_status'],
                $newData['online_qty']
            );

            $existingData = [
                'upc'                     => !empty($existingItem['upc']) ? (string)$existingItem['upc'] : null,
                'gtin'                    => !empty($existingItem['gtin']) ? (string)$existingItem['gtin'] : null,
                'wpid'                    => (string)$existingItem['wpid'],
                'item_id'                 => (string)$existingItem['item_id'],
                'online_qty'              => (int)$existingItem['online_qty'],
                'status'                  => (int)$existingItem['status'],
                'channel_url'             => (string)$existingItem['channel_url'],
                'publish_status'          => (string)$existingItem['publish_status'],
                'lifecycle_status'        => (string)$existingItem['lifecycle_status'],
                'status_change_reasons'   => (string)$existingItem['status_change_reasons'],
                'is_online_price_invalid' => (bool)$existingItem['is_online_price_invalid'],
                'is_missed_on_channel'    => (bool)$existingItem['is_missed_on_channel'],
            ];

            $existingAdditionalData = $this->getHelper('Data')->jsonDecode($existingItem['additional_data']);

            if (!empty($existingAdditionalData['last_synchronization_dates']['qty']) &&
                !empty($receivedItem['actual_on_date'])
            ) {
                $lastQtySynchDate = $existingAdditionalData['last_synchronization_dates']['qty'];

                if ($this->isProductInfoOutdated($lastQtySynchDate, $receivedItem['actual_on_date'])) {
                    unset(
                        $newData['online_qty'],
                        $newData['status'],
                        $newData['lifecycle_status'],
                        $newData['publish_status']
                    );
                    unset(
                        $existingData['online_qty'],
                        $existingData['status'],
                        $existingData['lifecycle_status'],
                        $existingData['publish_status']
                    );
                }
            }

            if (!empty($existingAdditionalData['last_synchronization_dates']['price']) &&
                !empty($receivedItem['actual_on_date'])
            ) {
                $lastPriceSynchDate = $existingAdditionalData['last_synchronization_dates']['price'];

                if ($this->isProductInfoOutdated($lastPriceSynchDate, $receivedItem['actual_on_date'])) {
                    unset(
                        $newData['status'],
                        $newData['lifecycle_status'],
                        $newData['publish_status'],
                        $newData['is_online_price_invalid']
                    );
                    unset(
                        $existingData['status'],
                        $existingData['lifecycle_status'],
                        $existingData['publish_status'],
                        $existingData['is_online_price_invalid']
                    );
                }
            }

            if ($newData == $existingData) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $this->walmartFactory
                ->getObjectLoaded('Listing_Product', (int)$existingItem['listing_product_id']);

            if ($this->isDataChanged($existingData, $newData, 'status') ||
                $this->isDataChanged($existingData, $newData, 'online_qty')
            ) {
                $this->activeRecordFactory->getObject('ProductChange')->addUpdateAction(
                    $existingItem['product_id'],
                    \Ess\M2ePro\Model\ProductChange::INITIATOR_SYNCHRONIZATION
                );

                if (!empty($existingItem['is_variation_product']) && !empty($existingItem['variation_parent_id'])) {
                    $parentIdsForProcessing[] = (int)$existingItem['variation_parent_id'];
                }

                if ($listingProduct->isSetProcessingLock('in_action') &&
                    ($this->isDataChanged($existingData, $newData, 'status') ||
                     $this->isDataChanged($existingData, $newData, 'online_qty'))
                ) {
                    $listingsProductsIdsForNeedSynchRulesCheck[] = $listingProduct->getId();
                }
            }

            $tempLogMessages = [];

            if (isset($newData['online_qty']) && $newData['online_qty'] != $existingData['online_qty']) {
                // M2ePro_TRANSLATIONS
                // Item QTY was successfully changed from %from% to %to% .
                $tempLogMessages[] = $this->getHelper('Module\Translation')->__(
                    'Item QTY was successfully changed from %from% to %to% .',
                    (int)$existingData['online_qty'],
                    (int)$newData['online_qty']
                );
            }

            if (isset($newData['status']) && $newData['status'] != $existingData['status']) {
                $newData['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT;

                $statusChangedFrom = $this->getHelper('Component\Walmart')
                                         ->getHumanTitleByListingProductStatus($existingData['status']);
                $statusChangedTo = $this->getHelper('Component\Walmart')
                                       ->getHumanTitleByListingProductStatus($newData['status']);

                if (!empty($statusChangedFrom) && !empty($statusChangedTo)) {
                    // M2ePro_TRANSLATIONS
                    // Item Status was successfully changed from "%from%" to "%to%" .
                    $tempLogMessages[] = $this->getHelper('Module\Translation')->__(
                        'Item Status was successfully changed from "%from%" to "%to%" .',
                        $statusChangedFrom,
                        $statusChangedTo
                    );
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
                    \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS,
                    \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
                );
            }

            $listingProduct->addData($newData);
            $listingProduct->getChildObject()->addData($newData);
            $listingProduct->save();
        }

        if (!empty($parentIdsForProcessing)) {
            $this->processParentProcessors($parentIdsForProcessing);
        }

        if (!empty($listingsProductsIdsForNeedSynchRulesCheck)) {
            $this->activeRecordFactory->getObject('Listing\Product')
                ->getResource()->setNeedSynchRulesCheck(array_unique($listingsProductsIdsForNeedSynchRulesCheck));
        }
    }

    // ########################################

    protected function getPdoStatementExistingListings($withData = false)
    {
        $listingTable = $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable();

        $collection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
        $collection->getSelect()->join(['l' => $listingTable], 'main_table.listing_id = l.id', []);

        $collection->getSelect()->where('l.account_id = ?', (int)$this->getAccount()->getId());
        $collection->getSelect()->where(
            '`main_table`.`status` != ?',
            (int)\Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED
        );
        $collection->getSelect()->where("`second_table`.`wpid` is not null and `second_table`.`wpid` != ''");
        $collection->getSelect()->where("`second_table`.`is_variation_parent` != ?", 1);

        $tempColumns = ['second_table.wpid'];

        if ($withData) {
            $tempColumns = [
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
                'second_table.channel_url',
                'second_table.publish_status',
                'second_table.lifecycle_status',
                'second_table.status_change_reasons',
                'second_table.is_missed_on_channel',
            ];
        }

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS)->columns($tempColumns);

        $stmtTemp = $this->resourceConnection->getConnection()->query($collection->getSelect()->__toString());

        return $stmtTemp;
    }

    protected function processParentProcessors(array $parentIds)
    {
        if (empty($parentIds)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $parentListingProductCollection */
        $parentListingProductCollection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
        $parentListingProductCollection->addFieldToFilter('id', ['in' => array_unique($parentIds)]);

        $parentListingsProducts = $parentListingProductCollection->getItems();
        if (empty($parentListingsProducts)) {
            return;
        }

        $massProcessor = $this->modelFactory->getObject(
            'Walmart_Listing_Product_Variation_Manager_Type_Relation_ParentRelation_Processor_Mass'
        );
        $massProcessor->setListingsProducts($parentListingsProducts);
        $massProcessor->setForceExecuting(false);

        $massProcessor->execute();
    }

    // ########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    protected function getAccount()
    {
        return $this->getObjectByParam('Account', 'account_id');
    }

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    protected function getMarketplace()
    {
        return $this->getAccount()->getChildObject()->getMarketplace();
    }

    //-----------------------------------------

    protected function getLogsActionId()
    {
        if ($this->logsActionId !== null) {
            return $this->logsActionId;
        }

        return $this->logsActionId = $this->activeRecordFactory->getObject('Listing\Log')
                                          ->getResource()->getNextActionId();
    }

    protected function getSynchronizationLog()
    {
        if ($this->synchronizationLog !== null) {
            return $this->synchronizationLog;
        }

        $this->synchronizationLog = $this->activeRecordFactory->getObject('Synchronization\Log');
        $this->synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Walmart::NICK);
        $this->synchronizationLog->setSynchronizationTask(
            \Ess\M2ePro\Model\Synchronization\Log::TASK_LISTINGS_PRODUCTS
        );

        return $this->synchronizationLog;
    }

    //-----------------------------------------

    private function isProductInfoOutdated($lastDate, $actualOnDate)
    {
        $lastDate = new \DateTime($lastDate, new \DateTimeZone('UTC'));
        $actualOnDate = new \DateTime($actualOnDate, new \DateTimeZone('UTC'));

        $lastDate->modify('+1 hour');

        return $lastDate > $actualOnDate;
    }

    private function isDataChanged($existData, $newData, $key)
    {
        if (!isset($existData[$key]) || !isset($newData[$key])) {
            return false;
        }

        return $existData[$key] != $newData[$key];
    }

    // ########################################
}
