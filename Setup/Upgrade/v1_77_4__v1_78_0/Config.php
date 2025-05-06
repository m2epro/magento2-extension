<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Upgrade\v1_77_4__v1_78_0;

class Config extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y25_m04/AddCustomValueToEbayVideoAndDocuments',
            '@y25_m04/AddIntoWalmartProductIsNotMappedToExistingChannelItemColumn',
        ];
    }
}
