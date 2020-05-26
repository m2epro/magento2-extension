<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product\Channel;

use Ess\M2ePro\Helper\Component\Walmart;
use \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product\Channel\SynchronizeData\ProcessingRunner as ProcessingRunner;
use Ess\M2ePro\Model\Synchronization\Log;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product\Channel\SynchronizeData
 */
class SynchronizeData extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'walmart/listing/product/channel/synchronize_data';

    /**
     * @var int (in seconds)
     */
    protected $interval = 86400;

    const QUICKER_TASK_INTERVAL = 7200;

    //####################################

    public function isPossibleToRun()
    {
        if ($this->getHelper('Server\Maintenance')->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    /**
     * Trying to get online data somewhat quicker after a successful List action
     * @return bool
     */
    protected function isIntervalExceeded()
    {
        $lastRun = $this->getConfigValue('last_run');
        if ($lastRun === null) {
            return true;
        }

        $borderDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $borderDate->modify('- 24 hours');

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->parentFactory->getObject(\Ess\M2ePro\Helper\View\Walmart::NICK, 'Listing\Product')
            ->getCollection();
        $collection->addFieldToFilter('list_date', ['gt' => $borderDate->format('Y-m-d H:i:s')]);

        $interval = $collection->getSize() > 0 ? self::QUICKER_TASK_INTERVAL : (int)$this->getConfigValue('interval');

        $currentTimeStamp = $this->getHelper('Data')->getCurrentGmtDate(true);
        return $currentTimeStamp > strtotime($lastRun) + $interval;
    }

    //########################################

    /**
     * @return Log
     */
    protected function getSynchronizationLog()
    {
        $synchronizationLog = parent::getSynchronizationLog();

        $synchronizationLog->setComponentMode(Walmart::NICK);
        $synchronizationLog->setSynchronizationTask(Log::TASK_LISTINGS_PRODUCTS);

        return $synchronizationLog;
    }

    //########################################

    protected function performActions()
    {
        $accounts = $this->parentFactory->getObject(Walmart::NICK, 'Account')->getCollection()->getItems();

        if (empty($accounts)) {
            return;
        }

        foreach ($accounts as $account) {
            /** @var $account \Ess\M2ePro\Model\Account **/

            $this->getOperationHistory()->addText('Starting Account "'.$account->getTitle().'"');

            if (!$this->isLockedAccount($account)) {
                $this->getOperationHistory()->addTimePoint(
                    __METHOD__.'process'.$account->getId(),
                    'Process Account '.$account->getTitle()
                );

                try {
                    $this->processAccount($account);
                } catch (\Exception $exception) {
                    $message = 'The "Update Listings Products" Action for Walmart Account "%account%"';
                    $message .= ' was completed with error.';
                    $message = $this->getHelper('Module_Translation')->__($message, $account->getTitle());

                    $this->processTaskAccountException($message, __FILE__, __LINE__);
                    $this->processTaskException($exception);
                }

                $this->getOperationHistory()->saveTimePoint(__METHOD__.'process'.$account->getId());
            }
        }
    }

    //########################################

    protected function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        /** @var $collection \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel */
        $collection = $this->activeRecordFactory->getObject('Listing')->getCollection();
        $collection->addFieldToFilter('component_mode', Walmart::NICK);
        $collection->addFieldToFilter('account_id', (int)$account->getId());

        if ($collection->getSize()) {
            $dispatcherObject = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');
            $connectorObj = $dispatcherObject->getCustomConnector(
                'Cron_Task_Walmart_Listing_Product_Channel_SynchronizeData_Requester',
                [],
                $account
            );
            $dispatcherObject->process($connectorObj);
        }
    }

    protected function isLockedAccount(\Ess\M2ePro\Model\Account $account)
    {
        $lockItemNick = ProcessingRunner::LOCK_ITEM_PREFIX .'_'. $account->getId();

        /** @var $lockItemManager \Ess\M2ePro\Model\Lock\Item\Manager */
        $lockItemManager = $this->modelFactory->getObject('Lock_Item_Manager', [
            'nick' => $lockItemNick
        ]);
        if (!$lockItemManager->isExist()) {
            return false;
        }

        if ($lockItemManager->isInactiveMoreThanSeconds(\Ess\M2ePro\Model\Processing\Runner::MAX_LIFETIME)) {
            $lockItemManager->remove();
            return false;
        }

        return true;
    }

    //########################################
}
