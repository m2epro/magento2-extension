<?php

namespace Ess\M2ePro\Setup\Update\y22_m09;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class AddWalmartCustomerOrderId extends AbstractFeature
{
    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    public function execute(): void
    {
        $this->getTableModifier('walmart_order')
             ->addColumn(
                 'customer_order_id',
                 'VARCHAR(255) NOT NULL',
                 '',
                 'walmart_order_id',
                 true,
                 false
             )
             ->commit();
    }
}
