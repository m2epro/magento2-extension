<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_2_0__v1_3_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class RepricingSynchronization extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['module_config'];
    }

    public function execute()
    {
        $moduleConfigModifier = $this->getConfigModifier('module');

        $moduleConfigModifier->insert(
            '/cron/task/repricing_synchronization_actual_price/', 'mode', 1, '0 - disable,\r\n1 - enable'
        );
        $moduleConfigModifier->insert(
            '/cron/task/repricing_synchronization_actual_price/', 'interval', 3600, 'in seconds'
        );
        $moduleConfigModifier->insert(
            '/cron/task/repricing_synchronization_actual_price/', 'last_run', NULL, 'date of last access'
        );
        $moduleConfigModifier->updateGroup(
            '/cron/task/repricing_synchronization_general/',
            ['`group` = ?' => '/cron/task/repricing_synchronization/']
        );
    }

    //########################################
}