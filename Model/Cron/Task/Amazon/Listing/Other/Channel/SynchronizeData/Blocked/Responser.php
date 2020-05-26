<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Other\Channel\SynchronizeData\Blocked;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Other\Channel\SynchronizeData\Blocked\Responser
 */
class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\Blocked\ItemsResponser
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
        try {
            $this->updateBlockedListingProducts();
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

    protected function updateBlockedListingProducts()
    {
        $responseData = $this->getPreparedResponseData();

        if (empty($responseData['data'])) {
            return;
        }

        $connection = $this->resourceConnection->getConnection();

        /** @var $stmtTemp \Zend_Db_Statement_Pdo */
        $stmtTemp = $connection->query($this->getPdoStatementExistingListings());

        $notReceivedIds = [];
        while ($existingItem = $stmtTemp->fetch()) {
            if (in_array($existingItem['sku'], $responseData['data'])) {
                continue;
            }

            $notReceivedItem = $existingItem;

            $notReceivedIds[] = (int)$notReceivedItem['id'];
        }

        $notReceivedIds = array_unique($notReceivedIds);

        if (empty($notReceivedIds)) {
            $this->updateLastOtherListingProductsSynchronization();
        }

        $bind = [
            'status' => \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED,
            'status_changer' => \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT
        ];

        $connection = $this->resourceConnection->getConnection();
        $listingOtherMainTable = $this->activeRecordFactory->getObject('Listing_Other')->getResource()->getMainTable();

        $chunckedIds = array_chunk($notReceivedIds, 1000);
        foreach ($chunckedIds as $partIds) {
            $where = '`id` IN ('.implode(',', $partIds).')';
            $connection->update($listingOtherMainTable, $bind, $where);
        }
    }

    protected function getPdoStatementExistingListings()
    {
        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Listing\Other\Collection */
        $collection = $this->amazonFactory->getObject('Listing_Other')->getCollection();
        $collection->getSelect()->where('`main_table`.account_id = ?', (int)$this->getAccount()->getId());
        $collection->getSelect()->where(
            '`main_table`.`status` != ?',
            (int)\Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED
        );
        $collection->getSelect()->where(
            '`main_table`.`status` != ?',
            (int)\Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED
        );

        $tempColumns = ['main_table.id', 'main_table.status', 'second_table.sku'];
        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS)->columns($tempColumns);

        return $collection->getSelect()->__toString();
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

    protected function updateLastOtherListingProductsSynchronization()
    {
        $additionalData = $this->getHelper('Data')->jsonDecode($this->getAccount()->getAdditionalData());
        $lastSynchData = [
            'last_other_listing_products_synchronization' => $this->getHelper('Data')->getCurrentGmtDate()
        ];

        if (!empty($additionalData)) {
            $additionalData = array_merge($additionalData, $lastSynchData);
        } else {
            $additionalData = $lastSynchData;
        }

        $this->getAccount()
             ->setAdditionalData($this->getHelper('Data')->jsonEncode($additionalData))
             ->save();
    }

    //-----------------------------------------

    protected function getSynchronizationLog()
    {
        if ($this->synchronizationLog !== null) {
            return $this->synchronizationLog;
        }

        $this->synchronizationLog = $this->activeRecordFactory->getObject('Synchronization_Log');
        $this->synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $this->synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_OTHER_LISTINGS);

        return $this->synchronizationLog;
    }

    //########################################
}
