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
class EnvironmentToConfigs extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $currentEnv = (string)getenv('M2EPRO_ENV') ? (string)getenv('M2EPRO_ENV') : 'production';
        $this->getConfigModifier('module')->insert(null, 'environment', $currentEnv);
    }

    //########################################
}
