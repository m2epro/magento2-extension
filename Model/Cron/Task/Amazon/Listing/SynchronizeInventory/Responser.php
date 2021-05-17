<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Listing\SynchronizeInventory;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\SynchronizeInventory\Responser
 */
class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\ItemsResponser
{
    const INSTRUCTION_INITIATOR = 'channel_changes_synchronization';

    /** @var int */
    protected $logsActionId;

    /** @var \Ess\M2ePro\Model\Synchronization\Log */
    protected $synchronizationLog;

    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    /** @var \Ess\M2ePro\Model\Listing\SynchronizeInventory\Amazon\ListingProductsHandler */
    protected $listingProductHandler;

    /** @var \Ess\M2ePro\Model\Listing\SynchronizeInventory\Amazon\OtherListingsHandler */
    protected $otherListingsHandler;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory, \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Listing\SynchronizeInventory\Amazon\ListingProductsHandler $listingProductHandler,
        \Ess\M2ePro\Model\Listing\SynchronizeInventory\Amazon\OtherListingsHandler $otherListingsHandler,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        array $params = []
    ) {
        parent::__construct(
            $response,
            $helperFactory,
            $modelFactory,
            $amazonFactory,
            $walmartFactory,
            $ebayFactory,
            $activeRecordFactory,
            $params
        );

        $this->listingProductHandler = $listingProductHandler;
        $this->otherListingsHandler  = $otherListingsHandler;
        $this->resourceConnection    = $resourceConnection;
    }

    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
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
                $logType
            );
        }
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function eventAfterExecuting()
    {
        parent::eventAfterExecuting();

        $acc = $this->amazonFactory->getObjectLoaded('Account', $this->params['account_id'])->getChildObject();
        $newSynchDate = $this->getHelper('Data')->getCurrentGmtDate();

        if ($this->getResponse()->getMessages() && $this->getResponse()->getMessages()->hasErrorEntities()) {
            //try to download inventory again in an hour
            $newSynchDate = date('Y-m-d H:i:s', strtotime($newSynchDate) + 3600);
        }

        $acc->setData('inventory_last_synchronization', $newSynchDate)->save();
    }

    /**
     * @return bool
     */
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

    /**
     * @param $messageText
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        $this->getSynchronizationLog()->addMessage(
            $this->getHelper('Module_Translation')->__($messageText),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
        );
    }

    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function processResponseData()
    {
        try {
            $this->storeReceivedSkus();

            $filteredData = $this->listingProductHandler
                ->setResponserParams($this->params)
                ->handle($this->getPreparedResponseData());

            if ($this->getAccount()->getChildObject()->getOtherListingsSynchronization()) {
                $this->otherListingsHandler->setResponserParams($this->params)->handle($filteredData);
            }
        } catch (\Exception $e) {
            $this->getHelper('Module\Exception')->process($e);
            $this->getSynchronizationLog()->addMessageFromException($e);
        }
    }

    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function storeReceivedSkus()
    {
        $insertData = [];
        $accountId = $this->getAccount()->getId();

        foreach (array_keys($this->preparedResponseData) as $sku) {
            $insertData[] = ['account_id' => $accountId, 'sku' => $sku];
        }

        $this->resourceConnection->getConnection()->insertOnDuplicate(
            $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_amazon_inventory_sku'),
            $insertData
        );
    }

    /**
     * @return \Ess\M2ePro\Model\Account
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getAccount()
    {
        return $this->activeRecordFactory->getObjectLoaded('Account', $this->params['account_id']);
    }

    /**
     * @return int
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getLogsActionId()
    {
        if ($this->logsActionId !== null) {
            return $this->logsActionId;
        }

        return $this->logsActionId = (int)$this->activeRecordFactory->getObject('Listing\Log')
            ->getResource()->getNextActionId();
    }

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Log
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getSynchronizationLog()
    {
        if ($this->synchronizationLog !== null) {
            return $this->synchronizationLog;
        }

        $this->synchronizationLog = $this->activeRecordFactory->getObject('Synchronization\Log');
        $this->synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $this->synchronizationLog
            ->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_LISTINGS);

        return $this->synchronizationLog;
    }

    //########################################
}
