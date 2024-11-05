<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\Documents\Upload;

class ItemsRequester extends \Ess\M2ePro\Model\Ebay\Connector\Command\Pending\Requester
{
    protected function getRequestData(): array
    {
        return [
            'documents' => $this->params['documents'],
        ];
    }

    public function getCommand(): array
    {
        return ['document', 'upload', 'entities'];
    }

    protected function getResponserParams(): array
    {
        return [
            'account_id' => $this->account->getId(),
            'documents' => $this->params['documents'],
        ];
    }

    protected function getProcessingRunnerModelName(): string
    {
        return 'Ebay_Connector_Documents_Upload_ProcessingRunner';
    }
}
