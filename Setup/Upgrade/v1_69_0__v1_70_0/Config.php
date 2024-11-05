<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Upgrade\v1_69_0__v1_70_0;

class Config extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y24_m10/AddAttributeMapping',
            '@y24_m10/AddEbayComplianceDocuments',
            '@y24_m11/AddDeliveryDateFromColumnToAmazonOrder',
        ];
    }
}
