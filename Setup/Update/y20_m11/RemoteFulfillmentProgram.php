<?php

namespace Ess\M2ePro\Setup\Update\y20_m11;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m11\RemoteFulfillmentProgram
 */
class RemoteFulfillmentProgram extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('amazon_account')->addColumn(
            'remote_fulfillment_program_mode',
            'TINYINT(2) UNSIGNED NOT NULL',
            '0',
            'create_magento_shipment'
        );
    }

    //########################################
}
