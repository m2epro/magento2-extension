<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_6_0__v1_7_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    //########################################

    public function getFeaturesList()
    {
        return [
            '@y20_m03/EbayCategories',
            '@y20_m04/SaveEbayCategory',

            '@y20_m05/Logs',
            '@y20_m05/RemoveMagentoQtyRules',
            '@y20_m05/RemovePriceDeviationRules',
            '@y20_m05/PrimaryConfigs',
            '@y20_m05/CacheConfigs',
            '@y20_m05/ModuleConfigs',
            '@y20_m05/ConvertIntoInnoDB',

            '@y20_m06/WalmartConsumerId',
            '@y20_m06/RemoveCronDomains',
            '@y20_m06/GeneralConfig',
            '@y20_m06/EbayConfig',
            '@y20_m06/AmazonConfig',

            '@y20_m07/EbayTemplateStoreCategory',
        ];
    }

    //########################################
}
