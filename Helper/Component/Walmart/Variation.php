<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Walmart;

class Variation
{
    public const DATA_REGISTRY_KEY = 'walmart_variation_themes_usage';

    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;
    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $permanentCache;
    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseStructure;
    /** @var \Ess\M2ePro\Helper\Magento\Product */
    private $helperMagentoProduct;
    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registry;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Ess\M2ePro\Helper\Module\Database\Structure $databaseStructure
     * @param \Ess\M2ePro\Helper\Magento\Product $helperMagentoProduct
     * @param \Ess\M2ePro\Helper\Data\Cache\Permanent $permanentCache
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\Registry\Manager $registry,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseStructure,
        \Ess\M2ePro\Helper\Magento\Product $helperMagentoProduct,
        \Ess\M2ePro\Helper\Data\Cache\Permanent $permanentCache
    ) {
        $this->databaseStructure = $databaseStructure;
        $this->helperMagentoProduct = $helperMagentoProduct;
        $this->permanentCache = $permanentCache;
        $this->resourceConnection = $resourceConnection;
        $this->registry = $registry;
    }

    // ----------------------------------------

    public function filterProductsNotMatchingForNewAsin($productsIds)
    {
        $productsIds = $this->filterProductsByGeneralId($productsIds);
        $productsIds = $this->filterProductsByGeneralIdOwner($productsIds);
        $productsIds = $this->filterProductsByStatus($productsIds);
        $productsIds = $this->filterLockedProducts($productsIds);
        $productsIds = $this->filterProductsByMagentoProductType($productsIds);

        return $productsIds;
    }

    //########################################

    public function filterProductsByGeneralId($productsIds)
    {
        $connRead = $this->resourceConnection->getConnection();
        $table = $this->databaseStructure->getTableNameWithPrefix('m2epro_walmart_listing_product');

        $select = $connRead->select();
        $select->from(['alp' => $table], ['listing_product_id'])
               ->where('listing_product_id IN (?)', $productsIds)
               ->where('general_id IS NULL');

        return $this->resourceConnection->getConnection()
                                        ->fetchCol($select);
    }

    public function filterProductsByGeneralIdOwner($productsIds)
    {
        $connRead = $this->resourceConnection->getConnection();
        $table = $this->databaseStructure->getTableNameWithPrefix('m2epro_walmart_listing_product');

        $select = $connRead->select();
        $select->from(['alp' => $table], ['listing_product_id'])
               ->where('listing_product_id IN (?)', $productsIds)
               ->where('is_general_id_owner = 0');

        return $this->resourceConnection->getConnection()
                                        ->fetchCol($select);
    }

    public function filterProductsByStatus($productsIds)
    {
        $connRead = $this->resourceConnection->getConnection();
        $table = $this->databaseStructure->getTableNameWithPrefix('m2epro_listing_product');

        $select = $connRead->select();
        $select->from(['lp' => $table], ['id'])
               ->where('id IN (?)', $productsIds)
               ->where('status = ?', \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED);

        return $this->resourceConnection->getConnection()
                                        ->fetchCol($select);
    }

    public function filterLockedProducts($productsIds)
    {
        $connRead = $this->resourceConnection->getConnection();
        $table = $this->databaseStructure->getTableNameWithPrefix('m2epro_processing_lock');

        $select = $connRead->select();
        $select->from(['lo' => $table], ['object_id'])
               ->where('model_name = "Listing_Product"')
               ->where('object_id IN (?)', $productsIds)
               ->where('tag IS NULL');

        $lockedProducts = $this->resourceConnection->getConnection()->fetchCol($select);

        foreach ($lockedProducts as $id) {
            $key = array_search($id, $productsIds);
            if ($key !== false) {
                unset($productsIds[$key]);
            }
        }

        return $productsIds;
    }

    public function filterProductsByMagentoProductType($listingProductsIds)
    {
        $connRead = $this->resourceConnection->getConnection();
        $tableListingProduct = $this->databaseStructure->getTableNameWithPrefix('m2epro_listing_product');
        $tableProductEntity = $this->databaseStructure->getTableNameWithPrefix('catalog_product_entity');
        $tableProductOption = $this->databaseStructure->getTableNameWithPrefix('catalog_product_option');

        $productsIdsChunks = array_chunk($listingProductsIds, 1000);
        $listingProductsIds = [];

        foreach ($productsIdsChunks as $productsIdsChunk) {
            $select = $connRead->select();
            $select->from(['alp' => $tableListingProduct], ['id', 'product_id'])
                   ->where('id IN (?)', $productsIdsChunk);

            $listingProductToProductIds = $this->resourceConnection->getConnection()
                                                                   ->fetchPairs($select);

            $select = $connRead->select();
            $select->from(['cpe' => $tableProductEntity], ['entity_id', 'type_id'])
                   ->where('entity_id IN (?)', $listingProductToProductIds);

            $select->joinLeft(
                ['cpo' => $tableProductOption],
                'cpe.entity_id=cpo.product_id',
                ['option_id']
            );

            $select->group('entity_id');

            $productsData = $this->resourceConnection->getConnection()->fetchAll($select);

            $productToListingProductIds = array_flip($listingProductToProductIds);

            foreach ($productsData as $product) {
                if ($this->helperMagentoProduct->isBundleType($product['type_id'])) {
                    unset($productToListingProductIds[$product['entity_id']]);
                }

                if ($this->helperMagentoProduct->isDownloadableType($product['type_id'])) {
                    unset($productToListingProductIds[$product['entity_id']]);
                }

                if (
                    $this->helperMagentoProduct->isSimpleType($product['type_id']) &&
                    !empty($product['option_id'])
                ) {
                    unset($productToListingProductIds[$product['entity_id']]);
                }
            }

            $productsIdsFiltered = array_flip($productToListingProductIds);

            foreach ($listingProductToProductIds as $listingProductId => $productId) {
                if (!in_array($productId, $productsIdsFiltered)) {
                    unset($listingProductToProductIds[$listingProductId]);
                }
            }

            $listingProductsIds = array_merge(
                $listingProductsIds,
                array_keys($listingProductToProductIds)
            );
        }

        return $listingProductsIds;
    }

    //########################################

    public function increaseThemeUsageCount($theme, $marketplaceId)
    {
        $data = $this->registry->getValueFromJson(self::DATA_REGISTRY_KEY);

        if (empty($data[$marketplaceId][$theme])) {
            $data[$marketplaceId][$theme] = 0;
        }
        $data[$marketplaceId][$theme]++;

        arsort($data[$marketplaceId]);

        $this->registry->setValue(self::DATA_REGISTRY_KEY, $data);

        $this->removeThemeUsageDataCache();
    }

    // ---------------------------------------

    public function getThemesUsageData()
    {
        $cacheData = $this->getThemeUsageDataCache();
        if (is_array($cacheData)) {
            return $cacheData;
        }

        $data = $this->registry->getValueFromJson(self::DATA_REGISTRY_KEY);

        $this->setThemeUsageDataCache($data);

        return $data;
    }

    // ----------------------------------------

    private function getThemeUsageDataCache()
    {
        $cacheKey = __CLASS__ . self::DATA_REGISTRY_KEY;

        return $this->permanentCache->getValue($cacheKey);
    }

    // ---------------------------------------

    private function setThemeUsageDataCache(array $data)
    {
        $cacheKey = __CLASS__ . self::DATA_REGISTRY_KEY;
        $this->permanentCache->setValue($cacheKey, $data);
    }

    // ---------------------------------------

    private function removeThemeUsageDataCache()
    {
        $cacheKey = __CLASS__ . self::DATA_REGISTRY_KEY;
        $this->permanentCache->removeValue($cacheKey);
    }
}
