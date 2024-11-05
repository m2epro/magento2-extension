<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\ComplianceDocuments;

class PendingStatusProcessor
{
    private const REQUEST_LIMIT = 50;

    private \Ess\M2ePro\Model\Ebay\ComplianceDocuments\Repository $complianceDocumentsRepository;
    private \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\ComplianceDocuments\Repository $complianceDocumentsRepository,
        \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher
    ) {
        $this->complianceDocumentsRepository = $complianceDocumentsRepository;
        $this->dispatcher = $dispatcher;
    }

    public function process(\Ess\M2ePro\Model\Account $account)
    {
        $readyToUploadComplianceDocuments = $this
            ->complianceDocumentsRepository
            ->findReadyToUploadByAccountId((int)$account->getId(), self::REQUEST_LIMIT);

        if (empty($readyToUploadComplianceDocuments)) {
            return;
        }

        $documents = [];
        foreach ($readyToUploadComplianceDocuments as $complianceDocument) {
            $documents[] = [
                'id' => $complianceDocument->getHash(),
                'type' => $complianceDocument->getType(),
                'url' => $complianceDocument->getUrl()
            ];
        }

        /** @var \Ess\M2ePro\Model\Ebay\Connector\Documents\Upload\ItemsRequester $connectorObj */
        $connectorObj = $this->dispatcher->getCustomConnector(
            'Ebay_Connector_Documents_Upload_ItemsRequester',
            ['documents' => $documents],
            null,
            $account
        );

        $this->dispatcher->process($connectorObj);

        foreach ($readyToUploadComplianceDocuments as $document) {
            $document->setStatusUploading();
            $this->complianceDocumentsRepository->update($document);
        }
    }
}
