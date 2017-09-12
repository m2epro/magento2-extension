<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\OtherListings\Update;

use \Ess\M2ePro\Model\Amazon\Listing\Other;

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
        $receivedItems = $this->getReceivedOnlyOtherListings();

        try {

            $this->updateReceivedOtherListings($receivedItems);
            $this->createNotExistedOtherListings($receivedItems);

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

    protected function updateReceivedOtherListings($receivedItems)
    {
        /** @var $stmtTemp \Zend_Db_Statement_Pdo */
        $stmtTemp = $this->getPdoStatementExistingListings(true);

        $tempLog = $this->activeRecordFactory->getObject('Listing\Other\Log');
        $tempLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);

        while ($existingItem = $stmtTemp->fetch()) {

            if (!isset($receivedItems[$existingItem['sku']])) {
                continue;
            }

            $receivedItem = $receivedItems[$existingItem['sku']];

            $newData = array(
                'general_id'         => (string)$receivedItem['identifiers']['general_id'],
                'title'              => (string)$receivedItem['title'],
                'online_price'       => (float)$receivedItem['price'],
                'online_qty'         => (int)$receivedItem['qty'],
                'is_afn_channel'     => (bool)$receivedItem['channel']['is_afn'],
                'is_isbn_general_id' => (bool)$receivedItem['identifiers']['is_isbn']
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
                'general_id'         => (string)$existingItem['general_id'],
                'title'              => (string)$existingItem['title'],
                'online_price'       => (float)$existingItem['online_price'],
                'online_qty'         => (int)$existingItem['online_qty'],
                'is_afn_channel'     => (bool)$existingItem['is_afn_channel'],
                'is_isbn_general_id' => (bool)$existingItem['is_isbn_general_id'],
                'status'             => (int)$existingItem['status']
            );

            if (is_null($receivedItem['title']) || $receivedItem['title'] == Other::EMPTY_TITLE_PLACEHOLDER) {
                unset($newData['title'], $existingData['title']);
            }

            if ($existingItem['is_repricing'] && !$existingItem['is_repricing_disabled']) {
                unset($newData['online_price'], $existingData['online_price']);
            }

            if ($newData == $existingData) {
                continue;
            }

            $tempLogMessages = array();

            if (isset($newData['online_price'], $existingData['online_price']) &&
                $newData['online_price'] != $existingData['online_price']) {
                // M2ePro\TRANSLATIONS
                // Item Price was successfully changed from %from% to %to%.
                $tempLogMessages[] = $this->getHelper('Module\Translation')->__(
                    'Item Price was successfully changed from %from% to %to%.',
                    $existingData['online_price'],
                    $newData['online_price']
                );
            }

            if (!is_null($newData['online_qty']) && $newData['online_qty'] != $existingData['online_qty']) {
                // M2ePro\TRANSLATIONS
                // Item QTY was successfully changed from %from% to %to%.
                $tempLogMessages[] = $this->getHelper('Module\Translation')->__(
                    'Item QTY was successfully changed from %from% to %to%.',
                    $existingData['online_qty'],
                    $newData['online_qty']
                );
            }

            if (is_null($newData['online_qty']) && $newData['is_afn_channel'] != $existingData['is_afn_channel']) {

                $from = \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\Qty::FULFILLMENT_MODE_MFN;
                $to = \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\Qty::FULFILLMENT_MODE_MFN;

                if ($existingData['is_afn_channel']) {
                    $from = \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\Qty::FULFILLMENT_MODE_AFN;
                }

                if ($newData['is_afn_channel']) {
                    $to = \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\Qty::FULFILLMENT_MODE_AFN;
                }

                // M2ePro\TRANSLATIONS
                // Item Fulfillment was successfully changed from %from% to %to%.
                $tempLogMessages[] = $this->getHelper('Module\Translation')->__(
                    'Item Fulfillment was successfully changed from %from% to %to%.',
                    $from,
                    $to
                );
            }

            if ($newData['status'] != $existingData['status']) {
                $newData['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT;

                $statusChangedFrom = $this->getHelper('Component\Amazon')
                    ->getHumanTitleByListingProductStatus($existingData['status']);
                $statusChangedTo = $this->getHelper('Component\Amazon')
                    ->getHumanTitleByListingProductStatus($newData['status']);

                if (!empty($statusChangedFrom) && !empty($statusChangedTo)) {
                    // M2ePro\TRANSLATIONS
                    // Item Status was successfully changed from "%from%" to "%to%".
                    $tempLogMessages[] = $this->getHelper('Module\Translation')->__(
                        'Item Status was successfully changed from "%from%" to "%to%".',
                        $statusChangedFrom,
                        $statusChangedTo
                    );
                }
            }

            foreach ($tempLogMessages as $tempLogMessage) {
                $tempLog->addProductMessage(
                    (int)$existingItem['listing_other_id'],
                    \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
                    $this->getLogsActionId(),
                   \Ess\M2ePro\Model\Listing\Other\Log::ACTION_CHANNEL_CHANGE,
                    $tempLogMessage,
                    \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS,
                    \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
                );
            }

            $listingOtherObj = $this->amazonFactory->getObjectLoaded(
                'Listing\Other',(int)$existingItem['listing_other_id']
            );

            $listingOtherObj->addData($newData);
            $listingOtherObj->getChildObject()->addData($newData);
            $listingOtherObj->save();
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

        /** @var $logModel \Ess\M2ePro\Model\Listing\Other\Log */
        $logModel = $this->activeRecordFactory->getObject('Listing\Other\Log');
        $logModel->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);

        /** @var $mappingModel \Ess\M2ePro\Model\Amazon\Listing\Other\Mapping */
        $mappingModel = $this->modelFactory->getObject('Amazon\Listing\Other\Mapping');

        /** @var $movingModel \Ess\M2ePro\Model\Amazon\Listing\Other\Moving */
        $movingModel = $this->modelFactory->getObject('Amazon\Listing\Other\Moving');

        foreach ($receivedItems as $receivedItem) {

            if (isset($receivedItem['founded'])) {
                continue;
            }

            $newData = array(
                'account_id'     => $this->getAccount()->getId(),
                'marketplace_id' => $this->getMarketplace()->getId(),
                'product_id'     => NULL,

                'general_id' => (string)$receivedItem['identifiers']['general_id'],

                'sku'   => (string)$receivedItem['identifiers']['sku'],
                'title' => (string)$receivedItem['title'],

                'online_price' => (float)$receivedItem['price'],
                'online_qty'   => (int)$receivedItem['qty'],

                'is_afn_channel'     => (bool)$receivedItem['channel']['is_afn'],
                'is_isbn_general_id' => (bool)$receivedItem['identifiers']['is_isbn']
            );

            if (
                isset($this->params['full_items_data'])
                && $this->params['full_items_data']
                && $newData['title'] == \Ess\M2ePro\Model\Amazon\Listing\Other::EMPTY_TITLE_PLACEHOLDER
            ) {
                $newData['title'] = NULL;
            }

            if ((bool)$newData['is_afn_channel']) {
                $newData['online_qty'] = NULL;
                $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN;
            } else {
                if ((int)$newData['online_qty'] > 0) {
                    $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;
                } else {
                    $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED;
                }
            }

            $newData['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT;

            /** @var \Ess\M2ePro\Model\Listing\Other $listingOtherModel */
            $listingOtherModel = $this->amazonFactory->getObject('Listing\Other');
            $listingOtherModel->setData($newData);
            $listingOtherModel->save();

            $logModel->addProductMessage($listingOtherModel->getId(),
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
            $mappingResult = $mappingModel->autoMapOtherListingProduct($listingOtherModel);

            if ($mappingResult) {

                if (!$this->getAccount()->getChildObject()->isOtherListingsMoveToListingsEnabled()) {
                    continue;
                }

                $movingModel->initialize($this->getAccount());
                $movingModel->autoMoveOtherListingProduct($listingOtherModel);
            }
        }
    }

    // ########################################

    protected function getReceivedOnlyOtherListings()
    {
        $collection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS)->columns(array('second_table.sku'));

        $listingTable = $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable();

        $collection->getSelect()->join(array('l' => $listingTable), 'main_table.listing_id = l.id', array());
        $collection->getSelect()->where('l.account_id = ?',(int)$this->getAccount()->getId());

        /** @var $stmtTemp \Zend_Db_Statement_Pdo */
        $stmtTemp = $this->resourceConnection->getConnection()->query($collection->getSelect()->__toString());

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
        $collection = $this->amazonFactory->getObject('Listing\Other')->getCollection();
        $collection->getSelect()->where('`main_table`.`account_id` = ?',(int)$this->params['account_id']);

        $tempColumns = array('second_table.sku');

        if ($withData) {
            $tempColumns = array('main_table.status',
                                 'second_table.sku','second_table.general_id','second_table.title',
                                 'second_table.online_price','second_table.online_qty',
                                 'second_table.is_afn_channel', 'second_table.is_isbn_general_id',
                                 'second_table.listing_other_id',
                                 'second_table.is_repricing', 'second_table.is_repricing_disabled');
        }

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS)->columns($tempColumns);

        /** @var $stmtTemp \Zend_Db_Statement_Pdo */
        $stmtTemp = $this->resourceConnection->getConnection()->query($collection->getSelect()->__toString());

        return $stmtTemp;
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

        return $this->logsActionId = $this->activeRecordFactory->getObject('Listing\Other\Log')
                                          ->getResource()->getNextActionId();
    }

    protected function getSynchronizationLog()
    {
        if (!is_null($this->synchronizationLog)) {
            return $this->synchronizationLog;
        }

        $this->synchronizationLog = $this->activeRecordFactory->getObject('Synchronization\Log');
        $this->synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $this->synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_OTHER_LISTINGS);

        return $this->synchronizationLog;
    }

    // ########################################
}