<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */
// @codingStandardsIgnoreFile

namespace Ess\M2ePro\Setup\Update\y19_m10;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y19_m10\CronTaskRemovedFromConfig
 */
class CronTaskRemovedFromConfig extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getConfigModifier('module')->delete('/cron/checker/task/repair_crashed_tables/');

        $configTable = $this->getFullTableName('module_config');
        $groupsToSkip = [
            '/cron/task/system/servicing/synchronize/' => 'interval',
            '/cron/task/ebay/listing/product/process_instructions/' => 'mode',
            '/cron/task/amazon/listing/product/process_instructions/' => 'mode',
            '/cron/task/walmart/listing/product/process_instructions/' => 'mode'
        ];

        $query = $this->getConnection()->select()
            ->from($configTable)
            ->where('`key` IN (?)', ['last_access', 'last_run', 'interval', 'mode'])
            ->where('`group` LIKE ?', '/cron/task/%')
            ->query();

        $ids = [];
        while ($row = $query->fetch()) {
            if (isset($groupsToSkip[$row['group']]) && $groupsToSkip[$row['group']] == $row['key']) {
                continue;
            }

            $ids[] = $row['id'];
        }

        $this->getConnection()->delete($configTable, ['`id` IN (?)' => $ids]);
    }

    //########################################
}
