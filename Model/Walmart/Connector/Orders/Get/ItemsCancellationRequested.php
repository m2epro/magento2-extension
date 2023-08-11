<?php

namespace Ess\M2ePro\Model\Walmart\Connector\Orders\Get;

class ItemsCancellationRequested extends \Ess\M2ePro\Model\Walmart\Connector\Command\RealTime
{
    protected function getRequestData(): array
    {
        return [
            'account' => $this->params['account'],
            'from_create_date' => $this->params['from_create_date'],
        ];
    }

    protected function getCommand(): array
    {
        return ['orders', 'get', 'itemsCancellationRequested'];
    }

    protected function validateResponse(): bool
    {
        $responseData = $this->getResponse()->getResponseData();

        return isset($responseData['items']);
    }

    protected function prepareResponseData(): void
    {
        $result = [];
        $responseData = $this->getResponse()->getResponseData();

        foreach ($responseData['items'] as $item) {
            $result[] = [
                'sku' => $item['sku'],
                'walmart_order_id' => $item['walmart_order_id'],
            ];
        }

        $this->responseData = $result;
    }
}
