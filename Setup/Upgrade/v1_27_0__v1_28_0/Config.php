<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_27_0__v1_28_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList()
    {
        return [
            '@y22_m08/AddAfnProductActualQty',
            '@y22_m08/AddIsReplacementColumnToAmazonOrder',
            '@y22_m08/ClearPartListingAdditionalData',
            '@y22_m08/FixNullableGroupsInConfigs',
            '@y22_m08/MoveAmazonProductIdentifiers',
            '@y22_m09/AddAmazonMarketplaceBelgium',
            '@y22_m09/RemoveHitCounterFromEbayDescriptionPolicy',
        ];
    }
}
