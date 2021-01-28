<?php

namespace Ess\M2ePro\Setup\Update\y20_m11;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class Ess\M2ePro\Setup\Update\y20_m11\DisableVCSOnNL
 */
class DisableVCSOnNL extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getConnection()->update(
            $this->getFullTableName('amazon_marketplace'),
            [
                'is_vat_calculation_service_available' => 0
            ],
            [
                'marketplace_id = ?' => 39
            ]
        );
    }

    //########################################
}
