<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Get\ReturnDetails;

abstract class Requester extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Requester
{
    protected function getRequestData(): array
    {
        return [
            'from_date' => $this->params[RequestProcessor::REQUEST_PARAM_KEY_FROM_DATE],
        ];
    }

    protected function getCommand(): array
    {
        return ['orders', 'get', 'returnDetails'];
    }
}
