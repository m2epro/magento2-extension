<?php

namespace Ess\M2ePro\Setup\Update\y19_m11;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y19_m11\WalmartProductIdOverride
 */
class WalmartProductIdOverride extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return [
            'module_config',
        ];
    }

    public function execute()
    {
        $this->getConfigModifier('module')->insert(
            '/walmart/configuration/',
            'product_id_override_mode',
            '0'
        );
    }

    //########################################
}
