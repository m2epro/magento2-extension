<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y19_m10;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y19_m10\ConfigsNoticeRemoved
 */
class ConfigsNoticeRemoved extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('module_config')->dropColumn('notice');
        $this->getTableModifier('primary_config')->dropColumn('notice');
        $this->getTableModifier('cache_config')->dropColumn('notice');
        $this->getTableModifier('synchronization_config')->dropColumn('notice');
    }

    //########################################
}
