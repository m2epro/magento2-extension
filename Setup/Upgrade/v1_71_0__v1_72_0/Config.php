<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Upgrade\v1_71_0__v1_72_0;

class Config extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y24_m11/AddCustomValueToAttributeMapping',
        ];
    }
}
