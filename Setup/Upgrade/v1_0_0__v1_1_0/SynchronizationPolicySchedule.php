<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_0_0__v1_1_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class SynchronizationPolicySchedule extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['ebay_template_synchronization'];
    }

    public function execute()
    {
        $this->getTableModifier('ebay_template_synchronization')->dropColumn('schedule_mode');
        $this->getTableModifier('ebay_template_synchronization')->dropColumn('schedule_interval_settings');
        $this->getTableModifier('ebay_template_synchronization')->dropColumn('schedule_week_settings');
    }

    //########################################
}