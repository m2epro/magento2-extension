<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Log;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Log\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    const ACTION_KEY = 'last_action_id';

    //########################################

    public function getConfigGroupSuffix()
    {
        return 'general';
    }

    public function getNextActionId()
    {
        $connection = $this->getConnection();

        $table = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_module_config');
        $groupConfig = '/logs/'.$this->getConfigGroupSuffix().'/';

        $lastActionId = (int)$connection->select()
            ->from($table, 'value')
            ->where('`group` = ?', $groupConfig)
            ->where('`key` = ?', self::ACTION_KEY)
            ->query()->fetchColumn();

        $nextActionId = $lastActionId + 1;

        $connection->update(
            $table,
            ['value' => $nextActionId],
            ['`group` = ?' => $groupConfig, '`key` = ?' => 'last_action_id']
        );

        return $nextActionId;
    }

    public function clearMessages($filters = [])
    {
        $where = [];
        foreach ($filters as $column => $value) {
            $where[$column.' = ?'] = $value;
        }

        $this->getConnection()->delete($this->getMainTable(), $where);
    }

    //########################################
}
