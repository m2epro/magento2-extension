<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_33_1__v1_34_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    /**
     * @return string[]
     */
    public function getFeaturesList(): array
    {
        return [
            '@y23_m01/UpdateConfigSupportUrl',
        ];
    }
}
