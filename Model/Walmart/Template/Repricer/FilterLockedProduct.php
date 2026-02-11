<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Template\Repricer;

class FilterLockedProduct
{
    private const CHUNK_SIZE  = 1;

    private \Ess\M2ePro\Model\ResourceModel\Processing\Lock\CollectionFactory $processingLockCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Processing\Lock\CollectionFactory $processingLockCollectionFactory
    ) {
        $this->processingLockCollectionFactory = $processingLockCollectionFactory;
    }

    /**
     * @param array $listingProductIds
     *
     * @return array Listing Product Ids  without locked
     */
    public function execute(array $listingProductIds): array
    {
        $filteredListingProductIds = [];
        $listingProductIds = array_chunk($listingProductIds, self::CHUNK_SIZE);

        foreach ($listingProductIds as $listingProductsIdsChunk) {
            $collection = $this->processingLockCollectionFactory->create();
            $collection->distinct(true);
            $collection->addFieldToSelect('object_id');
            $collection->addFieldToFilter('model_name', ['eq' => 'Listing_Product']);
            $collection->addFieldToFilter('object_id', ['in' => $listingProductsIdsChunk]);
            $collection->addFieldToFilter('tag', ['notnull' => true]);

            foreach ($collection->getColumnValues('object_id') as $id) {
                $key = array_search($id, $listingProductsIdsChunk);
                if ($key !== false) {
                    unset($listingProductsIdsChunk[$key]);
                }
            }

            $filteredListingProductIds = array_merge($filteredListingProductIds, $listingProductsIdsChunk);
        }

        return $filteredListingProductIds;
    }
}
