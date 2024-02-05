<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\Order\Get;

class Fees extends \Ess\M2ePro\Model\Amazon\Connector\Command\RealTime
{
    protected function getRequestData(): array
    {
        return [
            'account' => $this->params['account'],
            'from_date' => $this->params['from_date'],
            'to_date' => $this->params['to_date'],
        ];
    }

    protected function getCommand(): array
    {
        return ['orders', 'get', 'fees'];
    }

    protected function validateResponse(): bool
    {
        $responseData = $this->getResponse()->getResponseData();

        return isset($responseData['orders']) && isset($responseData['to_date']);
    }
}
