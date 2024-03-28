<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m03;

class AddOnlineRegularMapPriceToAmazonListingProduct extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $tableModifier = $this->getTableModifier(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_AMAZON_LISTING_PRODUCT
        );
        $tableModifier->addColumn(
            'online_regular_map_price',
            'DECIMAL(12, 4) UNSIGNED',
            'NULL',
            'online_regular_price'
        );
    }
}
