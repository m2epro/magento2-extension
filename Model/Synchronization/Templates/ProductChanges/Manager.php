<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Synchronization\Templates\ProductChanges;

/**
 * Class \Ess\M2ePro\Model\Synchronization\Templates\ProductChanges\Manager
 */
class Manager extends \Ess\M2ePro\Model\AbstractModel
{
    private $parentFactory;
    private $activeRecordFactory;

    private $component = null;
    private $cache = [];

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->parentFactory = $parentFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function setComponent($component)
    {
        $this->component = $component;
    }

    public function getComponent()
    {
        return $this->component;
    }

    //########################################

    public function init()
    {
        if (!isset($this->cache['listings_products'])) {
            $this->cache['listings_products'] = [];
        }

        if (!isset($this->cache['listings_products_by_params'])) {
            $this->cache['listings_products_by_params'] = [];
        }
    }

    public function clearCache()
    {
        $this->cache = [];
    }

    //########################################

    /**
     * @param array $attributes
     * @param bool $withStoreFilter
     * @return array
     */
    public function getInstances(array $attributes, $withStoreFilter = false)
    {
        return $this->getListingProducts(
            $attributes,
            $withStoreFilter,
            'getChangedItems'
        );
    }

    /**
     * @param array $attributes
     * @param bool $withStoreFilter
     * @return array
     */
    public function getInstancesByListingProduct(array $attributes, $withStoreFilter = false)
    {
        return $this->getListingProducts(
            $attributes,
            $withStoreFilter,
            'getChangedItemsByListingProduct'
        );
    }

    /**
     * @param array $attributes
     * @param bool $withStoreFilter
     * @return array
     */
    public function getInstancesByVariationOption(array $attributes, $withStoreFilter = false)
    {
        return $this->getListingProducts(
            $attributes,
            $withStoreFilter,
            'getChangedItemsByVariationOption'
        );
    }

    //########################################

    private function getListingProducts(array $attributes, $withStoreFilter, $fetchFunction)
    {
        $args = func_get_args();
        $cacheKey = sha1($this->getHelper('Data')->jsonEncode($args));

        if (isset($this->cache['listings_products_by_params'][$cacheKey])) {
            return $this->cache['listings_products_by_params'][$cacheKey];
        }

        $this->cache['listings_products_by_params'][$cacheKey] = [];

        $listingProductsIds = [];
        $resultListingProducts = [];

        $changedListingsProducts =
            $this->activeRecordFactory->getObject(ucfirst($this->getComponent()).'\Listing\Product')->getResource()
                ->$fetchFunction(
                    $attributes,
                    $withStoreFilter
                );

        foreach ($changedListingsProducts as $key => $listingProductData) {
            $lpId = $listingProductData['id'];

            if (!isset($this->cache['listings_products'][$lpId])) {
                $listingProductsIds[$key] = $lpId;
                continue;
            }

            $resultListingProducts[$lpId] = $this->cache['listings_products'][$lpId];
            $resultListingProducts[$lpId]->addData($listingProductData);
            $resultListingProducts[$lpId]->getMagentoProduct()->enableCache();

            $this->cache['listings_products_by_params'][$cacheKey][$lpId] = $resultListingProducts[$lpId];

            unset($changedListingsProducts[$key]);
        }

        if (empty($changedListingsProducts)) {
            return $this->cache['listings_products_by_params'][$cacheKey] = $resultListingProducts;
        }

        $listingProducts = $this->parentFactory->getObject(strtolower($this->getComponent()), 'Listing\Product')
            ->getCollection()
            ->addFieldToFilter('listing_product_id', ['in' => $listingProductsIds])
            ->getItems();

        foreach ($listingProductsIds as $key => $lpId) {
            if (!isset($listingProducts[$lpId])) {
                continue;
            }

            $listingProducts[$lpId]->addData($changedListingsProducts[$key]);
            $listingProducts[$lpId]->getMagentoProduct()->enableCache();

            $this->cache['listings_products'][$lpId] = $listingProducts[$lpId];
            $this->cache['listings_products_by_params'][$cacheKey][$lpId] = $listingProducts[$lpId];
        }

        return $this->cache['listings_products_by_params'][$cacheKey];
    }

    //########################################
}
