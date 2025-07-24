<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\ComplianceDocuments\Product;

use Ess\M2ePro\Model\Ebay\ComplianceDocuments\ProductDocumentUrlFinder\ResultCollection
    as UrlFinderResultCollection;

class ComplianceDocumentCalculator
{
    private \Ess\M2ePro\Helper\Data $dataHelper;
    private \Ess\M2ePro\Model\Ebay\ComplianceDocuments\Repository $complianceDocumentRepository;

    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\Ebay\ComplianceDocuments\Repository $complianceDocumentRepository
    ) {
        $this->dataHelper = $dataHelper;
        $this->complianceDocumentRepository = $complianceDocumentRepository;
    }

    public function process(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        UrlFinderResultCollection $resultCollection
    ): bool {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        if ($resultCollection->isEmpty()) {
            return $this->updateComplianceDocuments($ebayListingProduct, []);
        }

        $documentsToSave = [];

        foreach ($resultCollection->getSuccessResults() as $findUrlResult) {
            $savedDocument = $this->complianceDocumentRepository->findByAccountIdAndTypeAndUrl(
                (int)$ebayListingProduct->getAccount()->getId(),
                $findUrlResult->getType(),
                $findUrlResult->getUrl()
            );

            if ($savedDocument === null) {
                continue;
            }

            if (!$savedDocument->isUploadedToEbay()) {
                continue;
            }

            $documentsToSave[$savedDocument->getEbayDocumentId()] = [
                'type' => $savedDocument->getType(),
                'document_id' => $savedDocument->getEbayDocumentId(),
            ];
        }

        $documentsToSave = array_values($documentsToSave);

        return $this->updateComplianceDocuments($ebayListingProduct, $documentsToSave);
    }

    private function updateComplianceDocuments(
        \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct,
        array $documentsToSave
    ): bool {
        $currentDocuments = $ebayListingProduct->getComplianceDocuments();

        if ($this->isEqualDocuments($documentsToSave, $currentDocuments)) {
            return false;
        }

        $ebayListingProduct->setComplianceDocuments($documentsToSave);
        $ebayListingProduct->save();

        return true;
    }

    private function isEqualDocuments(array $firstDocuments, array $secondDocuments): bool
    {
        if (count($firstDocuments) !== count($secondDocuments)) {
            return false;
        }

        return $this->documentsToString($firstDocuments)
            === $this->documentsToString($secondDocuments);
    }

    private function documentsToString(array $documents): string
    {
        $data = [];
        foreach ($documents as $document) {
            $data[] = $this->dataHelper->md5String($document['type'] . $document['document_id']);
        }

        sort($data, SORT_STRING);

        return json_encode($data, JSON_THROW_ON_ERROR);
    }
}
