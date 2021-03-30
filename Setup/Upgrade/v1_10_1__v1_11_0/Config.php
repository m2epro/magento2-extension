<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_10_1__v1_11_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    //########################################

    public function getFeaturesList()
    {
        return [
            '@y20_m10/ChangeSingleItemOption',

            '@y20_m11/SynchronizeInventoryConfigs',
            '@y20_m11/EbayOrderCancelRefund',
            '@y20_m11/AmazonDuplicatedMarketplaceFeature',

            '@y21_m01/AmazonJP',
            '@y21_m01/EbayRemoveClickAndCollect',
            '@y21_m01/WalmartCancelRefundOption',

            '@y21_m02/MoveAUtoAsiaPacific'
        ];
    }

    //########################################
}
