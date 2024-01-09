<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\TecDoc\Get;

class Ktypes extends \Ess\M2ePro\Model\Amazon\Connector\Command\RealTime
{
    protected function getRequestData(): array
    {
        return [
            'vat_id' => $this->params['vat_id'],
            'mpns' => $this->params['mpns'],
        ];
    }

    protected function getCommand(): array
    {
        return ['tecDoc', 'get', 'ktypes'];
    }

    protected function validateResponse(): bool
    {
        $responseData = $this->getResponse()->getResponseData();

        return isset($responseData['mpns']);
    }

    protected function prepareResponseData(): void
    {
        $response = $this->getResponse()->getResponseData();

        $preparedData = [];

        foreach ($response['mpns'] as $mpn) {
            $preparedData[$mpn['mpn']] = $mpn['ktypes'];
        }

        $this->responseData = $preparedData;
    }
}
