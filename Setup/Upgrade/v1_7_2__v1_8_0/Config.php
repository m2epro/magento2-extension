<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_7_2__v1_8_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    //########################################

    public function getFeaturesList()
    {
        return [
            '@y20_m06/RefundShippingCost',

            '@y20_m07/HashLongtextFields',
            '@y20_m07/WalmartOrderItemQty'
        ];
    }

    //########################################
}
