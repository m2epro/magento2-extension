<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_50_3__v1_51_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y23_m11/AddWalmartIsWFS',
            '@y23_m11/AddWalmartOrdersWfsLastSynchronization',
        ];
    }
}
