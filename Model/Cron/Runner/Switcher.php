<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Runner;

use \Ess\M2ePro\Helper\Module\Cron as CronHelper;

/**
 * Class \Ess\M2ePro\Model\Cron\Runner\Switcher
 */
class Switcher extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var array  */
    protected $runnerPriority = [
        CronHelper::RUNNER_SERVICE_PUB        => 30,
        CronHelper::RUNNER_SERVICE_CONTROLLER => 20,
        CronHelper::RUNNER_MAGENTO            => 10,
        CronHelper::RUNNER_DEVELOPER          => -1,
    ];

    //########################################

    public function check(AbstractModel $currentRunner)
    {
        $helper = $this->getHelper('Module\Cron');

        $currentPriority = $this->getRunnerPriority($currentRunner->getNick());
        $configPriority  = $this->getRunnerPriority($helper->getRunner());

        // switch to a new runner by higher priority
        if ($currentPriority > $configPriority) {
            $helper->setRunner($currentRunner->getNick());
            $helper->setLastRunnerChange($this->getHelper('Data')->getCurrentGmtDate());

            if ($currentRunner instanceof Service\AbstractModel) {
                $currentRunner->resetTasksStartFrom();
            }

            return;
        }

        if ($currentRunner instanceof Service\AbstractModel &&
            $helper->isLastAccessMoreThan(Service\AbstractModel::MAX_INACTIVE_TIME)) {
            $currentRunner->resetTasksStartFrom();
        }

        //switch to a new runner by inactivity
        if ($currentPriority < $configPriority && $currentPriority > 0 &&
            $helper->isLastAccessMoreThan(Service\AbstractModel::MAX_INACTIVE_TIME)) {
            $helper->setRunner($currentRunner->getNick());
            $helper->setLastRunnerChange($this->getHelper('Data')->getCurrentGmtDate());
        }
    }

    //########################################

    private function getRunnerPriority($nick)
    {
        return isset($this->runnerPriority[$nick]) ? $this->runnerPriority[$nick] : -1;
    }

    //########################################
}
