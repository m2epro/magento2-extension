<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_68_0__v1_68_1;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y24_m10/DropTableWalmartDictionarySpecific',
        ];
    }
}
