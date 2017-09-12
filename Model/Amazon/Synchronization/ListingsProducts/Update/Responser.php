<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\ListingsProducts\Update;

class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\ItemsResponser
{
    protected $resourceConnection;

    protected $activeRecordFactory;

    protected $logsActionId = NULL;
    protected $synchronizationLog = NULL;

    // ########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = array()
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($amazonFactory, $response, $helperFactory, $modelFactory, $params);
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
        $tempLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);

        $responseData = $this->getPreparedResponseData();

        $parentIdsForProcessing = array();
        $listingsProductsIdsForNeedSynchRulesCheck = array();

        while ($existingItem = $stmtTemp->fetch()) {

            if (!isset($responseData['data'][$existingItem['sku']])) {
                continue;
            }

            $receivedItem = $responseData['data'][$existingItem['sku']];

            $newData = array(
                'general_id'           => (string)$receivedItem['identifiers']['general_id'],
                'online_regular_price' => !empty($receivedItem['price']) ? (float)$receivedItem['price'] : NULL,
                'online_qty'           => (int)$receivedItem['qty'],
                'is_afn_channel'       => (bool)$receivedItem['channel']['is_afn'],
                'is_isbn_general_id'   => (bool)$receivedItem['identifiers']['is_isbn']
            );

            if ($newData['is_afn_channel']) {
                $newData['online_qty'] = NULL;
                $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN;
            } else {
                if ($newData['online_qty'] > 0) {
                    $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;
                } else {
                    $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED;
                }
            }

            $existingData = array(
                'general_id'           => (string)$existingItem['general_id'],
                'online_regular_price' => !empty($existingItem['online_regular_price'])
                    ? (float)$existingItem['online_regular_price'] : NULL,
                'online_qty'           => (int)$existingItem['online_qty'],
                'is_afn_channel'       => (bool)$existingItem['is_afn_channel'],
                'is_isbn_general_id'   => (bool)$existingItem['is_isbn_general_id'],
                'status'               => (int)$existingItem['status']
            );

            $existingAdditionalData = $this->getHelper('Data')->jsonDecode($existingItem['additional_data']);

            if (!empty($existingAdditionalData['last_synchronization_dates']['qty']) &&
                !empty($this->params['request_date'])
            ) {
                $lastQtySynchDate = $existingAdditionalData['last_synchronization_dates']['qty'];

                if ($this->isProductInfoOutdated($lastQtySynchDate)) {
                    unset($newData['online_qty'], $newData['status'], $newData['is_afn_channel']);
                    unset($existingData['online_qty'], $existingData['status'], $existingData['is_afn_channel']);
                }
            }

            if (!empty($existingAdditionalData['last_synchronization_dates']['price']) &&
                !empty($this->params['request_date'])
            ) {
                $lastPriceSynchDate = $existingAdditionalData['last_synchronization_dates']['price'];

                if ($this->isProductInfoOutdated($lastPriceSynchDate)) {
                    unset($newData['online_regular_price']);
                    unset($existingData['online_regular_price']);
                }
            }

            if (!empty($existingAdditionalData['last_synchronization_dates']['fulfillment_switching']) &&
                !empty($this->params['request_date'])
            ) {
                $lastFulfilmentSwitchingDate =
                    $existingAdditionalData['last_synchronization_dates']['fulfillment_switching'];

                if ($this->isProductInfoOutdated($lastFulfilmentSwitchingDate)) {
                    unset($newData['online_qty'], $newData['status'], $newData['is_afn_channel']);
                    unset($existingData['online_qty'], $existingData['status'], $existingData['is_afn_channel']);
                }
            }

            if ($existingItem['is_repricing'] && !$existingItem['is_online_disabled']) {
                unset($newData['online_regular_price'], $existingData['online_regular_price']);
            }

            if ($newData == $existingData) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $this->amazonFactory->getObjectLoaded(
                'Listing\Product',(int)$existingItem['listing_product_id']
            );

            if ($this->isDataChanged($existingData, $newData, 'status') ||
                $this->isDataChanged($existingData, $newData, 'online_qty') ||
                $this->isDataChanged($existingData, $newData, 'online_regular_price')
            ) {
                $this->activeRecordFactory->getObject('ProductChange')->addUpdateAction(
                    $existingItem['product_id'], \Ess\M2ePro\Model\ProductChange::INITIATOR_SYNCHRONIZATION
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

            $tempLogMessages = array();

            if (
                isset($newData['online_regular_price'])
                && $newData['online_regular_price']
                != $existingData['online_regular_price']
            ) {
                // M2ePro\TRANSLATIONS
                // Item Price was successfully changed from %from% to %to% .
                $tempLogMessages[] = $this->getHelper('Module\Translation')->__(
                    'Item Price was successfully changed from %from% to %to% .',
                    (float)$existingData['online_regular_price'],
                    (float)$newData['online_regular_price']
                );
            }

            if (isset($newData['online_qty']) && $newData['online_qty'] != $existingData['online_qty']) {
                // M2ePro\TRANSLATIONS
                // Item QTY was successfully changed from %from% to %to% .
                $tempLogMessages[] = $this->getHelper('Module\Translation')->__(
                    'Item QTY was successfully changed from %from% to %to% .',
                    (int)$existingData['online_qty'],
                    (int)$newData['online_qty']
                );
            }

            if (isset($newData['status']) && $newData['status'] != $existingData['status']) {

                $newData['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT;

                $statusChangedFrom = $this->getHelper('Component\Amazon')
                    ->getHumanTitleByListingProductStatus($existingData['status']);
                $statusChangedTo = $this->getHelper('Component\Amazon')
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

        $collection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $collection->getSelect()->join(array('l' => $listingTable), 'main_table.listing_id = l.id', array());
        $collection->getSelect()->where('l.account_id = ?',(int)$this->getAccount()->getId());
        $collection->getSelect()->where('`main_table`.`status` != ?',
                                        (int)\Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED);
        $collection->getSelect()->where("`second_table`.`sku` is not null and `second_table`.`sku` != ''");
        $collection->getSelect()->where("`second_table`.`is_variation_parent` != ?", 1);

        $tempColumns = array('second_table.sku');

        if ($withData) {

            $repricingTable = $this->activeRecordFactory->getObject('Amazon\Listing\Product\Repricing')
                                                        ->getResource()->getMainTable();

            $collection->getSelect()->joinLeft(
                ['repricing' => $repricingTable],
                'second_table.listing_product_id = repricing.listing_product_id',
                ['is_online_disabled']
            );

            $tempColumns = array(
                'main_table.listing_id',
                'main_table.product_id','main_table.status',
                'main_table.additional_data',
                'second_table.sku','second_table.general_id',
                'second_table.online_regular_price','second_table.online_qty',
                'second_table.is_afn_channel', 'second_table.is_isbn_general_id',
                'second_table.listing_product_id',
                'second_table.is_variation_product', 'second_table.variation_parent_id',
                'second_table.is_repricing', 'repricing.is_online_disabled'
            );
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
        $parentListingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $parentListingProductCollection->addFieldToFilter('id', array('in' => array_unique($parentIds)));

        $parentListingsProducts = $parentListingProductCollection->getItems();
        if (empty($parentListingsProducts)) {
            return;
        }

        $massProcessor = $this->modelFactory->getObject(
            'Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Mass'
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
        return $this->getObjectByParam('Account','account_id');
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
        if (!is_null($this->logsActionId)) {
            return $this->logsActionId;
        }

        return $this->logsActionId = $this->activeRecordFactory->getObject('Listing\Log')
                                          ->getResource()->getNextActionId();
    }

    protected function getSynchronizationLog()
    {
        if (!is_null($this->synchronizationLog)) {
            return $this->synchronizationLog;
        }

        $this->synchronizationLog = $this->activeRecordFactory->getObject('Synchronization\Log');
        $this->synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $this->synchronizationLog->setSynchronizationTask(
            \Ess\M2ePro\Model\Synchronization\Log::TASK_LISTINGS_PRODUCTS
        );

        return $this->synchronizationLog;
    }

    //-----------------------------------------

    private function isProductInfoOutdated($lastDate)
    {
        $lastDate = new \DateTime($lastDate, new \DateTimeZone('UTC'));
        $requestDate = new \DateTime($this->params['request_date'], new \DateTimeZone('UTC'));

        $lastDate->modify('+1 hour');

        return $lastDate > $requestDate;
    }

    //-----------------------------------------

    private function isDataChanged($existData, $newData, $key)
    {
        if (!isset($existData[$key]) || !isset($newData[$key])) {
            return false;
        }

        return $existData[$key] != $newData[$key];
    }

    // ########################################
}