<?php

namespace Ess\M2ePro\Setup\Update\y20_m03;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m03\CronStrategy
 */
class CronStrategy extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getConfigModifier('module')->insert('/cron/', 'last_executed_task_group', null);
    }

    //########################################
}
