<?php

namespace Ess\M2ePro\Setup\Update\y23_m12;

class AddAmazonInventoryFbaFieldsInAmazonAccountTable extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    public function execute()
    {
        $this->getTableModifier('amazon_account')
             ->addColumn(
                 'fba_inventory_mode',
                 'SMALLINT UNSIGNED NOT NULL',
                 0,
                 'info',
                 false,
                 false
             )
             ->addColumn(
                 'fba_inventory_source',
                 'VARCHAR(255)',
                 null,
                 'fba_inventory_mode',
                 false,
                 false
             )
             ->commit();
    }
}
