<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_47_1__v1_48_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y23_m09/AddAmazonProductTypeAttributeMappingTable',
            '@y23_m09/AddPriceRoundingToEbayAmazonWalmartSellingTemplate',
            '@y23_m10/EnableAmazonShippingServiceForSomeMarketplaces',
        ];
    }
}
