<?php

namespace Ess\M2ePro\Setup\Upgrade\v1_43_0__v1_43_1;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y23_m06/AddEbayBlockingErrorSetting',
            '@y23_m07/ChangeProductTypeValidationTableErrorMessageField',
            '@y23_m07/DropTemplateDescriptionIdIndex',
        ];
    }
}
