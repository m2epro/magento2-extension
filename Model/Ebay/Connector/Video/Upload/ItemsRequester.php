<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\Video\Upload;

class ItemsRequester extends \Ess\M2ePro\Model\Ebay\Connector\Command\Pending\Requester
{
    protected function getRequestData(): array
    {
        return [
            'videos' => $this->params['videos'],
        ];
    }

    public function getCommand(): array
    {
        return ['video', 'upload', 'entities'];
    }

    protected function getResponserParams(): array
    {
        return [
            'account_id' => $this->account->getId(),
            'videos' => $this->params['videos'],
        ];
    }

    protected function getProcessingRunnerModelName(): string
    {
        return 'Ebay_Connector_Video_Upload_ProcessingRunner';
    }
}
