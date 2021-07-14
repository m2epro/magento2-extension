<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y21_m05;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class Ess\M2ePro\Setup\Update\y21_m05\EbayStoreCategoryIDs
 */
class EbayStoreCategoryIDs extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $ebayTemplateStoreCategoryTable = $this->getFullTableName('ebay_template_store_category');

        $query = $this->getConnection()
            ->select()
            ->from($ebayTemplateStoreCategoryTable)
            ->where('category_id = ?', 4294967295)
            ->query();

        $accountStoreCategoriesCache = [];

        while ($row = $query->fetch()) {
            if (empty($accountStoreCategoriesCache[$row['account_id']])) {
                $accountStoreCategoriesCache[$row['account_id']] = $this->getEbayStoreCategories($row['account_id']);
            }

            $category = $this->findCategoryByPath(
                $row['category_path'],
                $accountStoreCategoriesCache[$row['account_id']]
            );

            if ($category !== null) {
                $this->getConnection()->update(
                    $ebayTemplateStoreCategoryTable,
                    ['category_id' => $category['category_id']],
                    ['id = ?' => $row['id']]
                );
            }
        }

        $listingTable = $this->getFullTableName('listing');
        $listingProductTable = $this->getFullTableName('listing_product');
        $ebayListingProductTable = $this->getFullTableName('ebay_listing_product');

        $listingProductsQuery = $this->getConnection()->query(
            <<<SQL
SELECT `ml`.account_id, 
       `ml`.marketplace_id ,
       `mlp`.listing_id, 
       `mlp`.product_id, 
       `melp`.listing_product_id, 
       `ml`.title as listing_title,
       `melp`.online_title as product_title
FROM `{$listingProductTable}` as `mlp`
    JOIN `{$ebayListingProductTable}` as melp on `mlp`.id = melp.listing_product_id 
    JOIN `{$listingTable}` as `ml` on `mlp`.listing_id = `ml`.id 
WHERE `melp`.`template_store_category_id` IN ( 
    SELECT * FROM ( SELECT id FROM `{$ebayTemplateStoreCategoryTable}` WHERE `category_id` = 4294967295 ) AS subquery 
)
SQL
        );

        $listingLogTable = $this->getFullTableName('listing_log');
        $catalogProductEntityVarcharTable = $this->installer->getTable('catalog_product_entity_varchar');
        $eavAttributeTable = $this->installer->getTable('eav_attribute');
        $entityColumn = $this->getEavColumn($catalogProductEntityVarcharTable, 'entity_id');

        $logActionId = $this->getNextLogActionId();
        $nowDate = new \DateTime();
        $nowDate = $nowDate->format('Y-m-d H:i:s');

        while ($row = $listingProductsQuery->fetch()) {
            $productTitle = $this->getConnection()->query(
                <<<SQL
SELECT `value`
FROM `{$catalogProductEntityVarcharTable}` as `cpev`
    LEFT JOIN `{$eavAttributeTable}` as `ea` ON `ea`.attribute_id = cpev.attribute_id 
WHERE `ea`.attribute_code = 'name' AND `cpev`.{$entityColumn} = {$row['product_id']}
SQL
            )->fetch();

            $productTitle = $productTitle['value'];

            $this->getConnection()->insert(
                $listingLogTable,
                [
                    'account_id'         => $row['account_id'],
                    'marketplace_id'     => $row['marketplace_id'],
                    'listing_id'         => $row['listing_id'],
                    'product_id'         => $row['product_id'],
                    'listing_product_id' => $row['listing_product_id'],
                    'listing_title'      => $row['listing_title'],
                    'product_title'      => empty($productTitle) ?
                        empty($row['product_title']) ? '[Product Title]' : $row['product_title'] :
                        $productTitle,
                    'initiator'          => 2, // INITIATOR_EXTENSION
                    'action_id'          => $logActionId,
                    'action'             => 1, // ACTION_UNKNOWN
                    'type'               => 3, // TYPE_WARNING
                    'description'        => <<<TEXT
The specified eBay Store category ID is invalid. Please select the correct eBay Store category ID for this Product.
TEXT
                    ,
                    'component_mode'     => 'ebay',
                    'additional_data'    => json_encode([]),
                    'create_date'        => $nowDate
                ]
            );
        }
    }

    private function getEbayStoreCategories($accountId)
    {
        $query = $this->getConnection()
            ->select()
            ->from($this->getFullTableName('ebay_account_store_category'))
            ->where('`account_id` = ?', (int)$accountId)
            ->order(['sorder ASC']);

        return $this->getConnection()->fetchAll($query);
    }

    private function findCategoryByPath($categoryPath, $categories)
    {
        $path = array_map('trim', array_reverse(explode('>', $categoryPath)));

        $lastParentId = 0;
        $lastCategory = null;
        $resultCategory = null;

        foreach ($path as $pathPart) {
            foreach ($categories as $category) {
                if ($category['parent_id'] == $lastParentId && $category['title'] == $pathPart) {
                    $lastParentId = $category['category_id'];
                    $lastCategory = $category;
                    break;
                }
            }

            if (isset($lastCategory['title']) && $lastCategory['title'] == $pathPart) {
                $resultCategory = $lastCategory;
            } else {
                $resultCategory = null;
            }
        }

        return $resultCategory;
    }

    private function getNextLogActionId()
    {
        $config = $this->getConfigModifier()->getEntity(
            '/logs/listings/',
            'last_action_id'
        );

        $value = $config->getValue() + 1;
        $config->updateValue($value);

        return $value;
    }

    //########################################
}
