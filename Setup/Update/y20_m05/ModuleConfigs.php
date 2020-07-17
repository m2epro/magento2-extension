<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y20_m05;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m05\ModuleConfigs
 */
class ModuleConfigs extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->renameTable('module_config', 'config');
    }

    //########################################
}
