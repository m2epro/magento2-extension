<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m01;

use Magento\Framework\DB\Ddl\Table;

class AddListingProductAdvancedFilterTable extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $listingProductAdvancedFilterTable = $this->getConnection()->newTable(
            $this->getFullTableName('listing_product_advanced_filter')
        );
        $listingProductAdvancedFilterTable->addColumn(
            'id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
        );
        $listingProductAdvancedFilterTable->addColumn(
            'model_nick',
            Table::TYPE_TEXT,
            100,
            ['nullable' => false]
        );
        $listingProductAdvancedFilterTable->addColumn(
            'title',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false]
        );
        $listingProductAdvancedFilterTable->addColumn(
            'conditionals',
            Table::TYPE_TEXT,
            \Ess\M2ePro\Model\Setup\Installer::LONG_COLUMN_SIZE,
            ['nullable' => false]
        );
        $listingProductAdvancedFilterTable->addColumn(
            'update_date',
            Table::TYPE_DATETIME,
            null,
            ['nullable' => false]
        );
        $listingProductAdvancedFilterTable->addColumn(
            'create_date',
            Table::TYPE_DATETIME,
            null,
            ['nullable' => false]
        );

        $listingProductAdvancedFilterTable->setOption('type', 'INNODB');
        $listingProductAdvancedFilterTable->setOption('charset', 'utf8');
        $listingProductAdvancedFilterTable->setOption('collate', 'utf8_general_ci');
        $listingProductAdvancedFilterTable->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($listingProductAdvancedFilterTable);
    }
}
