<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_0_0__v1_1_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class ModuleInPrimaryConfig extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['primary_config'];
    }

    public function execute()
    {
        $primaryConfigModifier = $this->getConfigModifier('primary');

        $primaryConfigModifier->delete('/modules/');

        $select = $this->getConnection()->select()->from($this->getFullTableName('primary_config'));
        $select->reset(\Zend_Db_Select::COLUMNS);
        $select->columns('group');
        $select->where('`group` like ?', '/M2ePro/%');

        $groupsForRenaming = $this->getConnection()->fetchCol($select);

        foreach (array_unique($groupsForRenaming) as $group) {
            $newGroup = preg_replace('/^\/M2ePro/', '', $group);
            $primaryConfigModifier->updateGroup($newGroup, ['`group` = ?' => $group]);
        }

        $primaryConfigModifier->getEntity('/server/', 'application_key')
            ->updateValue('02edcc129b6128f5fa52d4ad1202b427996122b6');
    }

    //########################################
}