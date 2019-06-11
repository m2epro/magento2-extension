<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_3_3__v1_3_4;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class TryToCreateMagentoOrderAgainIfInitialCreationWasFailed extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return [];
    }

    public function execute()
    {
        // merged with CatchMagentoOrdersCreationFailure

        $this->getConfigModifier('synchronization')->insert(
            '/ebay/orders/create_failed/', 'mode', 1, '0 - disable, \r\n1 - enable'
        );
        $this->getConfigModifier('synchronization')->insert(
            '/ebay/orders/create_failed/', 'interval', 300, 'in seconds'
        );
        $this->getConfigModifier('synchronization')->insert(
            '/ebay/orders/create_failed/', 'last_time', NULL, 'Last check time'
        );

        $this->getConfigModifier('synchronization')->insert(
            '/amazon/orders/create_failed/', 'mode', 1, '0 - disable, \r\n1 - enable'
        );
        $this->getConfigModifier('synchronization')->insert(
            '/amazon/orders/create_failed/', 'interval', 300, 'in seconds'
        );
        $this->getConfigModifier('synchronization')->insert(
            '/amazon/orders/create_failed/', 'last_time', NULL, 'Last check time'
        );
    }

    //########################################
}