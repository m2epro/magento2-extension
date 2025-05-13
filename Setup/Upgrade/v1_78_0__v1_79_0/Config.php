<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Upgrade\v1_78_0__v1_79_0;

class Config extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y25_m04/AddCustomizationDetailsToAmazonOrderItem',
            '@y25_m04/AddVariationsToEbayUnmanagedProduct',
        ];
    }
}
