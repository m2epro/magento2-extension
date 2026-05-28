<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y26_m05;

use Ess\M2ePro\Helper\Module\Database\Tables;

class AmazonAddMultiLocationInventory extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->addMultiLocationInventoryMappingColumnToAmazonAccountTable();
        $this->addOnlineMultiLocationInventoryColumnToAmazonListingProductTable();
    }

    private function addMultiLocationInventoryMappingColumnToAmazonAccountTable(): void
    {
        $modifier = $this->getTableModifier(Tables::TABLE_AMAZON_ACCOUNT);

        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Amazon\Account::COLUMN_MULTI_LOCATION_INVENTORY_MAPPING,
            'LONGTEXT',
            'NULL',
            \Ess\M2ePro\Model\ResourceModel\Amazon\Account::COLUMN_FBA_INVENTORY_SOURCE_NAME,
            false,
            false
        );

        $modifier->commit();
    }

    private function addOnlineMultiLocationInventoryColumnToAmazonListingProductTable(): void
    {
        $modifier = $this->getTableModifier(Tables::TABLE_AMAZON_LISTING_PRODUCT);

        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product::COLUMN_ONLINE_MULTI_LOCATION_INVENTORY,
            'LONGTEXT',
            'NULL',
            null,
            false,
            false
        );

        $modifier->commit();
    }
}
