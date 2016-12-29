<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_2_0__v1_3_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class AmazonOrdersFulfillmentDetails extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['synchronization_config'];
    }

    public function execute()
    {
        $this->getConfigModifier('synchronization')->insert(
            '/amazon/orders/receive_details/', 'mode', 0, '0 - disable, \r\n1 - enable'
        );
        $this->getConfigModifier('synchronization')->insert(
            '/amazon/orders/receive_details/', 'interval', 3600, 'in seconds'
        );
        $this->getConfigModifier('synchronization')->insert(
            '/amazon/orders/receive_details/', 'last_time', NULL, 'Last check time'
        );
    }

    //########################################
}