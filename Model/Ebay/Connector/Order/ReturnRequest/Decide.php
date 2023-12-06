<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\Order\ReturnRequest;

class Decide extends \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime
{
    protected function getCommand(): array
    {
        return ['orders', 'return', 'decide'];
    }

    protected function getRequestData(): array
    {
        return [
            'decision' => $this->params['decision'],
            'order_id' => $this->params['order_id'],
        ];
    }

    protected function validateResponse(): bool
    {
        $response = $this->getResponse()->getResponseData();

        return isset($response['result']) && is_bool($response['result']);
    }

    protected function prepareResponseData(): void
    {
        $responseData = $this->getResponse()->getResponseData();

        $this->responseData = [
            'result' => $responseData['result'],
        ];
    }
}
