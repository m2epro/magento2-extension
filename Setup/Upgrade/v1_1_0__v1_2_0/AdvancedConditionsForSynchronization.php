<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_1_0__v1_2_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class AdvancedConditionsForSynchronization extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return [
            'amazon_template_synchronization',
            'ebay_template_synchronization'
        ];
    }

    public function execute()
    {
        $this->getTableModifier('amazon_template_synchronization')
            ->addColumn(
                'list_advanced_rules_mode','SMALLINT(4) UNSIGNED NOT NULL',NULL,'list_qty_calculated_value_max'
            )
            ->addColumn(
                'relist_advanced_rules_mode','SMALLINT(4) UNSIGNED NOT NULL',NULL,'relist_qty_calculated_value_max'
            )
            ->addColumn(
                'stop_advanced_rules_mode','SMALLINT(4) UNSIGNED NOT NULL',NULL,'stop_qty_calculated_value_max'
            )
            ->addColumn('list_advanced_rules_filters','TEXT',NULL,'list_advanced_rules_mode')
            ->addColumn('relist_advanced_rules_filters','TEXT',NULL,'relist_advanced_rules_mode')
            ->addColumn('stop_advanced_rules_filters','TEXT',NULL,'stop_advanced_rules_mode');

        $this->getTableModifier('ebay_template_synchronization')
           ->addColumn(
               'list_advanced_rules_mode','SMALLINT(4) UNSIGNED NOT NULL',NULL,'list_qty_calculated_value_max'
           )
           ->addColumn(
               'relist_advanced_rules_mode','SMALLINT(4) UNSIGNED NOT NULL',NULL,'relist_qty_calculated_value_max'
           )
           ->addColumn(
               'stop_advanced_rules_mode','SMALLINT(4) UNSIGNED NOT NULL',NULL,'stop_qty_calculated_value_max'
           )
           ->addColumn('list_advanced_rules_filters','TEXT',NULL,'list_advanced_rules_mode')
           ->addColumn('relist_advanced_rules_filters','TEXT',NULL,'relist_advanced_rules_mode')
           ->addColumn('stop_advanced_rules_filters','TEXT',NULL,'stop_advanced_rules_mode');
    }

    //########################################
}