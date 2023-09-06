<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_44_2__v1_45_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y23_m08/CreateAmazonShippingMapTable',
            '@y23_m08/AddNewColumnsToAmazonOrder',
            '@y23_m08/AddAmazonSellingFormatListPrice',
        ];
    }
}
