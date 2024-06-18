<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_62_0__v1_62_1;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y24_m06/RemoveAuEpidsVisibleFromConfigTable',
            '@y24_m06/RemoveEbayCharity',
        ];
    }
}
