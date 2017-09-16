<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Strategy;

use Ess\M2ePro\Model\Exception;

class Parallel extends AbstractModel
{
    const GENERAL_LOCK_ITEM_PREFIX = 'cron_strategy_parallel_';

    const MAX_PARALLEL_EXECUTED_CRONS_COUNT = 10;

    /**
     * @var \Ess\M2ePro\Model\Lock\Item\Manager
     */
    private $generalLockItem = NULL;

    /**
     * @var \Ess\M2ePro\Model\Lock\Item\Manager
     */
    private $fastTasksLockItem = NULL;

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
        $transactionalManager = $this->modelFactory->getObject('Lock\Transactional\Manager');
        $transactionalManager->setNick(self::INITIALIZATION_TRANSACTIONAL_LOCK_NICK);

        $transactionalManager->lock();

        if ($this->isSerialStrategyInProgress()) {
            $transactionalManager->unlock();
            return $result;
        }

        $this->getGeneralLockItem()->create();
        $this->getGeneralLockItem()->makeShutdownFunction();

        if (!$this->getFastTasksLockItem()->isExist()) {
            $this->getFastTasksLockItem()->create($this->getGeneralLockItem()->getRealId());
            $this->getFastTasksLockItem()->makeShutdownFunction();

            $transactionalManager->unlock();

            $result = !$this->processFastTasks() ? false : $result;

            $this->getFastTasksLockItem()->remove();
        }

        $transactionalManager->unlock();

        $result = !$this->processSlowTasks() ? false : $result;

        $this->getGeneralLockItem()->remove();

        return $result;
    }

    // ---------------------------------------

    private function processFastTasks()
    {
        $result = true;

        foreach ($this->getAllowedFastTasks() as $taskNick) {

            try {

                $taskObject = $this->getTaskObject($taskNick);
                $taskObject->setParentLockItem($this->getFastTasksLockItem());

                $tempResult = $taskObject->process();

                if (!is_null($tempResult) && !$tempResult) {
                    $result = false;
                }

                $this->getFastTasksLockItem()->activate();

            } catch (\Exception $exception) {

                $result = false;

                $this->getOperationHistory()->addContentData('exception', array(
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

    private function processSlowTasks()
    {
        $helper = $this->getHelper('Module\Cron');

        $result = true;

        for ($i = 0; $i < count($this->getAllowedSlowTasks()); $i++) {

            $transactionalManager = $this->modelFactory->getObject('Lock\Transactional\Manager');
            $transactionalManager->setNick(self::GENERAL_LOCK_ITEM_PREFIX.'slow_task_switch');

            $transactionalManager->lock();

            $taskNick = $this->getNextSlowTask();
            $helper->setLastExecutedSlowTask($taskNick);

            $transactionalManager->unlock();

            $taskObject = $this->getTaskObject($taskNick);
            $taskObject->setParentLockItem($this->getGeneralLockItem());

            if (!$taskObject->isPossibleToRun()) {
                continue;
            }

            try {
                $result = $taskObject->process();
            } catch (\Exception $exception) {

                $result = false;

                $this->getOperationHistory()->addContentData('exception', array(
                    'message' => $exception->getMessage(),
                    'file'    => $exception->getFile(),
                    'line'    => $exception->getLine(),
                    'trace'   => $exception->getTraceAsString(),
                ));

                $this->getHelper('Module\Exception')->process($exception);
            }

            break;
        }

        return $result;
    }

    //########################################

    private function getAllowedFastTasks()
    {
        return array_intersect($this->getAllowedTasks(), array(
            \Ess\M2ePro\Model\Cron\Task\IssuesResolver::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\RepricingInspectProducts::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\RepricingUpdateSettings::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\RepricingSynchronizationGeneral::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\RepricingSynchronizationActualPrice::NICK,
            \Ess\M2ePro\Model\Cron\Task\RequestPendingSingle::NICK,
            \Ess\M2ePro\Model\Cron\Task\RequestPendingPartial::NICK,
            \Ess\M2ePro\Model\Cron\Task\ConnectorRequesterPendingSingle::NICK,
            \Ess\M2ePro\Model\Cron\Task\ConnectorRequesterPendingPartial::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Actions::NICK,
            \Ess\M2ePro\Model\Cron\Task\LogsClearing::NICK,
            \Ess\M2ePro\Model\Cron\Task\Servicing::NICK,
            \Ess\M2ePro\Model\Cron\Task\HealthStatus::NICK,
            \Ess\M2ePro\Model\Cron\Task\Ebay\UpdateAccountsPreferences::NICK,
            \Ess\M2ePro\Model\Cron\Task\Synchronization::NICK,
            \Ess\M2ePro\Model\Cron\Task\ArchiveOrdersEntities::NICK
        ));
    }

    private function getAllowedSlowTasks()
    {
        return array_intersect($this->getAllowedTasks(), array(
            \Ess\M2ePro\Model\Cron\Task\Ebay\Actions::NICK
        ));
    }

    // ---------------------------------------

    private function getNextSlowTask()
    {
        $helper = $this->getHelper('Module\Cron');
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
     * @throws Exception
     */
    private function getGeneralLockItem()
    {
        if (!is_null($this->generalLockItem)) {
            return $this->generalLockItem;
        }

        for ($index = 1; $index <= self::MAX_PARALLEL_EXECUTED_CRONS_COUNT; $index++) {
            $lockItem = $this->modelFactory->getObject('Lock\Item\Manager');
            $lockItem->setNick(self::GENERAL_LOCK_ITEM_PREFIX.$index);

            if (!$lockItem->isExist()) {
                return $this->generalLockItem = $lockItem;
            }
        }

        throw new Exception('Too many parallel lock items.');
    }

    /**
     * @return \Ess\M2ePro\Model\Lock\Item\Manager
     */
    private function getFastTasksLockItem()
    {
        if (!is_null($this->fastTasksLockItem)) {
            return $this->fastTasksLockItem;
        }

        $this->fastTasksLockItem = $this->modelFactory->getObject('Lock\Item\Manager');
        $this->fastTasksLockItem->setNick('cron_strategy_parallel_fast_tasks');

        return $this->fastTasksLockItem;
    }

    /**
     * @return bool
     */
    private function isSerialStrategyInProgress()
    {
        $serialLockItem = $this->modelFactory->getObject('Lock\Item\Manager');
        $serialLockItem->setNick(Serial::LOCK_ITEM_NICK);

        return $serialLockItem->isExist();
    }

    //########################################
}