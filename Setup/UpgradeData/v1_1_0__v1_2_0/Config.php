<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_1_0__v1_2_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    //########################################

    public function getFeaturesList()
    {
        return [
            'InStorePickupGlobalKey',
            'PublicVersionsChecker',
            'LogsImprovements',
            'MoreLogsImprovements',
            'ProductCustomTypes',
            'ClearListingOtherLogsFromRemovedActions',
            'EnableRepricingInConfig',
            'PartialReviseBySpecifics',
            'CharityMigration',
            'IsAfnChannelZero',
            'PartialReviseByShippingServices',
            'AdvancedConditionsForSynchronization'
        ];
    }

    //########################################
}