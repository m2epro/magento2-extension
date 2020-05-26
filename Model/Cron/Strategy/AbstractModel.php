<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Strategy;

use Ess\M2ePro\Model\Lock\Item\Manager as LockManager;

/**
 * Class \Ess\M2ePro\Model\Cron\Strategy\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    const INITIALIZATION_TRANSACTIONAL_LOCK_NICK = 'cron_strategy_initialization';

    const PROGRESS_START_EVENT_NAME           = 'ess_cron_progress_start';
    const PROGRESS_SET_PERCENTAGE_EVENT_NAME  = 'ess_cron_progress_set_percentage';
    const PROGRESS_SET_DETAILS_EVENT_NAME     = 'ess_cron_progress_set_details';
    const PROGRESS_STOP_EVENT_NAME            = 'ess_cron_progress_stop';

    protected $observerKeepAlive;
    protected $observerProgress;
    protected $activeRecordFactory;

    protected $initiator = null;

    /**
     * @var \Ess\M2ePro\Model\Lock\Transactional\Manager
     */
    protected $initializationLockManager;

    /**
     * @var \Ess\M2ePro\Model\Cron\OperationHistory
     */
    protected $operationHistory = null;

    /**
     * @var \Ess\M2ePro\Model\Cron\OperationHistory
     */
    protected $parentOperationHistory = null;

    /**
     * @var \Ess\M2ePro\Model\Cron\Task\Repository
     */
    protected $taskRepo;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Cron\Strategy\Observer\KeepAlive $observerKeepAlive,
        \Ess\M2ePro\Model\Cron\Strategy\Observer\Progress $observerProgress,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory
    ) {
        $this->observerKeepAlive = $observerKeepAlive;
        $this->observerProgress = $observerProgress;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->taskRepo = $taskRepo;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function setInitiator($initiator)
    {
        $this->initiator = $initiator;
        return $this;
    }

    public function getInitiator()
    {
        return $this->initiator;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Cron\OperationHistory $operationHistory
     * @return $this
     */
    public function setParentOperationHistory(\Ess\M2ePro\Model\Cron\OperationHistory $operationHistory)
    {
        $this->parentOperationHistory = $operationHistory;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Cron\OperationHistory
     */
    public function getParentOperationHistory()
    {
        return $this->parentOperationHistory;
    }

    //########################################

    abstract protected function getNick();

    //########################################

    public function process()
    {
        $this->beforeStart();

        try {
            $this->processTasks();
        } catch (\Exception $exception) {
            $this->processException($exception);
        }

        $this->afterEnd();
    }

    // ---------------------------------------

    /**
     * @param $taskNick
     * @return \Ess\M2ePro\Model\Cron\Task\AbstractModel
     */
    protected function getTaskObject($taskNick)
    {
        $taskNick = preg_replace_callback(
            '/_([a-z])/i',
            function ($matches) {
                return ucfirst($matches[1]);
            },
            $taskNick
        );

        $taskNick = preg_replace_callback(
            '/\/([a-z])/i',
            function ($matches) {
                return '_' . ucfirst($matches[1]);
            },
            $taskNick
        );

        $taskNick = ucfirst($taskNick);

        /** @var $task \Ess\M2ePro\Model\Cron\Task\AbstractModel **/
        $task = $this->modelFactory->getObject('Cron\Task\\'.trim($taskNick));

        $task->setInitiator($this->getInitiator());
        $task->setParentOperationHistory($this->getOperationHistory());

        return $task;
    }

    protected function getNextTaskGroup()
    {
        $lastExecuted = $this->getHelper('Module_Cron')->getLastExecutedTaskGroup();
        $allowed = $this->taskRepo->getRegisteredGroups();
        $lastExecutedIndex = array_search($lastExecuted, $allowed, true);

        if (empty($lastExecuted) || $lastExecutedIndex === false || end($allowed) === $lastExecuted) {
            return reset($allowed);
        }

        return $allowed[$lastExecutedIndex + 1];
    }

    abstract protected function processTasks();

    //########################################

    protected function beforeStart()
    {
        $parentId = $this->getParentOperationHistory()
            ? $this->getParentOperationHistory()->getObject()->getId() : null;
        $this->getOperationHistory()->start('cron_strategy_'.$this->getNick(), $parentId, $this->getInitiator());
        $this->getOperationHistory()->makeShutdownFunction();
    }

    protected function afterEnd()
    {
        $this->getOperationHistory()->stop();
    }

    //########################################

    protected function keepAliveStart(\Ess\M2ePro\Model\Lock\Item\Manager $lockItemManager)
    {
        $this->observerKeepAlive->enable();
        $this->observerKeepAlive->setLockItemManager($lockItemManager);
    }

    protected function keepAliveStop()
    {
        $this->observerKeepAlive->disable();
    }

    //########################################

    protected function startListenProgressEvents(\Ess\M2ePro\Model\Lock\Item\Manager $lockItemManager)
    {
        $this->observerProgress->enable();
        $this->observerProgress->setLockItemManager($lockItemManager);
    }

    protected function stopListenProgressEvents()
    {
        $this->observerProgress->disable();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Cron\OperationHistory
     */
    protected function getOperationHistory()
    {
        if ($this->operationHistory !== null) {
            return $this->operationHistory;
        }

        return $this->operationHistory = $this->activeRecordFactory->getObject('Cron_OperationHistory');
    }

    protected function makeLockItemShutdownFunction(\Ess\M2ePro\Model\Lock\Item\Manager $lockItemManager)
    {
        /** @var \Ess\M2ePro\Model\Lock\Item $lockItem */
        $lockItem = $this->activeRecordFactory->getObjectLoaded('Lock\Item', $lockItemManager->getNick(), 'nick');
        if (!$lockItem->getId()) {
            return;
        }

        $id = $lockItem->getId();

        // @codingStandardsIgnoreLine
        register_shutdown_function(
            function () use ($id) {
                $error = error_get_last();
                if ($error === null || !in_array((int)$error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR])) {
                    return;
                }

                /** @var \Ess\M2ePro\Model\Lock\Item $lockItem */
                $lockItem = $this->activeRecordFactory->getObjectLoaded('Lock_Item', $id);
                if ($lockItem->getId()) {
                    $lockItem->delete();
                }
            }
        );
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Lock\Transactional\Manager
     */
    protected function getInitializationLockManager()
    {
        if ($this->initializationLockManager !== null) {
            return $this->initializationLockManager;
        }

        $this->initializationLockManager = $this->modelFactory->getObject(
            'Lock_Transactional_Manager',
            [
               'nick' => self::INITIALIZATION_TRANSACTIONAL_LOCK_NICK
            ]
        );

        return $this->initializationLockManager;
    }

    /**
     * @return bool
     */
    protected function isParallelStrategyInProgress()
    {
        for ($i = 1; $i <= Parallel::MAX_PARALLEL_EXECUTED_CRONS_COUNT; $i++) {
            $lockManager = $this->modelFactory->getObject('Lock_Item_Manager', [
                'nick' => Parallel::GENERAL_LOCK_ITEM_PREFIX.$i
            ]);

            if ($lockManager->isExist()) {
                if ($lockManager->isInactiveMoreThanSeconds(LockManager::DEFAULT_MAX_INACTIVE_TIME)) {
                    $lockManager->remove();
                    continue;
                }

                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    protected function isSerialStrategyInProgress()
    {
        $lockManager = $this->modelFactory->getObject('Lock_Item_Manager', ['nick' => Serial::LOCK_ITEM_NICK]);
        if (!$lockManager->isExist()) {
            return false;
        }

        if ($lockManager->isInactiveMoreThanSeconds(LockManager::DEFAULT_MAX_INACTIVE_TIME)) {
            $lockManager->remove();
            return false;
        }

        return true;
    }

    //########################################

    protected function processException(\Exception $exception)
    {
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

    //########################################
}
