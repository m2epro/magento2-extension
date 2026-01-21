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
    /**
     * @var \Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\ImageDownloader
     */
    private ProductCreate\ImageDownloader $imageDownloader;

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
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\ImageDownloader $imageDownloader
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
        $this->imageDownloader = $imageDownloader;
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
            $product = $this->createMagentoProduct($channelItem);
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
            $parentProduct = $this->createMagentoProduct($channelItem);
            $parentProduct = $this->attributesProcessor
                ->setSuperAttributesToProduct($parentProduct, $channelItem);
            $parentProduct->setCanSaveConfigurableAttributes(true);

            $childProducts = $this->createChildProducts($channelItem);

            $extensionAttributes = $parentProduct->getExtensionAttributes();
            $extensionAttributes->setConfigurableProductLinks(array_keys($childProducts));

            $this->productRepository->save($parentProduct);
        } catch (\Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }

        $connection->commit();

        return $parentProduct;
    }

    private function createMagentoProduct(ChannelItem $channelItem): \Magento\Catalog\Api\Data\ProductInterface
    {
        if ($this->isExistProduct($channelItem->sku)) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                sprintf('Product with sku "%s" exists', $channelItem->sku)
            );
        }

        $product = $this->productFactory->create();
        $product->setTypeId($channelItem->magentoProductType);
        $product->setAttributeSetId($channelItem->attributeSetId);
        $product->setStoreId($channelItem->storeId);
        $product->setSku($channelItem->sku);
        $product->setName($channelItem->title);
        $product->setPrice($channelItem->price);
        $product->setVisibility($channelItem->visibility);
        $product->setTaxClassId($channelItem->taxClassId);
        $product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
        $product->setStockData(
            [
                'use_config_manage_stock' => 0,
                'manage_stock' => 1,
                'is_in_stock' => $channelItem->stockStatus,
                'qty' => $channelItem->quantity,
            ]
        );
        $product->setDescription($channelItem->description);

        $firstImageIndex = array_key_first($channelItem->images);
        foreach ($channelItem->images as $index => $imageUrl) {
            $imagePath = $this->imageDownloader->execute($imageUrl);
            if (empty($imagePath)) {
                continue;
            }

            $mediaAttribute = null;
            if ($index === $firstImageIndex) {
                $mediaAttribute = ['image', 'small_image', 'thumbnail'];
            }

            $product->addImageToMediaGallery(
                $imagePath,
                $mediaAttribute,
                true,
                false
            );
        }

        $store = $this->storeFactory->create()->load($channelItem->storeId);
        $websiteIds = [$store->getWebsiteId()];

        if (empty($websiteIds)) {
            $websiteIds = [$this->helperFactory->getObject('Magento\Store')->getDefaultWebsiteId()];
        }

        $product->setWebsiteIds($websiteIds);

        return $this->productRepository->save($product);
    }

    private function createChildProducts(ChannelItem $channelItem): array
    {
        $simpleProducts = [];

        foreach ($channelItem->variations as $variationItem) {
            $newProduct = $this->createMagentoProduct($variationItem);
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

        $stockItem->setQty($channelItem->quantity)
                  ->setStockId(\Magento\CatalogInventory\Model\Stock::DEFAULT_STOCK_ID)
                  ->setIsInStock($channelItem->stockStatus)
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
