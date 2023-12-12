<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_52_0__v1_53_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y23_m11/AddAmazonOriginalOrderIdColumn',
            '@y23_m12/AddCustomizedInfoToAmazonItems',
        ];
    }
}

