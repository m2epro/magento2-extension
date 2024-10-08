<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\ProductType;

use Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product as ProductResource;

class Assign extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Main
{
    private \Magento\Framework\DB\TransactionFactory $transactionFactory;
    private \Ess\M2ePro\Model\Walmart\ProductType\Builder\SnapshotBuilderFactory $snapshotBuilderFactory;
    private \Ess\M2ePro\Model\Walmart\ProductType\Builder\DiffFactory $diffFactory;
    private \Ess\M2ePro\Model\Walmart\ProductType\Builder\ChangeProcessorFactory $changeProcessorFactory;
    private \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository,
        \Ess\M2ePro\Model\Walmart\ProductType\Builder\SnapshotBuilderFactory $snapshotBuilderFactory,
        \Ess\M2ePro\Model\Walmart\ProductType\Builder\DiffFactory $diffFactory,
        \Ess\M2ePro\Model\Walmart\ProductType\Builder\ChangeProcessorFactory $changeProcessorFactory,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->transactionFactory = $transactionFactory;
        $this->snapshotBuilderFactory = $snapshotBuilderFactory;
        $this->diffFactory = $diffFactory;
        $this->changeProcessorFactory = $changeProcessorFactory;
        $this->productTypeRepository = $productTypeRepository;
    }

    public function execute()
    {
        $productsIds = $this->getRequest()->getParam('products_ids');
        $productTypeId = $this->getRequest()->getParam('template_id');

        if (empty($productsIds) || empty($productTypeId)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        if (!is_array($productsIds)) {
            $productsIds = explode(',', $productsIds);
        }

        $this->setProductTypeForProducts((int)$productTypeId, $productsIds);
        $this->runProcessorForParents($productsIds);

        $this->setJsonContent([
            'type' => 'success',
            'messages' => [
                __('Product Type was assigned to %count Products', ['count' => count($productsIds)])
            ],
            'products_ids' => implode(',', $productsIds),
        ]);

        return $this->getResult();
    }

    private function setProductTypeForProducts(int $productTypeId, array $productsIds): void
    {
        $collection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('id', ['in' => $productsIds]);

        if ($collection->getSize() == 0) {
            return;
        }

        $transaction = $this->transactionFactory->create();
        $oldProductTypeIds = [];

        try {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            foreach ($collection->getItems() as $listingProduct) {
                /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
                $walmartListingProduct = $listingProduct->getChildObject();
                $oldProductTypeIds[
                    $listingProduct->getId()
                ] = $walmartListingProduct->getData(ProductResource::COLUMN_PRODUCT_TYPE_ID);

                $walmartListingProduct->setData(
                    ProductResource::COLUMN_PRODUCT_TYPE_ID,
                    $productTypeId
                );
                $transaction->addObject($listingProduct);
            }

            $transaction->save();
        } catch (\Exception $e) {
            $oldProductTypeIds = false;
        }

        if (!$oldProductTypeIds) {
            return;
        }

        $newProductType = $this->productTypeRepository->find((int)$productTypeId);
        if ($newProductType !== null) {
            $snapshotBuilder = $this->snapshotBuilderFactory->create($newProductType);
            $newSnapshot = $snapshotBuilder->getSnapshot();
        } else {
            $newSnapshot = [];
        }

        /**@var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        foreach ($collection->getItems() as $listingProduct) {
            $oldProductType = $this->productTypeRepository->find(
                (int)$oldProductTypeIds[$listingProduct->getId()],
            );

            if ($oldProductType !== null) {
                $snapshotBuilder = $this->snapshotBuilderFactory->create($oldProductType);
                $oldSnapshot = $snapshotBuilder->getSnapshot();
            } else {
                $oldSnapshot = [];
            }

            if (empty($newSnapshot) && empty($oldSnapshot)) {
                continue;
            }

            $diff = $this->diffFactory->create();
            $diff->setOldSnapshot($oldSnapshot);
            $diff->setNewSnapshot($newSnapshot);

            $changeProcessor = $this->changeProcessorFactory->create();
            $changeProcessor->process(
                $diff,
                [['id' => $listingProduct->getId(), 'status' => $listingProduct->getStatus()]]
            );
        }
    }

    private function runProcessorForParents(array $productsIds): void
    {
        $connection = $this->resourceConnection->getConnection();
        $tableWalmartListingProduct = $this->activeRecordFactory->getObject('Walmart_Listing_Product')
                                                                ->getResource()->getMainTable();

        $select = $connection->select();
        $select->from(['alp' => $tableWalmartListingProduct], ['listing_product_id'])
               ->where('listing_product_id IN (?)', $productsIds)
               ->where('is_variation_parent = ?', 1);

        $productsIds = $connection->fetchCol($select);
        foreach ($productsIds as $productId) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $this->walmartFactory->getObjectLoaded('Listing\Product', $productId);
            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
            $walmartListingProduct = $listingProduct->getChildObject();
            $walmartListingProduct->getVariationManager()->getTypeModel()->getProcessor()->process();
        }
    }
}
