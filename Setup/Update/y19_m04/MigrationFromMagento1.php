<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y19_m04;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y19_m04\MigrationFromMagento1
 */
class MigrationFromMagento1 extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['wizard'];
    }

    public function execute()
    {
        $select = $this->getConnection()
                       ->select()
                       ->from($this->getFullTableName('wizard'), 'nick')
                       ->where('nick = ?', 'migrationFromMagento1');

        if ($this->getConnection()->fetchOne($select) === false) {
            $this->getConnection()->insert($this->getFullTableName('wizard'), [
                'nick'     => 'migrationFromMagento1',
                'view'     => '*',
                'status'   => 2,
                'step'     => null,
                'type'     => 1,
                'priority' => 1,
            ]);
        }
    }

    //########################################
}
