<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m06;

class AddPriceLastUpdateDateColumnToEbayListingProductTable extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->getTableModifier(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_LISTING_PRODUCT)
             ->addColumn(
                 \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_PRICE_LAST_UPDATE_DATE,
                 'DATETIME',
                 'NULL',
                 'online_other_data'
             );
    }
}
