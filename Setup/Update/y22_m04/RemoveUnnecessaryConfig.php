<?php

namespace Ess\M2ePro\Setup\Update\y22_m04;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y22_m04\RemoveUnnecessaryConfig
 */
class RemoveUnnecessaryConfig extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getConfigModifier('module')->delete('/server/exceptions/', 'send');
        $this->getConfigModifier('module')->delete('/server/exceptions/', 'filters');
        $this->getConfigModifier('module')->delete('/server/fatal_error/', 'send');
        $this->getConfigModifier('module')->delete('/server/logging/', 'send');
    }

    //########################################
}
