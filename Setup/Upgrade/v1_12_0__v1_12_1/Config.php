<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_12_0__v1_12_1;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    //########################################

    public function getFeaturesList()
    {
        return [
            '@y21_m02/EbayManagedPayments',

            '@y21_m03/EbayMotorsAddManagedPayments',
            '@y21_m03/IncludeeBayProductDetails'
        ];
    }

    //########################################
}
