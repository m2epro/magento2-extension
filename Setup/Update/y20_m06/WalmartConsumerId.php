<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y20_m06;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m06\WalmartConsumerId
 */
class WalmartConsumerId extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('walmart_account')
            ->changeColumn('consumer_id', 'VARCHAR(255)', 'NULL', 'marketplace_id')
            ->renameColumn('old_private_key', 'private_key');
    }

    //########################################
}
