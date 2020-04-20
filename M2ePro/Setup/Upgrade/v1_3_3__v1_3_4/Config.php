<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_3_3__v1_3_4;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    //########################################

    public function getFeaturesList()
    {
        return [
            'CatchMagentoOrdersCreationFailure',
            'MpnValueCanBeChanged',
            'NewLastExecutedSlowTaskConfigValue',
            'MsrpRrp',
            'TryToCreateMagentoOrderAgainIfInitialCreationWasFailed',
            'ShouldBeUrlsSecureFromConfig',
            'EbayVariationsWithASpace',
            'RepricingSynchronizationImprovements',
            'RemoveMigrationFromM1',
            'SaveOnlineIdentifiers'
        ];
    }

    //########################################
}