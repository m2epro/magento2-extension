<?php

namespace Ess\M2ePro\Setup\Update\y23_m11;

use Magento\Framework\DB\Ddl\Table;

class AddAmazonOriginalOrderIdColumn extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $modifier = $this->getTableModifier('amazon_order');
        $modifier
            ->addColumn(
            'replaced_amazon_order_id',
            'varchar(255)',
            'NULL',
            null,
            true,
            false
            )
            ->commit();
    }
}
