<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_59_7__v1_60_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y24_m05/AddEbayPromotion',
        ];
    }
}
