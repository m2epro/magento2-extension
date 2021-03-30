<?php

namespace Ess\M2ePro\Setup\Update\y20_m11;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m11\SynchronizeInventoryConfigs
 */
class SynchronizeInventoryConfigs extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getConfigModifier()->insert(
            '/cron/task/amazon/listing/synchronize_inventory/', 'interval_per_account', '86400'
        );

        $this->getConfigModifier()->insert(
            '/cron/task/walmart/listing/synchronize_inventory/', 'interval_per_account', '86400'
        );
    }

    //########################################
}
