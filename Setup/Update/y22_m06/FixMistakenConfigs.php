<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y22_m06;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class FixMistakenConfigs extends AbstractFeature
{
    public function execute()
    {
       $configKeys = ['is_disabled', 'environment'];
       foreach ($configKeys as $configKey) {
           $this->fixMistakenConfig($configKey);
       }
    }

    private function fixMistakenConfig($configKey)
    {
        $entity = $this->getConfigModifier('module')->getEntity('//', $configKey);

        if ($entity->getValue() === null) {
            return;
        }

        $updated = $this->getConfigModifier('module')->updateValue($entity->getValue(), [
            '`group` = ?' => '/',
            '`key` = ?'   => $configKey
        ]);

        if ($updated > 0) {
            $entity->delete();
        }
    }
}
