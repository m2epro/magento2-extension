<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_66_2__v1_67_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y24_m08/UpdateAmazonDictionaryProductType',
            '@y24_m09/RemoveIsNewAsinAvailableFromAmazonMarketplace',
        ];
    }
}
