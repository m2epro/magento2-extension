<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_3_4__v1_4_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    //########################################

    public function getFeaturesList()
    {
        return [
            '@y19_m01/AmazonOrdersUpdateDetails',
            '@y19_m01/NewCronRunner',
            '@y19_m04/Walmart',
            '@y19_m04/Maintenance',
            '@y19_m04/WalmartAuthenticationForCA',
            '@y19_m04/WalmartOptionImagesURL',
            '@y19_m04/WalmartOrdersReceiveOn'
        ];
    }

    //########################################
}