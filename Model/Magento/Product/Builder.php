<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product;

/**
 * Class \Ess\M2ePro\Model\Magento\Product\Builder
 */
class Builder extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Magento\Framework\Filesystem\DriverPool  */
    protected $driverPool;

    /** @var \Magento\Framework\Filesystem  */
    protected $filesystem;

    /** @var \Magento\Store\Model\StoreFactory  */
    protected $storeFactory;

    /** @var \Magento\CatalogInventory\Api\StockRegistryInterface  */
    protected $stockRegistry;

    /** @var \Magento\CatalogInventory\Api\StockItemRepositoryInterface  */
    protected $stockItemRepository;

    /** @var \Magento\Catalog\Model\Product\Media\Config  */
    protected $productMediaConfig;

    /** @var \Magento\Catalog\Model\ProductFactory  */
    protected $productFactory;

    /** @var \Ess\M2ePro\Helper\Factory  */
    protected $helperFactory;

    /** @var \Magento\CatalogInventory\Model\Indexer\Stock\Processor  */
    protected $indexStockProcessor;

    /** @var \Magento\CatalogInventory\Api\StockConfigurationInterface  */
    protected $stockConfiguration;

    /** @var \Magento\Catalog\Model\Product */
    private $product;

    //########################################

    public function __construct(
        \Magento\Framework\Filesystem\DriverPool $driverPool,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Store\Model\StoreFactory $storeFactory,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockItemRepositoryInterface $stockItemRepository,
        \Magento\Catalog\Model\Product\Media\Config $productMediaConfig,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\CatalogInventory\Model\Indexer\Stock\Processor $indexStockProcessor,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration
    ) {
        $this->driverPool           = $driverPool;
        $this->filesystem           = $filesystem;
        $this->storeFactory         = $storeFactory;
        $this->stockRegistry        = $stockRegistry;
        $this->productMediaConfig   = $productMediaConfig;
        $this->productFactory       = $productFactory;
        $this->helperFactory        = $helperFactory;
        $this->indexStockProcessor  = $indexStockProcessor;
        $this->stockConfiguration   = $stockConfiguration;
        $this->stockItemRepository  = $stockItemRepository;
        parent::__construct(
            $helperFactory,
            $modelFactory
        );
    }

    //########################################

    public function getProduct()
    {
        return $this->product;
    }

    //########################################

    public function buildProduct()
    {
        $this->createProduct();
        $this->createStockItem();

        /*
         * Since version 2.1.8 Magento performs check if there is a record for product in table
         * cataloginventory_stock_status during quantity validation. Force reindex for new product will be helpful
         * if scheduled reindexing for stock status is enabled.
         */
        if ($this->indexStockProcessor->isIndexerScheduled() && $this->product->getId()) {
            $this->indexStockProcessor->reindexRow($this->product->getId(), true);
        }
    }

    private function createProduct()
    {
        $this->product = $this->productFactory->create();
        $this->product->setTypeId(\Ess\M2ePro\Model\Magento\Product::TYPE_SIMPLE_ORIGIN);
        $this->product->setAttributeSetId($this->productFactory->create()->getDefaultAttributeSetId());

        // ---------------------------------------

        $this->product->setName($this->getData('title'));
        $this->product->setDescription($this->getData('description'));
        $this->product->setShortDescription($this->getData('short_description'));
        $this->product->setSku($this->getData('sku'));

        // ---------------------------------------

        $this->product->setPrice($this->getData('price'));
        $this->product->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE);
        $this->product->setTaxClassId($this->getData('tax_class_id'));
        $this->product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);

        // ---------------------------------------

        $websiteIds = [];
        if ($this->getData('store_id') !== null) {
            $store = $this->storeFactory->create()->load($this->getData('store_id'));
            $websiteIds = [$store->getWebsiteId()];
        }

        if (empty($websiteIds)) {
            $websiteIds = [$this->helperFactory->getObject('Magento\Store')->getDefaultWebsiteId()];
        }

        $this->product->setWebsiteIds($websiteIds);

        // ---------------------------------------

        $gallery = $this->makeGallery();

        if (!empty($gallery)) {
            $firstImage = reset($gallery);
            $firstImage = $firstImage['file'];

            $this->product->setData('image', $firstImage);
            $this->product->setData('thumbnail', $firstImage);
            $this->product->setData('small_image', $firstImage);

            $this->product->setData('media_gallery', [
                'images' => $gallery,
                'values' => [
                    'main'        => $firstImage,
                    'image'       => $firstImage,
                    'small_image' => $firstImage,
                    'thumbnail'   => $firstImage
                ]
            ]);
        }

        // ---------------------------------------

        $this->product->getResource()->save($this->product);
    }

    //########################################

    private function createStockItem()
    {
        $stockItem = $this->stockRegistry
                          ->getStockItem(
                              $this->product->getId(),
                              $this->stockConfiguration->getDefaultScopeId()
                          );
        $stockItem->setProduct($this->product);

        $stockItem->setQty($this->getData('qty'))
                  ->setStockId(\Magento\CatalogInventory\Model\Stock::DEFAULT_STOCK_ID)
                  ->setIsInStock(true)
                  ->setUseConfigMinQty(true)
                  ->setUseConfigMinSaleQty(true)
                  ->setUseConfigMaxSaleQty(true)
                  ->setUseConfigBackorders(true)
                  ->setUseConfigNotifyStockQty(true)
                  ->setIsQtyDecimal(false);

        $this->stockItemRepository->save($stockItem);
    }

    private function makeGallery()
    {
        if (!is_array($this->getData('images')) || count($this->getData('images')) == 0) {
            return [];
        }

        $fileDriver = $this->driverPool->getDriver(\Magento\Framework\Filesystem\DriverPool::FILE);
        $tempMediaPath = $this->filesystem->getDirectoryRead(
            \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
        )->getAbsolutePath()
        . $this->productMediaConfig->getBaseTmpMediaPath() . DIRECTORY_SEPARATOR;

        $gallery = [];
        $imagePosition = 1;

        foreach ($this->getData('images') as $tempImageName) {
            if (!$fileDriver->isFile($tempMediaPath . $tempImageName)) {
                continue;
            }

            $gallery[] = [
                'file'     => $tempImageName,
                'label'    => '',
                'position' => $imagePosition++,
                'disabled' => 0
            ];
        }

        return $gallery;
    }

    //########################################
}
