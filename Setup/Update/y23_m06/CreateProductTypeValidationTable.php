<?php

namespace Ess\M2ePro\Setup\Update\y23_m06;

use Magento\Framework\DB\Ddl\Table;

class CreateProductTypeValidationTable extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $tableName = $this->getFullTableName('amazon_product_type_validation');

        if ($this->installer->tableExists($tableName)) {
            return;
        }

        $amazonProductTypeValidationTable = $this
            ->getConnection()
            ->newTable($this->getFullTableName('amazon_product_type_validation'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'listing_product_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'status',
                Table::TYPE_INTEGER,
                1,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'error_messages',
                Table::TYPE_TEXT,
                null,
                ['default' => '', 'nullable' => false]
            )
            ->addColumn(
                'create_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                'update_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addIndex('listing_product_id', 'listing_product_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');

        $this->getConnection()->createTable($amazonProductTypeValidationTable);
    }
}
