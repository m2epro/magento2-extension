<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\ComplianceDocuments;

use Ess\M2ePro\Model\Ebay\ComplianceDocuments;
use Ess\M2ePro\Model\Ebay\ComplianceDocuments\ProductDocumentUrlFinder\Result as FindUrlResult;
use Ess\M2ePro\Model\Ebay\ComplianceDocuments\ProductProcessor\Result as ProcessResult;
use Ess\M2ePro\Model\Ebay\ComplianceDocuments\ProductProcessor\ResultCollection as ProcessResultCollection;

class ProductProcessor
{
    private const INSTRUCTION_INITIATOR = 'ebay_compliance_document_product_processor';

    private array $instructionData = [];

    private ComplianceDocuments\Repository $complianceDocumentsRepository;
    private \Ess\M2ePro\Model\Ebay\ComplianceDocumentsFactory $complianceDocumentsFactory;
    private ComplianceDocuments\ListingProductRelation\Repository $relationRepository;
    private ComplianceDocuments\ListingProductRelationFactory $relationFactory;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instructionResource;
    private ComplianceDocuments\Product\ComplianceDocumentCalculator $complianceDocumentCalculator;
    private ComplianceDocuments\ProductDocumentUrlFinder $complianceDocumentUrlFinder;
    private \Ess\M2ePro\Model\Listing\LogFactory $logFactory;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\ComplianceDocumentsFactory $complianceDocumentsFactory,
        \Ess\M2ePro\Model\Ebay\ComplianceDocuments\ProductDocumentUrlFinder $complianceDocumentUrlFinder,
        \Ess\M2ePro\Model\Ebay\ComplianceDocuments\Repository $complianceDocumentsRepository,
        \Ess\M2ePro\Model\Ebay\ComplianceDocuments\ListingProductRelation\Repository $relationRepository,
        \Ess\M2ePro\Model\Ebay\ComplianceDocuments\ListingProductRelationFactory $relationFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instructionResource,
        ComplianceDocuments\Product\ComplianceDocumentCalculator $complianceDocumentCalculator,
        \Ess\M2ePro\Model\Listing\LogFactory $logFactory
    ) {
        $this->complianceDocumentsRepository = $complianceDocumentsRepository;
        $this->complianceDocumentsFactory = $complianceDocumentsFactory;
        $this->relationRepository = $relationRepository;
        $this->relationFactory = $relationFactory;
        $this->instructionResource = $instructionResource;
        $this->complianceDocumentCalculator = $complianceDocumentCalculator;
        $this->complianceDocumentUrlFinder = $complianceDocumentUrlFinder;
        $this->logFactory = $logFactory;
    }

    public function process(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        bool $writeLogs
    ): ProcessResultCollection {
        $findUrlResultsCollection = $this->complianceDocumentUrlFinder->process($listingProduct);

        $processResults = [];
        foreach ($findUrlResultsCollection->getResults() as $findResult) {
            $processResults[] = $this->processFindUrlResult($findResult, $listingProduct);
        }

        $resultCollection = new ProcessResultCollection($processResults);

        if ($writeLogs) {
            foreach ($resultCollection->getFailResults() as $failResult) {
                $this->addLogToProduct($listingProduct, $failResult->getFailMessage());
            }
        }

        $isUpdatedComplianceDocuments = $this->complianceDocumentCalculator
            ->process($listingProduct, $findUrlResultsCollection);

        if ($isUpdatedComplianceDocuments) {
            $this->addInstructionToReviseProduct($listingProduct);
        }

        $this->instructionResource->add(array_values($this->instructionData));

        return $resultCollection;
    }

    private function processFindUrlResult(
        FindUrlResult $findUrlResult,
        \Ess\M2ePro\Model\Listing\Product $listingProduct
    ): ProcessResult {
        if ($findUrlResult->isFail()) {
            return ProcessResult::createFail(
                $findUrlResult->getType(),
                $findUrlResult->getAttributeCode(),
                $findUrlResult->getFailMessage()
            );
        }

        $savedComplianceDocument = $this->complianceDocumentsRepository->findByAccountIdAndTypeAndUrl(
            (int)$listingProduct->getAccount()->getId(),
            $findUrlResult->getType(),
            $findUrlResult->getUrl()
        );

        if ($savedComplianceDocument === null) {
            $savedComplianceDocument = $this->createAndSaveDocument(
                $listingProduct->getAccount(),
                $findUrlResult
            );
        }

        if ($savedComplianceDocument->isStatusPending()) {
            $this->addRelationIfNeed($listingProduct, $savedComplianceDocument);
        }

        if (
            $savedComplianceDocument->isStatusPending()
            || $savedComplianceDocument->isStatusUploading()
        ) {
            return ProcessResult::createInProgress(
                $findUrlResult->getType(),
                $findUrlResult->getAttributeCode(),
                $findUrlResult->getUrl(),
                $savedComplianceDocument->getEbayDocumentId()
            );
        }

        if ($savedComplianceDocument->isStatusFailed()) {
            $failMessage = 'The compliance document was not uploaded to eBay: Document file URL is ' .
                'missing or has an invalid format.';

            return ProcessResult::createFail(
                $savedComplianceDocument->getType(),
                $findUrlResult->getAttributeCode(),
                $failMessage
            );
        }

        return ProcessResult::createSuccess(
            $savedComplianceDocument->getType(),
            $findUrlResult->getAttributeCode(),
            $savedComplianceDocument->getUrl(),
            $savedComplianceDocument->getEbayDocumentId()
        );
    }

    private function createAndSaveDocument(
        \Ess\M2ePro\Model\Account $account,
        FindUrlResult $findUrlResult
    ): \Ess\M2ePro\Model\Ebay\ComplianceDocuments {
        $newDocument = $this->complianceDocumentsFactory->create();
        $newDocument->init(
            (int)$account->getId(),
            $findUrlResult->getType(),
            $findUrlResult->getUrl()
        );

        $this->complianceDocumentsRepository->create($newDocument);

        return $newDocument;
    }

    private function addRelationIfNeed(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        \Ess\M2ePro\Model\Ebay\ComplianceDocuments $newComplianceDocument
    ): void {
        $complianceDocumentId = (int)$newComplianceDocument->getId();
        $listingProductId = (int)$listingProduct->getId();

        $existRelation = $this
            ->relationRepository
            ->findRelation($complianceDocumentId, $listingProductId);

        if ($existRelation !== null) {
            return;
        }

        $relation = $this->relationFactory->create();
        $relation->init($complianceDocumentId, $listingProductId);

        $this->relationRepository->create($relation);
    }

    private function addInstructionToReviseProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct): void
    {
        $this->instructionData[$listingProduct->getId()] = [
            'listing_product_id' => $listingProduct->getId(),
            'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'type' => UploadingStatusProcessor::INSTRUCTION_TYPE_EBAY_COMPLIANCE_DOCUMENT_UPLOADED,
            'initiator' => self::INSTRUCTION_INITIATOR,
            'priority' => 30,
        ];
    }

    private function addLogToProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct, string $message)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Log $log */
        $log = $this->logFactory->create();
        $log->setComponentMode($listingProduct->getComponentMode());

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
}
