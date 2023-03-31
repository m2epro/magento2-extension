<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_36_0__v1_37_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y23_m01/AmazonRemoveUnnecessaryData',
            '@y23_m03/RemoveLicenseStatus',
            '@y23_m03/WalmartProductIdentifiers',
            '@y23_m03/RenameClientsToAccounts',
        ];
    }
}
