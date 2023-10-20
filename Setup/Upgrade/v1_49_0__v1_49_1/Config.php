<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_49_0__v1_49_1;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y23_m10/EnableEbayShippingRate',
        ];
    }
}
