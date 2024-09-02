<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_66_1__v1_66_2;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y24_m08/RemoveBlockingErrorsFromConfigTable',
        ];
    }
}
