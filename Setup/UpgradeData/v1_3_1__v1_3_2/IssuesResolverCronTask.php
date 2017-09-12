<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_3_1__v1_3_2;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class IssuesResolverCronTask extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['module_config'];
    }

    public function execute()
    {
        $this->getConfigModifier('module')->insert(
            '/cron/task/issues_resolver/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $this->getConfigModifier('module')->insert(
            '/cron/task/issues_resolver/', 'interval', '3600', 'in seconds'
        );
        $this->getConfigModifier('module')->insert(
            '/cron/task/issues_resolver/', 'last_access', NULL, 'date of last access'
        );
        $this->getConfigModifier('module')->insert(
            '/cron/task/issues_resolver/', 'last_run', NULL, 'date of last run'
        );
    }

    //########################################
}