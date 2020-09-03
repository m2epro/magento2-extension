<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Inspector;

use Ess\M2ePro\Model\ControlPanel\Inspection\AbstractInspection;
use Ess\M2ePro\Model\ControlPanel\Inspection\InspectorInterface;
use Ess\M2ePro\Model\ControlPanel\Inspection\Manager;
use Ess\M2ePro\Helper\Module\Cron;

class ExtensionCron extends AbstractInspection implements InspectorInterface
{
    //########################################

    public function getTitle()
    {
        return 'Extension Cron';
    }

    public function getExecutionSpeed()
    {
        return Manager::EXECUTION_SPEED_FAST;
    }

    public function getDescription()
    {
        return <<<HTML
- Cron [runner] does not work<br>
- Cron [runner] is not working more than 30 min<br>
- Cron [runner] is disabled by developer
HTML;
    }

    public function getGroup()
    {
        return Manager::GROUP_GENERAL;
    }

    //########################################

    public function process()
    {
        $issues = [];
        /**  @var \Ess\M2ePro\Helper\Module\Cron $helper*/
        $helper = $this->helperFactory->getObject('Module_Cron');
        /**@var \Ess\M2ePro\Model\Config\Manager $moduleConfig */
        $moduleConfig = $this->helperFactory->getObject('Module')->getConfig();

        if ($helper->getLastRun() === null) {
            $issues[] = $this->resultFactory->createError(
                $this,
                "Cron [{$helper->getRunner()}] does not work"
            );
        } elseif ($helper->isLastRunMoreThan(1800)) {
            $now = new \DateTime('now', new \DateTimeZone('UTC'));
            $cron = new \DateTime($helper->getLastRun(), new \DateTimeZone('UTC'));
            $diff = round(($now->getTimestamp() - $cron->getTimestamp()) / 60, 0);

            $issues[] = $this->resultFactory->createError(
                $this,
                "Cron [{$helper->getRunner()}] is not working for {$diff} min",
                <<<HTML
Last run: {$helper->getLastRun()}
Now:      {$now->format('Y-m-d H:i:s')}
HTML
            );
        }

        foreach ([Cron::RUNNER_MAGENTO, Cron::RUNNER_SERVICE_CONTROLLER, Cron::RUNNER_SERVICE_PUB] as $runner) {
            if ($moduleConfig->getGroupValue("/cron/{$runner}/", 'disabled')) {
                $issues[] = $this->resultFactory->createNotice(
                    $this,
                    "Cron [{$runner}] is disabled by developer"
                );
            }
        }

        return $issues;
    }

    //########################################
}
