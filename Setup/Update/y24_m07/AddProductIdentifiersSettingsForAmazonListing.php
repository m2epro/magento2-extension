<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m07;

class AddProductIdentifiersSettingsForAmazonListing extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $modifier = $this->getTableModifier(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_AMAZON_LISTING);
        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Amazon\Listing::COLUMN_GENERAL_ID_ATTRIBUTE,
            'VARCHAR(255)',
            'NULL',
            \Ess\M2ePro\Model\ResourceModel\Amazon\Listing::COLUMN_RESTOCK_DATE_CUSTOM_ATTRIBUTE
        );
        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Amazon\Listing::COLUMN_WORLDWIDE_ID_ATTRIBUTE,
            'VARCHAR(255)',
            'NULL',
            \Ess\M2ePro\Model\ResourceModel\Amazon\Listing::COLUMN_GENERAL_ID_ATTRIBUTE
        );
    }
}
