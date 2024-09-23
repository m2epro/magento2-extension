<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m08;

use Ess\M2ePro\Helper\Module\Database\Tables as TablesHelper;

class UpdateAmazonDictionaryProductType extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->addColumnForDictionaryPT();
        $this->updateLastUpdateDate();
        $this->updateVariationThemesGroups();

        $this->removeOldColumnFromMarketplaceDictionary();
    }

    private function addColumnForDictionaryPT(): void
    {
        $this->getTableModifier(TablesHelper::TABLE_AMAZON_DICTIONARY_PRODUCT_TYPE)
             ->addColumn(
                 \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType::COLUMN_VARIATION_THEMES,
                 'LONGTEXT',
                 'NULL',
                 \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType::COLUMN_SCHEMA,
                 false,
                 false,
             )
             ->addColumn(
                 \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType::COLUMN_ATTRIBUTES_GROUPS,
                 'LONGTEXT',
                 'NULL',
                 \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType::COLUMN_VARIATION_THEMES,
                 false,
                 false,
             )
             ->addColumn(
                 \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType::COLUMN_CLIENT_DETAILS_LAST_UPDATE_DATE,
                 'DATETIME',
                 'NULL',
                 \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType::COLUMN_ATTRIBUTES_GROUPS,
                 false,
                 false,
             )
             ->addColumn(
                 \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType::COLUMN_SERVER_DETAILS_LAST_UPDATE_DATE,
                 'DATETIME',
                 'NULL',
                 \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType::COLUMN_CLIENT_DETAILS_LAST_UPDATE_DATE,
                 false,
                 false,
             )
             ->commit();
    }

    private function updateLastUpdateDate(): void
    {
        $marketplaceDictionaryModifier = $this->getTableModifier(TablesHelper::TABLE_AMAZON_DICTIONARY_MARKETPLACE);
        if (
            !$marketplaceDictionaryModifier->isColumnExists('client_details_last_update_date')
            || !$marketplaceDictionaryModifier->isColumnExists('server_details_last_update_date')
        ) {
            return;
        }

        $dictionaryMarketplaceTableName = $this->getFullTableName(TablesHelper::TABLE_AMAZON_DICTIONARY_MARKETPLACE);
        $dictionaryPTTableName = $this->getFullTableName(TablesHelper::TABLE_AMAZON_DICTIONARY_PRODUCT_TYPE);

        $this->getConnection()
             ->query(
                 <<<MYSQL
                UPDATE $dictionaryPTTableName
                JOIN $dictionaryMarketplaceTableName dm ON dm.marketplace_id = {$dictionaryPTTableName}.marketplace_id
                SET $dictionaryPTTableName.client_details_last_update_date = dm.client_details_last_update_date,
                    $dictionaryPTTableName.server_details_last_update_date = dm.server_details_last_update_date;
                MYSQL
             );

        // ----------------------------------------

        $this->getTableModifier(TablesHelper::TABLE_AMAZON_DICTIONARY_PRODUCT_TYPE)
             ->changeColumn(
                 \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType::COLUMN_CLIENT_DETAILS_LAST_UPDATE_DATE,
                 'DATETIME NOT NULL',
             )
             ->changeColumn(
                 \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType::COLUMN_SERVER_DETAILS_LAST_UPDATE_DATE,
                 'DATETIME NOT NULL',
             );
    }

    private function updateVariationThemesGroups(): void
    {
        $dictionaryPTTableName = $this->getFullTableName(TablesHelper::TABLE_AMAZON_DICTIONARY_PRODUCT_TYPE);

        $stmt = $this->getConnection()
                     ->query(
                         <<<MYSQL
                        SELECT id, marketplace_id, nick
                        FROM $dictionaryPTTableName;
                        MYSQL
                     );
        $productTypeMarketplacesDictionaryData = [];
        while ($ptRow = $stmt->fetch()) {
            $ptDictionaryId = (int)$ptRow['id'];
            $marketplaceId = (int)$ptRow['marketplace_id'];
            $nick = $ptRow['nick'];

            if (!isset($productTypeMarketplacesDictionaryData[$marketplaceId])) {
                $productTypeMarketplacesDictionaryData[$marketplaceId] = $this->getMarketplaceDictionaryData(
                    $marketplaceId
                );
            }

            $dictionaryPTData = $productTypeMarketplacesDictionaryData[$marketplaceId][$nick] ?? null;
            if ($dictionaryPTData === null) {
                continue;
            }

            $groups = $dictionaryPTData['groups'] ?? [];
            $variationThemes = $dictionaryPTData['variation_themes'] ?? [];

            //$mySqlGroups = $this->getConnection()->quote(json_encode($groups));
            $mySqlGroups = json_encode($groups);
            //$mySqlVariationThemes = $this->getConnection()->quote(json_encode($variationThemes));
            $mySqlVariationThemes = json_encode($variationThemes);

            $this->getConnection()
                 ->query(
                     <<<MYSQL
                    UPDATE $dictionaryPTTableName
                    SET variation_themes = '$mySqlVariationThemes',
                        attributes_groups = '$mySqlGroups'
                    WHERE id = $ptDictionaryId;
                    MYSQL
                 );
        }

        $this->getTableModifier(TablesHelper::TABLE_AMAZON_DICTIONARY_PRODUCT_TYPE)
             ->changeColumn(
                 \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType::COLUMN_VARIATION_THEMES,
                 'LONGTEXT NOT NULL',
                 null,
                 null,
                 false,
             )
             ->changeColumn(
                 \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType::COLUMN_ATTRIBUTES_GROUPS,
                 'LONGTEXT NOT NULL',
                 null,
                 null,
                 false,
             )
             ->commit();
    }

    private function getMarketplaceDictionaryData(int $marketplaceId): array
    {
        $dictionaryMarketplaceTableName = $this->getFullTableName(TablesHelper::TABLE_AMAZON_DICTIONARY_MARKETPLACE);

        $ptData = $this->getConnection()
                       ->query(
                           <<<MYSQL
                            SELECT product_types
                            FROM $dictionaryMarketplaceTableName
                            WHERE marketplace_id = {$marketplaceId}
                            MYSQL
                       )
                       ->fetchColumn();

        return (array)json_decode($ptData ?? '[]', true);
    }

    // ----------------------------------------

    private function removeOldColumnFromMarketplaceDictionary(): void
    {
        $this->getTableModifier(TablesHelper::TABLE_AMAZON_DICTIONARY_MARKETPLACE)
             ->dropColumn('client_details_last_update_date', true, false)
             ->dropColumn('server_details_last_update_date', true, false)
             ->commit();
    }
}
