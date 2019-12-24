<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Magento;

use Ess\M2ePro\Model\Exception;
use \Ess\M2ePro\Model\Magento\Product as ProductModel;

/**
 * Class \Ess\M2ePro\Helper\Magento\Product
 */
class Product extends \Ess\M2ePro\Helper\AbstractHelper
{
    const TYPE_SIMPLE       = 'simple';
    const TYPE_DOWNLOADABLE = 'downloadable';
    const TYPE_CONFIGURABLE = 'configurable';
    const TYPE_BUNDLE       = 'bundle';
    const TYPE_GROUPED      = 'grouped';

    const SKU_MAX_LENGTH = 64;

    private $cacheLoadedProducts = [];

    protected $productFactory;
    protected $catalogInventoryConfiguration;
    protected $modelFactory;

    //########################################

    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\CatalogInventory\Model\Configuration $catalogInventoryConfiguration,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->productFactory = $productFactory;
        $this->catalogInventoryConfiguration = $catalogInventoryConfiguration;
        $this->modelFactory = $modelFactory;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function isSimpleType($originType)
    {
        return in_array($originType, $this->getOriginKnownTypes(self::TYPE_SIMPLE));
    }

    public function isDownloadableType($originType)
    {
        return in_array($originType, $this->getOriginKnownTypes(self::TYPE_DOWNLOADABLE));
    }

    public function isConfigurableType($originType)
    {
        return in_array($originType, $this->getOriginKnownTypes(self::TYPE_CONFIGURABLE));
    }

    public function isBundleType($originType)
    {
        return in_array($originType, $this->getOriginKnownTypes(self::TYPE_BUNDLE));
    }

    public function isGroupedType($originType)
    {
        return in_array($originType, $this->getOriginKnownTypes(self::TYPE_GROUPED));
    }

    // ---------------------------------------

    public function getOriginKnownTypes($byLogicType = null)
    {
        if ($byLogicType && !in_array($byLogicType, $this->getLogicTypes())) {
            throw new Exception('Unknown logic type.');
        }

        $cache = $this->getHelper('Data_Cache_Runtime');

        if (!$byLogicType) {
            if ($cache->getValue(__METHOD__)) {
                return $cache->getValue(__METHOD__);
            }

            $originTypes = $this->getOriginTypes();
            foreach ($this->getLogicTypes() as $logicType) {
                $originTypes = array_merge($originTypes, $this->getOriginCustomTypes($logicType));
            }

            $originTypes = array_unique($originTypes);
            $cache->setValue(__METHOD__, $originTypes);

            return $originTypes;
        }

        if ($cache->getValue(__METHOD__ . $byLogicType)) {
            return $cache->getValue(__METHOD__ . $byLogicType);
        }

        $associatedTypes = [
            self::TYPE_SIMPLE => [
                ProductModel::TYPE_SIMPLE_ORIGIN,
                ProductModel::TYPE_VIRTUAL_ORIGIN
            ],
            self::TYPE_DOWNLOADABLE => [ProductModel::TYPE_DOWNLOADABLE_ORIGIN],
            self::TYPE_CONFIGURABLE => [ProductModel::TYPE_CONFIGURABLE_ORIGIN],
            self::TYPE_BUNDLE       => [ProductModel::TYPE_BUNDLE_ORIGIN],
            self::TYPE_GROUPED      => [ProductModel::TYPE_GROUPED_ORIGIN]
        ];

        $originTypes = array_unique(array_merge(
            $associatedTypes[$byLogicType],
            $this->getOriginCustomTypes($byLogicType)
        ));

        $cache->setValue(__METHOD__ . $byLogicType, $originTypes);

        return $originTypes;
    }

    // ---------------------------------------

    public function getOriginCustomTypes($byLogicType)
    {
        if (!in_array($byLogicType, $this->getLogicTypes())) {
            throw new Exception('Unknown logic type.');
        }

        $customTypes = $this->modelFactory->getObject('Config_Manager_Module')->getGroupValue(
            "/magento/product/{$byLogicType}_type/",
            "custom_types"
        );

        if (empty($customTypes)) {
            return [];
        }

        $customTypes = explode(',', $customTypes);
        return !empty($customTypes) ? array_map('trim', $customTypes) : [];
    }

    // ---------------------------------------

    public function getLogicTypes()
    {
        return [
            self::TYPE_SIMPLE,
            self::TYPE_DOWNLOADABLE,
            self::TYPE_CONFIGURABLE,
            self::TYPE_BUNDLE,
            self::TYPE_GROUPED
        ];
    }

    public function getOriginTypes()
    {
        return [
            ProductModel::TYPE_SIMPLE_ORIGIN,
            ProductModel::TYPE_VIRTUAL_ORIGIN,
            ProductModel::TYPE_CONFIGURABLE_ORIGIN,
            ProductModel::TYPE_BUNDLE_ORIGIN,
            ProductModel::TYPE_GROUPED_ORIGIN,
            ProductModel::TYPE_DOWNLOADABLE_ORIGIN
        ];
    }

    //########################################

    public function getCachedAndLoadedProduct($product, $storeId = null)
    {
        if ($product instanceof \Magento\Catalog\Model\Product) {
            return $product;
        }

        $productId = (int)$product;
        $cacheKey = $productId.'_'.(string)$storeId;

        if (isset($this->cacheLoadedProducts[$cacheKey])) {
            return $this->cacheLoadedProducts[$cacheKey];
        }

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->productFactory->create();
        $storeId !== null && $product->setStoreId((int)$storeId);
        $product->load($productId);

        return $this->cacheLoadedProducts[$cacheKey] = $product;
    }

    public function calculateStockAvailability($isInStock, $manageStock, $useConfigManageStock)
    {
        $manageStockGlobal = $this->catalogInventoryConfiguration->getManageStock();
        if (($useConfigManageStock && !$manageStockGlobal) || (!$useConfigManageStock && !$manageStock)) {
            return true;
        }
        return (bool)$isInStock;
    }

    /**
     * @param array $associatedProducts
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function prepareAssociatedProducts(array $associatedProducts, \Ess\M2ePro\Model\Magento\Product $product)
    {
        $productType = $product->getTypeId();
        $productId   = $product->getProductId();

        if ($this->isSimpleType($productType) ||
            $this->isDownloadableType($productType)) {
            return [$productId];
        }

        if ($this->isBundleType($productType)) {
            $bundleAssociatedProducts = [];

            foreach ($associatedProducts as $key => $productIds) {
                $bundleAssociatedProducts[$key] = reset($productIds);
            }

            return $bundleAssociatedProducts;
        }

        if ($this->isConfigurableType($productType)) {
            $configurableAssociatedProducts = [];

            foreach ($associatedProducts as $productIds) {
                if (count($configurableAssociatedProducts) == 0) {
                    $configurableAssociatedProducts = $productIds;
                } else {
                    $configurableAssociatedProducts = array_intersect($configurableAssociatedProducts, $productIds);
                }
            }

            if (count($configurableAssociatedProducts) != 1) {
                throw new \Ess\M2ePro\Model\Exception\Logic('There is no associated Product found for
                    Configurable Product.');
            }

            return $configurableAssociatedProducts;
        }

        if ($this->isGroupedType($productType)) {
            return array_values($associatedProducts);
        }

        return [];
    }

    //########################################
}
