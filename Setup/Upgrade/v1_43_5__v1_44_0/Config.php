<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_43_5__v1_44_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y23_m08/AddShippingIrregularForEbay',
        ];
    }
}
