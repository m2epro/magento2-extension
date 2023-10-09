<?php

namespace Ess\M2ePro\Setup\Update\y23_m10;

use Magento\Framework\DB\Ddl\Table;

class EnableAmazonShippingServiceForSomeMarketplaces extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $marketplaces = [
            ['name' => 'Canada', 'id' => 24],
            ['name' => 'United States', 'id' => 29],
            ['name' => 'Mexico', 'id' => 34],
            ['name' => 'Spain', 'id' => 30],
            ['name' => 'United Kingdom', 'id' => 28],
            ['name' => 'France', 'id' => 26],
            ['name' => 'Germany', 'id' => 25],
            ['name' => 'Italy', 'id' => 31],
            ['name' => 'India', 'id' => 46],
        ];

        $this->getConnection()->update(
            $this->getFullTableName('m2epro_amazon_marketplace'),
            ['is_merchant_fulfillment_available' => 1],
            ['marketplace_id IN (?)' => array_column($marketplaces, 'id')]
        );
    }
}
