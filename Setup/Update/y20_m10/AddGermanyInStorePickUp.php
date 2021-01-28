<?php

namespace Ess\M2ePro\Setup\Update\y20_m10;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m10\AddGermanyInStorePickUp
 */
class AddGermanyInStorePickUp extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getConnection()->update(
            $this->getFullTableName('ebay_marketplace'),
            [
                'is_in_store_pickup' => 1,
            ],
            [
                'marketplace_id IN (?)' => [2, 8]
            ]
        );
    }

    //########################################
}
