<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Strategy;

class Parallel extends AbstractStrategy
{
    /**
     * @var \Ess\M2ePro\Model\LockItem
     */
    private $fastTasksLockItem = null;

    //########################################

    protected function getNick()
    {
        return \Ess\M2ePro\Helper\Module\Cron::STRATEGY_PARALLEL;
    }

    //########################################

    protected function processTasks()
    {
        $result = true;

        if (!$this->getFastTasksLockItem()->isExist()) {

            $this->getFastTasksLockItem()->create();
            $this->getFastTasksLockItem()->makeShutdownFunction();
            sleep(2);

            $result = !$this->processFastTasks() ? false : $result;

            $this->getFastTasksLockItem()->remove();
            sleep(2);
        }

        return !$this->processSlowTasks() ? false : $result;
    }

    // ---------------------------------------

    private function processFastTasks()
    {
        $result = true;

        foreach ($this->getAllowedFastTasks() as $taskNick) {

            try {

                $tempResult = $this->getTaskObject($taskNick)->process();

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

            $taskNick = $this->getNextSlowTask();
            $helper->setLastExecutedSlowTask($taskNick);

            $taskObject = $this->getTaskObject($taskNick);

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
            \Ess\M2ePro\Model\Cron\Task\RepricingInspectProducts::NICK,
            \Ess\M2ePro\Model\Cron\Task\RepricingUpdateSettings::NICK,
            \Ess\M2ePro\Model\Cron\Task\RepricingSynchronization::NICK,
            \Ess\M2ePro\Model\Cron\Task\RequestPendingSingle::NICK,
            \Ess\M2ePro\Model\Cron\Task\RequestPendingPartial::NICK,
            \Ess\M2ePro\Model\Cron\Task\ConnectorRequesterPendingSingle::NICK,
            \Ess\M2ePro\Model\Cron\Task\ConnectorRequesterPendingPartial::NICK,
            \Ess\M2ePro\Model\Cron\Task\AmazonActions::NICK,
            \Ess\M2ePro\Model\Cron\Task\LogsClearing::NICK,
            \Ess\M2ePro\Model\Cron\Task\Servicing::NICK,
            \Ess\M2ePro\Model\Cron\Task\UpdateEbayAccountsPreferences::NICK,
            \Ess\M2ePro\Model\Cron\Task\Synchronization::NICK,
        ));
    }

    private function getAllowedSlowTasks()
    {
        return array_intersect($this->getAllowedTasks(), array(
            \Ess\M2ePro\Model\Cron\Task\EbayActions::NICK
        ));
    }

    // ---------------------------------------

    private function getNextSlowTask()
    {
        $helper = $this->getHelper('Module\Cron');
        $lastExecutedTask = $helper->getLastExecutedSlowTask();

        $allowedSlowTasks = $this->getAllowedSlowTasks();

        if (empty($lastExecutedTask) || end($allowedSlowTasks) == $lastExecutedTask) {
            return reset($allowedSlowTasks);
        }

        $lastExecutedTaskIndex = array_search($lastExecutedTask, $this->getAllowedSlowTasks());
        return $allowedSlowTasks[$lastExecutedTaskIndex + 1];
    }

    /**
     * @return \Ess\M2ePro\Model\LockItem
     */
    private function getFastTasksLockItem()
    {
        if (!is_null($this->fastTasksLockItem)) {
            return $this->fastTasksLockItem;
        }

        $this->fastTasksLockItem = $this->activeRecordFactory->getObject('LockItem');
        $this->fastTasksLockItem->setNick('cron_strategy_parallel_fast_tasks');

        return $this->fastTasksLockItem;
    }

    //########################################
}