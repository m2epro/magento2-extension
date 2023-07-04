<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_42_0__v1_43_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y23_m06/CreateProductTypeValidationTable',
            '@y23_m06/IgnoreVariationMpnInResolverConfig',
        ];
    }
}
