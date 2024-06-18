<?php

namespace Ess\M2ePro\Helper\Module\Database;

use Magento\Catalog\Api\Data\ProductAttributeInterface;

class Tables
{
    public const PREFIX = 'm2epro_';

    public const TABLE_LISTING = self::PREFIX . 'listing';

    public const TABLE_LISTING_PRODUCT = self::PREFIX . 'listing_product';
    public const TABLE_LISTING_PRODUCT_INSTRUCTION = self::PREFIX . 'listing_product_instruction';
    public const TABLE_LISTING_AUTO_CATEGORY = self::PREFIX . 'listing_auto_category';

    public const TABLE_EBAY_LISTING = self::PREFIX . 'ebay_listing';
    public const TABLE_EBAY_LISTING_PRODUCT = self::PREFIX . 'ebay_listing_product';
    public const TABLE_EBAY_LISTING_PRODUCT_PROMOTION = self::PREFIX . 'ebay_listing_product_promotion';
    public const TABLE_EBAY_TEMPLATE_SYNCHRONIZATION = self::PREFIX . 'ebay_template_synchronization';
    public const TABLE_EBAY_TEMPLATE_SELLING_FORMAT = self::PREFIX . 'ebay_template_selling_format';
    public const TABLE_EBAY_PROMOTION = self::PREFIX . 'ebay_promotion';
    public const TABLE_EBAY_PROMOTION_DISCOUNT = self::PREFIX . 'ebay_promotion_discount';
    public const TABLE_EBAY_MARKETPLACE = self::PREFIX . 'ebay_marketplace';
    public const TABLE_EBAY_DICTIONARY_MARKETPLACE = self::PREFIX . 'ebay_dictionary_marketplace';

    public const TABLE_AMAZON_ACCOUNT = self::PREFIX . 'amazon_account';
    public const TABLE_AMAZON_ACCOUNT_MERCHANT_SETTING = self::PREFIX . 'amazon_account_merchant_setting';
    public const TABLE_AMAZON_LISTING_PRODUCT = self::PREFIX . 'amazon_listing_product';
    public const TABLE_AMAZON_ORDER_ITEM = self::PREFIX . 'amazon_order_item';

    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;
    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseHelper;
    /** @var \Ess\M2ePro\Helper\Magento\Staging */
    private $stagingHelper;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper,
        \Ess\M2ePro\Helper\Magento\Staging $stagingHelper
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->databaseHelper = $databaseHelper;
        $this->stagingHelper = $stagingHelper;
    }

    /**
     * @param string $tableName
     *
     * @return bool
     */
    public function isExists(string $tableName): bool
    {
        return $this->resourceConnection
            ->getConnection()
            ->isTableExists($this->getFullName($tableName));
    }

    /**
     * @param string $tableName
     *
     * @return string
     */
    public function getFullName(string $tableName): string
    {
        if (strpos($tableName, self::PREFIX) === false) {
            $tableName = self::PREFIX . $tableName;
        }

        return $this->databaseHelper->getTableNameWithPrefix($tableName);
    }

    /**
     * @param string $oldTable
     * @param string $newTable
     *
     * @return bool
     */
    public function renameTable(string $oldTable, string $newTable): bool
    {
        $oldTable = $this->getFullName($oldTable);
        $newTable = $this->getFullName($newTable);

        if (
            $this->resourceConnection->getConnection()->isTableExists($oldTable) &&
            !$this->resourceConnection->getConnection()->isTableExists($newTable)
        ) {
            $this->resourceConnection->getConnection()->renameTable(
                $oldTable,
                $newTable
            );

            return true;
        }

        return false;
    }

    /**
     * @param array|string $table
     * @param string $columnName
     *
     * @return string
     */
    public function normalizeEavColumn($table, string $columnName): string
    {
        if (
            $this->stagingHelper->isInstalled() &&
            $this->stagingHelper->isStagedTable($table, ProductAttributeInterface::ENTITY_TYPE_CODE) &&
            strpos($columnName, 'entity_id') !== false
        ) {
            $columnName = str_replace(
                'entity_id',
                $this->stagingHelper->getTableLinkField(ProductAttributeInterface::ENTITY_TYPE_CODE),
                $columnName
            );
        }

        return $columnName;
    }
}
