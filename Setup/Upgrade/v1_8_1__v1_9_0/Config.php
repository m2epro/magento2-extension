<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_8_1__v1_9_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    //########################################

    public function getFeaturesList()
    {
        return [
            '@y20_m07/WalmartKeywordsFields',

            '@y20_m08/AmazonSkipTax',
            '@y20_m08/AmazonTR',
            '@y20_m08/EbayManagedPayments',
            '@y20_m08/EbayShippingSurcharge',
            '@y20_m08/GroupedProduct',

            '@y20_m09/AmazonSE',
            '@y20_m09/InventorySynchronization',
            '@y20_m09/SellOnAnotherSite',
            '@y20_m10/SellOnAnotherSite',
        ];
    }

    //########################################
}
