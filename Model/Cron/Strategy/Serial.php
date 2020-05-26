<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Strategy;

use Ess\M2ePro\Model\Lock\Item\Manager as LockManager;

/**
 * Class \Ess\M2ePro\Model\Cron\Strategy\Serial
 */
class Serial extends AbstractModel
{
    const LOCK_ITEM_NICK = 'cron_strategy_serial';

    /**
     * @var \Ess\M2ePro\Model\Lock\Item\Manager
     */
    protected $lockItemManager;

    protected $allowedTasks;

    //########################################

    protected function getNick()
    {
        return \Ess\M2ePro\Helper\Module\Cron::STRATEGY_SERIAL;
    }

    //########################################

    protected function processTasks()
    {
        $this->getInitializationLockManager()->lock();

        if ($this->isParallelStrategyInProgress()) {
            $this->getInitializationLockManager()->unlock();
            return;
        }

        if ($this->getLockItemManager() === false) {
            return;
        }

        try {
            $this->getLockItemManager()->create();
            $this->makeLockItemShutdownFunction($this->getLockItemManager());

            $this->getInitializationLockManager()->unlock();

            $this->keepAliveStart($this->getLockItemManager());
            $this->startListenProgressEvents($this->getLockItemManager());

            $this->processAllTasks();

            $this->keepAliveStop();
            $this->stopListenProgressEvents();
        } catch (\Exception $exception) {
            $this->processException($exception);
        }

        $this->getLockItemManager()->remove();
    }

    // ---------------------------------------

    protected function processAllTasks()
    {
        $taskGroup = null;
        /**
         * Developer cron runner
         */
        if ($this->allowedTasks === null) {
            $taskGroup = $this->getNextTaskGroup();
            $this->getHelper('Module_Cron')->setLastExecutedTaskGroup($taskGroup);
        }

        foreach ($this->getAllowedTasks($taskGroup) as $taskNick) {
            try {
                $taskObject = $this->getTaskObject($taskNick);
                $taskObject->setLockItemManager($this->getLockItemManager());

                $taskObject->process();
            } catch (\Exception $exception) {
                $this->processException($exception);
            }
        }
    }

    //########################################

    /**
     * @param array $tasks
     * @return $this
     */
    public function setAllowedTasks(array $tasks)
    {
        $this->allowedTasks = $tasks;
        return $this;
    }

    public function getAllowedTasks($taskGroup)
    {
        if ($this->allowedTasks !== null) {
            return $this->allowedTasks;
        }

        return $this->taskRepo->getGroupTasks($taskGroup);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Lock\Item\Manager|bool
     */
    protected function getLockItemManager()
    {
        if ($this->lockItemManager !== null) {
            return $this->lockItemManager;
        }

        $lockItemManager = $this->modelFactory->getObject('Lock_Item_Manager', [
            'nick' => self::LOCK_ITEM_NICK
        ]);

        if (!$lockItemManager->isExist()) {
            return $this->lockItemManager = $lockItemManager;
        }

        if ($lockItemManager->isInactiveMoreThanSeconds(LockManager::DEFAULT_MAX_INACTIVE_TIME)) {
            $lockItemManager->remove();
            return $this->lockItemManager = $lockItemManager;
        }

        return false;
    }

    //########################################
}
