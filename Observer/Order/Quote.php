<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Order;

class Quote extends \Ess\M2ePro\Observer\AbstractModel
{
    private $stockItemFactory;
    /**
     * @var null|\Magento\Catalog\Model\Product
     */
    private $product = NULL;

    /**
     * @var null|\Magento\CatalogInventory\Model\Stock\Item
     */
    private $stockItem = NULL;

    private $affectedListingsProducts = array();

    //########################################

    public function __construct(
        \Magento\CatalogInventory\Model\Stock\ItemFactory $stockItemFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->stockItemFactory = $stockItemFactory;
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
    }

    //########################################

    public function beforeProcess()
    {
        /* @var $quoteItem \Magento\Quote\Model\Quote\Item */
        $quoteItem = $this->getEvent()->getItem();

        /* @var $product \Magento\Catalog\Model\Product */
        $product = $quoteItem->getProduct();

        if (!($product instanceof \Magento\Catalog\Model\Product) || (int)$product->getId() <= 0) {
            throw new \Ess\M2ePro\Model\Exception('Product ID should be greater than 0.');
        }

        $this->product = $product;
    }

    public function process()
    {
        if (!$this->areThereAffectedItems()) {
            return;
        }

        $this->activeRecordFactory->getObject('ProductChange')->addUpdateAction(
            $this->getProduct()->getId(),
            \Ess\M2ePro\Model\ProductChange::INITIATOR_OBSERVER
        );

        $this->processQty();
        $this->processStockAvailability();
    }

    // ---------------------------------------

    private function processQty()
    {
        /* @var $quoteItem \Magento\Quote\Model\Quote\Item */
        $quoteItem = $this->getEvent()->getItem();

        $oldValue = (int)$this->getStockItem()->getQty();
        $newValue = $oldValue - (int)$quoteItem->getTotalQty();

        if (!$this->updateProductChangeRecord('qty',$oldValue,$newValue) || $oldValue == $newValue) {
            return;
        }

        foreach ($this->getAffectedListingsProducts() as $listingProduct) {

            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            $this->logListingProductMessage($listingProduct,
                                            \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_QTY,
                                            $oldValue, $newValue);
        }
    }

    private function processStockAvailability()
    {
        /* @var $quoteItem \Magento\Quote\Model\Quote\Item */
        $quoteItem = $this->getEvent()->getItem();

        $oldQty = (int)$this->getStockItem()->getQty();
        $newQty = $oldQty - (int)$quoteItem->getTotalQty();

        $oldValue = (bool)$this->getStockItem()->getIsInStock();
        $newValue = !($newQty <= (int)$this->stockItemFactory->create()->getMinQty());

        // M2ePro\TRANSLATIONS
        // IN Stock
        // OUT of Stock

        $oldValue = $oldValue ? 'IN Stock' : 'OUT of Stock';
        $newValue = $newValue ? 'IN Stock' : 'OUT of Stock';

        if (!$this->updateProductChangeRecord('stock_availability',(int)$oldValue,(int)$newValue) ||
            $oldValue == $newValue) {
            return;
        }

        foreach ($this->getAffectedListingsProducts() as $listingProduct) {

            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            $this->logListingProductMessage($listingProduct,
                                            \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_STOCK_AVAILABILITY,
                                            $oldValue, $newValue);
        }
    }

    //########################################

    /**
     * @return \Magento\Catalog\Model\Product
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getProduct()
    {
        if (!($this->product instanceof \Magento\Catalog\Model\Product)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Property "Product" should be set first.');
        }

        return $this->product;
    }

    /**
     * @return \Magento\CatalogInventory\Model\Stock\Item
     */
    private function getStockItem()
    {
        if (!is_null($this->stockItem)) {
            return $this->stockItem;
        }

        $stockItem = $this->stockItemFactory->create();
        $stockItem->getResource()->loadByProductId(
            $stockItem, $this->getProduct()->getId(), $stockItem->getStockId()
        );

        return $this->stockItem = $stockItem;
    }

    private function updateProductChangeRecord($attributeCode, $oldValue, $newValue)
    {
        return $this->activeRecordFactory->getObject('ProductChange')->updateAttribute(
            $this->getProduct()->getId(),
            $attributeCode,
            $oldValue,
            $newValue,
            \Ess\M2ePro\Model\ProductChange::INITIATOR_OBSERVER
        );
    }

    //########################################

    private function areThereAffectedItems()
    {
        return count($this->getAffectedListingsProducts()) > 0;
    }

    // ---------------------------------------

    private function getAffectedListingsProducts()
    {
        if (!empty($this->affectedListingsProducts)) {
            return $this->affectedListingsProducts;
        }

        return $this->affectedListingsProducts = $this->activeRecordFactory
                                                      ->getObject('Listing\Product')
                                                      ->getResource()
                                                      ->getItemsByProductId($this->getProduct()->getId());
    }

    //########################################

    private function logListingProductMessage(\Ess\M2ePro\Model\Listing\Product $listingProduct, $action,
                                              $oldValue, $newValue)
    {
        // M2ePro\TRANSLATIONS
        // From [%from%] to [%to%].

        $log = $this->activeRecordFactory->getObject('Listing\Log');
        $log->setComponentMode($listingProduct->getComponentMode());

        $log->addProductMessage(
            $listingProduct->getListingId(),
            $listingProduct->getProductId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            NULL,
            $action,
            $this->getHelper('Module\Log')->encodeDescription(
                'From [%from%] to [%to%].',
                array('from'=>$oldValue,'to'=>$newValue)
            ),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
        );
    }

    //########################################
}