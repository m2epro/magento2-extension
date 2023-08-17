<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_44_1__v1_44_2;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y23_m08/AddIsGetDeliveryPreferencesColumnToAmazonOrderTable',
            '@y23_m08/RemoveCashOnDelivery',
            '@y23_m08/RemoveAmazonDescriptionPolicyRelatedData',
        ];
    }
}
