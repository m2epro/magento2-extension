<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m07;

class AddProductIdentifiersSettingsForAmazonListing extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    private const FROM_M1_GENERAL_ID_CUSTOM_ATTRIBUTE = 'general_id_custom_attribute';
    private const FROM_M1_WORLDWIDE_ID_CUSTOM_ATTRIBUTE = 'worldwide_id_custom_attribute';

    public function execute(): void
    {
        $modifier = $this->getTableModifier(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_AMAZON_LISTING);

        $this->processGeneralId($modifier);
        $this->processWorldwideId($modifier);
    }

    private function processGeneralId(\Ess\M2ePro\Model\Setup\Database\Modifier\Table $modifier): void
    {
        if (!$modifier->isColumnExists(self::FROM_M1_GENERAL_ID_CUSTOM_ATTRIBUTE)) {
            $modifier->addColumn(
                \Ess\M2ePro\Model\ResourceModel\Amazon\Listing::COLUMN_GENERAL_ID_ATTRIBUTE,
                'VARCHAR(255)',
                'NULL',
                \Ess\M2ePro\Model\ResourceModel\Amazon\Listing::COLUMN_RESTOCK_DATE_CUSTOM_ATTRIBUTE
            );

            return;
        }

        if (
            $modifier->isColumnExists(
                \Ess\M2ePro\Model\ResourceModel\Amazon\Listing::COLUMN_GENERAL_ID_ATTRIBUTE
            )
        ) {
            return;
        }

        // Use Case: migration from m1
        // ---------------------------------------

        $modifier->changeAndRenameColumn(
            self::FROM_M1_GENERAL_ID_CUSTOM_ATTRIBUTE,
            \Ess\M2ePro\Model\ResourceModel\Amazon\Listing::COLUMN_GENERAL_ID_ATTRIBUTE,
            'VARCHAR(255)',
            'NULL',
            \Ess\M2ePro\Model\ResourceModel\Amazon\Listing::COLUMN_RESTOCK_DATE_CUSTOM_ATTRIBUTE,
        )->commit();

        $this->convertEmptyStringsToNull(
            $modifier->getTableName(),
            \Ess\M2ePro\Model\ResourceModel\Amazon\Listing::COLUMN_GENERAL_ID_ATTRIBUTE
        );
    }

    private function processWorldwideId(\Ess\M2ePro\Model\Setup\Database\Modifier\Table $modifier): void
    {
        if (!$modifier->isColumnExists(self::FROM_M1_WORLDWIDE_ID_CUSTOM_ATTRIBUTE)) {
            $modifier->addColumn(
                \Ess\M2ePro\Model\ResourceModel\Amazon\Listing::COLUMN_WORLDWIDE_ID_ATTRIBUTE,
                'VARCHAR(255)',
                'NULL',
                \Ess\M2ePro\Model\ResourceModel\Amazon\Listing::COLUMN_GENERAL_ID_ATTRIBUTE
            );

            return;
        }

        if (
            $modifier->isColumnExists(
                \Ess\M2ePro\Model\ResourceModel\Amazon\Listing::COLUMN_WORLDWIDE_ID_ATTRIBUTE
            )
        ) {
            return;
        }

        // Use Case: migration from m1
        // ---------------------------------------

        $modifier->changeAndRenameColumn(
            self::FROM_M1_WORLDWIDE_ID_CUSTOM_ATTRIBUTE,
            \Ess\M2ePro\Model\ResourceModel\Amazon\Listing::COLUMN_WORLDWIDE_ID_ATTRIBUTE,
            'VARCHAR(255)',
            'NULL',
            \Ess\M2ePro\Model\ResourceModel\Amazon\Listing::COLUMN_GENERAL_ID_ATTRIBUTE,
        )->commit();

        $this->convertEmptyStringsToNull(
            $modifier->getTableName(),
            \Ess\M2ePro\Model\ResourceModel\Amazon\Listing::COLUMN_WORLDWIDE_ID_ATTRIBUTE
        );
    }

    private function convertEmptyStringsToNull(string $tableName, string $column): void
    {
        $sql = "UPDATE {$tableName} SET {$column} = NULL WHERE {$column} = ''";
        $this->getConnection()->query($sql);
    }
}
