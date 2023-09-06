<?php

namespace Ess\M2ePro\Setup\Update\y23_m08;

use Magento\Framework\DB\Ddl\Table;

class CreateAmazonShippingMapTable extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $tableName = $this->getFullTableName('amazon_shipping_map');

        if ($this->installer->tableExists($tableName)) {
            return;
        }

        $shippingMethodsTable = $this
            ->getConnection()
            ->newTable($this->getFullTableName('amazon_shipping_map'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'marketplace_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Marketplace ID'
            )
            ->addColumn(
                'location',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Location (Domestic/International)'
            )
            ->addColumn(
                'amazon_code',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Amazon shipping code'
            )
            ->addColumn(
                'magento_code',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Magento shipping code'
            )
                ->setComment('Shipping Methods Table')
                ->setOption('type', 'INNODB')
                ->setOption('charset', 'utf8')
                ->setOption('collate', 'utf8_general_ci')
                ->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($shippingMethodsTable);
    }
}

