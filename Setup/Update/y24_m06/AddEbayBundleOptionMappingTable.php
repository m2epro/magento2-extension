<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m06;

use Ess\M2ePro\Model\ResourceModel\Ebay\Bundle\Options\Mapping as MappingResource;

class AddEbayBundleOptionMappingTable extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $this->createEbayBundleOptionMappingTable();
    }

    private function createEbayBundleOptionMappingTable()
    {
        $ebayBundleOptionsMappingTableName = $this->getFullTableName(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_BUNDLE_OPTIONS_MAPPING
        );

        $ebayBundleOptionsMappingTable = $this
            ->getConnection()
            ->newTable($ebayBundleOptionsMappingTableName);

        $ebayBundleOptionsMappingTable
            ->addColumn(
                MappingResource::COLUMN_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'primary' => true,
                    'nullable' => false,
                    'auto_increment' => true,
                ]
            )
            ->addColumn(
                MappingResource::COLUMN_OPTION_TITLE,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                MappingResource::COLUMN_ATTRIBUTE_CODE,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addIndex('option_title', MappingResource::COLUMN_OPTION_TITLE)
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($ebayBundleOptionsMappingTable);
    }

}
