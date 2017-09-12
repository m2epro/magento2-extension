<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_3_1__v1_3_2;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class NewFolderStructureForCronTasks extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['lock_item', 'operation_history'];
    }

    public function execute()
    {
        $changesMap = array(
            "amazon_actions"                         => "amazon/actions",
            "repricing_inspect_products"             => "amazon/repricing_inspect_products",
            "repricing_synchronization_actual_price" => "amazon/repricing_synchronization_actual_price",
            "repricing_synchronization_general"      => "amazon/repricing_synchronization_general",
            "repricing_update_settings"              => "amazon/repricing_update_settings",

            "ebay_actions"                     => "ebay/actions",
            "update_ebay_accounts_preferences" => "ebay/update_accounts_preferences"
        );

        // Config Migration
        //----------------------------------------

        $taskPrefix = "/cron/task/";

        $configModifier = $this->getConfigModifier("module");
        foreach ($changesMap as $oldGroup => $newGroup) {
            $configModifier->updateGroup(
                $taskPrefix . $newGroup . "/",
                array("`group` = ?" => $taskPrefix . $oldGroup . "/")
            );
        }

        // Lock Item Migration
        //----------------------------------------

        $nickPrefix = "cron_task_";

        $tableName = $this->getFullTableName("lock_item");
        foreach ($changesMap as $oldGroup => $newGroup) {

            $newNick = $nickPrefix . str_replace("/", "_", $newGroup);
            $oldNick = $nickPrefix . $oldGroup;

            if ($newNick == $oldNick) {
                continue;
            }

            $this->getConnection()->update(
                $tableName,
                array('nick' => $newNick),
                array('nick = ?' => $oldNick)
            );
        }

        // Operation History Migration
        //----------------------------------------

        $tableName = $this->getFullTableName("operation_history");
        foreach ($changesMap as $oldGroup => $newGroup) {

            $newNick = $nickPrefix . str_replace("/", "_", $newGroup);
            $oldNick = $nickPrefix . $oldGroup;

            if ($newNick == $oldNick) {
                continue;
            }

            $this->getConnection()->update(
                $tableName,
                array('nick' => $newNick),
                array('nick = ?' => $oldNick)
            );
        }
    }

    //########################################
}