<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

/**
 * Class Config
 * @package Ess\M2ePro\Setup\Update
 */
class Config extends AbstractConfig
{
    //########################################

    public function getFeaturesList()
    {
        return [
            'y19_m01' => [
                'NewUpgradesEngine',
                'AmazonOrdersUpdateDetails',
                'NewCronRunner',
                'ChangeDevelopVersion',
            ],
            'y19_m04' => [
                'Walmart',
                'Maintenance',
                'WalmartAuthenticationForCA',
                'WalmartOptionImagesURL',
                'WalmartOrdersReceiveOn',
                'MigrationFromMagento1'
            ],
            'y19_m05' => [
                'WalmartAddMissingColumn'
            ],
            'y19_m07' => [
                'WalmartSynchAdvancedConditions'
            ]
        ];
    }

    //########################################

    public function getMultiRunFeaturesList()
    {
        return [];
    }

    //########################################
}
