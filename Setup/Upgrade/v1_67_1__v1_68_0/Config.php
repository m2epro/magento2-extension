<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_67_1__v1_68_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y24_m07/NewListingWizardTables',
            '@y24_m09/AddInternationalShippingRateTablesForAustralia',
            '@y24_m09/RemoveUnusedAmazonTables',
            '@y24_m09/AddWalmartProductTypes',
        ];
    }
}
