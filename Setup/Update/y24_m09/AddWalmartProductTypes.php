<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m09;

class AddWalmartProductTypes extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    private const LONG_COLUMN_SIZE = 16777217;
    private const WIZARD_STATUS_ACTIVE = 1;
    private const WIZARD_STATUS_SKIPPED = 3;

    public function execute(): void
    {
        $this->addProductTypeToListingProduct();
        $this->updateAutoActions();
        $this->updateMarketplaceDictionary();
        $this->updateCategoryDictionary();
        $this->createDictionaryProductType();
        $this->createProductType();
        $this->insertIntoWizard();
        $this->deleteAttributesFromDescriptionPolicy();
        $this->deleteAttributesFromSellingPolicy();
    }

    private function addProductTypeToListingProduct(): void
    {
        $tableModifier = $this->getTableModifier(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_LISTING_PRODUCT
        );
        $tableModifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product::COLUMN_PRODUCT_TYPE_ID,
            'INT UNSIGNED',
            'NULL'
        );
    }

    private function updateAutoActions(): void
    {
        $walmartListingModifier = $this->getTableModifier(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_LISTING
        );
        $listingAutoCategoryGroupModifier = $this->getTableModifier(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_LISTING_AUTO_CATEGORY_GROUP
        );

        if (
            !$walmartListingModifier->isColumnExists('auto_global_adding_category_template_id')
            || !$walmartListingModifier->isColumnExists('auto_website_adding_category_template_id')
            || !$listingAutoCategoryGroupModifier->isColumnExists('adding_category_template_id')
        ) {
            return;
        }

        $this->resetListingAutoActionsByCategoryTemplate();
        $this->resetListingAutoActionsByCategoryGroup();

        $this->removeAutoActionsWithCategoryTemplateId();
        $this->changeSchemeAutoActions();
    }

    private function resetListingAutoActionsByCategoryTemplate(): void
    {
        $subSelect = $this->getConnection()
            ->select()
            ->from(
                $this->getFullTableName(
                    \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_LISTING
                ),
                \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_LISTING_ID
            )
            ->where(
                'auto_global_adding_category_template_id IS NOT NULL'
                . ' OR auto_website_adding_category_template_id IS NOT NULL'
            );

        $this->resetListingAutoActions($subSelect);
    }

    private function resetListingAutoActionsByCategoryGroup(): void
    {
        $subSelect = $this->getConnection()
            ->select()
            ->from(
                $this->getFullTableName(
                    \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_LISTING_AUTO_CATEGORY_GROUP
                )
            )
            ->join(
                [
                    'wg' => $this->getFullTableName(
                        \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_LISTING_AUTO_CATEGORY_GROUP
                    )
                ],
                'id = listing_auto_category_group_id',
            )->where('wg.adding_category_template_id IS NOT NULL')
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns('listing_id');

        $this->resetListingAutoActions($subSelect);
    }

    private function resetListingAutoActions(\Magento\Framework\DB\Select $subSelect): void
    {
        $this->getConnection()->update(
            $this->getFullTableName(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_LISTING),
            [
                'auto_mode' => 0,
                'auto_global_adding_mode' => 1,
                'auto_global_adding_add_not_visible' => 1,
                'auto_website_adding_mode' => 0,
                'auto_website_adding_add_not_visible' => 1,
                'auto_website_deleting_mode' => 0,
            ],
            [\Ess\M2ePro\Model\ResourceModel\Listing::COLUMN_ID . ' IN (?)' => $subSelect]
        );
    }

    private function removeAutoActionsWithCategoryTemplateId(): void
    {
        $walmartGroupTableName = $this->getFullTableName(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_LISTING_AUTO_CATEGORY_GROUP
        );
        $groupTableName = $this->getFullTableName(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_LISTING_AUTO_CATEGORY_GROUP
        );
        $categoryTableName = $this->getFullTableName(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_LISTING_AUTO_CATEGORY
        );

        $subSelect = "SELECT listing_auto_category_group_id FROM $walmartGroupTableName"
            . " WHERE adding_category_template_id IS NOT NULL";
        $rmFromGroups = "DELETE FROM $groupTableName WHERE id IN ($subSelect)";
        $rmFromCategory = "DELETE FROM $categoryTableName WHERE group_id IN ($subSelect)";

        $rmFromWalmartGroups = "DELETE FROM $walmartGroupTableName WHERE adding_category_template_id IS NOT NULL";

        foreach (
            [
                $rmFromGroups,
                $rmFromCategory,
                $rmFromWalmartGroups,
            ] as $sql
        ) {
            $this->getConnection()
                ->query($sql)
                ->execute();
        }
    }

    private function changeSchemeAutoActions(): void
    {
        $walmartListingModifier = $this->getTableModifier(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_LISTING
        );
        $walmartListingModifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_AUTO_GLOBAL_ADDING_PRODUCT_TYPE_ID,
            'INT UNSIGNED',
            'NULL',
            'auto_website_adding_category_template_id'
        );
        $walmartListingModifier->addIndex(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_AUTO_GLOBAL_ADDING_PRODUCT_TYPE_ID
        );
        $walmartListingModifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_AUTO_WEBSITE_ADDING_PRODUCT_TYPE_ID,
            'INT UNSIGNED',
            'NULL',
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_AUTO_GLOBAL_ADDING_PRODUCT_TYPE_ID,
        );
        $walmartListingModifier->addIndex(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_AUTO_WEBSITE_ADDING_PRODUCT_TYPE_ID,
        );
        $walmartListingModifier->dropColumn('auto_global_adding_category_template_id');
        $walmartListingModifier->dropIndex('auto_global_adding_category_template_id');
        $walmartListingModifier->dropColumn('auto_website_adding_category_template_id');
        $walmartListingModifier->dropIndex('auto_website_adding_category_template_id');

        $listingAutoCategoryGroupModifier = $this->getTableModifier(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_LISTING_AUTO_CATEGORY_GROUP
        );
        $listingAutoCategoryGroupModifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Auto\Category\Group::COLUMN_ADDING_PRODUCT_TYPE_ID,
            'INT UNSIGNED',
            'NULL'
        );
        $listingAutoCategoryGroupModifier->addIndex(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Auto\Category\Group::COLUMN_ADDING_PRODUCT_TYPE_ID,
        );
        $listingAutoCategoryGroupModifier->dropColumn('adding_category_template_id');
        $listingAutoCategoryGroupModifier->dropIndex('adding_category_template_id');
    }

    private function updateMarketplaceDictionary(): void
    {
        $tableModifier = $this->getTableModifier(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_DICTIONARY_MARKETPLACE
        );
        $tableModifier->truncate();
        $tableModifier->dropColumn('product_data');
        $tableModifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Marketplace::COLUMN_PRODUCT_TYPES,
            'LONGTEXT',
            'NULL',
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Marketplace::COLUMN_SERVER_DETAILS_LAST_UPDATE_DATE,
            false,
            false
        )->commit();
    }

    private function updateCategoryDictionary(): void
    {
        $tableModifier = $this->getTableModifier(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_DICTIONARY_CATEGORY
        );
        $tableModifier->truncate();
        $tableModifier->dropColumn('browsenode_id');
        $tableModifier->dropIndex('browsenode_id');
        $tableModifier->dropColumn('product_data_nicks');
        $tableModifier->dropColumn('path');
        $tableModifier->dropIndex('path');
        $tableModifier->dropColumn('keywords');

        $tableModifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category::COLUMN_PRODUCT_TYPE_NICK,
            'VARCHAR(255)',
            'NULL',
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category::COLUMN_TITLE
        );
        $tableModifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category::COLUMN_PRODUCT_TYPE_TITLE,
            'VARCHAR(255)',
            'NULL',
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category::COLUMN_PRODUCT_TYPE_NICK
        );
    }

    private function createDictionaryProductType(): void
    {
        $walmartDictionaryProductTypeTableName = $this->getFullTableName(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_DICTIONARY_PRODUCT_TYPE
        );
        $walmartDictionaryProductTypeTable = $this->getConnection()->newTable(
            $walmartDictionaryProductTypeTableName
        );
        $walmartDictionaryProductTypeTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType::COLUMN_ID,
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [
                'unsigned' => true,
                'primary' => true,
                'nullable' => false,
                'auto_increment' => true,
            ]
        );
        $walmartDictionaryProductTypeTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType::COLUMN_MARKETPLACE_ID,
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false]
        );
        $walmartDictionaryProductTypeTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType::COLUMN_NICK,
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false]
        );
        $walmartDictionaryProductTypeTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType::COLUMN_TITLE,
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false]
        );
        $walmartDictionaryProductTypeTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType::COLUMN_ATTRIBUTES,
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            self::LONG_COLUMN_SIZE,
            ['nullable' => false]
        );
        $walmartDictionaryProductTypeTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType::COLUMN_VARIATION_ATTRIBUTES,
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            self::LONG_COLUMN_SIZE,
            ['nullable' => false]
        );
        $walmartDictionaryProductTypeTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType::COLUMN_INVALID,
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 0]
        );
        $walmartDictionaryProductTypeTable->addIndex(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType::COLUMN_MARKETPLACE_ID
            . '__'
            . \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType::COLUMN_NICK,
            [
                \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType::COLUMN_MARKETPLACE_ID,
                \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType::COLUMN_NICK,
            ],
            ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
        );
        $walmartDictionaryProductTypeTable->setOption('type', 'INNODB');
        $walmartDictionaryProductTypeTable->setOption('charset', 'utf8');
        $walmartDictionaryProductTypeTable->setOption('collate', 'utf8_general_ci');
        $walmartDictionaryProductTypeTable->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($walmartDictionaryProductTypeTable);
    }

    private function createProductType(): void
    {
        $walmartProductTypeTableName = $this->getFullTableName(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_PRODUCT_TYPE
        );
        $walmartProductTypeTable = $this->getConnection()->newTable(
            $this->getFullTableName($walmartProductTypeTableName)
        );
        $walmartProductTypeTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType::COLUMN_ID,
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [
                'unsigned' => true,
                'primary' => true,
                'nullable' => false,
                'auto_increment' => true,
            ]
        );
        $walmartProductTypeTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType::COLUMN_TITLE,
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['default' => null]
        );
        $walmartProductTypeTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType::COLUMN_DICTIONARY_PRODUCT_TYPE_ID,
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false]
        );
        $walmartProductTypeTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType::COLUMN_ATTRIBUTES_SETTINGS,
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            self::LONG_COLUMN_SIZE,
            ['nullable' => false]
        );
        $walmartProductTypeTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType::COLUMN_UPDATE_DATE,
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            ['default' => null]
        );
        $walmartProductTypeTable->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType::COLUMN_CREATE_DATE,
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            ['default' => null]
        );
        $walmartProductTypeTable->addIndex(
            \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType::COLUMN_DICTIONARY_PRODUCT_TYPE_ID,
            \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType::COLUMN_DICTIONARY_PRODUCT_TYPE_ID,
            ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
        );
        $walmartProductTypeTable->addIndex(
            \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType::COLUMN_TITLE,
            \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType::COLUMN_TITLE
        );
        $walmartProductTypeTable->setOption('type', 'INNODB');
        $walmartProductTypeTable->setOption('charset', 'utf8');
        $walmartProductTypeTable->setOption('collate', 'utf8_general_ci');
        $walmartProductTypeTable->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($walmartProductTypeTable);
    }

    private function insertIntoWizard(): void
    {
        $wizardTableName = $this->getFullTableName('wizard');
        $nick = 'walmartMigrationToProductTypes';

        $query = $this->getConnection()
            ->select()
            ->from($wizardTableName)
            ->where('nick = ?', $nick)
            ->query();

        $row = $query->fetch();
        if ($row) {
            return;
        }

        $query = $this->getConnection()
                      ->select()
                      ->from($this->getFullTableName(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_MARKETPLACE))
                      ->where('component_mode = "walmart" AND status = 1')
                      ->query();

        $row = $query->fetch();
        $status = $row ? self::WIZARD_STATUS_ACTIVE : self::WIZARD_STATUS_SKIPPED;

        $this->getConnection()->insert(
            $wizardTableName,
            [
                'nick' => $nick,
                'view' => 'walmart',
                'status' => $status,
                'step' => null,
                'type' => 1,
                'priority' => 8,
            ]
        );
    }

    private function deleteAttributesFromDescriptionPolicy()
    {
        $tableModifier = $this->getTableModifier(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_TEMPLATE_DESCRIPTION
        );

        $tableModifier->dropColumn('attributes_mode', false, false)
                      ->dropColumn('attributes', false, false)
                      ->commit();
    }

    private function deleteAttributesFromSellingPolicy()
    {
        $tableModifier = $this->getTableModifier(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_TEMPLATE_SELLING_FORMAT
        );

        $tableModifier->dropColumn('attributes_mode', false, false)
                      ->dropColumn('attributes', false, false)
                      ->commit();
    }
}
