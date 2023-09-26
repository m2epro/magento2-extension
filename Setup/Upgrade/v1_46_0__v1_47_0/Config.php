<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_46_0__v1_47_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y23_m09/RemoveLastAccessAndRunFromConfigTable',
        ];
    }
}
