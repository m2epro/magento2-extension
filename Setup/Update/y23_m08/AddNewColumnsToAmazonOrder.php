<?php

namespace Ess\M2ePro\Setup\Update\y23_m08;

use Magento\Framework\DB\Ddl\Table;

class AddNewColumnsToAmazonOrder extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    public function execute()
    {
        $this->getTableModifier('amazon_order')
             ->addColumn(
                 'shipping_category',
                 'VARCHAR(255)',
                 0,
                 'shipping_service',
                 false,
                 false
             )
            ->addColumn(
                'shipping_mapping',
                'VARCHAR(255)',
                0,
                'shipping_category',
                false,
                false
            )
             ->commit();
    }
}
