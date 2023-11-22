<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Connector\Orders\Get;

class WfsItems extends \Ess\M2ePro\Model\Walmart\Connector\Command\RealTime
{
    protected function getRequestData(): array
    {
        return [
            'account' => $this->params['account'],
            'from_update_date' => $this->params['from_update_date'],
            'to_update_date' => $this->params['to_update_date'],
        ];
    }

    protected function getCommand(): array
    {
        return ['orders', 'get', 'wfsItems'];
    }

    protected function validateResponse(): bool
    {
        $responseData = $this->getResponse()->getResponseData();

        return isset($responseData['items']);
    }

    protected function prepareResponseData(): void
    {
        $responseData = $this->getResponse()->getResponseData();
        $this->responseData = $responseData;
    }
}
