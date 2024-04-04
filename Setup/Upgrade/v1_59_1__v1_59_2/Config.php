<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_59_1__v1_59_2;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y24_m02/CombineInactiveProductStatuses',
            '@y24_m03/CreateAndFillAmazonAccountMerchantSettingTable',
        ];
    }
}
