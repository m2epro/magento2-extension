<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y22_m08;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class FixNullableGroupsInConfigs extends AbstractFeature
{
    /**
     * @return void
     */
    public function execute()
    {
        $this->getConfigModifier('module')->updateGroup('/', [
            "`key` IN ('is_disabled', 'environment')",
            "`group` IS NULL",
        ]);
    }
}
