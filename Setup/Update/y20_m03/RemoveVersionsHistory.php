<?php

namespace Ess\M2ePro\Setup\Update\y20_m03;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m03\RemoveVersionsHistory
 */
class RemoveVersionsHistory extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $tableName = $this->getFullTableName('versions_history');
        $this->installer->run("DROP TABLE IF EXISTS `{$tableName}`");
    }

    //########################################
}
