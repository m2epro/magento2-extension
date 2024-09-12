<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m06;

use Ess\M2ePro\Helper\Module\Database\Tables;
use Ess\M2ePro\Model\ResourceModel\Marketplace as MarketplaceResource;
use Ess\M2ePro\Model\ResourceModel\Amazon\Marketplace as AmazonMarketplaceResource;

class AddAmazonMarketplaceSaudiArabia extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->createMarketplace();
        $this->createAmazonMarketplace();
    }

    private function createMarketplace(): void
    {
        $marketplaceTableName = $this->getFullTableName(Tables::TABLE_MARKETPLACE);

        $marketplace = $this->installer->getConnection()->select()
                                       ->from($marketplaceTableName)
                                       ->where(
                                           MarketplaceResource::COLUMN_ID . ' = ?',
                                           50
                                       )
                                       ->query()
                                       ->fetch();

        if ($marketplace !== false) {
            return;
        }

        $this->installer->getConnection()->insert(
            $marketplaceTableName,
            [
                MarketplaceResource::COLUMN_ID => 50,
                MarketplaceResource::COLUMN_NATIVE_ID => 22,
                MarketplaceResource::COLUMN_TITLE => 'Saudi Arabia',
                MarketplaceResource::COLUMN_CODE => 'SA',
                MarketplaceResource::COLUMN_URL => 'amazon.sa',
                MarketplaceResource::COLUMN_STATUS => 0,
                MarketplaceResource::COLUMN_SORDER => 23,
                MarketplaceResource::COLUMN_GROUP_TITLE => 'Europe',
                MarketplaceResource::COLUMN_COMPONENT_MODE => 'amazon',
                'update_date' => '2023-06-25 00:00:00',
                'create_date' => '2023-06-25 00:00:00',
            ]
        );
    }

    private function createAmazonMarketplace(): void
    {
        $amazonMarketplaceTableName = $this->getFullTableName(Tables::TABLE_AMAZON_MARKETPLACE);

        $marketplace = $this->installer->getConnection()->select()
                                       ->from($amazonMarketplaceTableName)
                                       ->where(
                                           AmazonMarketplaceResource::COLUMN_MARKETPLACE_ID . ' = ?',
                                           50
                                       )
                                       ->query()
                                       ->fetch();

        if ($marketplace !== false) {
            return;
        }

        $this->installer->getConnection()->insert(
            $amazonMarketplaceTableName,
            [
                AmazonMarketplaceResource::COLUMN_MARKETPLACE_ID => 50,
                AmazonMarketplaceResource::COLUMN_DEFAULT_CURRENCY => 'SAR',
                'is_new_asin_available' => 1,
                AmazonMarketplaceResource::COLUMN_IS_MERCHANT_FULFILLMENT_AVAILABLE => 1,
                AmazonMarketplaceResource::COLUMN_IS_BUSINESS_AVAILABLE => 1,
                AmazonMarketplaceResource::COLUMN_IS_VAT_CALCULATION_SERVICE_AVAILABLE => 1,
                AmazonMarketplaceResource::COLUMN_IS_PRODUCT_TAX_CODE_POLICY_AVAILABLE => 0,
            ]
        );
    }
}
