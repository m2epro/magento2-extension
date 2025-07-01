<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y25_m05;

use Ess\M2ePro\Helper\Module\Database\Tables;
use Ess\M2ePro\Model\ResourceModel\Ebay\PromotedListing\Campaign as CampaignResource;
use Magento\Framework\DB\Ddl\Table;

class EbayPromotedListingCampaigns extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $this->createCampaignTable();
        $this->addColumnsToEbayListingProduct();
    }

    private function createCampaignTable(): void
    {
        $tableName = $this->getFullTableName(Tables::TABLE_EBAY_PROMOTED_LISTING_CAMPAIGN);

        if ($this->getConnection()->isTableExists($tableName)) {
            return;
        }

        $table = $this
            ->getConnection()
            ->newTable($tableName);

        $table
            ->addColumn(
                CampaignResource::COLUMN_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                CampaignResource::COLUMN_ACCOUNT_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                CampaignResource::COLUMN_MARKETPLACE_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                CampaignResource::COLUMN_EBAY_CAMPAIGN_ID,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                CampaignResource::COLUMN_NAME,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                CampaignResource::COLUMN_TYPE,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                CampaignResource::COLUMN_STATUS,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                CampaignResource::COLUMN_START_DATE,
                Table::TYPE_DATETIME,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                CampaignResource::COLUMN_END_DATE,
                Table::TYPE_DATETIME,
                null,
                ['nullable' => true]
            )
            ->addColumn(
                CampaignResource::COLUMN_RATE,
                Table::TYPE_DECIMAL,
                [12, 4],
                ['nullable' => false]
            )
            ->addColumn(
                CampaignResource::COLUMN_UPDATE_DATE,
                Table::TYPE_DATETIME,
                null,
                ['nullable' => true]
            )
            ->addColumn(
                CampaignResource::COLUMN_CREATE_DATE,
                Table::TYPE_DATETIME,
                null,
                ['nullable' => false]
            );

        $this->getConnection()->createTable($table);
    }

    private function addColumnsToEbayListingProduct()
    {
        $modifier = $this->getTableModifier(Tables::TABLE_EBAY_LISTING_PRODUCT);
        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_PROMOTED_LISTING_CAMPAIGN_ID,
            'INT UNSIGNED',
            'NULL',
            null,
            false,
            false
        );
        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_PROMOTED_LISTING_CAMPAIGN_RATE,
            'DECIMAL(12, 4) UNSIGNED',
            'NULL',
            null,
            false,
            false
        );
        $modifier->commit();
    }
}
