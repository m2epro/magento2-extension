<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y25_m07;

use Ess\M2ePro\Helper\Module\Database\Tables;

class AddOnlineStrikeThroughPriceColumnToEbayListingProductTable extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $modifier = $this->getTableModifier(Tables::TABLE_EBAY_LISTING_PRODUCT);

        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_ONLINE_STRIKE_THROUGH_PRICE,
            'DECIMAL(12, 4) UNSIGNED',
            null,
            null,
            false,
            false
        );

        $modifier->commit();
    }
}
