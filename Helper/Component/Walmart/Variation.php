<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Walmart;

/**
 * Class \Ess\M2ePro\Helper\Component\Walmart\Variation
 */
class Variation extends \Ess\M2ePro\Helper\AbstractHelper
{
    const DATA_REGISTRY_KEY  = 'walmart_variation_themes_usage';

    protected $activeRecordFactory;
    protected $modelFactory;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->modelFactory = $modelFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($helperFactory, $context);
    }

    //########################################

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
        $table = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_walmart_listing_product');

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
        $table = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_walmart_listing_product');

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
        $table = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_listing_product');

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
        $table = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_processing_lock');

        $select = $connRead->select();
        $select->from(['lo' => $table], ['object_id'])
            ->where('model_name = "M2ePro/Listing\Product"')
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
        $tableListingProduct = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_listing_product');
        $tableProductEntity = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('catalog_product_entity');
        $tableProductOption = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('catalog_product_option');

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
                if ($product['type_id'] == \Ess\M2ePro\Model\Magento\Product::TYPE_BUNDLE) {
                    unset($productToListingProductIds[$product['entity_id']]);
                }

                if ($product['type_id'] == \Ess\M2ePro\Model\Magento\Product::TYPE_DOWNLOADABLE) {
                    unset($productToListingProductIds[$product['entity_id']]);
                }

                if ($product['type_id'] == \Ess\M2ePro\Model\Magento\Product::TYPE_SIMPLE &&
                    !empty($product['option_id'])) {
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
        $tableWalmartListingProduct = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_walmart_listing_product');
        $tableWalmartTemplateDescription = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_walmart_template_description');

        foreach ($productsIdsChunks as $productsIdsChunk) {
            $select = $connRead->select();
            $select->from(['alp' => $tableWalmartListingProduct], ['listing_product_id'])
                ->where('listing_product_id IN (?)', $productsIdsChunk);

            $select->join(
                ['atd' => $tableWalmartTemplateDescription],
                'alp.template_description_id=atd.template_description_id',
                []
            )->where('atd.is_new_asin_accepted = 1');

            $productsIds = array_merge(
                $productsIds,
                $this->resourceConnection->getConnection()->fetchCol($select)
            );
        }

        return $productsIds;
    }

    public function filterParentProductsByVariationTheme($productsIds)
    {
        $detailsModel = $this->modelFactory->getObject('Walmart_Marketplace_Details');

        foreach ($productsIds as $key => $productId) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $this->getHelper('Component\Walmart')->getObject('Listing\Product', $productId);

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
            $walmartListingProduct = $listingProduct->getChildObject();

            if (!$walmartListingProduct->getVariationManager()->isRelationParentType()) {
                continue;
            }

            $detailsModel->setMarketplaceId($listingProduct->getListing()->getMarketplaceId());

            $themes = $detailsModel->getVariationAttributes(
                $walmartListingProduct->getWalmartDescriptionTemplate()->getProductDataNick()
            );

            if (empty($themes)) {
                unset($productsIds[$key]);
            }
        }

        return $productsIds;
    }

    //########################################

    public function increaseThemeUsageCount($theme, $marketplaceId)
    {
        /** @var $registry \Ess\M2ePro\Model\Registry */
        $registry = $this->activeRecordFactory->getObjectLoaded('Registry', self::DATA_REGISTRY_KEY, 'key', false);

        if ($registry === null) {
            $registry = $this->activeRecordFactory->getObject('Registry');
        }

        $data = $registry->getSettings('value');

        if (empty($data[$marketplaceId][$theme])) {
            $data[$marketplaceId][$theme] = 0;
        }
        $data[$marketplaceId][$theme]++;

        arsort($data[$marketplaceId]);

        $registry->setData('key', self::DATA_REGISTRY_KEY);
        $registry->setSettings('value', $data)->save();

        $this->removeThemeUsageDataCache();
    }

    // ---------------------------------------

    public function getThemesUsageData()
    {
        $cacheData = $this->getThemeUsageDataCache();
        if (is_array($cacheData)) {
            return $cacheData;
        }

        /** @var \Ess\M2ePro\Model\Registry $registry */
        $registry = $this->activeRecordFactory->getObjectLoaded('Registry', self::DATA_REGISTRY_KEY, 'key', false);
        if ($registry === null) {
            $registry = $this->activeRecordFactory->getObject('Registry');
        }

        $data = $registry->getSettings('value');

        $this->setThemeUsageDataCache($data);

        return $data;
    }

    //########################################

    private function getThemeUsageDataCache()
    {
        $cacheKey = __CLASS__.self::DATA_REGISTRY_KEY;
        return $this->getHelper('Data_Cache_Permanent')->getValue($cacheKey);
    }

    // ---------------------------------------

    private function setThemeUsageDataCache(array $data)
    {
        $cacheKey = __CLASS__.self::DATA_REGISTRY_KEY;
        $this->getHelper('Data_Cache_Permanent')->setValue($cacheKey, $data);
    }

    // ---------------------------------------

    private function removeThemeUsageDataCache()
    {
        $cacheKey = __CLASS__.self::DATA_REGISTRY_KEY;
        $this->getHelper('Data_Cache_Permanent')->removeValue($cacheKey);
    }

    //########################################
}
