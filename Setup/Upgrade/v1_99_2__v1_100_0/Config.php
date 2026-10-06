<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Upgrade\v1_99_2__v1_100_0;

class Config extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y26_m09/AmazonAutoActionAdvancedFilterMode',
            '@y26_m09/AmazonRetrieveBillingAddress',
            '@y26_m09/EbayRetrieveBillingAddress',
            '@y26_m10/AddIsEasyShipSignToAmazonOrder',
        ];
    }
}
