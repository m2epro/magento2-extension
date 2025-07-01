<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Upgrade\v1_80_2__v1_81_0;

class Config extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y25_m05/AddAmazonInventoryFbaFieldsInAmazonAccountTable',
            '@y25_m05/EbayPromotedListingCampaigns',
            '@y25_m06/FixValueOfProductTypeIdInWalmartListingProduct',
        ];
    }
}
