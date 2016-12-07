<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product;

class Builder extends \Ess\M2ePro\Model\AbstractModel
{
    protected $driverPool;
    protected $filesystem;
    protected $storeFactory;
    protected $stockItemFactory;
    protected $productMediaConfig;
    protected $productFactory;
    protected $helperFactory;

    /** @var $product \Magento\Catalog\Model\Product */
    private $product = NULL;

    //########################################

    public function __construct(
        \Magento\Framework\Filesystem\DriverPool $driverPool,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Store\Model\StoreFactory $storeFactory,
        \Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory $stockItemFactory,
        \Magento\Catalog\Model\Product\Media\Config $productMediaConfig,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->driverPool = $driverPool;
        $this->filesystem = $filesystem;
        $this->storeFactory = $storeFactory;
        $this->stockItemFactory = $stockItemFactory;
        $this->productMediaConfig = $productMediaConfig;
        $this->productFactory = $productFactory;
        $this->helperFactory = $helperFactory;
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

        $websiteIds = array();
        if (!is_null($this->getData('store_id'))) {
            $store = $this->storeFactory->create()->load($this->getData('store_id'));
            $websiteIds = array($store->getWebsiteId());
        }

        if (empty($websiteIds)) {
            $websiteIds = array($this->helperFactory->getObject('Magento\Store')->getDefaultWebsiteId());
        }

        $this->product->setWebsiteIds($websiteIds);

        // ---------------------------------------

        $gallery = $this->makeGallery();

        if (count($gallery) > 0) {
            $firstImage = reset($gallery);
            $firstImage = $firstImage['file'];

            $this->product->setData('image', $firstImage);
            $this->product->setData('thumbnail', $firstImage);
            $this->product->setData('small_image', $firstImage);

            $this->product->setData('media_gallery', array(
                'images' => $gallery,
                'values' => array(
                    'main'        => $firstImage,
                    'image'       => $firstImage,
                    'small_image' => $firstImage,
                    'thumbnail'   => $firstImage
                )
            ));
        }

        // ---------------------------------------

        $this->product->getResource()->save($this->product);

        $this->createStockItem();
    }

    //########################################

    private function createStockItem()
    {
        /** @var $stockItem \Magento\CatalogInventory\Model\Stock\Item */
        $stockItem = $this->stockItemFactory->create();
        $stockItem->setProduct($this->product);

        $stockItem->addData(array(
            'qty'                         => $this->getData('qty'),
            'stock_id'                    => 1,
            'is_in_stock'                 => 1,
            'use_config_min_qty'          => 1,
            'use_config_min_sale_qty'     => 1,
            'use_config_max_sale_qty'     => 1,
            'is_qty_decimal'              => 0,
            'use_config_backorders'       => 1,
            'use_config_notify_stock_qty' => 1
        ));

        $stockItem->save();
    }

    private function makeGallery()
    {
        if (!is_array($this->getData('images')) || count($this->getData('images')) == 0) {
            return array();
        }

        $fileDriver = $this->driverPool->getDriver(\Magento\Framework\Filesystem\DriverPool::FILE);
        $tempMediaPath = $this->filesystem->getDirectoryRead(
            \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
        )->getAbsolutePath()
        . $this->productMediaConfig->getBaseTmpMediaPath() . DIRECTORY_SEPARATOR;

        $gallery = array();
        $imagePosition = 1;

        foreach ($this->getData('images') as $tempImageName) {
            if (!$fileDriver->isFile($tempMediaPath . $tempImageName)) {
                continue;
            }

            $gallery[] = array(
                'file'     => $tempImageName,
                'label'    => '',
                'position' => $imagePosition++,
                'disabled' => 0
            );
        }

        return $gallery;
    }

    //########################################
}