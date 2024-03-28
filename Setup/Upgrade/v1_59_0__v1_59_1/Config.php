<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_59_0__v1_59_1;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y24_m02/DisableAmazonMarketplaceWithoutAccounts',
            '@y24_m03/AddOnlineRegularMapPriceToAmazonListingProduct',
            '@y24_m03/AddKtypesResolveAttemptColumn',
        ];
    }
}
