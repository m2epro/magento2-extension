<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_25_1__v1_26_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList()
    {
        return [
            '@y22_m07/AddEpidsForItaly',
            '@y22_m07/AmazonAccountRemoveToken',
            '@y22_m07/AmazonMarketplaceRemoveAutomaticTokenColumn',
            '@y22_m07/MoveEbayProductIdentifiers',
            '@y22_m07/FixRemovedPolicyInScheduledActions',
        ];
    }
}
