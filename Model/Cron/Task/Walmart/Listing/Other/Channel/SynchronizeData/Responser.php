<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Other\Channel\SynchronizeData;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Other\Channel\SynchronizeData\Responser
 */
class Responser extends \Ess\M2ePro\Model\Walmart\Connector\Inventory\Get\ItemsResponser
{
    protected $synchronizationLog = null;

    protected $resourceConnection;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = []
    ) {
        $this->resourceConnection = $resourceConnection;
        parent::__construct($walmartFactory, $activeRecordFactory, $response, $helperFactory, $modelFactory, $params);
    }

    //########################################

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
                $this->getHelper('Module_Translation')->__($message->getText()),
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

    //########################################

    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        $this->getSynchronizationLog()->addMessage(
            $this->getHelper('Module_Translation')->__($messageText),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
        );
    }

    //########################################

    protected function processResponseData()
    {
        $receivedItems = $this->getReceivedOnlyOtherListings();

        try {
            $this->updateReceivedOtherListings($receivedItems);
            $this->createNotExistedOtherListings($receivedItems);
        } catch (\Exception $exception) {
            $this->getHelper('Module_Exception')->process($exception);

            $this->getSynchronizationLog()->addMessage(
                $this->getHelper('Module_Translation')->__($exception->getMessage()),
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );
        }
    }

    //########################################

    protected function updateReceivedOtherListings($receivedItems)
    {
        /** @var $stmtTemp \Zend_Db_Statement_Pdo */
        $stmtTemp = $this->getPdoStatementExistingListings(true);

        while ($existingItem = $stmtTemp->fetch()) {
            if (!isset($receivedItems[$existingItem['wpid']])) {
                continue;
            }

            $receivedItem = $receivedItems[$existingItem['wpid']];

            $isOnlinePriceInvalid = in_array(
                \Ess\M2ePro\Helper\Component\Walmart::PRODUCT_STATUS_CHANGE_REASON_INVALID_PRICE,
                $receivedItem['status_change_reason']
            );

            $newData = [
                'upc'                   => !empty($receivedItem['upc']) ? (string)$receivedItem['upc'] : null,
                'gtin'                  => !empty($receivedItem['gtin']) ? (string)$receivedItem['gtin'] : null,
                'wpid'                  => (string)$receivedItem['wpid'],
                'item_id'               => (string)$receivedItem['item_id'],
                'sku'                   => (string)$receivedItem['sku'],
                'title'                 => (string)$receivedItem['title'],
                'online_price'          => (float)$receivedItem['price'],
                'online_qty'            => (int)$receivedItem['qty'],
                'publish_status'        => (string)$receivedItem['publish_status'],
                'lifecycle_status'      => (string)$receivedItem['lifecycle_status'],
                'status_change_reasons' => $this->getHelper('Data')->jsonEncode($receivedItem['status_change_reason']),
                'is_online_price_invalid' => $isOnlinePriceInvalid,
            ];

            $newData['status'] = $this->getHelper('Component_Walmart')->getResultProductStatus(
                $receivedItem['publish_status'],
                $receivedItem['lifecycle_status'],
                $newData['online_qty']
            );

            $existingData = [
                'upc'                   => !empty($existingItem['upc']) ? (string)$existingItem['upc'] : null,
                'gtin'                  => !empty($existingItem['gtin']) ? (string)$existingItem['gtin'] : null,
                'wpid'                  => (string)$existingItem['wpid'],
                'item_id'               => (string)$existingItem['item_id'],
                'sku'                   => (string)$existingItem['sku'],
                'title'                 => (string)$existingItem['title'],
                'online_price'          => (float)$existingItem['online_price'],
                'online_qty'            => (int)$existingItem['online_qty'],
                'publish_status'        => (string)$existingItem['publish_status'],
                'lifecycle_status'      => (string)$existingItem['lifecycle_status'],
                'status_change_reasons' => (string)$existingItem['status_change_reasons'],
                'status'                => (int)$existingItem['status'],
                'is_online_price_invalid' => (bool)$existingItem['is_online_price_invalid'],
            ];

            if ($newData == $existingData) {
                continue;
            }

            if ($newData['status'] != $existingData['status']) {
                $newData['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT;
            }

            $listingOtherObj = $this->walmartFactory->getObjectLoaded(
                'Listing\Other',
                (int)$existingItem['listing_other_id']
            );

            $listingOtherObj->addData($newData)->save();
        }
    }

    protected function createNotExistedOtherListings($receivedItems)
    {
        /** @var $stmtTemp \Zend_Db_Statement_Pdo */
        $stmtTemp = $this->getPdoStatementExistingListings(false);

        while ($existingItem = $stmtTemp->fetch()) {
            if (!isset($receivedItems[$existingItem['wpid']])) {
                continue;
            }

            $receivedItems[$existingItem['wpid']]['founded'] = true;
        }

        /** @var $mappingModel \Ess\M2ePro\Model\Walmart\Listing\Other\Mapping */
        $mappingModel = $this->modelFactory->getObject('Walmart_Listing_Other_Mapping');

        foreach ($receivedItems as $receivedItem) {
            if (isset($receivedItem['founded'])) {
                continue;
            }

            $isOnlinePriceInvalid = in_array(
                \Ess\M2ePro\Helper\Component\Walmart::PRODUCT_STATUS_CHANGE_REASON_INVALID_PRICE,
                $receivedItem['status_change_reason']
            );

            $newData = [
                'account_id'     => $this->getAccount()->getId(),
                'marketplace_id' => $this->getMarketplace()->getId(),
                'product_id'     => null,

                'upc'     => !empty($receivedItem['upc']) ? (string)$receivedItem['upc'] : null,
                'gtin'    => !empty($receivedItem['gtin']) ? (string)$receivedItem['gtin'] : null,
                'wpid'    => (string)$receivedItem['wpid'],
                'item_id' => (string)$receivedItem['item_id'],

                'sku'   => (string)$receivedItem['sku'],
                'title' => $receivedItem['title'],

                'online_price' => (float)$receivedItem['price'],
                'online_qty'   => (int)$receivedItem['qty'],

                'publish_status'        => (string)$receivedItem['publish_status'],
                'lifecycle_status'      => (string)$receivedItem['lifecycle_status'],
                'status_change_reasons' => $this->getHelper('Data')->jsonEncode($receivedItem['status_change_reason']),
                'is_online_price_invalid' => $isOnlinePriceInvalid,
            ];

            $newData['status'] = $this->getHelper('Component_Walmart')->getResultProductStatus(
                $receivedItem['publish_status'],
                $receivedItem['lifecycle_status'],
                $newData['online_qty']
            );

            $newData['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT;

            $listingOtherModel = $this->walmartFactory->getObject('Listing\Other');
            $listingOtherModel->setData($newData)->save();

            if (!$this->getAccount()->getChildObject()->isOtherListingsMappingEnabled()) {
                continue;
            }

            $mappingModel->initialize($this->getAccount());
            $mappingModel->autoMapOtherListingProduct($listingOtherModel);
        }
    }

    //########################################

    protected function getReceivedOnlyOtherListings()
    {
        $connection = $this->resourceConnection->getConnection();

        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection */
        $collection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS)->columns(['second_table.wpid']);

        $listingTable = $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable();

        $collection->getSelect()->join(['l' => $listingTable], 'main_table.listing_id = l.id', []);
        $collection->getSelect()->where('l.account_id = ?', (int)$this->getAccount()->getId());

        /** @var $stmtTemp \Zend_Db_Statement_Pdo */
        $stmtTemp = $connection->query($collection->getSelect()->__toString());

        $responseData = $this->getPreparedResponseData();
        $receivedItems = $responseData['data'];

        while ($existListingProduct = $stmtTemp->fetch()) {
            if (empty($existListingProduct['wpid'])) {
                continue;
            }

            if (isset($receivedItems[$existListingProduct['wpid']])) {
                unset($receivedItems[$existListingProduct['wpid']]);
            }
        }

        return $receivedItems;
    }

    protected function getPdoStatementExistingListings($withData = false)
    {
        $connection = $this->resourceConnection->getConnection();

        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Listing\Other\Collection */
        $collection = $this->walmartFactory->getObject('Listing\Other')->getCollection();
        $collection->getSelect()->where('`main_table`.`account_id` = ?', (int)$this->params['account_id']);

        $tempColumns = ['second_table.wpid'];

        if ($withData) {
            $tempColumns = [
                'main_table.status',
                'second_table.sku', 'second_table.title',
                'second_table.online_price', 'second_table.online_qty',
                'second_table.publish_status', 'second_table.lifecycle_status',
                'second_table.status_change_reasons',
                'second_table.upc', 'second_table.gtin', 'second_table.ean', 'second_table.wpid',
                'second_table.item_id', 'second_table.listing_other_id',
                'second_table.is_online_price_invalid'
            ];
        }

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS)->columns($tempColumns);

        /** @var $stmtTemp \Zend_Db_Statement_Pdo */
        $stmtTemp = $connection->query($collection->getSelect()->__toString());

        return $stmtTemp;
    }

    //########################################

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

    protected function getSynchronizationLog()
    {
        if ($this->synchronizationLog !== null) {
            return $this->synchronizationLog;
        }

        $this->synchronizationLog = $this->activeRecordFactory->getObject('Synchronization\Log');
        $this->synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Walmart::NICK);
        $this->synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_OTHER_LISTINGS);

        return $this->synchronizationLog;
    }

    //########################################
}
