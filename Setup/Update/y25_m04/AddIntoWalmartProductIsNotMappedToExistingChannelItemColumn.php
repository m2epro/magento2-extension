<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y25_m04;

class AddIntoWalmartProductIsNotMappedToExistingChannelItemColumn extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $modifier = $this->getTableModifier(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_LISTING_PRODUCT
        );

        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product::COLUMN_IS_NOT_MAPPED_TO_EXISTING_CHANNEL_ITEM,
            'SMALLINT UNSIGNED NOT NULL',
            0,
            null,
            false,
            null
        );

        $modifier->commit();
    }
}
