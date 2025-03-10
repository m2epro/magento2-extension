<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Upgrade\v1_75_3__v1_76_0;

class Config extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y25_m02/DisableB2BForSomeAmazonMarketplaces',
            '@y25_m02/AddConditionDescriptorIntoEbayDescriptionTemplate',
        ];
    }
}
