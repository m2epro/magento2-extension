<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\ComplianceDocuments\ListingProductRelation;

use Ess\M2ePro\Model\ResourceModel\Ebay\ComplianceDocuments\ListingProductRelation;

class Repository
{
    private ListingProductRelation\CollectionFactory $collectionFactory;
    private ListingProductRelation $resource;

    public function __construct(
        ListingProductRelation\CollectionFactory $collectionFactory,
        ListingProductRelation $resource
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->resource = $resource;
    }

    public function findRelation(
        int $complianceDocumentId,
        int $listingProductId
    ): ?\Ess\M2ePro\Model\Ebay\ComplianceDocuments\ListingProductRelation {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(
            ListingProductRelation::COLUMN_COMPLIANCE_DOCUMENT_ID,
            ['eq' => $complianceDocumentId]
        );
        $collection->addFieldToFilter(
            ListingProductRelation::COLUMN_LISTING_PRODUCT_ID,
            ['eq' => $listingProductId]
        );

        $relation = $collection->getFirstItem();

        if ($relation->isObjectNew()) {
            return null;
        }

        return $relation;
    }

    /**
     * @param int $complianceDocumentId
     *
     * @return \Ess\M2ePro\Model\Ebay\ComplianceDocuments\ListingProductRelation[]
     */
    public function findByDocumentId(int $complianceDocumentId): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(
            ListingProductRelation::COLUMN_COMPLIANCE_DOCUMENT_ID,
            ['eq' => $complianceDocumentId]
        );

        return array_values($collection->getItems());
    }

    public function deleteByDocumentId(int $complianceDocumentId)
    {
        $connection = $this->resource->getConnection();

        $whereCondition = sprintf(
            '%s = %s',
            $connection->quoteIdentifier(ListingProductRelation::COLUMN_COMPLIANCE_DOCUMENT_ID),
            $connection->quote($complianceDocumentId)
        );

        $connection->delete($this->resource->getMainTable(), $whereCondition);
    }

    public function create(\Ess\M2ePro\Model\Ebay\ComplianceDocuments\ListingProductRelation $relation)
    {
        $this->resource->save($relation);
    }
}
