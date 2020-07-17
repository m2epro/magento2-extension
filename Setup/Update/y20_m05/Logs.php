<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y20_m05;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m05\Logs
 */
class Logs extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->installer->getConnection()->truncateTable(
            $this->getFullTableName('system_log')
        );

        $this->installer->getConnection()->truncateTable(
            $this->getFullTableName('synchronization_log')
        );

        //----------------------------------------

        // exception in renaming on repeat upgrade because of "description" column will be added again
        $systemLogMod = $this->getTableModifier('system_log');
        if ($systemLogMod->isColumnExists('description') && !$systemLogMod->isColumnExists('detailed_description')) {
            $systemLogMod->renameColumn('description', 'detailed_description');
        }

        $systemLogMod
            ->changeColumn('detailed_description', 'LONGTEXT', 'NULL', null, false)
            ->addColumn('class', 'VARCHAR(255)', 'NULL', 'type', true, false)
            ->addColumn('description', 'TEXT', 'NULL', 'class', false, false)
            ->dropColumn('update_date', true, false)
            ->commit();

        $this->getTableModifier('synchronization_log')
            ->addColumn('detailed_description', 'LONGTEXT', 'NULL', 'description', false, false)
            ->dropColumn('priority', true, false)
            ->dropColumn('update_date', true, false)
            ->commit();

        $this->getTableModifier('listing_log')
            ->dropColumn('priority', true, false)
            ->dropColumn('update_date', true, false)
            ->commit();

        $this->getTableModifier('order_log')
            ->dropColumn('update_date', true, false)
            ->commit();

        $this->getTableModifier('ebay_account_pickup_store_log')
            ->dropColumn('priority', true, false)
            ->dropColumn('update_date', true, false)
            ->commit();
    }

    //########################################
}
