<?php

namespace Ess\M2ePro\Setup\Update\y23_m11;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class AddWalmartIsWFS extends AbstractFeature
{
    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    public function execute(): void
    {
        $this->getTableModifier('walmart_order')
             ->addColumn(
                 'is_wfs',
                 'SMALLINT UNSIGNED NOT NULL',
                 0,
                 'purchase_create_date',
                 true,
                 false
             )
             ->commit();
    }
}
