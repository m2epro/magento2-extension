<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y23_m06;

class RemoveBuildLastVersionFromRegistry extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $sql = sprintf(
            "DELETE FROM `%s` WHERE `key` = '/installation/build_last_version/';",
            $this->getFullTableName('registry')
        );

        $this->installer->run($sql);
    }
}
