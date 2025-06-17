<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_51_0__v1_51_1;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y23_m11/RestoreEpidsForAustralia',
        ];
    }
}
