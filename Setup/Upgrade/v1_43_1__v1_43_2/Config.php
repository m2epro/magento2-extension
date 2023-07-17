<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_43_1__v1_43_2;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y23_m07/RemoveScaleFromWatermarkSetting',
            '@y23_m07/ChangeDocumentationUrl',
        ];
    }
}
