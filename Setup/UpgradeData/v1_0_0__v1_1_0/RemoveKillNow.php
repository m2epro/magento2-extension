<?php

namespace Ess\M2ePro\Setup\UpgradeData\v1_0_0__v1_1_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class RemoveKillNow extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('lock_item')->dropColumn('kill_now');
    }

    //########################################
}