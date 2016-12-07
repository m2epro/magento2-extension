<?php

namespace Ess\M2ePro\Setup\UpgradeData\v1_0_0__v1_1_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class ServicingMessages extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['primary_config'];
    }

    public function execute()
    {
        $this->getConfigModifier('primary')->getEntity('/M2ePro/server/', 'messages')->delete();
    }

    //########################################
}