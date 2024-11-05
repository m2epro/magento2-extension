<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\ComplianceDocuments;

use Ess\M2ePro\Model\ResourceModel\Ebay\ComplianceDocuments as DocumentsResource;

class Repository
{
    private \Ess\M2ePro\Model\ResourceModel\Ebay\ComplianceDocuments\CollectionFactory $collectionFactory;
    private DocumentsResource $resource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Ebay\ComplianceDocuments\CollectionFactory $collectionFactory,
        DocumentsResource $resource
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->resource = $resource;
    }

    public function create(\Ess\M2ePro\Model\Ebay\ComplianceDocuments $document): void
    {
        $document->isObjectCreatingState(true);
        $this->resource->save($document);
    }

    public function update(\Ess\M2ePro\Model\Ebay\ComplianceDocuments $document): void
    {
        $this->resource->save($document);
    }

    public function delete(\Ess\M2ePro\Model\Ebay\ComplianceDocuments $document)
    {
        $this->resource->delete($document);
    }

    public function findByAccountIdAndTypeAndUrl(
        int $accountId,
        string $type,
        string $url
    ): ?\Ess\M2ePro\Model\Ebay\ComplianceDocuments {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(DocumentsResource::COLUMN_ACCOUNT_ID, ['eq' => $accountId]);
        $collection->addFieldToFilter(DocumentsResource::COLUMN_TYPE, ['eq' => $type]);
        $collection->addFieldToFilter(DocumentsResource::COLUMN_URL, ['eq' => $url]);

        $document = $collection->getFirstItem();

        if ($document->isObjectNew()) {
            return null;
        }

        return $document;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\ComplianceDocuments[]
     */
    public function findReadyToUploadByAccountId(int $accountId, int $limit): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(DocumentsResource::COLUMN_ACCOUNT_ID, ['eq' => $accountId]);
        $collection->addFieldToFilter(
            DocumentsResource::COLUMN_STATUS,
            ['eq' => \Ess\M2ePro\Model\Ebay\ComplianceDocuments::STATUS_PENDING]
        );
        $collection->setPageSize($limit);

        return array_values($collection->getItems());
    }

    public function findByAccountIdAndHash(int $accountId, string $hash): ?\Ess\M2ePro\Model\Ebay\ComplianceDocuments
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(DocumentsResource::COLUMN_ACCOUNT_ID, ['eq' => $accountId]);
        $collection->addFieldToFilter(DocumentsResource::COLUMN_HASH, ['eq' => $hash]);

        $document = $collection->getFirstItem();
        if ($document->isObjectNew()) {
            return null;
        }

        return $document;
    }
}
