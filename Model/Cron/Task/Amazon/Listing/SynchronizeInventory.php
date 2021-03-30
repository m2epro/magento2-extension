<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Listing;

use \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\SynchronizeInventory\ProcessingRunner as ProcessingRunner;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\SynchronizeInventory
 */
class SynchronizeInventory extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'amazon/listing/synchronize_inventory';

    const DEFAULT_INTERVAL_PER_ACCOUNT = 86400;

    //####################################

    public function isPossibleToRun()
    {
        if ($this->getHelper('Server\Maintenance')->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Log
     */
    protected function getSynchronizationLog()
    {
        $synchronizationLog = parent::getSynchronizationLog();

        $synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_LISTINGS);

        return $synchronizationLog;
    }

    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function performActions()
    {
        if ($this->isTaskInProgress()) {
            return;
        }

        $account = $this->getAccountForProcess();

        if (!$account->getId()) {
            return;
        }

        $this->getOperationHistory()->addText('Starting Account "' . $account->getTitle() . '"');
        $this->getOperationHistory()->addTimePoint(
            __METHOD__ . 'process' . $account->getId(),
            'Process Account ' . $account->getTitle()
        );

        try {

            $params = [];

            if ($account->getChildObject()->getOtherListingsSynchronization() &&
                !$this->isFullItemsDataAlreadyReceived($account)
            ) {
                $params['full_items_data'] = true;

                $additionalData = (array)$this->getHelper('Data')->jsonDecode($account->getAdditionalData());
                $additionalData['is_amazon_other_listings_full_items_data_already_received'] = true;
                $account->setSettings('additional_data', $additionalData)->save();
            }

            /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcherObject */
            $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
            $connectorObj = $dispatcherObject->getCustomConnector(
                'Cron_Task_Amazon_Listing_SynchronizeInventory_Requester',
                [],
                $account
            );
            $dispatcherObject->process($connectorObj);
        } catch (\Exception $exception) {
            $message = 'The "Inventory Synchronize" Action for Amazon Account "%account%"';
            $message .= ' was completed with error.';
            $message = $this->getHelper('Module_Translation')->__($message, $account->getTitle());

            $this->processTaskAccountException($message, __FILE__, __LINE__);
            $this->processTaskException($exception);
        }

        $this->getOperationHistory()->saveTimePoint(__METHOD__ . 'process' . $account->getId());
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getAccountForProcess()
    {
        $interval = $this->getConfigValue('interval_per_account') !== null
            ? $this->getConfigValue('interval_per_account')
            : self::DEFAULT_INTERVAL_PER_ACCOUNT;

        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->modify('-' . $interval . ' seconds');

        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $collection */
        $collection = $this->parentFactory->getObject(\Ess\M2ePro\Helper\Component\Amazon::NICK, 'Account')
            ->getCollection();
        $collection->getSelect()->joinLeft(
            ['l' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()],
            'main_table.id = l.account_id',
            []
        );
        $collection->addFieldToFilter(
            'second_table.inventory_last_synchronization',
            [
                ['lt' => $date->format('Y-m-d H:i:s')],
                ['null' => true]
            ]
        );
        $collection->getSelect()->where('l.id IS NOT NULL OR second_table.other_listings_synchronization = 1');
        $collection->getSelect()->group('main_table.id');
        $collection->getSelect()->order(new \Zend_Db_Expr('second_table.inventory_last_synchronization ASC'));

        return $collection->getFirstItem();
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
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
     * @param \Ess\M2ePro\Model\Account $account
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function isFullItemsDataAlreadyReceived(\Ess\M2ePro\Model\Account $account)
    {
        $additionalData = (array)$this->getHelper('Data')->jsonDecode($account->getAdditionalData());

        return !empty($additionalData['is_amazon_other_listings_full_items_data_already_received']);
    }

    //########################################
}
