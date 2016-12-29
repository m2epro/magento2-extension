<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_2_0__v1_3_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class WizardMigrationFromMagento1 extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['wizard'];
    }

    public function execute()
    {
        $this->getConnection()->update(
            $this->getFullTableName('wizard'),
            ['step' => 'synchronization'],
            'step = "welcome" AND nick = "migrationFromMagento1"'
        );
    }

    //########################################
}