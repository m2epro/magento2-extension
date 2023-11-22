<?php

namespace Ess\M2ePro\Setup\Update\y23_m11;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class AddWalmartOrdersWfsLastSynchronization extends AbstractFeature
{
    public function execute()
    {
        $this->getTableModifier('walmart_account')->addColumn(
            'orders_wfs_last_synchronization',
            'DATETIME',
            'NULL',
            'inventory_last_synchronization'
        );
    }
}
