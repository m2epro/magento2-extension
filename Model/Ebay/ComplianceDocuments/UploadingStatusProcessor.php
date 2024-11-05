<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\ComplianceDocuments;

class UploadingStatusProcessor
{
    private const INSTRUCTION_INITIATOR = 'ebay_compliance_document_uploading_processor';

    public const INSTRUCTION_TYPE_EBAY_COMPLIANCE_DOCUMENT_UPLOADED = 'ebay_compliance_document_uploaded';

    private array $instructionsData = [];
    private array $processedListingProducts = [];

    private \Ess\M2ePro\Model\Ebay\ComplianceDocuments\Repository $complianceDocumentsRepository;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instructionResource;
    private \Ess\M2ePro\Model\Listing\Log\Factory $logFactory;
    private \Ess\M2ePro\Model\Ebay\ComplianceDocuments\ListingProductRelation\Repository $documentRelationRepository;
    private \Ess\M2ePro\Model\Ebay\ComplianceDocuments\Product\ComplianceDocumentCalculator $columnCalculator;
    private \Ess\M2ePro\Model\Ebay\ComplianceDocuments\ProductDocumentUrlFinder $urlFinder;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\ComplianceDocuments\Repository $complianceDocumentsRepository,
        \Ess\M2ePro\Model\Ebay\ComplianceDocuments\ListingProductRelation\Repository $documentRelationRepository,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instructionResource,
        \Ess\M2ePro\Model\Listing\Log\Factory $logFactory,
        \Ess\M2ePro\Model\Ebay\ComplianceDocuments\Product\ComplianceDocumentCalculator $columnCalculator,
        \Ess\M2ePro\Model\Ebay\ComplianceDocuments\ProductDocumentUrlFinder $urlFinder
    ) {
        $this->complianceDocumentsRepository = $complianceDocumentsRepository;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->instructionResource = $instructionResource;
        $this->logFactory = $logFactory;
        $this->documentRelationRepository = $documentRelationRepository;
        $this->columnCalculator = $columnCalculator;
        $this->urlFinder = $urlFinder;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\ComplianceDocuments\Channel\Document[] $channelDocuments
     *
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \JsonException
     */
    public function processResponseData(
        \Ess\M2ePro\Model\Account $account,
        array $channelDocuments
    ): void {
        foreach ($channelDocuments as $channelDocument) {
            $this->processChannelDocument($account, $channelDocument);
        }

        foreach ($this->processedListingProducts as $listingProduct) {
            $resultCollection = $this->urlFinder->process($listingProduct);
            $this->columnCalculator->process($listingProduct, $resultCollection);
        }

        $this->instructionResource->add(array_values($this->instructionsData));
    }

    private function processChannelDocument(
        \Ess\M2ePro\Model\Account $account,
        \Ess\M2ePro\Model\Ebay\ComplianceDocuments\Channel\Document $channelDocument
    ): void {
        $complianceDocument = $this
            ->complianceDocumentsRepository
            ->findByAccountIdAndHash((int)$account->getId(), $channelDocument->hash);

        if (
            $complianceDocument === null
            || !$complianceDocument->isStatusUploading()
        ) {
            return;
        }

        $listingProducts = $this->findRelations($complianceDocument);

        if ($channelDocument->isUploaded) {
            $complianceDocument->setStatusSuccess($channelDocument->ebayDocumentId);
            $this->complianceDocumentsRepository->update($complianceDocument);

            foreach ($listingProducts as $listingProduct) {
                $this->addInstruction($listingProduct);
            }
        } else {
            $complianceDocument->setStatusFailed($channelDocument->error);
            $this->complianceDocumentsRepository->update($complianceDocument);

            foreach ($listingProducts as $listingProduct) {
                $this->logListingProductMessage($listingProduct, $channelDocument->error);
            }
        }

        foreach ($listingProducts as $listingProduct) {
            $this->processedListingProducts[$listingProduct->getId()] = $listingProduct;
        }

        $this->documentRelationRepository->deleteByDocumentId((int)$complianceDocument->getId());
    }

    private function logListingProductMessage(
        \Ess\M2ePro\Model\Listing\Product $listingProduct
    ): void {
        $log = $this->logFactory->create();
        $log->setComponentMode($listingProduct->getComponentMode());

        $message = 'The compliance document was not uploaded to eBay: ' .
            'Document file URL is missing or has an invalid format.';

        $log->addProductMessage(
            $listingProduct->getListingId(),
            $listingProduct->getProductId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            null,
            \Ess\M2ePro\Model\Listing\Log::ACTION_COMPLIANCE_DOCUMENTS,
            $message,
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
        );
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product[]
     */
    private function findRelations(
        \Ess\M2ePro\Model\Ebay\ComplianceDocuments $document
    ): array {
        $relations = $this
            ->documentRelationRepository
            ->findByDocumentId((int)$document->getId());

        $listingProductIds = [];
        foreach ($relations as $relation) {
            $listingProductIds[] = $relation->getListingProductId();
        }

        $listingsProductsCollection = $this->listingProductCollectionFactory->create();
        $listingsProductsCollection->addFieldToFilter('id', ['in' => $listingProductIds]);

        return $listingsProductsCollection->getItems();
    }

    private function addInstruction(\Ess\M2ePro\Model\Listing\Product $listingProduct): void
    {
        $this->instructionsData[$listingProduct->getId()] = [
            'listing_product_id' => $listingProduct->getId(),
            'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'type' => self::INSTRUCTION_TYPE_EBAY_COMPLIANCE_DOCUMENT_UPLOADED,
            'initiator' => self::INSTRUCTION_INITIATOR,
            'priority' => 30,
        ];
    }
}
