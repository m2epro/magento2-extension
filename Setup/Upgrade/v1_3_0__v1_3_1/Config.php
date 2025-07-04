<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_3_0__v1_3_1;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    //########################################

    public function getFeaturesList()
    {
        return [
            'PartialStatesForAfnRepricingFilters',
            'MarketplacesFeatures',
            'HealthStatus',
        ];
    }

    //########################################
}
