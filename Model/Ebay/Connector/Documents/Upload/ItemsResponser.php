<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\Documents\Upload;

class ItemsResponser extends \Ess\M2ePro\Model\Connector\Command\Pending\Responser
{
    private \Ess\M2ePro\Model\Ebay\ComplianceDocuments\UploadingStatusProcessor $documentUpdatingProcessor;
    private \Ess\M2ePro\Helper\Module\Exception $exceptionHelper;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\ComplianceDocuments\UploadingStatusProcessor $documentUpdatingProcessor,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        array $params = []
    ) {
        parent::__construct(
            $response,
            $helperFactory,
            $modelFactory,
            $amazonFactory,
            $walmartFactory,
            $ebayFactory,
            $activeRecordFactory,
            $params
        );

        $this->documentUpdatingProcessor = $documentUpdatingProcessor;
        $this->exceptionHelper = $exceptionHelper;
    }

    protected function validateResponse(): bool
    {
        $responseData = $this->getResponse()->getResponseData();
        if (!isset($responseData['documents'])) {
            return false;
        }

        return true;
    }

    protected function prepareResponseData(): void
    {
        $responseData = $this->getResponse()->getResponseData();

        foreach ($responseData['documents'] as $document) {
            if ($document['is_success']) {
                $channelDocument = \Ess\M2ePro\Model\Ebay\ComplianceDocuments\Channel\Document::createUploaded(
                    $document['id'],
                    $document['ebay_document_id']
                );
            } else {
                $channelDocument = \Ess\M2ePro\Model\Ebay\ComplianceDocuments\Channel\Document::createNotUploaded(
                    $document['id'],
                    $document['error'] ?? ''
                );
            }

            $this->preparedResponseData[] = $channelDocument;
        }
    }

    protected function isNeedProcessResponse(): bool
    {
        if (!parent::isNeedProcessResponse()) {
            return false;
        }

        if ($this->getResponse()->getMessages()->hasErrorEntities()) {
            return false;
        }

        return true;
    }

    protected function processResponseData()
    {
        try {
            /** @var \Ess\M2ePro\Model\Account $account */
            $account = $this->ebayFactory->getObjectLoaded('Account', $this->params['account_id']);

            $this
                ->documentUpdatingProcessor
                ->processResponseData($account, $this->getPreparedResponseData());
        } catch (\Throwable $e) {
            $this->exceptionHelper->process($e);
        }
    }
}
