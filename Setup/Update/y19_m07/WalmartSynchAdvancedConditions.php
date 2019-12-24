<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y19_m07;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y19\WalmartSynchAdvancedConditions_m07
 */
class WalmartSynchAdvancedConditions extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return [
            'walmart_template_synchronization'
        ];
    }

    public function execute()
    {
        $this->getTableModifier('walmart_template_synchronization')
            ->addColumn(
                'list_advanced_rules_mode',
                'SMALLINT(4) UNSIGNED NOT NULL',
                null,
                'list_qty_calculated_value_max'
            )
            ->addColumn(
                'relist_advanced_rules_mode',
                'SMALLINT(4) UNSIGNED NOT NULL',
                null,
                'relist_qty_calculated_value_max'
            )
            ->addColumn(
                'stop_advanced_rules_mode',
                'SMALLINT(4) UNSIGNED NOT NULL',
                null,
                'stop_qty_calculated_value_max'
            )
            ->addColumn('list_advanced_rules_filters', 'TEXT', null, 'list_advanced_rules_mode')
            ->addColumn('relist_advanced_rules_filters', 'TEXT', null, 'relist_advanced_rules_mode')
            ->addColumn('stop_advanced_rules_filters', 'TEXT', null, 'stop_advanced_rules_mode');
    }

    //########################################
}