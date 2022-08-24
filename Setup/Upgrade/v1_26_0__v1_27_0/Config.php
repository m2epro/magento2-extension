<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_26_0__v1_27_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList()
    {
        return [
            '@y22_m07/ClearPolicyLinkingToDeletedAccount',
            '@y22_m07/FixFieldBuyerCancellationRequested',
            '@y22_m08/AddAmazonMarketplacesBrSgInAe',
            '@y22_m08/FixDevKeyForJapanAmazonMarketplace',
        ];
    }
}
