<?php

namespace Ess\M2ePro\Setup\Update\y20_m10;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m10\AddITCAShippingRateTable
 */
class AddITCAShippingRateTable extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getConnection()->update(
            $this->getFullTableName('ebay_marketplace'),
            [
                'is_local_shipping_rate_table'         => 1,
                'is_international_shipping_rate_table' => 1
            ],
            [
                'marketplace_id IN(?)' => [2, 10, 19]
            ]
        );
    }

    //########################################
}
