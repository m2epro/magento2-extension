<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Strategy;

class Serial extends AbstractStrategy
{
    /**
     * @var \Ess\M2ePro\Model\LockItem
     */
    private $lockItem = NULL;

    //########################################

    protected function getNick()
    {
        return \Ess\M2ePro\Helper\Module\Cron::STRATEGY_SERIAL;
    }

    //########################################

    public function process()
    {
        if ($this->getLockItem()->isExist()) {
            return true;
        }

        return parent::process();
    }

    // ---------------------------------------

    /**
     * @param $taskNick
     * @return \Ess\M2ePro\Model\Cron\Task\AbstractTask
     */
    protected function getTaskObject($taskNick)
    {
        $task = parent::getTaskObject($taskNick);
        return $task->setParentLockItem($this->getLockItem());
    }

    protected function processTasks()
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

    //########################################

    protected function beforeStart()
    {
        $this->getLockItem()->create();
        $this->getLockItem()->makeShutdownFunction();

        parent::beforeStart();
    }

    protected function afterEnd()
    {
        parent::afterEnd();

        $this->getLockItem()->remove();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\LockItem
     */
    protected function getLockItem()
    {
        if (!is_null($this->lockItem)) {
            return $this->lockItem;
        }

        $this->lockItem = $this->activeRecordFactory->getObject('LockItem');
        $this->lockItem->setNick('cron_strategy_serial');

        return $this->lockItem;
    }

    //########################################
}