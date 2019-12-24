<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_4_2__v1_4_3;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    //########################################

    public function getFeaturesList()
    {
        return [
            '@y19_m11/ProductsStatisticsImprovements',
            '@y19_m11/WalmartProductIdOverride'
        ];
    }

    //########################################
}