<?php

namespace Ess\M2ePro\Setup\Update\y22_m06;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class WalmartOrderItemBuyerCancellation extends AbstractFeature
{
    public function execute()
    {
        $this->getTableModifier('walmart_order_item')
             ->addColumn(
                 'buyer_cancellation_requested',
                 'SMALLINT UNSIGNED NOT NULL',
                 0,
                 'qty_purchased',
                 false,
                 false
             )
             ->commit();
    }
}
