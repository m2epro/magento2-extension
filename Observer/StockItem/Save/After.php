<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\StockItem\Save;

/**
 * Class \Ess\M2ePro\Observer\StockItem\Save\After
 */
class After extends \Ess\M2ePro\Observer\StockItem\AbstractStockItem
{
    /**
     * @var null|int
     */
    private $productId = null;

    private $affectedListingsProducts = [];

    //########################################

    public function beforeProcess()
    {
        parent::beforeProcess();

        $productId = (int)$this->getStockItem()->getProductId();

        if ($productId <= 0) {
            throw new \Ess\M2ePro\Model\Exception('Product ID should be greater than 0.');
        }

        $this->productId = $productId;

        $this->reloadStockItem();
    }

    public function process()
    {
        if ($this->getStoredStockItem() === null) {
            return;
        }

        if (!$this->areThereAffectedItems()) {
            return;
        }

        $this->activeRecordFactory->getObject('ProductChange')->addUpdateAction(
            $this->getProductId(),
            \Ess\M2ePro\Model\ProductChange::INITIATOR_OBSERVER
        );

        $this->processQty();
        $this->processStockAvailability();
    }

    // ---------------------------------------

    private function processQty()
    {
        $oldValue = (int)$this->getStoredStockItem()->getOrigData('qty');
        $newValue = (int)$this->getStockItem()->getQty();

        if (!$this->updateProductChangeRecord('qty', $oldValue, $newValue) || $oldValue == $newValue) {
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

    private function processStockAvailability()
    {
        $oldValue = (bool)$this->getStoredStockItem()->getOrigData('is_in_stock');
        $newValue = (bool)$this->getStockItem()->getIsInStock();

        // M2ePro\TRANSLATIONS
        // IN Stock
        // OUT of Stock

        $oldValue = $oldValue ? 'IN Stock' : 'OUT of Stock';
        $newValue = $newValue ? 'IN Stock' : 'OUT of Stock';

        if (!$this->updateProductChangeRecord('stock_availability', $oldValue, $newValue) ||
            $oldValue == $newValue) {
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

    private function getProductId()
    {
        return $this->productId;
    }

    private function updateProductChangeRecord($attributeCode, $oldValue, $newValue)
    {
        return $this->activeRecordFactory->getObject('ProductChange')->updateAttribute(
            $this->getProductId(),
            $attributeCode,
            $oldValue,
            $newValue,
            \Ess\M2ePro\Model\ProductChange::INITIATOR_OBSERVER
        );
    }

    //########################################

    private function areThereAffectedItems()
    {
        return !empty($this->getAffectedListingsProducts());
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
            ->getItemsByProductId($this->getProductId());
    }

    //########################################

    private function logListingProductMessage(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        $action,
        $oldValue,
        $newValue
    ) {
        // M2ePro\TRANSLATIONS
        // From [%from%] to [%to%].

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
                ['!from'=>$oldValue,'!to'=>$newValue]
            ),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
        );
    }

    //########################################

    private function getStoredStockItem()
    {
        $key = $this->getStockItemId().'_'.$this->getStoreId();
        return $this->getRegistry()->registry($key);
    }

    //########################################
}
