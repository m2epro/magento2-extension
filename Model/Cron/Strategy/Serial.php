<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Strategy;

class Serial extends AbstractModel
{
    const LOCK_ITEM_NICK = 'cron_strategy_serial';

    /**
     * @var \Ess\M2ePro\Model\Lock\Item\Manager
     */
    private $lockItem = NULL;

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
        return $task->setParentLockItem($this->getLockItem());
    }

    protected function processTasks()
    {
        $result = true;

        /** @var \Ess\M2ePro\Model\Lock\Transactional\Manager $transactionalManager */
        $transactionalManager = $this->modelFactory->getObject('Lock\Transactional\Manager');
        $transactionalManager->setNick(self::INITIALIZATION_TRANSACTIONAL_LOCK_NICK);

        $transactionalManager->lock();

        if ($this->getLockItem()->isExist() || $this->isParallelStrategyInProgress()) {
            $transactionalManager->unlock();
            return $result;
        }

        $this->getLockItem()->create();
        $this->getLockItem()->makeShutdownFunction();

        $transactionalManager->unlock();

        $result = $this->processAllTasks();

        $this->getLockItem()->remove();

        return $result;
    }

    private function processAllTasks()
    {
        $result = true;

        foreach ($this->getAllowedTasks() as $taskNick) {

            try {

                $tempResult = $this->getTaskObject($taskNick)->process();

                if (!is_null($tempResult) && !$tempResult) {
                    $result = false;
                }

                $this->getLockItem()->activate();

            } catch (\Exception $exception) {

                $result = false;

                $this->getOperationHistory()->addContentData('exceptions', array(
                    'message' => $exception->getMessage(),
                    'file'    => $exception->getFile(),
                    'line'    => $exception->getLine(),
                    'trace'   => $exception->getTraceAsString(),
                ));

                $this->getHelper('Module\Exception')->process($exception);
            }
        }

        return $result;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Lock\Item\Manager
     */
    protected function getLockItem()
    {
        if (!is_null($this->lockItem)) {
            return $this->lockItem;
        }

        $this->lockItem = $this->modelFactory->getObject('Lock\Item\Manager');
        $this->lockItem->setNick(self::LOCK_ITEM_NICK);

        return $this->lockItem;
    }

    /**
     * @return bool
     */
    protected function isParallelStrategyInProgress()
    {
        for ($i = 1; $i <= Parallel::MAX_PARALLEL_EXECUTED_CRONS_COUNT; $i++) {
            $lockItem = $this->modelFactory->getObject('Lock\Item\Manager');
            $lockItem->setNick(Parallel::GENERAL_LOCK_ITEM_PREFIX.$i);

            if ($lockItem->isExist()) {
                return true;
            }
        }

        return false;
    }

    //########################################
}