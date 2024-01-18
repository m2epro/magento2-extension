<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_55_1__v1_56_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y23_m12/AddAmazonInventoryFbaFieldsInAmazonAccountTable',
        ];
    }
}
