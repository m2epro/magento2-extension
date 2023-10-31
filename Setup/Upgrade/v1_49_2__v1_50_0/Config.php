<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_49_2__v1_50_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y23_m10/ImproveAmazonOrderPrefixes',
            '@y23_m10/RenameSoldByAmazonSetting',
            '@y23_m10/ReAddIsSoldByAmazonColumnToAmazonOrder',
            '@y23_m10/CreateEbayCategorySpecificValidationResultTable',
        ];
    }
}
