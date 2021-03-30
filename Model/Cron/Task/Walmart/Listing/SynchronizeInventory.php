<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Walmart\Listing;

use Ess\M2ePro\Helper\Component\Walmart;
use \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\SynchronizeInventory\ProcessingRunner as ProcessingRunner;
use Ess\M2ePro\Model\Synchronization\Log;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\SynchronizeInventory
 */
class SynchronizeInventory extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'walmart/listing/synchronize_inventory';

    const DEFAULT_INTERVAL_PER_ACCOUNT = 86400;
    const QUICKER_INTERVAL_PER_ACCOUNT = 7200;

    //####################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isPossibleToRun()
    {
        if ($this->getHelper('Server\Maintenance')->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    //########################################

    /**
     * @throws \Exception
     */
    protected function performActions()
    {
        if ($this->isTaskInProgress()) {
            return;
        }

        if (!$account = $this->getAccountForProcess()) {
            return;
        }

        $this->getOperationHistory()->addText('Starting Account "' . $account->getTitle() . '"');
        $this->getOperationHistory()->addTimePoint(
            __METHOD__ . 'process' . $account->getId(),
            'Process Account ' . $account->getTitle()
        );

        try {
            $dispatcherObject = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');
            $connectorObj = $dispatcherObject->getCustomConnector(
                'Cron_Task_Walmart_Listing_SynchronizeInventory_Requester',
                [],
                $account
            );
            $dispatcherObject->process($connectorObj);
        } catch (\Exception $exception) {
            $message = 'The "Synchronize Inventory" Action for Walmart Account "%account%"';
            $message .= ' was completed with error.';
            $message = $this->getHelper('Module\Translation')->__($message, $account->getTitle());

            $this->processTaskAccountException($message, __FILE__, __LINE__);
            $this->processTaskException($exception);
        }

        $this->getOperationHistory()->saveTimePoint(__METHOD__ . 'process' . $account->getId());
    }

    //########################################

    /**
     * @return bool|\Ess\M2ePro\Model\Account
     * @throws \Exception
     */
    protected function getAccountForProcess()
    {
        /**
         * Trying to get online data somewhat quicker after a successful List action
         */
        $quickerDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $quickerDate->modify('-' . self::QUICKER_INTERVAL_PER_ACCOUNT . ' seconds');

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->parentFactory->getObject(\Ess\M2ePro\Helper\View\Walmart::NICK, 'Account')
            ->getCollection();
        $collection->getSelect()->joinLeft(
            ['l' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()],
            'main_table.id = l.account_id',
            []
        );
        $collection->addFieldToFilter(
            'second_table.inventory_last_synchronization',
            [
                ['lt' => $quickerDate->format('Y-m-d H:i:s')],
                ['null' => true]
            ]
        );
        $collection->getSelect()->where('l.id IS NOT NULL OR second_table.other_listings_synchronization = 1');
        $collection->getSelect()->group('main_table.id');
        $collection->getSelect()->order(new \Zend_Db_Expr('second_table.inventory_last_synchronization ASC'));

        $interval = $this->getConfigValue('interval_per_account') !== null
            ? $this->getConfigValue('interval_per_account')
            : self::DEFAULT_INTERVAL_PER_ACCOUNT;

        $dayAgoDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $dayAgoDate->modify('-' . $interval . ' seconds');

        foreach ($collection->getItems() as $account) {
            /**@var \Ess\M2ePro\Model\Account $account */
            if (!$account->getChildObject()->getInventoryLastSynchronization()) {
                return $account;
            }

            $lastSynchDate = new \DateTime(
                $account->getChildObject()->getInventoryLastSynchronization(),
                new \DateTimeZone('UTC')
            );

            if ($dayAgoDate->getTimestamp() >= $lastSynchDate->getTimestamp()) {
                return $account;
            }

            /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
            $collection = $this->parentFactory->getObject(\Ess\M2ePro\Helper\View\Walmart::NICK, 'Listing\Product')
                ->getCollection();
            $collection->joinListingTable();

            $collection->addFieldToFilter('l.account_id', (int)$account->getId());
            $collection->addFieldToFilter('list_date', ['gt' => $lastSynchDate->format('Y-m-d H:i:s')]);

            if ($collection->getSize() > 0) {
                return $account;
            }
        }

        return false;
    }

    protected function isTaskInProgress()
    {
        /** @var $lockItemManager \Ess\M2ePro\Model\Lock\Item\Manager */
        $lockItemManager = $this->modelFactory->getObject(
            'Lock_Item_Manager',
            [
                'nick' => ProcessingRunner::LOCK_ITEM_PREFIX
            ]
        );
        if (!$lockItemManager->isExist()) {
            return false;
        }

        if ($lockItemManager->isInactiveMoreThanSeconds(\Ess\M2ePro\Model\Processing\Runner::MAX_LIFETIME)) {
            $lockItemManager->remove();

            return false;
        }

        return true;
    }

    /**
     * @return Log
     */
    protected function getSynchronizationLog()
    {
        $synchronizationLog = parent::getSynchronizationLog();

        $synchronizationLog->setComponentMode(Walmart::NICK);
        $synchronizationLog->setSynchronizationTask(Log::TASK_LISTINGS);

        return $synchronizationLog;
    }

    //########################################
}
