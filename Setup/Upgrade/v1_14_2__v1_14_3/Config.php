<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_14_2__v1_14_3;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    //########################################

    public function getFeaturesList()
    {
        return [
            '@y21_m08/FixedStuckedManualPriceRevise',
            '@y21_m10/UpdateWatermarkImage'
        ];
    }

    //########################################
}
