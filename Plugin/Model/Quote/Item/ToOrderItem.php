<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\Model\Quote\Item;

class ToOrderItem
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

    private $quoteItem = NULL;

    protected $helperFactory;
    protected $activeRecordFactory;
    protected $modelFactory;
    protected $maintenanceHelper;

    private $affectedListingsProducts = array();
    private $affectedOtherListings = array();

    //########################################

    public function __construct(
        \Magento\CatalogInventory\Model\Stock\ItemFactory $stockItemFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Module\Maintenance\Setup $maintenanceHelper
    )
    {
        $this->stockItemFactory = $stockItemFactory;
        $this->helperFactory = $helperFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->modelFactory = $modelFactory;
        $this->maintenanceHelper = $maintenanceHelper;
    }

    //########################################

    public function aroundConvert($subject, \Closure $proceed, $item)
    {
        $orderItem = $proceed($item);

        $this->quoteItem = $item;

        /* @var $product \Magento\Catalog\Model\Product */
        $product = $this->quoteItem->getProduct();

        if (!($product instanceof \Magento\Catalog\Model\Product) || (int)$product->getId() <= 0) {
            return $orderItem;
        }

        $this->product = $product;

        if ($this->maintenanceHelper->isEnabled() ||
            !$this->helperFactory->getObject('Module')->isReadyToWork() ||
            !$this->helperFactory->getObject('Component')->getEnabledComponents()) {

            return $orderItem;
        }

        try {

            $this->process();

        } catch (\Exception $exception) {
            $this->helperFactory->getObject('Module\Exception')->process($exception);
        }

        return $orderItem;
    }

    //########################################

    private function process()
    {
        if (!$this->areThereAffectedItems()) {
            return;
        }

        if ((int)$this->getStockItem()->getQty() <= 0) {
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

        $oldValue = (int)$this->getStockItem()->getQty();
        $newValue = $oldValue - (int)$this->quoteItem->getTotalQty();

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
        $oldQty = (int)$this->getStockItem()->getQty();
        $newQty = $oldQty - (int)$this->quoteItem->getTotalQty();

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
            $stockItem, $this->getProduct()->getId(), $stockItem->getWebsiteId()
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
        return count($this->getAffectedListingsProducts()) > 0 ||
        count($this->getAffectedOtherListings()) > 0;
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

    private function getAffectedOtherListings()
    {
        if (!empty($this->affectedOtherListings)) {
            return $this->affectedOtherListings;
        }

        return $this->affectedOtherListings = $this->activeRecordFactory
            ->getObject('Listing\Other')
            ->getResource()
            ->getItemsByProductId(
                $this->getProduct()->getId(),
                ['component_mode' => \Ess\M2ePro\Helper\Component\Ebay::NICK]
            );
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
            $log->encodeDescription(
                'From [%from%] to [%to%].',
                array('from'=>$oldValue,'to'=>$newValue)
            ),
            \Ess\M2ePro\Model\Log\AbstractLog::TYPE_NOTICE,
            \Ess\M2ePro\Model\Log\AbstractLog::PRIORITY_LOW
        );
    }

    //########################################
}