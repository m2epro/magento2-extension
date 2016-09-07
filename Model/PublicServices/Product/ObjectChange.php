<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

/*
    // $this->modelFactory instanceof \Ess\M2ePro\Model\Factory
    $model = $this->modelFactory->getObject('PublicServices\Product\ObjectChange');

    // you have a product ID for observing
    $model->observeProduct(561);

    // you have '\Magento\Catalog\Model\Product' object for observing
    $product = $this->productFactory->create();
    $product->load(561);

    $model->observeProduct($product);

    // make changes for these products by direct sql
    $model->applyChanges();
*/

namespace Ess\M2ePro\Model\PublicServices\Product;

use Magento\Framework\Event\Observer;

class ObjectChange extends \Ess\M2ePro\Model\AbstractModel
{
    private $productFactory;
    private $stockRegistry;

    private $observerProductSaveBeforeFactory;
    private $observerProductSaveAfterFactory;
    private $observerStockItemSaveAfterFactory;

    protected $observers = [];

    //########################################

    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Ess\M2ePro\Observer\Product\AddUpdate\BeforeFactory $observerProductSaveBeforeFactory,
        \Ess\M2ePro\Observer\Product\AddUpdate\AfterFactory $observerProductSaveAfterFactory,
        \Ess\M2ePro\Observer\StockItem\Save\AfterFactory $observerStockItemSaveAfterFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->productFactory = $productFactory;
        $this->stockRegistry  = $stockRegistry;

        $this->observerProductSaveBeforeFactory  = $observerProductSaveBeforeFactory;
        $this->observerProductSaveAfterFactory   = $observerProductSaveAfterFactory;
        $this->observerStockItemSaveAfterFactory = $observerStockItemSaveAfterFactory;

        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function applyChanges()
    {
        if (count($this->observers) <= 0) {
            return $this;
        }

        foreach ($this->observers as $productObserver) {

            $product = $productObserver->getEvent()->getData('product');
            $stockItemObserver = $this->prepareStockItemObserver($product);

            $this->observerStockItemSaveAfterFactory->create()->execute($stockItemObserver);
            $this->observerProductSaveAfterFactory->create()->execute($productObserver);
        }

        return $this->flushObservers();
    }

    /**
     * @return $this
     */
    public function flushObservers()
    {
        $this->observers = array();
        return $this;
    }

    //########################################

    /**
     * @param \Magento\Catalog\Model\Product|int $product
     * @param int $storeId
     * @return $this
     */
    public function observeProduct($product, $storeId = 0)
    {
        $productId = $product instanceof \Magento\Catalog\Model\Product ? $product->getId() : $product;
        $key = $productId.'##'.$storeId;

        if (array_key_exists($key, $this->observers)) {
            return $this;
        }

        if (!($product instanceof \Magento\Catalog\Model\Product)) {

            $product = $this->productFactory->create()->setStoreId($storeId);
            $product->load($productId);
        }

        $observer = $this->prepareProductObserver($product);

        $this->observerProductSaveBeforeFactory->create()->execute($observer);

        $this->observers[$key] = $observer;
        return $this;
    }

    // ---------------------------------------

    private function prepareProductObserver(\Magento\Catalog\Model\Product $product)
    {
        $data = ['product' => $product];

        $event = new \Magento\Framework\Event($data);

        $observer = new Observer();
        $observer->setData(array_merge(['event' => $event], $data));

        return $observer;
    }

    private function prepareStockItemObserver(\Magento\Catalog\Model\Product $product)
    {
        $stockItem = $this->stockRegistry->getStockItem(
            $product->getId(),
            $product->getStore()->getWebsiteId()
        );

        $data = ['object' => $stockItem];

        $event = new \Magento\Framework\Event($data);

        $observer = new Observer();
        $observer->setData(array_merge(['event' => $event], $data));

        return $observer;
    }

    //########################################
}