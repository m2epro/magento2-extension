<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Inspector;

use Ess\M2ePro\Model\ControlPanel\Inspection\InspectorInterface;
use Ess\M2ePro\Helper\Module\Cron;
use Ess\M2ePro\Helper\Module;
use Ess\M2ePro\Model\ControlPanel\Inspection\Issue\Factory as IssueFactory;

class ExtensionCron implements InspectorInterface
{
    /** @var Cron */
    private $moduleCron;

    /** @var Module */
    private $helperModule;

    /** @var IssueFactory  */
    private $issueFactory;

    //########################################

    public function __construct(
        Cron $moduleCron,
        Module $helperModule,
        IssueFactory $issueFactory
    ) {
        $this->moduleCron = $moduleCron;
        $this->helperModule = $helperModule;
        $this->issueFactory = $issueFactory;
    }

    //########################################

    public function process()
    {
        $issues = [];
        $helper = $this->moduleCron;
        $moduleConfig = $this->helperModule->getConfig();

        if ($helper->getLastRun() === null) {
            $issues[] = $this->issueFactory->create(
                "Cron [{$helper->getRunner()}] does not work"
            );
        } elseif ($helper->isLastRunMoreThan(1800)) {
            $now = new \DateTime('now', new \DateTimeZone('UTC'));
            $cron = new \DateTime($helper->getLastRun(), new \DateTimeZone('UTC'));
            $diff = round(($now->getTimestamp() - $cron->getTimestamp()) / 60, 0);

            $issues[] = $this->issueFactory->create(
                "Cron [{$helper->getRunner()}] is not working for {$diff} min",
                <<<HTML
Last run: {$helper->getLastRun()}
Now:      {$now->format('Y-m-d H:i:s')}
HTML
            );
        }

        foreach ([Cron::RUNNER_MAGENTO, Cron::RUNNER_SERVICE_CONTROLLER, Cron::RUNNER_SERVICE_PUB] as $runner) {
            if ($moduleConfig->getGroupValue("/cron/{$runner}/", 'disabled')) {
                $issues[] = $this->issueFactory->create(
                    "Cron [{$runner}] is disabled by developer"
                );
            }
        }

        return $issues;
    }

    //########################################
}
