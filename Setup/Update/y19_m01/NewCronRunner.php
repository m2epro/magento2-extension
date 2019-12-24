<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y19_m01;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y19\NewCronRunner_m01
 */
class NewCronRunner extends AbstractFeature
{
    public function getBackupTables()
    {
        return ['module_config'];
    }

    public function execute()
    {
        $this->getConnection()->update(
            $this->getFullTableName('module_config'),
            ['value' => 'service_controller'],
            '`group` = "/cron/" AND `key` = "runner" AND `value` = "service"'
        );

        $this->getConfigModifier('module')
             ->getEntity('/cron/service/', 'disabled')
             ->updateGroup('/cron/service_controller/');

        $this->getConfigModifier('module')
             ->insert('/cron/service_pub/', 'disabled', '0');
    }
}
