<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Order;

use \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel as ChangeProcessorAbstract;

/**
 * Class \Ess\M2ePro\Observer\Order\Quote
 */
class Quote extends \Ess\M2ePro\Observer\AbstractModel
{
    /** @var \Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory */
    private $stockItemFactory;

    /** @var \Magento\CatalogInventory\Api\StockRegistryInterface */
    private $stockRegistry;

    /** @var null|\Magento\Catalog\Model\Product */
    private $product = null;

    /** @var null|\Magento\CatalogInventory\Api\Data\StockItemInterface */
    private $stockItem = null;

    private $affectedListingsProducts = [];

    //########################################

    public function __construct(
        \Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory $stockItemFactory,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->stockItemFactory = $stockItemFactory;
        $this->stockRegistry = $stockRegistry;
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
    }

    //########################################

    public function beforeProcess()
    {
        /** @var $quoteItem \Magento\Quote\Model\Quote\Item */
        $quoteItem = $this->getEvent()->getItem();

        /** @var $product \Magento\Catalog\Model\Product */
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

        $this->addListingProductInstructions();

        $this->processQty();
        $this->processStockAvailability();
    }

    // ---------------------------------------

    protected function processQty()
    {
        /** @var $quoteItem \Magento\Quote\Model\Quote\Item */
        $quoteItem = $this->getEvent()->getItem();

        if ($quoteItem->getHasChildren()) {
            return;
        }

        $oldValue = (int)$this->getStockItem()->getQty();
        $newValue = $oldValue - (int)$quoteItem->getTotalQty();

        if ($oldValue == $newValue) {
            return;
        }

        foreach ($this->getAffectedListingsProducts() as $listingProduct) {

            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            $this->logListingProductMessage(
                $listingProduct,
                \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_QTY,
                $oldValue,
                $newValue
            );
        }
    }

    protected function processStockAvailability()
    {
        /** @var $quoteItem \Magento\Quote\Model\Quote\Item */
        $quoteItem = $this->getEvent()->getItem();

        if ($quoteItem->getHasChildren()) {
            return;
        }

        $oldQty = (int)$this->getStockItem()->getQty();
        $newQty = $oldQty - (int)$quoteItem->getTotalQty();

        $oldValue = (bool)$this->getStockItem()->getIsInStock();
        $newValue = !($newQty <= (int)$this->stockItemFactory->create()->getMinQty());

        $oldValue = $oldValue ? 'IN Stock' : 'OUT of Stock';
        $newValue = $newValue ? 'IN Stock' : 'OUT of Stock';

        if ($oldValue == $newValue) {
            return;
        }

        foreach ($this->getAffectedListingsProducts() as $listingProduct) {

            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            $this->logListingProductMessage(
                $listingProduct,
                \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_STOCK_AVAILABILITY,
                $oldValue,
                $newValue
            );
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
     * @return \Magento\CatalogInventory\Api\Data\StockItemInterface
     */
    protected function getStockItem()
    {
        if ($this->stockItem !== null) {
            return $this->stockItem;
        }

        $stockItem = $this->stockRegistry->getStockItem(
            $this->getProduct()->getId(),
            $this->getProduct()->getStore()->getWebsiteId()
        );

        return $this->stockItem = $stockItem;
    }

    protected function addListingProductInstructions()
    {
        $synchronizationInstructionsData = [];

        foreach ($this->getAffectedListingsProducts() as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel $changeProcessor */
            $changeProcessor = $this->modelFactory->getObject(
                ucfirst($listingProduct->getComponentMode()) . '_Magento_Product_ChangeProcessor'
            );
            $changeProcessor->setListingProduct($listingProduct);
            $changeProcessor->setDefaultInstructionTypes(
                [
                    ChangeProcessorAbstract::INSTRUCTION_TYPE_PRODUCT_STATUS_DATA_POTENTIALLY_CHANGED,
                    ChangeProcessorAbstract::INSTRUCTION_TYPE_PRODUCT_QTY_DATA_POTENTIALLY_CHANGED,
                ]
            );
            $changeProcessor->process();
        }

        $this->activeRecordFactory->getObject('Listing_Product_Instruction')->getResource()->add(
            $synchronizationInstructionsData
        );
    }

    //########################################

    protected function areThereAffectedItems()
    {
        return !empty($this->getAffectedListingsProducts());
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Listing\Product[]
     */
    protected function getAffectedListingsProducts()
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

    protected function logListingProductMessage(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        $action,
        $oldValue,
        $newValue
    ) {
        $log = $this->activeRecordFactory->getObject('Listing\Log');
        $log->setComponentMode($listingProduct->getComponentMode());

        $log->addProductMessage(
            $listingProduct->getListingId(),
            $listingProduct->getProductId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            null,
            $action,
            $this->getHelper('Module\Log')->encodeDescription(
                'From [%from%] to [%to%].',
                ['!from' => $oldValue, '!to' => $newValue]
            ),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE
        );
    }

    //########################################
}
