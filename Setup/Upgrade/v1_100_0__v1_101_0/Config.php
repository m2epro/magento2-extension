<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Upgrade\v1_100_0__v1_101_0;

class Config extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y26_m10/RemoveOldHangingUploads',
        ];
    }
}
