<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_58_1__v1_58_2;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y24_m02/ChangeTypeCustomValueFieldOnCategorySpecificTable',
        ];
    }
}
