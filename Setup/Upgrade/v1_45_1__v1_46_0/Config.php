<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_45_1__v1_46_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y23_m08/AddFinalFeesColumnToAmazonOrderTable',
            '@y23_m09/AddOnlineBestOfferForEbayProduct',
            '@y23_m09/RefactorAmazonOrderColumns',
        ];
    }
}
