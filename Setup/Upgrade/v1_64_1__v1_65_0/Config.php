<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_64_1__v1_65_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y24_m06/AddEbayBundleOptionMappingTable',
            '@y24_m07/EnableVatCalculationServiceForPolandAndSweden',
            '@y24_m07/AddEbayVideo',
        ];
    }
}
