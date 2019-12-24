<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y19_m04;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y19\Maintenance_m04
 */
class Maintenance extends AbstractFeature
{
    public function getBackupTables()
    {
        return ['synchronization_config'];
    }

    public function execute()
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->helperFactory->getObject('Module_Database_Structure')
                ->getTableNameWithPrefix('core_config_data'), 'value')
            ->where('scope = ?', 'default')
            ->where('scope_id = ?', 0)
            ->where('path = ?', 'm2epro/general/maintenance');

        if ($this->getConnection()->fetchOne($select) !== false) {
            $this->getConnection()->delete(
                $this->helperFactory->getObject('Module_Database_Structure')
                    ->getTableNameWithPrefix('core_config_data'),
                [
                    'scope = ?' => 'default',
                    'scope_id = ?' => 0,
                    'path = ?' => 'm2epro/general/maintenance',
                ]
            );
            return;
        }

        $this->getConfigModifier('module')->delete('/debug/maintenance/');
    }
}
