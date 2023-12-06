<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_51_1__v1_52_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y23_m11/RemoveSupportUrlFromConfigTable',
            '@y23_m12/AddEbayBuyerReturnRequested',
        ];
    }
}

