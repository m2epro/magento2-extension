<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m09;

use Ess\M2ePro\Helper\Module\Database\Tables as TablesHelper;
use Ess\M2ePro\Model\ResourceModel\Ebay\Marketplace as ResourceEbayMarketplace;

class AddInternationalShippingRateTablesForAustralia extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->getConnection()->update(
            $this->getFullTableName(TablesHelper::TABLE_EBAY_MARKETPLACE),
            [ResourceEbayMarketplace::COLUMN_IS_INTERNATIONAL_SHIPPING_RATE_TABLE => 1],
            [ResourceEbayMarketplace::COLUMN_MARKETPLACE_ID . ' = ?' => 4]
        );
    }
}
