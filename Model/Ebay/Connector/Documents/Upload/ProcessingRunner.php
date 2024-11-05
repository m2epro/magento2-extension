<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\Documents\Upload;

class ProcessingRunner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Single\Runner
{
    private \Ess\M2ePro\Model\Ebay\ComplianceDocuments\Repository $complianceDocumentsRepository;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\ComplianceDocuments\Repository $complianceDocumentRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        parent::__construct(
            $parentFactory,
            $activeRecordFactory,
            $helperData,
            $helperFactory,
            $modelFactory
        );
        $this->complianceDocumentsRepository = $complianceDocumentRepository;
    }

    public function processExpired(): void
    {
        $this->rollbackVideoStatus();

        parent::processExpired();
    }

    public function complete(): void
    {
        if ($this->isEmptyResponseOrHasError()) {
            $this->rollbackVideoStatus();
        }

        parent::complete();
    }

    private function isEmptyResponseOrHasError(): bool
    {
        if (
            empty($this->processingObject->getResultData())
            || $this->getResponse()->getMessages()->hasErrorEntities()
        ) {
            return true;
        }

        return false;
    }

    private function rollbackVideoStatus(): void
    {
        $responserParams = $this->getResponserParams();
        $requestDocuments = $responserParams['documents'];
        $accountId = (int)$responserParams['account_id'];

        foreach ($requestDocuments as $requestDocument) {
            $document = $this->complianceDocumentsRepository
                ->findByAccountIdAndHash($accountId, $requestDocument['hash']);

            if ($document === null || !$document->isStatusUploading()) {
                continue;
            }

            $document->setStatusPending();
            $this->complianceDocumentsRepository->update($document);
        }
    }
}
