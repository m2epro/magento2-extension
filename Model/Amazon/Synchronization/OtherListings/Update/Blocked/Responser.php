<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\OtherListings\Update\Blocked;

class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\Blocked\ItemsResponser
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

            $this->updateBlockedListingProducts();

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

    protected function updateBlockedListingProducts()
    {
        $responseData = $this->getPreparedResponseData();

        if (empty($responseData['data'])) {
            return false;
        }

        /** @var $stmtTemp \Zend_Db_Statement_Pdo */
        $stmtTemp = $this->resourceConnection->getConnection()->query($this->getPdoStatementExistingListings());

        $tempLog = $this->activeRecordFactory->getObject('Listing\Other\Log');
        $tempLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);

        $notReceivedIds = array();
        while ($existingItem = $stmtTemp->fetch()) {

            if (in_array($existingItem['sku'], $responseData['data'])) {
                continue;
            }

            $notReceivedItem = $existingItem;

            if (!in_array((int)$notReceivedItem['id'],$notReceivedIds)) {
                $statusChangedFrom = $this->getHelper('Component\Amazon')
                    ->getHumanTitleByListingProductStatus($notReceivedItem['status']);
                $statusChangedTo = $this->getHelper('Component\Amazon')
                    ->getHumanTitleByListingProductStatus(\Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED);

                // M2ePro\TRANSLATIONS
                // Item Status was successfully changed from "%from%" to "%to%" .
                $tempLogMessage = $this->getHelper('Module\Translation')->__(
                    'Item Status was successfully changed from "%from%" to "%to%" .',
                    $statusChangedFrom,
                    $statusChangedTo
                );

                $tempLog->addProductMessage(
                    (int)$notReceivedItem['id'],
                    \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
                    $this->getLogsActionId(),
                   \Ess\M2ePro\Model\Listing\Other\Log::ACTION_CHANNEL_CHANGE,
                    $tempLogMessage,
                    \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS,
                    \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
                );
            }

            $notReceivedIds[] = (int)$notReceivedItem['id'];
        }
        $notReceivedIds = array_unique($notReceivedIds);

        if (empty($notReceivedIds)) {
            $this->updateLastOtherListingProductsSynchronization();
        }

        $bind = array(
            'status' => \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED,
            'status_changer' => \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT
        );

        $listingOtherMainTable = $this->activeRecordFactory->getObject('Listing\Other')->getResource()->getMainTable();

        $chunckedIds = array_chunk($notReceivedIds,1000);
        foreach ($chunckedIds as $partIds) {
            $where = '`id` IN ('.implode(',',$partIds).')';
            $this->resourceConnection->getConnection()->update($listingOtherMainTable,$bind,$where);
        }
    }

    protected function getPdoStatementExistingListings()
    {
        $collection = $this->amazonFactory->getObject('Listing\Other')->getCollection();
        $collection->getSelect()->where('`main_table`.account_id = ?',(int)$this->getAccount()->getId());
        $collection->getSelect()->where('`main_table`.`status` != ?',
            (int)\Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED);
        $collection->getSelect()->where('`main_table`.`status` != ?',
            (int)\Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED);

        $tempColumns = array('main_table.id','main_table.status', 'second_table.sku');
        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS)->columns($tempColumns);

        return $collection->getSelect()->__toString();
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

    protected function updateLastOtherListingProductsSynchronization()
    {
        $additionalData = $this->getHelper('Data')->jsonDecode($this->getAccount()->getAdditionalData());
        $lastSynchData = array(
            'last_other_listing_products_synchronization' => $this->getHelper('Data')->getCurrentGmtDate()
        );

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