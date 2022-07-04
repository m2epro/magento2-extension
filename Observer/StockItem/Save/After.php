<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\StockItem\Save;

use \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel as ChangeProcessorAbstract;

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

    private $affectedListingsParentProducts = [];

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

        $this->addListingProductInstructions();

        $this->processQty();
        $this->processStockAvailability();
    }

    // ---------------------------------------

    protected function processQty()
    {
        $oldValue = (int)$this->getStoredStockItem()->getOrigData('qty');
        $newValue = (int)$this->getStockItem()->getQty();

        if ($oldValue == $newValue) {
            return;
        }

        $listingProducts = array_merge(
            $this->getAffectedListingsProducts(),
            $this->getAffectedListingsParentProducts()
        );

        foreach ($listingProducts as $listingProduct) {

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
        $oldValue = (bool)$this->getStoredStockItem()->getOrigData('is_in_stock');
        $newValue = (bool)$this->getStockItem()->getIsInStock();

        $oldValue = $oldValue ? 'IN Stock' : 'OUT of Stock';
        $newValue = $newValue ? 'IN Stock' : 'OUT of Stock';

        if ($oldValue == $newValue) {
            return;
        }

        $listingProducts = array_merge(
            $this->getAffectedListingsProducts(),
            $this->getAffectedListingsParentProducts()
        );

        foreach ($listingProducts as $listingProduct) {

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

    protected function getProductId()
    {
        return $this->productId;
    }

    protected function addListingProductInstructions()
    {
        $synchronizationInstructionsData = [];

        $listingProducts = array_merge(
            $this->getAffectedListingsProducts(),
            $this->getAffectedListingsParentProducts()
        );

        foreach ($listingProducts as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel $changeProcessor */
            $changeProcessor = $this->modelFactory->getObject(
                ucfirst($listingProduct->getComponentMode()) . '_Magento_Product_ChangeProcessor'
            );
            $changeProcessor->setListingProduct($listingProduct);
            $changeProcessor->setDefaultInstructionTypes(
                [
                    ChangeProcessorAbstract::INSTRUCTION_TYPE_PRODUCT_DATA_POTENTIALLY_CHANGED,
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
        return !empty($this->getAffectedListingsProducts()) || !empty($this->getAffectedListingsParentProducts());
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
            ->getItemsByProductId($this->getProductId());
    }

    // ---------------------------------------

    private function getAffectedListingsParentProducts()
    {
        if (!empty($this->affectedListingsParentProducts)) {
            return $this->affectedListingsParentProducts;
        }

        $listingProduct = $this->activeRecordFactory->getObject('Listing\Product')->getResource();
        $parentIds = $listingProduct->getParentEntityIdsByChild($this->getProductId());

        $affectedListingsParentProducts = [];
        foreach ($parentIds as $id) {
            $listingsParentProducts = $listingProduct->getItemsByProductId($id);
            $affectedListingsParentProducts = array_merge($affectedListingsParentProducts, $listingsParentProducts);
        }

        return $this->affectedListingsParentProducts = $affectedListingsParentProducts;
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
                ['!from'=>$oldValue,'!to'=>$newValue]
            ),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_INFO
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
