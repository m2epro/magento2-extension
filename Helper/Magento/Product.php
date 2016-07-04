<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Magento;

class Product extends \Ess\M2ePro\Helper\AbstractHelper
{
    const SKU_MAX_LENGTH = 64;

    private $cacheLoadedProducts = array();

    protected $productFactory;
    protected $catalogInventoryConfiguration;

    //########################################

    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\CatalogInventory\Model\Configuration $catalogInventoryConfiguration,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->productFactory = $productFactory;
        $this->catalogInventoryConfiguration = $catalogInventoryConfiguration;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getCachedAndLoadedProduct($product, $storeId = NULL)
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
        !is_null($storeId) && $product->setStoreId((int)$storeId);
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

    //########################################
}