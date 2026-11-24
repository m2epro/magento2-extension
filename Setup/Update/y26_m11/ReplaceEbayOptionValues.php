<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y26_m11;

class ReplaceEbayOptionValues extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    private const TABLE_NAME = 'listing_product_variation_option';

    public function execute(): void
    {
        $modifier = $this->getTableModifier(self::TABLE_NAME);

        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Listing\Product\Variation\Option::COLUMN_CHANNEL_OPTION,
            'VARCHAR(255)',
            null,
            null,
            false,
            false
        );

        $modifier->commit();
    }
}
