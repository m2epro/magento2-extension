<?php

namespace Ess\M2ePro\Setup\Update\y22_m08;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class AddAfnProductActualQty extends AbstractFeature
{
    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    public function execute()
    {
        $this->getTableModifier('amazon_listing_product')
             ->addColumn(
                 'online_afn_qty',
                 'INT UNSIGNED',
                 null,
                 'online_qty',
                 false,
                 false
             )
             ->commit();

        $this->getTableModifier('amazon_listing_other')
             ->addColumn(
                 'online_afn_qty',
                 'INT UNSIGNED',
                 null,
                 'online_qty',
                 false,
                 false
             )
             ->commit();
    }
}
