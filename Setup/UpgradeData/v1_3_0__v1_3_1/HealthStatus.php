<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_3_0__v1_3_1;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class HealthStatus extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['module_config'];
    }

    public function execute()
    {
        $this->getConfigModifier('module')->insert(
            '/cron/task/health_status/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $this->getConfigModifier('module')->insert(
            '/cron/task/health_status/', 'interval', '1800', 'in seconds'
        );
        $this->getConfigModifier('module')->insert(
            '/cron/task/health_status/', 'last_access', NULL, 'date of last access'
        );
        $this->getConfigModifier('module')->insert(
            '/cron/task/health_status/', 'last_run', NULL, 'date of last run'
        );

        $this->getConfigModifier('module')->insert('/health_status/notification/', 'mode', 1);
        $this->getConfigModifier('module')->insert('/health_status/notification/', 'email', '');
        $this->getConfigModifier('module')->insert('/health_status/notification/', 'level', 40);
    }

    //########################################
}