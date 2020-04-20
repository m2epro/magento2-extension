<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Other\Channel\SynchronizeData;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Other\Channel\SynchronizeData\Responser
 */
class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\ItemsResponser
{
    protected $synchronizationLog = null;

    protected $resourceConnection;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = []
    ) {
        $this->resourceConnection = $resourceConnection;
        parent::__construct($amazonFactory, $activeRecordFactory, $response, $helperFactory, $modelFactory, $params);
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
            if (!isset($receivedItems[$existingItem['sku']])) {
                continue;
            }

            $receivedItem = $receivedItems[$existingItem['sku']];

            $newData = [
                'general_id'         => (string)$receivedItem['identifiers']['general_id'],
                'title'              => (string)$receivedItem['title'],
                'online_price'       => (float)$receivedItem['price'],
                'online_qty'         => (int)$receivedItem['qty'],
                'is_afn_channel'     => (bool)$receivedItem['channel']['is_afn'],
                'is_isbn_general_id' => (bool)$receivedItem['identifiers']['is_isbn']
            ];

            if ($newData['is_afn_channel']) {
                $newData['online_qty'] = null;
                $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN;
            } else {
                if ($newData['online_qty'] > 0) {
                    $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;
                } else {
                    $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED;
                }
            }

            $existingData = [
                'general_id'         => (string)$existingItem['general_id'],
                'title'              => (string)$existingItem['title'],
                'online_price'       => (float)$existingItem['online_price'],
                'online_qty'         => (int)$existingItem['online_qty'],
                'is_afn_channel'     => (bool)$existingItem['is_afn_channel'],
                'is_isbn_general_id' => (bool)$existingItem['is_isbn_general_id'],
                'status'             => (int)$existingItem['status']
            ];

            if ($receivedItem['title'] === null ||
                $receivedItem['title'] == \Ess\M2ePro\Model\Amazon\Listing\Other::EMPTY_TITLE_PLACEHOLDER) {
                unset($newData['title'], $existingData['title']);
            }

            if ($existingItem['is_repricing'] && !$existingItem['is_repricing_disabled']) {
                unset($newData['online_price'], $existingData['online_price']);
            }

            if ($newData == $existingData) {
                continue;
            }

            if ($newData['status'] != $existingData['status']) {
                $newData['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT;
            }

            $listingOtherObj = $this->amazonFactory->getObjectLoaded(
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
            if (!isset($receivedItems[$existingItem['sku']])) {
                continue;
            }

            $receivedItems[$existingItem['sku']]['founded'] = true;
        }

        /** @var $mappingModel \Ess\M2ePro\Model\Amazon\Listing\Other\Mapping */
        $mappingModel = $this->modelFactory->getObject('Amazon_Listing_Other_Mapping');

        foreach ($receivedItems as $receivedItem) {
            if (isset($receivedItem['founded'])) {
                continue;
            }

            $newData = [
                'account_id'     => $this->getAccount()->getId(),
                'marketplace_id' => $this->getMarketplace()->getId(),
                'product_id'     => null,

                'general_id' => (string)$receivedItem['identifiers']['general_id'],

                'sku'   => (string)$receivedItem['identifiers']['sku'],
                'title' => $receivedItem['title'],

                'online_price' => (float)$receivedItem['price'],
                'online_qty'   => (int)$receivedItem['qty'],

                'is_afn_channel'     => (bool)$receivedItem['channel']['is_afn'],
                'is_isbn_general_id' => (bool)$receivedItem['identifiers']['is_isbn']
            ];

            if (isset($this->params['full_items_data']) && $this->params['full_items_data'] &&
                $newData['title'] == \Ess\M2ePro\Model\Amazon\Listing\Other::EMPTY_TITLE_PLACEHOLDER) {

                $newData['title'] = null;
            }

            if ((bool)$newData['is_afn_channel']) {
                $newData['online_qty'] = null;
                $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN;
            } else {
                if ((int)$newData['online_qty'] > 0) {
                    $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;
                } else {
                    $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED;
                }
            }

            $newData['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT;

            $listingOtherModel = $this->amazonFactory->getObject('Listing\Other');
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
        $collection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS)->columns(['second_table.sku']);

        $listingTable = $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable();

        $collection->getSelect()->join(['l' => $listingTable], 'main_table.listing_id = l.id', []);
        $collection->getSelect()->where('l.account_id = ?', (int)$this->getAccount()->getId());

        /** @var $stmtTemp \Zend_Db_Statement_Pdo */
        $stmtTemp = $connection->query($collection->getSelect()->__toString());

        $responseData = $this->getPreparedResponseData();
        $receivedItems = $responseData['data'];

        while ($existListingProduct = $stmtTemp->fetch()) {
            if (empty($existListingProduct['sku'])) {
                continue;
            }

            if (isset($receivedItems[$existListingProduct['sku']])) {
                unset($receivedItems[$existListingProduct['sku']]);
            }
        }

        return $receivedItems;
    }

    protected function getPdoStatementExistingListings($withData = false)
    {
        $connection = $this->resourceConnection->getConnection();

        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Listing\Other\Collection */
        $collection = $this->amazonFactory->getObject('Listing\Other')->getCollection();
        $collection->getSelect()->where('`main_table`.`account_id` = ?', (int)$this->params['account_id']);

        $tempColumns = ['second_table.sku'];

        if ($withData) {
            $tempColumns = [
                'main_table.status',
                'second_table.sku', 'second_table.general_id', 'second_table.title',
                'second_table.online_price', 'second_table.online_qty',
                'second_table.is_afn_channel', 'second_table.is_isbn_general_id',
                'second_table.listing_other_id',
                'second_table.is_repricing', 'second_table.is_repricing_disabled'
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
        $this->synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $this->synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_OTHER_LISTINGS);

        return $this->synchronizationLog;
    }

    //########################################
}
