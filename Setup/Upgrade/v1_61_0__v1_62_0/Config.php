<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_61_0__v1_62_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y24_m06/AddAmazonShippingPalletDelivery',
            '@y24_m06/AddPriceLastUpdateDateColumnToEbayListingProductTable',
        ];
    }
}
