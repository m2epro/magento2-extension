<?php

namespace Ess\M2ePro\Setup\Update\y23_m12;

use Magento\Framework\DB\Ddl\Table;

class AddCreateShipmentFbaOrdersColumn extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $modifier = $this->getTableModifier('amazon_account');
        $modifier->addColumn(
            'create_magento_shipment_fba_orders',
            'SMALLINT UNSIGNED NOT NULL',
            1,
            'create_magento_shipment',
            false,
            false
        );

        $modifier->commit();

        $this->setCreateShipmentFbaOrdersValue();
    }

    private function setCreateShipmentFbaOrdersValue()
    {
        $this->getConnection()->update(
            $this->getFullTableName('amazon_account'),
            ['create_magento_shipment_fba_orders' => 0],
            ['create_magento_shipment = ?' => 0]
        );
    }
}
