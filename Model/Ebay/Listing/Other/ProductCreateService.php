<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Other;

use Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\AttributesProcessor;
use Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\ChannelItem;
use Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\ChannelItemProvideUnmanaged;
use Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\ProviderInterface;

class ProductCreateService
{
    private ProviderInterface $channelItemProvider;
    private AttributesProcessor $attributesProcessor;
    private \Magento\Catalog\Model\ProductFactory $productFactory;
    private \Magento\Store\Model\StoreFactory $storeFactory;
    private \Ess\M2ePro\Helper\Factory $helperFactory;
    private \Magento\Catalog\Api\ProductRepositoryInterface $productRepository;
    private \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry;
    private \Magento\CatalogInventory\Api\StockItemRepositoryInterface $stockItemRepository;
    private \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration;
    private \Magento\Framework\App\ResourceConnection $resourceConnection;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        ChannelItemProvideUnmanaged $channelItemProvider,
        AttributesProcessor $attributesProcessor,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Store\Model\StoreFactory $storeFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockItemRepositoryInterface $stockItemRepository,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration
    ) {
        $this->channelItemProvider = $channelItemProvider;
        $this->attributesProcessor = $attributesProcessor;
        $this->productFactory = $productFactory;
        $this->storeFactory = $storeFactory;
        $this->helperFactory = $helperFactory;
        $this->productRepository = $productRepository;
        $this->stockRegistry = $stockRegistry;
        $this->stockItemRepository = $stockItemRepository;
        $this->stockConfiguration = $stockConfiguration;
        $this->resourceConnection = $resourceConnection;
    }

    public function execute(
        \Ess\M2ePro\Model\Listing\Other $unmanagedProduct
    ): \Magento\Catalog\Api\Data\ProductInterface {
        $channelItem = $this->channelItemProvider->getItem($unmanagedProduct);
        if ($channelItem->isConfigurableProduct()) {
            return $this->createConfigurableProduct($channelItem);
        }

        return $this->createSimpleProduct($channelItem);
    }

    private function createSimpleProduct(ChannelItem $channelItem): \Magento\Catalog\Api\Data\ProductInterface
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();

        try {
            $product = $this->initMagentoProduct($channelItem);
            $this->createStockItem(
                $product,
                $channelItem
            );
        } catch (\Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }

        $connection->commit();

        return $product;
    }

    private function createConfigurableProduct(ChannelItem $channelItem): \Magento\Catalog\Api\Data\ProductInterface
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();

        try {
            $product = $this->initMagentoProduct($channelItem);
            $this->buildConfigurableProduct(
                $product,
                $channelItem
            );
        } catch (\Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }

        $connection->commit();

        return $product;
    }

    private function initMagentoProduct(ChannelItem $channelItem): \Magento\Catalog\Api\Data\ProductInterface
    {
        if ($this->isExistProduct($channelItem->getSku())) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                sprintf('Product with sku "%s" exists', $channelItem->getSku())
            );
        }

        $product = $this->productFactory->create();
        $product->setTypeId($channelItem->getMagentoProductType());
        $product->setAttributeSetId($channelItem->getAttributeSetId());
        $product->setStoreId($channelItem->getStoreId());
        $product->setSku($channelItem->getSku());
        $product->setName($channelItem->getTitle());
        $product->setPrice($channelItem->getPrice());
        $product->setVisibility($channelItem->getVisibility());
        $product->setTaxClassId($channelItem->getTaxClassId());
        $product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
        $product->setStockData(
            [
                'use_config_manage_stock' => 0,
                'manage_stock' => 1,
                'is_in_stock' => $channelItem->getStockStatus(),
                'qty' => $channelItem->getQuantity(),
            ]
        );

        $store = $this->storeFactory->create()->load($channelItem->getStoreId());
        $websiteIds = [$store->getWebsiteId()];

        if (empty($websiteIds)) {
            $websiteIds = [$this->helperFactory->getObject('Magento\Store')->getDefaultWebsiteId()];
        }

        $product->setWebsiteIds($websiteIds);

        return $this->productRepository->save($product);
    }

    private function buildConfigurableProduct(
        \Magento\Catalog\Api\Data\ProductInterface $parentProduct,
        ChannelItem $channelItem
    ): void {
        $parentProduct = $this->attributesProcessor->setSuperAttributesToProduct(
            $parentProduct,
            $channelItem
        );

        $childProducts = $this->createChildProducts($channelItem);
        $extensionAttributes = $parentProduct->getExtensionAttributes();
        $parentProduct->setCanSaveConfigurableAttributes(true);

        $extensionAttributes->setConfigurableProductLinks(array_keys($childProducts));

        $this->productRepository->save($parentProduct);
    }

    private function createChildProducts(ChannelItem $channelItem): array
    {
        $simpleProducts = [];

        foreach ($channelItem->getVariations() as $variationItem) {
            $newProduct = $this->initMagentoProduct($variationItem);
            $this->attributesProcessor->setOptionAttributeToChild(
                $newProduct,
                $variationItem,
            );
            $this->createStockItem(
                $newProduct,
                $variationItem
            );
            $simpleProducts[$newProduct->getId()] = $newProduct;
        }

        return $simpleProducts;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param \Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\ChannelItem $channelItem
     *
     * @return void
     */
    private function createStockItem(
        \Magento\Catalog\Api\Data\ProductInterface $product,
        ChannelItem $channelItem
    ): void {
        $stockItem = $this->stockRegistry
            ->getStockItem(
                $product->getId(),
                $this->stockConfiguration->getDefaultScopeId()
            );
        $stockItem->setProduct($product);

        $stockItem->setQty($channelItem->getQuantity())
                  ->setStockId(\Magento\CatalogInventory\Model\Stock::DEFAULT_STOCK_ID)
                  ->setIsInStock($channelItem->getStockStatus())
                  ->setUseConfigMinQty(true)
                  ->setUseConfigMinSaleQty(true)
                  ->setUseConfigMaxSaleQty(true)
                  ->setUseConfigBackorders(true)
                  ->setUseConfigNotifyStockQty(true)
                  ->setManageStock(true)
                  ->setIsQtyDecimal(false);

        $this->stockItemRepository->save($stockItem);
    }

    private function isExistProduct(string $sku): bool
    {
        try {
            $this->productRepository->get($sku);

            return true;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return false;
        }
    }
}
