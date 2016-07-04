<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */
//todo Fit to Magento2 Observers
/*
    $model = $this->modelFactory->getObject('PublicServices_Product_ObjectChange');

    // you have a product ID for observing
    $model->observeProduct(561);

    // you have 'catalog/product' object for observing
    $product = Mage::getModel('catalog/product')
                          ->setStoreId(2)
                          ->load(562);
    $model->observeProduct($product);

   // make changes for these products by direct sql

    $model->applyChanges();
*/

namespace Ess\M2ePro\Model\PublicServices\Product;

class ObjectChange extends \Ess\M2ePro\Model\AbstractModel
{
    protected $observers = array();

    //########################################

    public function applyChanges()
    {
        if (count($this->observers) <= 0) {
            return $this;
        }

        /** @var \Ess\M2ePro\Model\Observer\Dispatcher $dispatcher */
        $dispatcher = $this->modelFactory->getObject('Observer\Dispatcher');

        foreach ($this->observers as $productObserver) {

            $product = $productObserver->getEvent()->getData('product');
            $stockItemObserver = $this->prepareStockItemObserver($product);

            $dispatcher->catalogInventoryStockItemSaveAfter($stockItemObserver);
            $dispatcher->catalogProductSaveAfter($productObserver);
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
        $productId = $product instanceof \Magento\Catalog\Model\Product ? $product->getId()
                                                                    : $product;
        $key = $productId.'##'.$storeId;

        if (array_key_exists($key, $this->observers)) {
            return $this;
        }

        if (!($product instanceof \Magento\Catalog\Model\Product)) {

            $product = Mage::getModel('catalog/product')
                ->setStoreId($storeId)
                ->load($product);
        }

        $observer = $this->prepareProductObserver($product);

        /** @var \Ess\M2ePro\Model\Observer\Dispatcher $dispatcher */
        $dispatcher = $this->modelFactory->getObject('Observer\Dispatcher');
        $dispatcher->catalogProductSaveBefore($observer);

        $this->observers[$key] = $observer;
        return $this;
    }

    // ---------------------------------------

    private function prepareProductObserver(\Magento\Catalog\Model\Product $product)
    {
        $event = new \Varien_Event();
        $event->setProduct($product);

        $observer = new Varien_Event_Observer();
        $observer->setEvent($event);

        return $observer;
    }

    private function prepareStockItemObserver(\Magento\Catalog\Model\Product $product)
    {
        /** @var $stockItem \Magento\CatalogInventory\Model\Stock\Item */
        $stockItem = Mage::getModel('cataloginventory/stock_item');

        $stockItem->loadByProduct($product->getId())
                  ->setProductId($product->getId());

        foreach ($product->getData('stock_item')->getData() as $key => $value) {
            $stockItem->setOrigData($key, $value);
        }

        $observer = new Varien_Event_Observer();
        $observer->setEvent(new Varien_Event());
        $observer->setData('item', $stockItem);

        return $observer;
    }

    //########################################
}