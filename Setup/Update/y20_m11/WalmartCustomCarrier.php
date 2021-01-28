<?php

namespace Ess\M2ePro\Setup\Update\y20_m11;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m11\WalmartCustomCarrier
 */
class WalmartCustomCarrier extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('walmart_account')->addColumn(
            'other_carriers',
            'TEXT',
            'NULL',
            'create_magento_shipment'
        );
    }

    //########################################
}
