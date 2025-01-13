<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Upgrade\v1_74_0__v1_74_1;

class Config extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y24_m12/ChangeColumnValueSizeInAttributeMappingTable',
        ];
    }
}
