<?php

namespace Ess\M2ePro\Setup\Update\y23_m08;

use Magento\Framework\DB\Ddl\Table;

class RemoveCashOnDelivery extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this
            ->getTableModifier('ebay_marketplace')
            ->dropColumn('is_cash_on_delivery');
        $this
            ->getTableModifier('ebay_template_shipping')
            ->dropColumn('cash_on_delivery_cost');
    }
}
