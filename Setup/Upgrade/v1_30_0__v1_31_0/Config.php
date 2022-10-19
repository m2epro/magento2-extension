<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_30_0__v1_31_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList()
    {
        return [
            '@y22_m09/AddIsCriticalErrorReceivedFlag',
            '@y22_m10/RemoveEpidsForAustralia',
            '@y22_m10/UpdateAmazonMarketplace',
        ];
    }
}
