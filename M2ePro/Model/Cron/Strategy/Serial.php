<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Strategy;

/**
 * Class \Ess\M2ePro\Model\Cron\Strategy\Serial
 */
class Serial extends AbstractModel
{
    const LOCK_ITEM_NICK = 'cron_strategy_serial';

    /**
     * @var \Ess\M2ePro\Model\Lock\Item\Manager
     */
    private $lockItemManager = null;

    //########################################

    protected function getNick()
    {
        return \Ess\M2ePro\Helper\Module\Cron::STRATEGY_SERIAL;
    }

    //########################################

    /**
     * @param $taskNick
     * @return \Ess\M2ePro\Model\Cron\Task\AbstractModel
     */
    protected function getTaskObject($taskNick)
    {
        $task = parent::getTaskObject($taskNick);
        return $task->setLockItemManager($this->getLockItemManager());
    }

    protected function processTasks()
    {
        $result = true;

        /** @var \Ess\M2ePro\Model\Lock\Transactional\Manager $transactionalManager */
        $transactionalManager = $this->modelFactory->getObject('Lock_Transactional_Manager', [
            'nick' => self::INITIALIZATION_TRANSACTIONAL_LOCK_NICK
        ]);

        $transactionalManager->lock();

        if ($this->isParallelStrategyInProgress()) {
            $transactionalManager->unlock();
            return $result;
        }

        if ($this->getLockItemManager()->isExist()) {
            if (!$this->getLockItemManager()->isInactiveMoreThanSeconds(
                \Ess\M2ePro\Model\Lock\Item\Manager::DEFAULT_MAX_INACTIVE_TIME
            )) {
                $transactionalManager->unlock();
                return $result;
            }

            $this->getLockItemManager()->remove();
        }

        $this->getLockItemManager()->create();
        $this->makeLockItemShutdownFunction($this->getLockItemManager());

        $transactionalManager->unlock();

        $this->keepAliveStart($this->getLockItemManager());
        $this->startListenProgressEvents($this->getLockItemManager());

        $result = $this->processAllTasks();

        $this->keepAliveStop();
        $this->stopListenProgressEvents();

        $this->getLockItemManager()->remove();

        return $result;
    }

    // ---------------------------------------

    protected function processAllTasks()
    {
        $result = true;

        foreach ($this->getAllowedTasks() as $taskNick) {
            try {
                $tempResult = $this->getTaskObject($taskNick)->process();

                if ($tempResult !== null && !$tempResult) {
                    $result = false;
                }

                $this->getLockItemManager()->activate();
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
        }

        return $result;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Lock\Item\Manager
     */
    protected function getLockItemManager()
    {
        if ($this->lockItemManager !== null) {
            return $this->lockItemManager;
        }

        $this->lockItemManager = $this->modelFactory->getObject('Lock_Item_Manager', [
            'nick' => self::LOCK_ITEM_NICK
        ]);

        return $this->lockItemManager;
    }

    /**
     * @return bool
     */
    protected function isParallelStrategyInProgress()
    {
        for ($i = 1; $i <= Parallel::MAX_PARALLEL_EXECUTED_CRONS_COUNT; $i++) {
            $lockItemManager = $this->modelFactory->getObject('Lock_Item_Manager', [
                'nick' => Parallel::GENERAL_LOCK_ITEM_PREFIX.$i
            ]);

            if ($lockItemManager->isExist()) {
                if ($lockItemManager->isInactiveMoreThanSeconds(
                    \Ess\M2ePro\Model\Lock\Item\Manager::DEFAULT_MAX_INACTIVE_TIME
                )) {
                    $lockItemManager->remove();
                    continue;
                }

                return true;
            }
        }

        return false;
    }

    //########################################
}
