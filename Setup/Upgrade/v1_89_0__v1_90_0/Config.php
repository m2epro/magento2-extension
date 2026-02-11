<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Upgrade\v1_89_0__v1_90_0;

class Config extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y26_m01/AddWalmartRepricerPolicy',
        ];
    }
}
