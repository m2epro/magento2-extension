<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y26_m09;

use Ess\M2ePro\Helper\Module\Database\Tables;
use Magento\Framework\DB\Ddl\Table;

class AmazonAutoActionAdvancedFilterMode extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->createTableAmazonListingAutoAdvancedFilterGroup();
    }

    public function createTableAmazonListingAutoAdvancedFilterGroup()
    {
        $amazonListingAutoAdvancedFilterTableName = $this->getFullTableName(
            Tables::TABLE_AMAZON_LISTING_AUTO_ADVANCED_FILTER
        );
        if ($this->getConnection()->isTableExists($amazonListingAutoAdvancedFilterTableName)) {
            return;
        }

        $amazonListingAutoAdvancedFilterTable = $this
            ->getConnection()
            ->newTable($amazonListingAutoAdvancedFilterTableName)
            ->addColumn(
                \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Auto\Advanced\Filter::COLUMN_LISTING_AUTO_ADVANCED_FILTER_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Auto\Advanced\Filter::COLUMN_ADDING_PRODUCT_TYPE_TEMPLATE_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');

        $this->getConnection()->createTable($amazonListingAutoAdvancedFilterTable);
    }
}
