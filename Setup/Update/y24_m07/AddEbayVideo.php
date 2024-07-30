<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m07;

class AddEbayVideo extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->createEbayVideoTable();
        $this->addColumnsToEbayListingProductTable();
        $this->addColumnToEbayTemplateDescriptionTable();
    }

    private function createEbayVideoTable(): void
    {
        $ebayVideoTableName = $this->getFullTableName(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_VIDEO);
        $ebayVideoTable = $this->getConnection()->newTable($ebayVideoTableName);
        $ebayVideoTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Video::COLUMN_ID,
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
        );
        $ebayVideoTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Video::COLUMN_ACCOUNT_ID,
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false]
        );
        $ebayVideoTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Video::COLUMN_URL,
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => false]
        );
        $ebayVideoTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Video::COLUMN_STATUS,
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false]
        );
        $ebayVideoTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Video::COLUMN_VIDEO_ID,
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['default' => null]
        );
        $ebayVideoTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Video::COLUMN_ERROR,
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['default' => null]
        );
        $ebayVideoTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Video::COLUMN_UPDATE_DATE,
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            ['default' => null]
        );
        $ebayVideoTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Video::COLUMN_CREATE_DATE,
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            ['default' => null]
        );

        $ebayVideoTable->setOption('type', 'INNODB');
        $ebayVideoTable->setOption('charset', 'utf8');
        $ebayVideoTable->setOption('collate', 'utf8_general_ci');
        $ebayVideoTable->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($ebayVideoTable);
    }

    private function addColumnsToEbayListingProductTable(): void
    {
        $this->getTableModifier(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_LISTING_PRODUCT)
             ->addColumn(
                 \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_VIDEO_URL,
                 'TEXT',
                 'NULL',
                 null,
                 false,
                 false
             )
             ->addColumn(
                 \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_VIDEO_ID,
                 'VARCHAR(255)',
                 'NULL',
                 null,
                 false,
                 false
             )
            ->addColumn(
                \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_ONLINE_VIDEO_ID,
                'VARCHAR(255)',
                'NULL',
                null,
                false,
                false
            )
             ->commit();
    }

    private function addColumnToEbayTemplateDescriptionTable(): void
    {
        $this->getTableModifier(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_TEMPLATE_DESCRIPTION)
             ->addColumn(
                 \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Description::COLUMN_VIDEO_MODE,
                 'SMALLINT UNSIGNED NOT NULL',
                 0,
                 \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Description::COLUMN_USE_SUPERSIZE_IMAGES,
                 false,
                 false
             )
             ->addColumn(
                 \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Description::COLUMN_VIDEO_ATTRIBUTE,
                 'VARCHAR(255)',
                 'NULL',
                 \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Description::COLUMN_VIDEO_MODE,
                 false,
                 false
             )
            ->commit();
    }
}
