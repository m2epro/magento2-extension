<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Checker;

/**
 * Class \Ess\M2ePro\Model\Cron\Checker\Dispatcher
 */
class Dispatcher extends \Ess\M2ePro\Model\AbstractModel
{
    //########################################

    public function process()
    {
        foreach ($this->getAllowedTasks() as $taskNick) {
            $this->getTaskObject($taskNick)->process();
        }
    }

    //########################################

    protected function getAllowedTasks()
    {
        return [\Ess\M2ePro\Model\Cron\Checker\Task\RepairCrashedTables::NICK];
    }

    //########################################

    /**
     * @param $taskNick
     * @return \Ess\M2ePro\Model\Cron\Checker\Task\AbstractModel
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getTaskObject($taskNick)
    {
        $taskNick = str_replace('_', ' ', $taskNick);
        $taskNick = str_replace(' ', '', ucwords($taskNick));

        return $this->modelFactory->getObject('Cron\Checker\Task\\'.trim($taskNick));
    }

    //########################################
}
