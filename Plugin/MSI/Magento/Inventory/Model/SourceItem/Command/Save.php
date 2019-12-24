<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\MSI\Magento\Inventory\Model\SourceItem\Command;

use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\Inventory\Model\ResourceModel\SourceItem;

/**
 * Class \Ess\M2ePro\Plugin\MSI\Magento\Inventory\Model\SourceItem\Command\Save
 */
class Save extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory */
    protected $activeRecordFactory;

    /** @var \Ess\M2ePro\Model\MSI\AffectedProducts */
    protected $msiAffectedProducts;

    /** @var \Magento\Framework\Api\SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /** @var \Magento\Catalog\Model\ResourceModel\Product */
    protected $productResource;

    // ---------------------------------------

    /** @var \Magento\Inventory\Model\SourceItemRepository */
    protected $sourceItemRepo;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\MSI\AffectedProducts $msiAffectedProducts,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        parent::__construct($helperFactory, $modelFactory);
        $this->activeRecordFactory = $activeRecordFactory;
        $this->msiAffectedProducts = $msiAffectedProducts;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productResource = $productResource;

        $this->sourceItemRepo = $objectManager->get(\Magento\Inventory\Model\SourceItemRepository::class);
    }

    //########################################

    /**
     * @param $interceptor
     * @param \Closure $callback
     * @param array ...$arguments
     * @return mixed
     */
    public function aroundExecute($interceptor, \Closure $callback, ...$arguments)
    {
        return $this->execute('execute', $interceptor, $callback, $arguments);
    }

    /**
     * @param $interceptor
     * @param \Closure $callback
     * @param array $arguments
     * @return mixed
     */
    protected function processExecute($interceptor, \Closure $callback, array $arguments)
    {
        /** @var \Magento\InventoryApi\Api\Data\SourceItemInterface[] $sourceItems */
        $sourceItems = $arguments[0];
        $sourceItemsBefore = [];

        foreach ($sourceItems as $sourceItem) {

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(SourceItem::ID_FIELD_NAME, $sourceItem->getSourceItemId())
                ->create();

            foreach ($this->sourceItemRepo->getList($searchCriteria)->getItems() as $beforeSourceItem) {
                $sourceItemsBefore[$sourceItem->getSourceItemId()] = $beforeSourceItem;
            }
        }

        $result = $callback(...$arguments);

        foreach ($sourceItems as $sourceItem) {

            if (!isset($sourceItemsBefore[$sourceItem->getSourceItemId()])) {
                continue;
            }

            $sourceItemBefore = $sourceItemsBefore[$sourceItem->getSourceItemId()];
            $affected = $this->msiAffectedProducts->getAffectedProductsBySourceAndSku(
                $sourceItem->getSourceCode(), $sourceItem->getSku()
            );

            if (empty($affected)) {
                continue;
            }

            $this->activeRecordFactory->getObject('ProductChange')->addUpdateAction(
                $this->productResource->getIdBySku($sourceItem->getSku()),
                \Ess\M2ePro\Model\ProductChange::INITIATOR_OBSERVER
            );

            $this->processQty($sourceItemBefore, $sourceItem, $affected);
            $this->processStockAvailability($sourceItemBefore, $sourceItem, $affected);
        }

        return $result;
    }

    //########################################

    /**
     * @param \Magento\InventoryApi\Api\Data\SourceItemInterface|null $beforeSourceItem
     * @param \Magento\InventoryApi\Api\Data\SourceItemInterface $afterSourceItem
     * @param \Ess\M2ePro\Model\Listing\Product[] $affectedProducts
     */
    private function processQty($beforeSourceItem, $afterSourceItem, $affectedProducts)
    {
        $oldValue = $beforeSourceItem !== null ? $beforeSourceItem->getQuantity() : 'undefined';
        $newValue = $afterSourceItem->getQuantity();

        if ($oldValue == $newValue ||
            !$this->updateProductChangeRecord($afterSourceItem->getSku(), 'qty', $oldValue, $newValue)
        ) {
            return;
        }

        foreach ($affectedProducts as $listingProduct) {

            $this->logListingProductMessage(
                $listingProduct,
                $afterSourceItem,
                \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_QTY,
                $oldValue,
                $newValue
            );
        }
    }

    /**
     * @param \Magento\InventoryApi\Api\Data\SourceItemInterface|null $beforeSourceItem
     * @param \Magento\InventoryApi\Api\Data\SourceItemInterface $afterSourceItem
     * @param \Ess\M2ePro\Model\Listing\Product[] $affectedProducts
     */
    private function processStockAvailability($beforeSourceItem, $afterSourceItem, $affectedProducts)
    {
        $oldValue = 'undefined';
        $beforeSourceItem !== null && $oldValue = $beforeSourceItem->getStatus() ? 'IN Stock' : 'OUT of Stock';
        $newValue = $afterSourceItem->getStatus() ? 'IN Stock' : 'OUT of Stock';

        if ($oldValue == $newValue ||
            !$this->updateProductChangeRecord($afterSourceItem->getSku(), 'stock_availability', $oldValue, $newValue)
        ) {
            return;
        }

        foreach ($affectedProducts as $listingProduct) {

            $this->logListingProductMessage(
                $listingProduct,
                $afterSourceItem,
                \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_STOCK_AVAILABILITY,
                $oldValue,
                $newValue
            );
        }
    }

    /**
     * @param $sku
     * @param $attributeCode
     * @param $oldValue
     * @param $newValue
     * @return mixed
     */
    private function updateProductChangeRecord($sku, $attributeCode, $oldValue, $newValue)
    {
        return $this->activeRecordFactory->getObject('ProductChange')->updateAttribute(
            $this->productResource->getIdBySku($sku),
            $attributeCode,
            $oldValue,
            $newValue,
            \Ess\M2ePro\Model\ProductChange::INITIATOR_OBSERVER
        );
    }

    private function logListingProductMessage(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        \Magento\InventoryApi\Api\Data\SourceItemInterface $sourceItem,
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
                'Value was changed from [%from%] to [%to%] in the "%source%" Source.',
                ['!from' => $oldValue, '!to' => $newValue, '!source'=> $sourceItem->getSourceCode()]
            ),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
        );
    }

    //########################################
}
