<?php

namespace Ess\M2ePro\Setup\Update\dev;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\dev\DisableInStorePickUpCanadaFr
 */
class DisableInStorePickUpCanadaFr extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getConnection()->update(
            $this->getFullTableName('ebay_marketplace'),
            [
                'is_in_store_pickup' => 0,
            ],
            [
                'marketplace_id IN (?)' => [19]
            ]
        );
    }

    //########################################
}
