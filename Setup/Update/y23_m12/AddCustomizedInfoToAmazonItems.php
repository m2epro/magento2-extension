<?php

namespace Ess\M2ePro\Setup\Update\y23_m12;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;
use Magento\Framework\DB\Ddl\Table;

class AddCustomizedInfoToAmazonItems extends AbstractFeature
{
    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    public function execute(): void
    {
        $this->getTableModifier('amazon_order_item')
             ->addColumn(
                 'buyer_customized_info',
                 Table::TYPE_TEXT,
                 null,
                 null,
                 false,
                 false
             )
             ->commit();
    }
}
