<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Amazon;

class Variation
{
    private const DATA_REGISTRY_KEY = 'amazon_variation_themes_usage';

    /** @var \Ess\M2ePro\Model\Factory */
    private $modelFactory;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    private $activeRecordFactory;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory */
    private $amazonParentFactory;
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;
    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseStructure;
    /** @var \Ess\M2ePro\Helper\Magento\Product */
    private $helperMagentoProduct;
    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $cachePermanent;
    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registry;

    /**
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonParentFactory
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Ess\M2ePro\Helper\Module\Database\Structure $databaseStructure
     * @param \Ess\M2ePro\Helper\Magento\Product $helperMagentoProduct
     * @param \Ess\M2ePro\Helper\Data\Cache\Permanent $cachePermanent
     * @param \Ess\M2ePro\Model\Registry\Manager $registry
     */
    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonParentFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseStructure,
        \Ess\M2ePro\Helper\Magento\Product $helperMagentoProduct,
        \Ess\M2ePro\Helper\Data\Cache\Permanent $cachePermanent,
        \Ess\M2ePro\Model\Registry\Manager $registry
    ) {
        $this->modelFactory = $modelFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->amazonParentFactory = $amazonParentFactory;
        $this->resourceConnection = $resourceConnection;
        $this->databaseStructure = $databaseStructure;
        $this->helperMagentoProduct = $helperMagentoProduct;
        $this->cachePermanent = $cachePermanent;
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

    public function filterProductsByGeneralId($productsIds)
    {
        $connRead = $this->resourceConnection->getConnection();
        $table = $this->databaseStructure->getTableNameWithPrefix('m2epro_amazon_listing_product');

        $select = $connRead->select();
        $select->from(['alp' => $table], ['listing_product_id'])
               ->where('listing_product_id IN (?)', $productsIds)
               ->where('general_id IS NULL');

        return $connRead->fetchCol($select);
    }

    public function filterProductsByGeneralIdOwner($productsIds)
    {
        $connRead = $this->resourceConnection->getConnection();
        $table = $this->databaseStructure->getTableNameWithPrefix('m2epro_amazon_listing_product');

        $select = $connRead->select();
        $select->from(['alp' => $table], ['listing_product_id'])
               ->where('listing_product_id IN (?)', $productsIds)
               ->where('is_general_id_owner = 0');

        return $connRead->fetchCol($select);
    }

    public function filterProductsByStatus($productsIds)
    {
        $connRead = $this->resourceConnection->getConnection();
        $table = $this->databaseStructure->getTableNameWithPrefix('m2epro_listing_product');

        $select = $connRead->select();
        $select->from(['lp' => $table], ['id'])
               ->where('id IN (?)', $productsIds)
               ->where('status = ?', \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED);

        return $connRead->fetchCol($select);
    }

    public function filterLockedProducts($productsIds)
    {
        $connRead = $this->resourceConnection->getConnection();
        $table = $this->activeRecordFactory->getObject('Processing\Lock')->getResource()->getMainTable();

        $select = $connRead->select();
        $select->from(['pl' => $table], ['object_id'])
               ->where('model_name = "Listing_Product"')
               ->where('object_id IN (?)', $productsIds)
               ->where('tag IS NULL');

        $lockedProducts = $connRead->fetchCol($select);

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

            $listingProductToProductIds = $connRead->fetchPairs($select);

            $select = $connRead->select();
            $select->from(['cpe' => $tableProductEntity], ['entity_id', 'type_id'])
                   ->where('entity_id IN (?)', $listingProductToProductIds);

            $select->joinLeft(
                ['cpo' => $tableProductOption],
                'cpe.entity_id=cpo.product_id',
                [
                    'option_id' => 'option_id',
                    'option_is_require' => 'is_require',
                    'option_type' => 'type',
                ]
            );

            $select->group('entity_id');

            $productsData = $connRead->fetchAll($select);

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
                    !empty($product['option_id']) && $product['option_is_require'] == 1 &&
                    in_array($product['option_type'], ['drop_down', 'radio', 'multiple', 'checkbox'])
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

    public function filterProductsByDescriptionTemplate($productsIds)
    {
        $productsIdsChunks = array_chunk($productsIds, 1000);
        $productsIds = [];

        $connRead = $this->resourceConnection->getConnection();
        $tableAmazonListingProduct = $this->databaseStructure->getTableNameWithPrefix('m2epro_amazon_listing_product');
        $tableAmazonTemplateDescription = $this->databaseStructure
            ->getTableNameWithPrefix('m2epro_amazon_template_description');

        foreach ($productsIdsChunks as $productsIdsChunk) {
            $select = $connRead->select();
            $select->from(['alp' => $tableAmazonListingProduct], ['listing_product_id'])
                   ->where('listing_product_id IN (?)', $productsIdsChunk);

            $select->join(
                ['atd' => $tableAmazonTemplateDescription],
                'alp.template_description_id=atd.template_description_id',
                []
            )->where('atd.is_new_asin_accepted = 1');

            $productsIds = array_merge(
                $productsIds,
                $connRead->fetchCol($select)
            );
        }

        return $productsIds;
    }

    public function filterParentProductsByVariationTheme($productsIds)
    {
        $detailsModel = $this->modelFactory->getObject('Amazon_Marketplace_Details');

        foreach ($productsIds as $key => $productId) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $this->amazonParentFactory->getObjectLoaded('Listing\Product', $productId);

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();

            if (!$amazonListingProduct->getVariationManager()->isRelationParentType()) {
                continue;
            }

            $detailsModel->setMarketplaceId($listingProduct->getListing()->getMarketplaceId());

            $themes = $detailsModel->getVariationThemes(
                $amazonListingProduct->getAmazonDescriptionTemplate()->getProductDataNick()
            );

            if (empty($themes)) {
                unset($productsIds[$key]);
            }
        }

        return $productsIds;
    }

    //########################################

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

    //########################################

    private function getThemeUsageDataCache()
    {
        $cacheKey = __CLASS__ . self::DATA_REGISTRY_KEY;

        return $this->cachePermanent->getValue($cacheKey);
    }

    private function setThemeUsageDataCache(array $data)
    {
        $cacheKey = __CLASS__ . self::DATA_REGISTRY_KEY;
        $this->cachePermanent->setValue($cacheKey, $data);
    }

    private function removeThemeUsageDataCache()
    {
        $cacheKey = __CLASS__ . self::DATA_REGISTRY_KEY;
        $this->cachePermanent->removeValue($cacheKey);
    }

    //########################################
}
