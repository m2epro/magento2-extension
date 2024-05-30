<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m05;

class AddEbayPromotion extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $this->createEbayPromotionTable();
        $this->createEbayListingProductPromotionTable();
    }

    private function createEbayPromotionTable()
    {
        $ebayPromotionTableName = $this->getFullTableName(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_PROMOTION
        );
        $ebayPromotionTable = $this->getConnection()->newTable($ebayPromotionTableName);
        $ebayPromotionTable->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
        );
        $ebayPromotionTable->addColumn(
            'account_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false]
        );
        $ebayPromotionTable->addColumn(
            'marketplace_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false]
        );
        $ebayPromotionTable->addColumn(
            'promotion_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false]
        );
        $ebayPromotionTable->addColumn(
            'name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false]
        );
        $ebayPromotionTable->addColumn(
            'type',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false]
        );
        $ebayPromotionTable->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false]
        );
        $ebayPromotionTable->addColumn(
            'priority',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false]
        );
        $ebayPromotionTable->addColumn(
            'start_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            ['default' => null]
        );
        $ebayPromotionTable->addColumn(
            'end_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            ['default' => null]
        );
        $ebayPromotionTable->addColumn(
            'update_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            ['default' => null]
        );
        $ebayPromotionTable->addColumn(
            'create_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            ['default' => null]
        );

        $ebayPromotionTable->setOption('type', 'INNODB');
        $ebayPromotionTable->setOption('charset', 'utf8');
        $ebayPromotionTable->setOption('collate', 'utf8_general_ci');
        $ebayPromotionTable->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($ebayPromotionTable);
    }

    private function createEbayListingProductPromotionTable()
    {
        $ebayListingProductPromotionTableName = $this->getFullTableName(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_LISTING_PRODUCT_PROMOTION
        );
        $ebayListingProductPromotionTable = $this->getConnection()->newTable($ebayListingProductPromotionTableName);
        $ebayListingProductPromotionTable->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
        );
        $ebayListingProductPromotionTable->addColumn(
            'account_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false]
        );
        $ebayListingProductPromotionTable->addColumn(
            'marketplace_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false]
        );
        $ebayListingProductPromotionTable->addColumn(
            'listing_product_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false]
        );
        $ebayListingProductPromotionTable->addColumn(
            'promotion_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false]
        );
        $ebayListingProductPromotionTable->addColumn(
            'discount_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => true]
        );
        $ebayListingProductPromotionTable->addColumn(
            'update_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            ['default' => null]
        );
        $ebayListingProductPromotionTable->addColumn(
            'create_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            ['default' => null]
        );

        $ebayListingProductPromotionTable->setOption('type', 'INNODB');
        $ebayListingProductPromotionTable->setOption('charset', 'utf8');
        $ebayListingProductPromotionTable->setOption('collate', 'utf8_general_ci');
        $ebayListingProductPromotionTable->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($ebayListingProductPromotionTable);
    }
}
