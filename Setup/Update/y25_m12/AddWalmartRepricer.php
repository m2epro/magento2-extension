<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y25_m12;

use Ess\M2ePro\Helper\Module\Database\Tables;

class AddWalmartRepricer extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->addColumnsToWalmartTemplateSellingFormatTable();
        $this->addColumnsToWalmartListingProductTable();
        $this->addColumnsToWalmartListingOtherTable();
    }

    private function addColumnsToWalmartTemplateSellingFormatTable()
    {
        $modifier = $this->getTableModifier(Tables::TABLE_WALMART_TEMPLATE_SELLING_FORMAT);
        $modifier->addColumn(
            'repricer_min_price_mode',
            'SMALLINT UNSIGNED NOT NULL DEFAULT 0',
            null,
            null,
            false,
            false
        );
        $modifier->addColumn(
            'repricer_min_price_attribute',
            'VARCHAR(255)',
            null,
            null,
            false,
            false
        );
        $modifier->addColumn(
            'repricer_max_price_mode',
            'SMALLINT UNSIGNED NOT NULL DEFAULT 0',
            null,
            null,
            false,
            false
        );
        $modifier->addColumn(
            'repricer_max_price_attribute',
            'VARCHAR(255)',
            null,
            null,
            false,
            false
        );
        $modifier->addColumn(
            'repricer_account_strategies',
            'LONGTEXT',
            null,
            null,
            false,
            false
        );
        $modifier->commit();
    }

    private function addColumnsToWalmartListingProductTable()
    {
        $modifier = $this->getTableModifier(Tables::TABLE_WALMART_LISTING_PRODUCT);
        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product::COLUMN_ONLINE_REPRICER_STRATEGY_NAME,
            'VARCHAR(255)',
            null,
            null,
            false,
            false
        );
        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product::COLUMN_ONLINE_REPRICER_MIN_PRICE,
            'DECIMAL(12,4) UNSIGNED',
            null,
            null,
            false,
            false
        );
        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product::COLUMN_ONLINE_REPRICER_MAX_PRICE,
            'DECIMAL(12,4) UNSIGNED',
            null,
            null,
            false,
            false
        );
        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product::COLUMN_REPRICER_LAST_UPDATE_DATE,
            'DATETIME',
            null,
            null,
            false,
            false
        );
        $modifier->commit();
    }

    private function addColumnsToWalmartListingOtherTable()
    {
        $modifier = $this->getTableModifier('walmart_listing_other');
        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Other::COLUMN_ONLINE_REPRICER_STRATEGY_NAME,
            'VARCHAR(255)',
            null,
            null,
            false,
            false
        );
        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Other::COLUMN_ONLINE_REPRICER_MIN_PRICE,
            'DECIMAL(12,4) UNSIGNED',
            null,
            null,
            false,
            false
        );
        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Other::COLUMN_ONLINE_REPRICER_MAX_PRICE,
            'DECIMAL(12,4) UNSIGNED',
            null,
            null,
            false,
            false
        );
        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Other::COLUMN_ONLINE_REPRICER_STATUS,
            'VARCHAR(255)',
            null,
            null,
            false,
            false
        );
        $modifier->commit();
    }
}
