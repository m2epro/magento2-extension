<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Upgrade\v1_85_1__v1_86_0;

class Config extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y25_m10/RemoveWalmartShippingOverrides',
            '@y25_m10/RepricerMaxMinPriceUpdateWorkflow',
        ];
    }
}
