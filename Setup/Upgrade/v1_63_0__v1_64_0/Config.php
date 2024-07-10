<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_63_0__v1_64_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y24_m07/AddProductIdentifiersSettingsForAmazonListing',
        ];
    }
}
