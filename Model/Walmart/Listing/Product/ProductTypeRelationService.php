<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Listing\Product;

class ProductTypeRelationService
{
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory;
    private \Magento\Framework\DB\TransactionFactory $transactionFactory;
    private \Ess\M2ePro\Model\Walmart\ProductType\Builder\SnapshotBuilderFactory $snapshotBuilderFactory;
    private \Ess\M2ePro\Model\Walmart\ProductType\Builder\DiffFactory $diffFactory;
    private \Ess\M2ePro\Model\Walmart\ProductType\Builder\ChangeProcessorFactory $changeProcessorFactory;
    private \Ess\M2ePro\Model\ResourceModel\Processing\Lock $processingLockResource;
    private \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository;
    private \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product $walmartListingProductResource;
    private \Magento\Framework\App\ResourceConnection $resourceConnection;
    private \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository,
        \Ess\M2ePro\Model\Walmart\ProductType\Builder\SnapshotBuilderFactory $snapshotBuilderFactory,
        \Ess\M2ePro\Model\Walmart\ProductType\Builder\DiffFactory $diffFactory,
        \Ess\M2ePro\Model\Walmart\ProductType\Builder\ChangeProcessorFactory $changeProcessorFactory,
        \Ess\M2ePro\Model\ResourceModel\Processing\Lock $processingLockResource,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product $walmartListingProductResource,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->transactionFactory = $transactionFactory;
        $this->snapshotBuilderFactory = $snapshotBuilderFactory;
        $this->diffFactory = $diffFactory;
        $this->changeProcessorFactory = $changeProcessorFactory;
        $this->processingLockResource = $processingLockResource;
        $this->productTypeRepository = $productTypeRepository;
        $this->resourceConnection = $resourceConnection;
        $this->walmartListingProductResource = $walmartListingProductResource;
        $this->walmartFactory = $walmartFactory;
    }

    public function extractIdsOfNonLockedProducts(array $productsIds): array
    {
        $nonLockedProductsIds = [];

        $connection = $this->resourceConnection->getConnection();
        foreach (array_chunk($productsIds, 1000) as $productsIdsParamChunk) {
            $select = $connection->select();
            $select->from(
                ['lo' => $this->processingLockResource->getMainTable()],
                ['object_id']
            )
                   ->where('model_name = "Listing_Product"')
                   ->where('object_id IN (?)', $productsIdsParamChunk)
                   ->where('tag IS NOT NULL');

            $lockedProducts = $connection->fetchCol($select);

            foreach ($lockedProducts as $id) {
                $key = array_search($id, $productsIdsParamChunk);
                if ($key !== false) {
                    unset($productsIdsParamChunk[$key]);
                }
            }

            $nonLockedProductsIds = array_merge($nonLockedProductsIds, $productsIdsParamChunk);
        }

        return $nonLockedProductsIds;
    }

    public function assignProductType(int $productTypeId, array $productsIds): void
    {
        $this->processRelation($productsIds, $productTypeId);
        $this->runProcessorForParents($productsIds);
    }

    public function unassignProductType(array $productsIds): void
    {
        $this->processRelation($productsIds);
        $this->runProcessorForParents($productsIds);
    }

    private function processRelation(array $productsIds, int $productTypeId = null): void
    {
        $collection = $this->listingProductCollectionFactory->createWithWalmartChildMode();
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
                $oldProductTypeIds[$listingProduct->getId()] = $walmartListingProduct->isExistsProductType()
                    ? $walmartListingProduct->getProductTypeId()
                    : null;

                if ($productTypeId === null) {
                    $walmartListingProduct->unsetProductTypeId();
                } else {
                    $walmartListingProduct->setProductTypeId($productTypeId);
                }

                $transaction->addObject($listingProduct);
            }

            $transaction->save();
        } catch (\Exception $e) {
            $oldProductTypeIds = [];
        }

        if (empty($oldProductTypeIds)) {
            return;
        }

        $newProductType = $productTypeId !== null
            ? $this->productTypeRepository->find($productTypeId)
            : null;

        if ($newProductType !== null) {
            $snapshotBuilder = $this->snapshotBuilderFactory->create($newProductType);
            $newSnapshot = $snapshotBuilder->getSnapshot();
        } else {
            $newSnapshot = [];
        }

        /**@var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        foreach ($collection->getItems() as $listingProduct) {
            $oldProductType = null;
            if (!empty($oldProductTypeIds[$listingProduct->getId()])) {
                $oldProductType = $this->productTypeRepository->find(
                    $oldProductTypeIds[$listingProduct->getId()],
                );
            }

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
                [
                    ['id' => $listingProduct->getId(), 'status' => $listingProduct->getStatus()]
                ]
            );
        }
    }

    private function runProcessorForParents(array $productsIds): void
    {
        $connection = $this->resourceConnection->getConnection();
        $tableWalmartListingProduct = $this->walmartListingProductResource->getMainTable();

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
            $walmartListingProduct->getVariationManager()
                                  ->getTypeModel()
                                  ->getProcessor()
                                  ->process();
        }
    }
}
