<?php

namespace Ess\M2ePro\Setup\Update\y23_m10;

use Magento\Framework\DB\Ddl\Table;

class CreateEbayCategorySpecificValidationResultTable extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $tableName = $this->getFullTableName('ebay_category_specific_validation_result');
        if ($this->installer->tableExists($tableName)) {
            return;
        }

        $ebayCategorySpecificValidationResult = $this
            ->getConnection()
            ->newTable($this->getFullTableName('ebay_category_specific_validation_result'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'primary' => true,
                    'nullable' => false,
                    'auto_increment' => true
                ],
                'ID'
            )
            ->addColumn(
                'listing_product_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false
                ],
                'Listing Product ID'
            )
            ->addColumn(
                'status',
                Table::TYPE_SMALLINT,
                null,
                [
                    'nullable' => false,
                    'default' => 0
                ],
                'Status'
            )
            ->addColumn(
                'error_messages',
                Table::TYPE_TEXT,
                null,
                [],
                'Error Messages'
            )
            ->addColumn(
                'create_date',
                Table::TYPE_DATETIME,
                null,
                [],
                'Create Date'
            )
            ->addColumn(
                'update_date',
                Table::TYPE_DATETIME,
                null,
                [],
                'Update Date'
            )
            ->setComment('eBay categories specific validation result')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');

        $this
            ->getConnection()
            ->createTable($ebayCategorySpecificValidationResult);
    }
}
