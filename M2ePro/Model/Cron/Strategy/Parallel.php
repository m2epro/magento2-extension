<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Strategy;

/**
 * Class \Ess\M2ePro\Model\Cron\Strategy\Parallel
 */
class Parallel extends AbstractModel
{
    const GENERAL_LOCK_ITEM_PREFIX = 'cron_strategy_parallel_';

    const MAX_PARALLEL_EXECUTED_CRONS_COUNT = 10;

    const MAX_FIRST_SLOW_TASK_EXECUTION_TIME_FOR_CONTINUE = 60; // 1 minute

    /**
     * @var \Ess\M2ePro\Model\Lock\Item\Manager
     */
    protected $generalLockItemManager = null;

    /**
     * @var \Ess\M2ePro\Model\Lock\Item\Manager
     */
    protected $fastTasksLockItemManager = null;

    //########################################

    protected function getNick()
    {
        return \Ess\M2ePro\Helper\Module\Cron::STRATEGY_PARALLEL;
    }

    //########################################

    protected function processTasks()
    {
        $result = true;

        /** @var \Ess\M2ePro\Model\Lock\Transactional\Manager $transactionalManager */
        $transactionalManager = $this->modelFactory->getObject('Lock_Transactional_Manager', [
            'nick' => self::INITIALIZATION_TRANSACTIONAL_LOCK_NICK
        ]);

        $transactionalManager->lock();

        if ($this->isSerialStrategyInProgress()) {
            $transactionalManager->unlock();
            return $result;
        }

        $this->getGeneralLockItemManager()->create();
        $this->makeLockItemShutdownFunction($this->getGeneralLockItemManager());

        if (!$this->getFastTasksLockItemManager()->isExist() ||
            ($this->getFastTasksLockItemManager()->isInactiveMoreThanSeconds(
                \Ess\M2ePro\Model\Lock\Item\Manager::DEFAULT_MAX_INACTIVE_TIME
            ) && $this->getFastTasksLockItemManager()->remove())
        ) {
            $this->getFastTasksLockItemManager()->create($this->getGeneralLockItemManager()->getNick());
            $this->makeLockItemShutdownFunction($this->getFastTasksLockItemManager());

            $transactionalManager->unlock();

            $this->keepAliveStart($this->getFastTasksLockItemManager());
            $this->startListenProgressEvents($this->getFastTasksLockItemManager());

            $result = !$this->processFastTasks() ? false : $result;

            $this->keepAliveStop();
            $this->stopListenProgressEvents();

            $this->getFastTasksLockItemManager()->remove();
        }

        $transactionalManager->unlock();

        $result = !$this->processSlowTasks() ? false : $result;

        $this->getGeneralLockItemManager()->remove();

        return $result;
    }

    // ---------------------------------------

    protected function processFastTasks()
    {
        $result = true;

        foreach ($this->getAllowedFastTasks() as $taskNick) {

            try {

                $taskObject = $this->getTaskObject($taskNick);
                $taskObject->setLockItemManager($this->getFastTasksLockItemManager());

                $tempResult = $taskObject->process();

                if ($tempResult !== null && !$tempResult) {
                    $result = false;
                }

                $this->getFastTasksLockItemManager()->activate();
            } catch (\Exception $exception) {
                $result = false;

                $this->getOperationHistory()->addContentData(
                    'exceptions',
                    [
                        'message' => $exception->getMessage(),
                        'file'    => $exception->getFile(),
                        'line'    => $exception->getLine(),
                        'trace'   => $exception->getTraceAsString(),
                    ]
                );

                $this->getHelper('Module\Exception')->process($exception);
            }
        }

        return $result;
    }

    protected function processSlowTasks()
    {
        $helper = $this->getHelper('Module_Cron');

        $result = true;

        $isFirstTask = true;

        $allowedSlowTasksCount = count($this->getAllowedSlowTasks());

        for ($i = 0; $i < $allowedSlowTasksCount; $i++) {

            $transactionalManager = $this->modelFactory->getObject('Lock_Transactional_Manager', [
                'nick' => self::GENERAL_LOCK_ITEM_PREFIX . 'slow_task_switch'
            ]);

            $transactionalManager->lock();

            $taskNick = $this->getNextSlowTask();
            $helper->setLastExecutedSlowTask($taskNick);

            $transactionalManager->unlock();

            $taskLockItemManager = $this->modelFactory->getObject('Lock_Item_Manager', [
                'nick' => 'cron_task_'.str_replace("/", "_", $taskNick)
            ]);

            if ($taskLockItemManager->isExist()) {
                if (!$taskLockItemManager->isInactiveMoreThanSeconds(
                    \Ess\M2ePro\Model\Lock\Item\Manager::DEFAULT_MAX_INACTIVE_TIME
                )) {
                    continue;
                }

                $taskLockItemManager->remove();
            }

            $taskLockItemManager->create($this->getGeneralLockItemManager()->getNick());
            $this->makeLockItemShutdownFunction($taskLockItemManager);

            $taskObject = $this->getTaskObject($taskNick);
            $taskObject->setLockItemManager($taskLockItemManager);

            if (!$taskObject->isPossibleToRun()) {
                continue;
            }

            $this->keepAliveStart($taskLockItemManager);
            $this->startListenProgressEvents($taskLockItemManager);

            $taskStartTime = time();

            try {
                $result = $taskObject->process();
            } catch (\Exception $exception) {
                $result = false;

                $this->getOperationHistory()->addContentData(
                    'exceptions',
                    [
                        'message' => $exception->getMessage(),
                        'file'    => $exception->getFile(),
                        'line'    => $exception->getLine(),
                        'trace'   => $exception->getTraceAsString(),
                    ]
                );

                $this->getHelper('Module_Exception')->process($exception);
            }

            $taskProcessTime = time() - $taskStartTime;

            $this->keepAliveStop();
            $this->stopListenProgressEvents();

            $taskLockItemManager->remove();

            if (!$isFirstTask || $taskProcessTime > self::MAX_FIRST_SLOW_TASK_EXECUTION_TIME_FOR_CONTINUE) {
                break;
            }

            $isFirstTask = false;
        }

        return $result;
    }

    //########################################

    protected function getAllowedFastTasks()
    {
        return array_values(
            array_intersect(
                $this->getAllowedTasks(),
                [
                    \Ess\M2ePro\Model\Cron\Task\System\ArchiveOldOrders::NICK,
                    \Ess\M2ePro\Model\Cron\Task\System\ClearOldLogs::NICK,
                    \Ess\M2ePro\Model\Cron\Task\System\ConnectorCommandPending\ProcessPartial::NICK,
                    \Ess\M2ePro\Model\Cron\Task\System\ConnectorCommandPending\ProcessSingle::NICK,
                    \Ess\M2ePro\Model\Cron\Task\System\IssuesResolver\RemoveMissedProcessingLocks::NICK,
                    \Ess\M2ePro\Model\Cron\Task\System\Processing\ProcessResult::NICK,
                    \Ess\M2ePro\Model\Cron\Task\System\RequestPending\ProcessPartial::NICK,
                    \Ess\M2ePro\Model\Cron\Task\System\RequestPending\ProcessSingle::NICK,
                    \Ess\M2ePro\Model\Cron\Task\System\Servicing\Synchronize::NICK,
                    \Ess\M2ePro\Model\Cron\Task\System\HealthStatus::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Magento\Product\DetectDirectlyAdded::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Magento\Product\DetectDirectlyDeleted::NICK,
//                    \Ess\M2ePro\Model\Cron\Task\Magento\GlobalNotifications::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Listing\Product\AutoActions\ProcessMagentoProductWebsitesUpdates::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Listing\Product\StopQueue::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Ebay\UpdateAccountsPreferences::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Ebay\Template\RemoveUnused::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Ebay\Channel\SynchronizeChanges::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Ebay\Feedbacks\DownloadNew::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Ebay\Feedbacks\SendResponse::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Other\ResolveSku::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Other\Channel\SynchronizeData::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Product\ProcessInstructions::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Product\ProcessScheduledActions::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Product\RemovePotentialDuplicates::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Ebay\Order\CreateFailed::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Ebay\Order\Update::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Ebay\Order\ReserveCancel::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Ebay\PickupStore\ScheduleForUpdate::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Ebay\PickupStore\UpdateOnChannel::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Other\ResolveTitle::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Other\Channel\SynchronizeData::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Other\Channel\SynchronizeData\Blocked::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\Blocked::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\Defected::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\RunVariationParentProcessors::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\ProcessInstructions::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\ProcessActions::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\ProcessActionsResults::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive\Details::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Amazon\Order\CreateFailed::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Update::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Update\SellerOrderId::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Refund::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Cancel::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Amazon\Order\ReserveCancel::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Action\ProcessUpdate::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Action\ProcessRefund::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Action\ProcessCancel::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Action\ProcessResults::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Amazon\Repricing\InspectProducts::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Amazon\Repricing\UpdateSettings::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product\Channel\SynchronizeData::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product\Channel\SynchronizeData\Blocked::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Other\Channel\SynchronizeData::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product\ProcessInstructions::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product\ProcessActions::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product\ProcessActionsResults::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product\ProcessListActions::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Walmart\Order\Receive::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Walmart\Order\Acknowledge::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Walmart\Order\Shipping::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Walmart\Order\Cancel::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Walmart\Order\Refund::NICK
                ]
            )
        );
    }

    protected function getAllowedSlowTasks()
    {
        return array_values(
            array_intersect(
                $this->getAllowedTasks(),
                [
                    \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Product\ProcessActions::NICK,
                    \Ess\M2ePro\Model\Cron\Task\Amazon\Repricing\Synchronize::NICK
                ]
            )
        );
    }

    // ---------------------------------------

    protected function getNextSlowTask()
    {
        $helper = $this->getHelper('Module_Cron');
        $lastExecutedTask = $helper->getLastExecutedSlowTask();

        $allowedSlowTasks = $this->getAllowedSlowTasks();
        $lastExecutedTaskIndex = array_search($lastExecutedTask, $allowedSlowTasks);

        if (empty($lastExecutedTask)
            || $lastExecutedTaskIndex === false
            || end($allowedSlowTasks) == $lastExecutedTask) {
            return reset($allowedSlowTasks);
        }

        return $allowedSlowTasks[$lastExecutedTaskIndex + 1];
    }

    /**
     * @return \Ess\M2ePro\Model\Lock\Item\Manager
     * @throws \Ess\M2ePro\Model\Exception
     */
    protected function getGeneralLockItemManager()
    {
        if ($this->generalLockItemManager !== null) {
            return $this->generalLockItemManager;
        }

        for ($index = 1; $index <= self::MAX_PARALLEL_EXECUTED_CRONS_COUNT; $index++) {
            $lockItemManager = $this->modelFactory->getObject('Lock_Item_Manager', [
                'nick' => self::GENERAL_LOCK_ITEM_PREFIX.$index
            ]);

            if (!$lockItemManager->isExist()) {
                return $this->generalLockItemManager = $lockItemManager;
            }

            if ($lockItemManager->isInactiveMoreThanSeconds(
                \Ess\M2ePro\Model\Lock\Item\Manager::DEFAULT_MAX_INACTIVE_TIME
            )) {
                $lockItemManager->remove();
                return $this->generalLockItemManager = $lockItemManager;
            }
        }

        throw new \Ess\M2ePro\Model\Exception('Too many parallel lock items.');
    }

    /**
     * @return \Ess\M2ePro\Model\Lock\Item\Manager
     */
    protected function getFastTasksLockItemManager()
    {
        if ($this->fastTasksLockItemManager !== null) {
            return $this->fastTasksLockItemManager;
        }

        $this->fastTasksLockItemManager =  $this->modelFactory->getObject('Lock_Item_Manager', [
            'nick' => 'cron_strategy_parallel_fast_tasks'
        ]);

        return $this->fastTasksLockItemManager;
    }

    /**
     * @return bool
     */
    protected function isSerialStrategyInProgress()
    {
        $serialLockItemManager = $this->modelFactory->getObject('Lock_Item_Manager', [
            'nick' => \Ess\M2ePro\Model\Cron\Strategy\Serial::LOCK_ITEM_NICK
        ]);
        if (!$serialLockItemManager->isExist()) {
            return false;
        }

        if ($serialLockItemManager->isInactiveMoreThanSeconds(
            \Ess\M2ePro\Model\Lock\Item\Manager::DEFAULT_MAX_INACTIVE_TIME
        )) {
            $serialLockItemManager->remove();
            return false;
        }

        return true;
    }

    //########################################
}
